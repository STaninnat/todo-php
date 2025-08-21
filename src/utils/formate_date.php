<?php

/**
 * Convert a UTC datetime string to Bangkok timezone and format it.
 *
 * @param string|null $utcDate UTC datetime string
 * @return string|null Formatted date string in 'Y/M/d (g:i A)' format or null if input is empty
 */
function formateDateBkk(?string $utcDate): ?string
{
    // Return null if input is empty
    if (!$utcDate) return null;

    // Create DateTime in UTC, convert to Bangkok timezone, and format
    return (new DateTime($utcDate, new DateTimeZone('UTC')))
        ->setTimezone(new DateTimeZone('Asia/Bangkok'))
        ->format('Y/M/d (g:i A)');
}
