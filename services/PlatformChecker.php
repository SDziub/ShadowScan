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
    $html = file_get_contents($url);

    $confidence = 50;

    if (!$html) return ["exists" => false, "confidence" => 0];

    if (str_contains($html, "Sorry. Unless you've got a time machine")) {
        $confidence = 0;
    } else {
        $confidence = 80;
    }

    return [
        "exists" => $confidence > 50,
        "confidence" => $confidence
    ];
}

