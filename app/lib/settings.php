<?php
declare(strict_types=1);

const DEFAULT_PRIMARY_COLOR = '#2f6f4f';
const DEFAULT_SITE_NAME = 'Stock Association';

function getSetting(string $key, ?string $default = null): ?string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        $stmt = getDb()->query('SELECT key, value FROM settings');
        foreach ($stmt->fetchAll() as $row) {
            $cache[$row['key']] = $row['value'];
        }
    }
    return $cache[$key] ?? $default;
}

function setSetting(string $key, string $value): void
{
    $stmt = getDb()->prepare(
        'INSERT INTO settings (key, value) VALUES (?, ?)
         ON CONFLICT(key) DO UPDATE SET value = excluded.value'
    );
    $stmt->execute([$key, $value]);
}

function deleteSetting(string $key): void
{
    $stmt = getDb()->prepare('DELETE FROM settings WHERE key = ?');
    $stmt->execute([$key]);
}

function isValidHexColor(string $value): bool
{
    return (bool)preg_match('/^#[0-9a-fA-F]{6}$/', $value);
}

/**
 * Assombrit une couleur hex d'un pourcentage donné (pour les états hover/dark).
 */
function darkenHexColor(string $hex, float $percent = 0.2): string
{
    $hex = ltrim($hex, '#');
    $r = (int)hexdec(substr($hex, 0, 2));
    $g = (int)hexdec(substr($hex, 2, 2));
    $b = (int)hexdec(substr($hex, 4, 2));

    $r = (int)max(0, min(255, $r * (1 - $percent)));
    $g = (int)max(0, min(255, $g * (1 - $percent)));
    $b = (int)max(0, min(255, $b * (1 - $percent)));

    return sprintf('#%02x%02x%02x', $r, $g, $b);
}
