<?php

function checkPlatform(string $platform, string $url, string $username): array
{
    return match ($platform) {
        "GitHub"    => checkGitHub($url),
        "Reddit"    => checkReddit($url),
        "Twitch"    => checkTwitch($url),
        "X / Twitter" => checkGeneric($url),
        "TikTok"    => checkGeneric($url),
        "Spotify"   => checkGeneric($url),
        default     => checkGeneric($url),
    };
}

function checkGeneric(string $url): array
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 4,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_USERAGENT => "Mozilla/5.0"
    ]);

    $html = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $confidence = 50;

    if ($status === 404) $confidence = 0;
    elseif ($status >= 200 && $status < 300) $confidence = 60;
    elseif ($status === 302) $confidence = 40;
    elseif ($status === 403) $confidence = 30;
    elseif ($status === 429) $confidence = null; // unknown

    return [
        "exists" => $confidence >= 50,
        "confidence" => $confidence,
        "status" => $status
    ];
}

function checkGitHub(string $url): array
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 2,
        CURLOPT_CONNECTTIMEOUT => 1,
        CURLOPT_USERAGENT => "Mozilla/5.0"
    ]);

    $html = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $exists = ($status === 200);

    return [
        "exists" => $exists,
        "confidence" => $exists ? 95 : 5,
        "status" => $status
    ];
}

function checkReddit(string $url): array
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 2,
        CURLOPT_CONNECTTIMEOUT => 1,
        CURLOPT_USERAGENT => "Mozilla/5.0"
    ]);

    $html = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $confidence = 0;

    if (str_contains($html, "Sorry, nobody on Reddit goes by that name")) {
        $confidence = 0;
    }
    elseif (str_contains($html, "u/") || str_contains($html, "karma")) {
        $confidence = 85;
    }
    elseif ($status === 200) {
        $confidence = 60;
    }

    return [
        "exists" => $confidence > 50,
        "confidence" => $confidence,
        "status" => $status
    ];
}

function checkTwitch(string $url): array
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_NOBODY => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_CONNECTTIMEOUT => 1,
        CURLOPT_USERAGENT => 'Mozilla/5.0'
    ]);

    curl_exec($ch);

    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    return [
        'exists' => $status === 200,
        'confidence' => $status === 200 ? 90 : 0,
        'status' => $status
    ];
}

