<?php
declare(strict_types=1);

class UploadRejected extends RuntimeException
{
}

const ALLOWED_IMAGE_MIME_EXT = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

const STORED_MAX_DIMENSION = 2400;
const STORED_JPEG_QUALITY = 85;

/**
 * Valide, redimensionne si besoin et stocke une image uploadée pour un groupe.
 * L'original est toujours ramené à une taille raisonnable (poids + dimensions) :
 * les photos de téléphone (souvent 4000px+, plusieurs Mo) ne sont jamais rejetées
 * pour leur taille, seulement re-encodées plus légères.
 * Retourne les chemins relatifs (original redimensionné + vignette) et les métadonnées.
 */
function processGroupeImageUpload(array $file, int $groupeId): array
{
    $validated = validateUploadedImage($file);

    $origDir = UPLOADS_PATH . "/groupes/{$groupeId}";
    $thumbDir = PUBLIC_MEDIA_PATH . "/groupes/{$groupeId}";
    ensureDir($origDir, 0750);
    ensureDir($thumbDir, 0755);

    $randomName = bin2hex(random_bytes(16));
    $origRelative = "groupes/{$groupeId}/orig-{$randomName}.jpg";
    $origAbsolute = UPLOADS_PATH . '/' . $origRelative;
    $thumbRelative = "groupes/{$groupeId}/thumb-{$randomName}.jpg";
    $thumbAbsolute = PUBLIC_MEDIA_PATH . '/' . $thumbRelative;

    $source = loadImageResource($file['tmp_name'], $validated['mime']);
    resizeAndSaveJpeg($source, STORED_MAX_DIMENSION, $origAbsolute, STORED_JPEG_QUALITY);
    chmod($origAbsolute, 0640);
    resizeAndSaveJpeg($source, THUMB_MAX_DIMENSION, $thumbAbsolute, THUMB_JPEG_QUALITY);
    chmod($thumbAbsolute, 0644);
    imagedestroy($source);

    return [
        'original_path' => $origRelative,
        'thumb_path' => $thumbRelative,
        'mime_type' => 'image/jpeg',
        'size_bytes' => filesize($origAbsolute) ?: 0,
    ];
}

/**
 * Valide et stocke le logo du site (mêmes règles de sécurité que les images de groupe).
 * Retourne le chemin relatif (sous public/media/site/) de la vignette générée.
 */
function processSiteLogoUpload(array $file): string
{
    $validated = validateUploadedImage($file);

    $thumbDir = PUBLIC_MEDIA_PATH . '/site';
    ensureDir($thumbDir, 0755);

    $randomName = bin2hex(random_bytes(16));
    $thumbRelative = "site/logo-{$randomName}.png";
    $thumbAbsolute = PUBLIC_MEDIA_PATH . '/' . $thumbRelative;

    $source = loadImageResource($file['tmp_name'], $validated['mime']);
    resizeAndSavePng($source, 300, $thumbAbsolute);
    chmod($thumbAbsolute, 0644);
    imagedestroy($source);

    return $thumbRelative;
}

/**
 * Vérifications de sécurité communes à tout upload d'image : upload réel,
 * taille de fichier, MIME réel (finfo), image genuine, dimensions raisonnables
 * (garde-fou anti "bombe de décompression", pas une limite fonctionnelle —
 * les photos plus grandes sont redimensionnées, jamais rejetées pour leur taille).
 */
function validateUploadedImage(array $file): array
{
    // Le decodage/redimensionnement GD d'une grande photo de telephone peut
    // depasser la limite memoire par defaut de PHP (128M) ; on l'augmente
    // seulement pour cette requete, calibree pour rester sure sur le VPS.
    if ((int)ini_get('memory_limit') > 0 && (int)ini_get('memory_limit') < 256) {
        ini_set('memory_limit', IMAGE_PROCESSING_MEMORY_LIMIT);
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new UploadRejected(t('uploads_error_failed'));
    }
    if ($file['size'] <= 0 || $file['size'] > UPLOAD_MAX_BYTES) {
        throw new UploadRejected(t('uploads_error_too_large'));
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new UploadRejected(t('uploads_error_invalid_request'));
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset(ALLOWED_IMAGE_MIME_EXT[$mime])) {
        throw new UploadRejected(t('uploads_error_invalid_type'));
    }

    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        throw new UploadRejected(t('uploads_error_invalid_image'));
    }
    [$width, $height] = $imageInfo;
    if ($width > UPLOAD_MAX_PIXELS || $height > UPLOAD_MAX_PIXELS) {
        throw new UploadRejected(t('uploads_error_dimensions'));
    }

    return ['mime' => $mime, 'width' => $width, 'height' => $height];
}

function ensureDir(string $path, int $mode): void
{
    if (!is_dir($path) && !mkdir($path, $mode, true) && !is_dir($path)) {
        throw new UploadRejected(t('uploads_error_storage'));
    }
}

/**
 * @return \GdImage
 */
function loadImageResource(string $path, string $mime)
{
    $source = match ($mime) {
        'image/jpeg' => imagecreatefromjpeg($path),
        'image/png' => imagecreatefrompng($path),
        'image/webp' => imagecreatefromwebp($path),
        default => throw new UploadRejected(t('uploads_error_unsupported_format')),
    };

    if ($source === false) {
        throw new UploadRejected(t('uploads_error_processing'));
    }

    // Neutralise l'orientation EXIF (les photos de téléphone sont souvent
    // stockées "à plat" avec un tag de rotation) pour éviter des vignettes pivotées.
    if (function_exists('exif_read_data') && $mime === 'image/jpeg') {
        $exif = @exif_read_data($path);
        $orientation = $exif['Orientation'] ?? 1;
        $source = applyExifOrientation($source, (int)$orientation);
    }

    return $source;
}

/**
 * @param \GdImage $source
 * @return \GdImage
 */
function applyExifOrientation($source, int $orientation)
{
    return match ($orientation) {
        3 => imagerotate($source, 180, 0) ?: $source,
        6 => imagerotate($source, -90, 0) ?: $source,
        8 => imagerotate($source, 90, 0) ?: $source,
        default => $source,
    };
}

/**
 * Redimensionne (si besoin) l'image source vers $maxDimension max et l'enregistre en JPEG.
 * @param \GdImage $source
 */
function resizeAndSaveJpeg($source, int $maxDimension, string $destPath, int $quality): void
{
    $srcWidth = imagesx($source);
    $srcHeight = imagesy($source);
    $scale = min(1.0, $maxDimension / max($srcWidth, $srcHeight));
    $dstWidth = max(1, (int)round($srcWidth * $scale));
    $dstHeight = max(1, (int)round($srcHeight * $scale));

    $dest = imagecreatetruecolor($dstWidth, $dstHeight);
    $white = imagecolorallocate($dest, 255, 255, 255);
    imagefill($dest, 0, 0, $white);
    imagecopyresampled($dest, $source, 0, 0, 0, 0, $dstWidth, $dstHeight, $srcWidth, $srcHeight);

    imagejpeg($dest, $destPath, $quality);
    imagedestroy($dest);
}

/**
 * Redimensionne (si besoin) l'image source vers $maxDimension max et l'enregistre en PNG
 * (transparence conservée, utilisé pour le logo du site).
 * @param \GdImage $source
 */
function resizeAndSavePng($source, int $maxDimension, string $destPath): void
{
    $srcWidth = imagesx($source);
    $srcHeight = imagesy($source);
    $scale = min(1.0, $maxDimension / max($srcWidth, $srcHeight));
    $dstWidth = max(1, (int)round($srcWidth * $scale));
    $dstHeight = max(1, (int)round($srcHeight * $scale));

    $dest = imagecreatetruecolor($dstWidth, $dstHeight);
    imagesavealpha($dest, true);
    $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
    imagefill($dest, 0, 0, $transparent);
    imagecopyresampled($dest, $source, 0, 0, 0, 0, $dstWidth, $dstHeight, $srcWidth, $srcHeight);

    imagepng($dest, $destPath);
    imagedestroy($dest);
}

function deleteSiteLogoFile(?string $logoRelative): void
{
    if ($logoRelative === null) {
        return;
    }
    $absolute = PUBLIC_MEDIA_PATH . '/' . $logoRelative;
    if (is_file($absolute)) {
        @unlink($absolute);
    }
}

function deleteGroupeImageFiles(string $originalRelative, ?string $thumbRelative): void
{
    $origAbsolute = UPLOADS_PATH . '/' . $originalRelative;
    if (is_file($origAbsolute)) {
        @unlink($origAbsolute);
    }
    if ($thumbRelative !== null) {
        $thumbAbsolute = PUBLIC_MEDIA_PATH . '/' . $thumbRelative;
        if (is_file($thumbAbsolute)) {
            @unlink($thumbAbsolute);
        }
    }
}
