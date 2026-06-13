<?php

require_once ROOT_PATH . "services/UrlChecker.php";

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
    
    if (preg_match("/crypto|btc|eth|web3/i", $username)) {
    $interests[] = "kryptowaluty";
}

if (preg_match("/music|dj|beat|rap/i", $username)) {
    $interests[] = "muzyka";
}

if (preg_match("/ai|ml|data/i", $username)) {
    $interests[] = "sztuczna inteligencja";
}

    $existingCount = count(array_filter($foundProfiles, fn($p) => $p["exists"]));

    $risk = min(
    ($existingCount * 10) + (count($interests) * 10),
    100
);

    return [
        "username" => $username,
        "profiles" => $foundProfiles,
        "interests" => $interests,
        "risk" => $risk
    ];
}