<?php

require_once ROOT_PATH . "services/PlatformChecker.php";

function scanAccounts(string $email, string $username): array
{
    $emailName = explode("@", $email)[0] ?? '';

    $candidates = array_values(
        array_unique(
            array_filter([
                trim($username),
                trim($emailName)
            ])
        )
    );

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

    $best = [
        "exists" => null,
        "confidence" => -1,
        "url" => null,
        "foundAs" => null,
        "status" => null
    ];

    foreach ($candidates as $c) {

        $url = sprintf($pattern, rawurlencode($c));

        $res = checkPlatform($platform, $url, $c);

        $confidence = $res["confidence"] ?? 0;

        if ($confidence > $best["confidence"]) {
            $best = [
                "exists" => $res["exists"] ?? null,
                "confidence" => $confidence,
                "url" => $url,
                "foundAs" => $c,
                "status" => $res["status"] ?? null
            ];
        }
    }

    $results[] = array_merge([
        "platform" => $platform
    ], $best);
}

    return $results;
}