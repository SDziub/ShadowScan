<?php

require_once ROOT_PATH . "services/BreachScanner.php";
require_once ROOT_PATH . "services/UrlChecker.php";

function analyzeDigitalFootprint(string $email, string $username): array
{
    $signals = [];
    $risk = 0;

    $breaches = getBreaches($email);

    if (!empty($breaches)) {
        $signals[] = "Email znaleziony w wyciekach danych";
        $risk += 30;
    }

    $platforms = [
        "GitHub"      => "https://github.com/" . urlencode($username),
        "TikTok"      => "https://www.tiktok.com/" . urlencode($username),
        "Instagram"   => "https://www.instagram.com/" . urlencode($username),
        "X / Twitter" => "https://x.com/" . urlencode($username),
        "Twitch"      => "https://www.twitch.tv/" . urlencode($username),
        "Spotify"     => "https://open.spotify.com/user/" . urlencode($username)
    ];


    if (!empty(fetchIntelX($email))) {
        $signals[] = "Wzmianki w indeksach OSINT";
        $risk += 20;
    }

    return [
        "risk" => min($risk, 100),
        "signals" => $signals
    ];
}