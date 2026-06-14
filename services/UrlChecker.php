<?php

function checkUrl(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_NOBODY => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_USERAGENT => "Mozilla/5.0 (ShadowScanBot/1.0)",
    ]);

    curl_exec($ch);
    $error = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        "status" => $code,
        "error" => $error,
        "exists" => match(true) {
            $code === 404 => false,
            $code >= 200 && $code < 300 => true,
            $code >= 300 && $code < 400 => true,
            $code === 429 => null,
            $code === 403 => null,
            default => false
        }
    ];
}