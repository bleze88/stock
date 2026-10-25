<?php
declare(strict_types=1);

const SUPPORTED_LOCALES = ['fr', 'en', 'de', 'it'];
const DEFAULT_LOCALE = 'fr';

const LOCALE_LABELS = [
    'fr' => 'Français',
    'en' => 'English',
    'de' => 'Deutsch',
    'it' => 'Italiano',
];

function isValidLocale(string $value): bool
{
    return in_array($value, SUPPORTED_LOCALES, true);
}

function currentLocale(): string
{
    static $locale = null;
    if ($locale !== null) {
        return $locale;
    }

    $user = currentUser();
    if ($user !== null && isValidLocale($user['locale'] ?? '')) {
        $locale = $user['locale'];
        return $locale;
    }

    if (isset($_COOKIE['lang']) && isValidLocale($_COOKIE['lang'])) {
        $locale = $_COOKIE['lang'];
        return $locale;
    }

    $locale = DEFAULT_LOCALE;
    return $locale;
}

function loadTranslations(string $locale): array
{
    static $cache = [];
    if (isset($cache[$locale])) {
        return $cache[$locale];
    }
    $file = APP_PATH . "/lang/{$locale}.php";
    $cache[$locale] = is_file($file) ? require $file : [];
    return $cache[$locale];
}

/**
 * Convention d'échappement : t() ne fait QUE de l'interpolation de texte, sans
 * échapper quoi que ce soit — chaque appelant est responsable de passer le
 * résultat dans e() avant de l'afficher (comme pour toute autre valeur).
 * Vérifié (2026-10) : chaque appel de t() avec un paramètre utilisateur passe
 * par e(t(...)) ou par le rendu des messages flash (qui échappe l'ensemble du
 * message) — voir app/views/partials/flash.php. N'ajoutez jamais une chaîne
 * de traduction combinant du HTML littéral avec un paramètre non échappé, et
 * n'affichez jamais `<?= t(...) ?>` sans e() autour dès qu'un paramètre
 * provient d'une donnée saisie par un utilisateur.
 */
function t(string $key, array $params = []): string
{
    $locale = currentLocale();
    $translations = loadTranslations($locale);
    $text = $translations[$key] ?? loadTranslations(DEFAULT_LOCALE)[$key] ?? $key;

    foreach ($params as $paramKey => $value) {
        $text = str_replace(':' . $paramKey, (string)$value, $text);
    }

    return $text;
}
