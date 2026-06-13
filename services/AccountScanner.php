<?php

require_once ROOT_PATH . "services/PlatformChecker.php";

function scanAccounts(string $email, string $username): array
{
    $emailName = explode("@", $email)[0] ?? '';

    $candidates = array_values(array_unique(array_filter([
        trim($username),
        trim($emailName)
    ])));

    $platforms = [
        "GitHub" => "https://github.com/%s",
        "Reddit" => "https://www.reddit.com/user/%s",
        "Twitch" => "https://www.twitch.tv/%s",
        "X / Twitter" => "https://x.com/%s",
        "TikTok" => "https://www.tiktok.com/@%s",
        "Spotify" => "https://open.spotify.com/user/%s"
    ];

    $results = [];

    foreach ($platforms as $platform => $pattern) {

        $best = [
            "exists" => false,
            "confidence" => 0,
            "url" => null,
            "foundAs" => null
        ];

        foreach ($candidates as $c) {

            $url = sprintf($pattern, rawurlencode($c));

            $res = checkPlatform($platform, $url, $c);

            if ($res["confidence"] > $best["confidence"]) {
                $best = [
                    "exists" => $res["exists"],
                    "confidence" => $res["confidence"],
                    "url" => $url,
                    "foundAs" => $c
                ];
            }
        }

        $results[] = array_merge([
            "platform" => $platform
        ], $best);
    }

    return $results;
}