<?php

function checkUrl($url) {
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_NOBODY => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_USERAGENT => "ShadowScanBot/1.0"
    ]);

    curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $status >= 200 && $status < 400;
}

function scanAccounts($email, $username) {
    $emailName = explode("@", $email)[0];

    $candidates = array_unique([
        $username,
        $emailName
    ]);

    $platforms = [
        "GitHub" => "https://github.com/%s",
        "Reddit" => "https://www.reddit.com/user/%s",
        "TikTok" => "https://www.tiktok.com/@%s",
        "Instagram" => "https://www.instagram.com/%s",
        "X / Twitter" => "https://x.com/%s",
        "Twitch" => "https://www.twitch.tv/%s",
        "Pinterest" => "https://www.pinterest.com/%s",
        "GitLab" => "https://gitlab.com/%s",
        "Steam" => "https://steamcommunity.com/id/%s"
    ];

    $results = [];

    foreach ($candidates as $candidate) {
        foreach ($platforms as $platform => $urlPattern) {
            $url = sprintf($urlPattern, urlencode($candidate));

            $results[] = [
                "platform" => $platform,
                "searchedAs" => $candidate,
                "url" => $url,
                "exists" => checkUrl($url)
            ];
        }
    }

    return $results;
}