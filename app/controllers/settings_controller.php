<?php
declare(strict_types=1);

requireRole(ROLE_ADMIN);

function currentSettingsViewData(?string $error = null): array
{
    return [
        'error' => $error,
        'primaryColor' => getSetting('primary_color', DEFAULT_PRIMARY_COLOR),
        'logoPath' => getSetting('logo_path'),
        'siteName' => getSetting('site_name', DEFAULT_SITE_NAME),
    ];
}

if ($route === 'settings') {
    render('settings/index', currentSettingsViewData());
    exit;
}

if ($route === 'settings/update') {
    if (!isPost()) {
        redirect('settings');
    }
    csrfVerify();

    $action = postString('action');

    if ($action === 'update_color') {
        $color = postString('primary_color');
        if (!isValidHexColor($color)) {
            render('settings/index', currentSettingsViewData(t('settings_error_color_invalid')));
            exit;
        }
        setSetting('primary_color', $color);
        flashSet('success', t('settings_color_updated'));
        redirect('settings');
    }

    if ($action === 'reset_color') {
        deleteSetting('primary_color');
        flashSet('success', t('settings_color_reset_msg'));
        redirect('settings');
    }

    if ($action === 'update_name') {
        $name = postString('site_name');
        if (!isNonEmptyString($name, 100)) {
            render('settings/index', currentSettingsViewData(t('settings_error_name_required')));
            exit;
        }
        setSetting('site_name', $name);
        flashSet('success', t('settings_name_updated'));
        redirect('settings');
    }

    if ($action === 'reset_name') {
        deleteSetting('site_name');
        flashSet('success', t('settings_name_reset_msg'));
        redirect('settings');
    }

    if ($action === 'upload_logo') {
        $file = $_FILES['logo'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            flashSet('error', t('settings_error_no_file'));
            redirect('settings');
        }
        try {
            $newLogoPath = processSiteLogoUpload($file);
            deleteSiteLogoFile(getSetting('logo_path'));
            setSetting('logo_path', $newLogoPath);
            flashSet('success', t('settings_logo_updated'));
        } catch (UploadRejected $e) {
            flashSet('error', $e->getMessage());
        }
        redirect('settings');
    }

    if ($action === 'remove_logo') {
        deleteSiteLogoFile(getSetting('logo_path'));
        deleteSetting('logo_path');
        flashSet('success', t('settings_logo_removed'));
        redirect('settings');
    }

    redirect('settings');
}
