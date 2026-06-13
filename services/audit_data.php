<?php

require_once ROOT_PATH . "services/EmailScanner.php";
require_once ROOT_PATH . "services/UsernameScanner.php";
require_once ROOT_PATH . "services/RiskCalculator.php";
require_once ROOT_PATH . "services/AccountScanner.php";
require_once ROOT_PATH . "services/BreachScanner.php";
function scanEmail(string $email): array
{
    $domain = substr(strrchr($email, "@"), 1);

    $breaches = getBreaches($email);

    $risk = 0;

    if (!empty($breaches)) {
        $risk += count($breaches) * 15;
    }

    return [
        "email" => $email,
        "domain" => $domain,
        "breaches" => $breaches,
        "risk" => $risk
    ];
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

function analyzeDigitalFootprint(string $email, string $username): array
{
    $signals = [];
    $risk = 0;

    $breaches = getBreaches($email);

    if (!empty($breaches)) {
        $risk += count($breaches) * 15;
        $signals[] = "Email znaleziony w wyciekach danych";
    }

    $platforms = [
        "GitHub",
        "Reddit",
        "TikTok"
    ];

    $found = 0;

    foreach ($platforms as $p) {
        $url = match($p) {
            "GitHub" => "https://github.com/" . urlencode($username),
            "Reddit" => "https://www.reddit.com/user/" . urlencode($username),
            "TikTok" => "https://www.tiktok.com/@" . urlencode($username),
        };

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_exec($ch);

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code >= 200 && $code < 400) {
            $found++;
            $signals[] = "Profil istnieje: {$p}";
        }
    }

    $risk += $found * 10;


    $intelRisk = 0;

    $intelResults = fetchIntelX($email);

    if (!empty($intelResults)) {
        $intelRisk = 20;
        $signals[] = "Wzmianki w indeksach OSINT (IntelligenceX)";
    }

    $risk += $intelRisk;

    return [
        "risk" => min($risk, 100),
        "signals" => $signals
    ];
}
?>

<?php foreach ($accountsResult as $account): ?>
    <div class="profile-item">
        <strong><?= htmlspecialchars($account["platform"]) ?></strong>

        <?php if ($account["exists"]): ?>
            <p>✓ Znaleziono </p>

            <a href="<?= htmlspecialchars($account["url"]) ?>" target="_blank">
                Otwórz profil
            </a>
        <?php else: ?>
            <p>✗ Nie znaleziono</p>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
