<?php
session_start();
require_once "path.php";

$email = $_POST['email'] ?? '';
$username = $_POST['username'] ?? '';

if (empty($email) || empty($username)) {
    header("Location: index.php");
    exit;
}

require_once ROOT_PATH . "services/EmailScanner.php";
require_once ROOT_PATH . "services/UsernameScanner.php";
require_once ROOT_PATH . "services/RiskCalculator.php";
require_once ROOT_PATH . "services/AccountScanner.php";
require_once ROOT_PATH . "services/BreachScanner.php";

$accountsResult = scanAccounts($email, $username);

$emailResult = scanEmail($email);
$usernameResult = scanUsername($username);
$risk = calculateRisk($emailResult, $usernameResult);

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
$footprint = analyzeDigitalFootprint($email, $username);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <?php require_once ROOT_PATH . "public/includes/head.php"; ?>
    <title>Raport - ShadowScan</title>
</head>
<body>

<div class="container">

<section class="left-panel">

    <h1 class="typing-title">Raport audytu</h1>

    <p>Email: <?= htmlspecialchars($email) ?></p>
    <p>Nick: <?= htmlspecialchars($username) ?></p>

    <h2>Wynik prywatności</h2>

    <p><?= $risk['privacyScore'] ?>/100</p>
    <p><?= $risk['level'] ?></p>


    <div class="content">

        <h3>Szczegóły audytu</h3>

        <h3>Wycieki danych</h3>

        <?php if (!empty($emailResult['breaches'])): ?>

            <?php foreach ($emailResult['breaches'] as $breach): ?>

                <div class="audit-card">

                    <h3>
                        <?= htmlspecialchars($breach['source']['name'] ?? 'Nieznane źródło') ?>
                    </h3>

                    <p>
                        Data: <?= htmlspecialchars($breach['source']['date'] ?? 'Brak danych') ?>
                    </p>

                    <p>
                        Typ wycieku:
                        <?= htmlspecialchars($breach['source']['type'] ?? 'Brak danych') ?>
                    </p>

                    <?php if (!empty($breach['fields'])): ?>
                        <h4>Ujawnione dane:</h4>
                        <ul>
                            <?php foreach ($breach['fields'] as $field): ?>
                                <li><?= htmlspecialchars($field) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        <?php else: ?>
            <p>Nie znaleziono znanych wycieków.</p>
        <?php endif; ?>

<div class="audit-card">
    <h3>Digital footprint & tracking</h3>

    <p><strong>Ryzyko śledzenia:</strong> <?= $footprint['risk'] ?>/100</p>

    <?php if (!empty($footprint['signals'])): ?>
        <ul>
            <?php foreach ($footprint['signals'] as $s): ?>
                <li><?= htmlspecialchars($s) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Brak silnych sygnałów śledzenia.</p>
    <?php endif; ?>
</div>

        <div class="audit-card">
            <h3>Profilowanie</h3>

            <?php if (!empty($usernameResult["interests"])): ?>
                <ul>
                    <?php foreach ($usernameResult["interests"] as $interest): ?>
                        <li><?= htmlspecialchars($interest) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Brak danych o zainteresowaniach.</p>
            <?php endif; ?>

        </div>

            </div>
</section>

<section class="right-panel">

    <div class="content">
    <h2 class="typing-heading">Gdzie znaleziono konta?</h2>

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

</section>

</section>

</div>

</body>
</html>
