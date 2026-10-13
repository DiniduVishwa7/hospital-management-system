<?php
/**
 * Shared read-only directory search utilities for MediCore.
 * Include AFTER authentication and PDO setup in a directory page.
 * No sessions, database writes or form changes occur here.
 */
function mdSearchText(array $query, string $key, int $limit = 100): string
{
    $value = $query[$key] ?? '';
    if (!is_string($value)) {
        return '';
    }
    $value = trim($value);
    // Limit length even if someone manually changes the GET URL.
    return function_exists('mb_substr')
        ? mb_substr($value, 0, $limit, 'UTF-8')
        : substr($value, 0, $limit);
}

/** Treat user-entered %, _ and ! as literal search characters. */
function mdLikePattern(string $text): string
{
    return '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $text) . '%';
}

/** Return an ISO date or blank (ignore invalid query-string dates). */
function mdSearchDate(array $query, string $key): string
{
    $text = mdSearchText($query, $key, 10);
    if ($text === '') {
        return '';
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $text);
    return $date && $date->format('Y-m-d') === $text ? $text : '';
}

/** Positive numeric IDs can be searched directly (not through LIKE). */
function mdSearchId(string $text): ?int
{
    if ($text === '' || !ctype_digit($text)) {
        return null;
    }
    $value = filter_var($text, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $value === false ? null : (int) $value;
}
