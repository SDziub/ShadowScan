<?php

if (!function_exists('checkUrl')) {
    function checkUrl(string $url): bool
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 2,
            CURLOPT_USERAGENT => "ShadowScanBot/1.0"
        ]);

        curl_exec($ch);

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return $statusCode >= 200 && $statusCode < 400;
    }
}

function scanAccounts(string $email, string $username): array
{
    $emailName = explode("@", $email)[0] ?? '';

    $candidates = array_filter(array_unique([
        trim($username),
        trim($emailName)
    ]));

    $platforms = [
        "GitHub"      => "https://github.com/%s",
        "Reddit"      => "https://www.reddit.com/user/%s",
        "TikTok"      => "https://www.tiktok.com/@%s",
        "Instagram"   => "https://www.instagram.com/%s",
        "X / Twitter" => "https://x.com/%s",
        "Twitch"      => "https://www.twitch.tv/%s",
        "Pinterest"   => "https://www.pinterest.com/%s",
        "GitLab"      => "https://gitlab.com/%s",
        "Steam"       => "https://steamcommunity.com/id/%s"
    ];

    $results = [];

    foreach ($candidates as $candidate) {

        foreach ($platforms as $platform => $urlPattern) {

            $url = sprintf(
                $urlPattern,
                rawurlencode($candidate)
            );

            $exists = checkUrl($url);

            $results[] = [
                "platform"   => $platform,
                "searchedAs" => $candidate,
                "url"        => $url,
                "exists"     => $exists
            ];
        }
    }

    return $results;
}