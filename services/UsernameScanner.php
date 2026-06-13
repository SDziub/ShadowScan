<?php

function checkUrl($url)
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_NOBODY => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_USERAGENT => "ShadowScanBot/1.0"
    ]);

    curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    return $statusCode >= 200 && $statusCode < 400;
}

function scanUsername($username)
{
    $username = trim($username);

$platforms = [
    "GitHub" => "https://github.com/" . urlencode($username),
    "Reddit" => "https://www.reddit.com/user/" . urlencode($username),
    "TikTok" => "https://www.tiktok.com/@" . urlencode($username),
    "Instagram" => "https://www.instagram.com/" . urlencode($username),
    "X / Twitter" => "https://x.com/" . urlencode($username),
    "YouTube" => "https://www.youtube.com/@" . urlencode($username),
    "Pinterest" => "https://www.pinterest.com/" . urlencode($username),
    "Twitch" => "https://www.twitch.tv/" . urlencode($username),
    "Steam" => "https://steamcommunity.com/id/" . urlencode($username),
    "GitLab" => "https://gitlab.com/" . urlencode($username),
    "Medium" => "https://medium.com/@" . urlencode($username),
    "Kaggle" => "https://www.kaggle.com/" . urlencode($username),
];

    $foundProfiles = [];

    foreach ($platforms as $platform => $url) {
        $exists = checkUrl($url);

        $foundProfiles[] = [
            "platform" => $platform,
            "url" => $url,
            "exists" => $exists
        ];
    }

    $interests = [];

    if (preg_match("/dev|code|program|cpp|js|php|html|css/i", $username)) {
        $interests[] = "programowanie";
    }

    if (preg_match("/game|mc|lol|cs|valorant|fortnite/i", $username)) {
        $interests[] = "gry";
    }

    if (preg_match("/fit|gym|run|sport/i", $username)) {
        $interests[] = "sport";
    }

    $existingCount = count(array_filter($foundProfiles, fn($p) => $p["exists"]));

    $risk = ($existingCount * 15) + (count($interests) * 10);

    return [
        "username" => $username,
        "profiles" => $foundProfiles,
        "interests" => $interests,
        "risk" => $risk
    ];
}