<?php
declare(strict_types=1);

function isNonEmptyString(string $value, int $maxLength = 255): bool
{
    $len = mb_strlen($value);
    return $len > 0 && $len <= $maxLength;
}

function isValidUsername(string $value): bool
{
    return (bool)preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $value);
}

function isValidRole(string $value): bool
{
    return in_array($value, [ROLE_ADMIN, ROLE_MANAGER, ROLE_VIEWER], true);
}

function roleLabel(string $role): string
{
    return match ($role) {
        ROLE_ADMIN => t('users_role_admin'),
        ROLE_MANAGER => t('users_role_manager'),
        ROLE_VIEWER => t('users_role_viewer'),
        default => $role,
    };
}
