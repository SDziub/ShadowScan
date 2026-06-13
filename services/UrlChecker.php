<?php

function checkUrl(string $url): bool
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_NOBODY => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_USERAGENT => "ShadowScanBot/1.0"
    ]);

    curl_exec($ch);

    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    return $statusCode >= 200 && $statusCode < 400;
}