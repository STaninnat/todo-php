<?php

function formateDateBkk(?string $utcDate): ?string
{
    if (!$utcDate) return null;
    return (new DateTime($utcDate, new DateTimeZone('UTC')))
        ->setTimezone(new DateTimeZone('Asia/Bangkok'))
        ->format('Y/M/d (g:i A)');
}
