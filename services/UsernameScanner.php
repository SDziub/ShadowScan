<?php

function scanUsername($username) {
    $platforms = [
        "GitHub" => "https://github.com/" . $username,
        "Reddit" => "https://www.reddit.com/user/" . $username,
        "TikTok" => "https://www.tiktok.com/@" . $username
    ];

    $interests = [];

    if (preg_match("/dev|code|program|cpp|js/i", $username)) {
        $interests[] = "programowanie";
    }

    if (preg_match("/game|mc|lol|cs|valorant/i", $username)) {
        $interests[] = "gry";
    }

    return [
        "username" => $username,
        "possibleProfiles" => $platforms,
        "interests" => $interests,
        "risk" => count($interests) * 15
    ];
}