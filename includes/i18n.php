<?php
/**
 * Minimal translation layer. Site language is a global system setting
 * (same pattern as the existing 'theme' setting) — set once in
 * Settings > General, applied to every page/user from then on.
 *
 * t($key) returns the string for the active language, falling back to
 * English (then the key itself) if a translation is missing, so an
 * un-translated string never breaks the page.
 */

$GLOBALS['__lang'] = 'en';

function setActiveLanguage(string $lang): void
{
    $GLOBALS['__lang'] = $lang === 'so' ? 'so' : 'en';
}

function activeLanguage(): string
{
    return $GLOBALS['__lang'] ?? 'en';
}

function t(string $key): string
{
    static $strings = null;
    if ($strings === null) {
        $strings = require __DIR__ . '/i18n_strings.php';
    }
    $lang = activeLanguage();
    return $strings[$key][$lang] ?? $strings[$key]['en'] ?? $key;
}

/** Localized 3-letter weekday abbreviation for a Y-m-d date string (used by the dashboard activity chart). */
function tWeekday(string $ymd): string
{
    $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $key = 'day.' . $days[(int) date('N', strtotime($ymd)) - 1];
    return t($key);
}
