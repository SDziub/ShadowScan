<?php
require_once ROOT_PATH . "services/UrlChecker.php";

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

    foreach ($platforms as $platform => $urlPattern) {
        $checked = [];
        $found = false;
        $foundUrl = null;
        $foundAs = null;

        foreach ($candidates as $candidate) {
            $url = sprintf($urlPattern, rawurlencode($candidate));
            $exists = checkUrl($url);

            $checked[] = [
                "searchedAs" => $candidate,
                "url" => $url,
                "exists" => $exists
            ];

            if ($exists && !$found) {
                $found = true;
                $foundUrl = $url;
                $foundAs = $candidate;
            }
        }

        $results[] = [
            "platform" => $platform,
            "exists" => $found,
            "foundAs" => $foundAs,
            "url" => $foundUrl,
            "checked" => $checked
        ];
    }

    return $results;
}