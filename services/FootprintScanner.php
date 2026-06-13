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
        "GitHub" => "https://github.com/" . urlencode($username),
        "Reddit" => "https://www.reddit.com/user/" . urlencode($username),
        "TikTok" => "https://www.tiktok.com/@" . urlencode($username),
    ];

    $found = 0;

    foreach ($platforms as $platform => $url) {

        if (checkUrl($url)) {
            $found++;
            $signals[] = "Profil istnieje: {$platform}";
        }
    }

    $risk += $found * 10;

    if (!empty(fetchIntelX($email))) {
        $signals[] = "Wzmianki w indeksach OSINT";
        $risk += 20;
    }

    return [
        "risk" => min($risk, 100),
        "signals" => $signals
    ];
}