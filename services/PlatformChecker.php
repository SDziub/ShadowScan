<?php

function checkPlatform(string $platform, string $url, string $username): array
{
return match ($platform) {
    "GitHub"      => checkGitHub($url),
    "YouTube"     => checkYouTube($url),
    "Twitch"      => checkTwitch($url),
    "X / Twitter" => checkGeneric($url),
    "TikTok"      => checkGeneric($url),
    "Spotify"     => checkGeneric($url),
    default       => checkGeneric($url),
};
}

function checkGeneric(string $url): array
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_USERAGENT => "Mozilla/5.0"
    ]);

    $html = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    if ($html === false || !empty($error)) {
        return [
            "exists" => null,
            "confidence" => 0,
            "status" => $status
        ];
    }

    if ($status === 404) {
        return [
            "exists" => false,
            "confidence" => 90,
            "status" => $status
        ];
    }

    if ($status >= 200 && $status < 300) {
        return [
            "exists" => true,
            "confidence" => 60,
            "status" => $status
        ];
    }

    if (
        $status === 403 ||
        $status === 429 ||
        $status >= 500 ||
        $status === 0
    ) {
        return [
            "exists" => null,
            "confidence" => 0,
            "status" => $status
        ];
    }

    return [
        "exists" => null,
        "confidence" => 0,
        "status" => $status
    ];
}

function checkGitHub(string $url): array
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 5,
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

function checkYouTube(string $url): array
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_USERAGENT => "Mozilla/5.0"
    ]);

    $html = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if (!$html) {
        return [
            "exists" => false,
            "confidence" => 0,
            "status" => $status
        ];
    }

    if (
        stripos($html, "This channel does not exist") !== false ||
        stripos($html, "404 Not Found") !== false
    ) {
        return [
            "exists" => false,
            "confidence" => 0,
            "status" => $status
        ];
    }

    return [
        "exists" => ($status === 200),
        "confidence" => ($status === 200 ? 80 : 20),
        "status" => $status
    ];
}

function checkTwitch(string $url): array
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_USERAGENT => "Mozilla/5.0"
    ]);

    $html = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if (!$html || $status === 404) {
        return [
            "exists" => false,
            "confidence" => 0,
            "status" => $status
        ];
    }

    if (
        stripos($html, "Sorry. Unless you've got a time machine") !== false
    ) {
        return [
            "exists" => false,
            "confidence" => 0,
            "status" => $status
        ];
    }

    return [
        "exists" => $status === 200,
        "confidence" => $status === 200 ? 80 : 20,
        "status" => $status
    ];
}

