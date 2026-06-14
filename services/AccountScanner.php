<?php

require_once ROOT_PATH . "services/PlatformChecker.php";

function scanAccounts(string $email, string $username): array
{
$emailName = explode('@', $email)[0];

$candidates = array_unique([
    $username,
    $emailName,

    str_replace('.', '', $emailName),
    str_replace('_', '', $emailName),

    strtolower($username),
    strtolower($emailName)
]);

    $platforms = [
        "GitHub" => "https://github.com/%s",
        "YouTube" => "https://www.youtube.com/@%s",
        "Twitch" => "https://www.twitch.tv/%s",
        "X / Twitter" => "https://x.com/%s",
        "TikTok" => "https://www.tiktok.com/@%s",
        "Spotify" => "https://open.spotify.com/user/%s"
    ];

    $results = [];

foreach ($platforms as $platform => $pattern) {

    $usernameUrl = sprintf($pattern, rawurlencode($username));
    $emailUrl    = sprintf($pattern, rawurlencode($emailName));

    $usernameRes = checkPlatform($platform, $usernameUrl, $username);
    $emailRes    = checkPlatform($platform, $emailUrl, $emailName);

    $usernameExists = $usernameRes["exists"] === true;
    $emailExists    = $emailRes["exists"] === true;

    // 1. znaleziono oba
    if ($usernameExists && $emailExists) {

        $results[] = [
            "platform" => $platform,
            "exists" => true,
            "confidence" => 100,
            "url" => $usernameUrl,
            "foundAs" => "username+email",
            "status" => 200
        ];

        continue;
    }

    // 2. znaleziono po mailu
    if ($emailExists) {

        $results[] = [
            "platform" => $platform,
            "exists" => true,
            "confidence" => 90,
            "url" => $emailUrl,
            "foundAs" => $emailName,
            "status" => $emailRes["status"]
        ];

        continue;
    }

    // 3. znaleziono po nicku
    if ($usernameExists) {

        $results[] = [
            "platform" => $platform,
            "exists" => true,
            "confidence" => 80,
            "url" => $usernameUrl,
            "foundAs" => $username,
            "status" => $usernameRes["status"]
        ];

        continue;
    }

    // nic nie znaleziono
    $results[] = [
        "platform" => $platform,
        "exists" => false,
        "confidence" => 0,
        "url" => null,
        "foundAs" => null,
        "status" => max(
            $usernameRes["status"] ?? 0,
            $emailRes["status"] ?? 0
        )
    ];
}

    return $results;
}