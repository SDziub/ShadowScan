<?php
session_start();
require_once "path.php";

$email = trim($_POST['email'] ?? '');
$username = trim($_POST['username'] ?? '');

if (
    empty($email) ||
    empty($username) ||
    !filter_var($email, FILTER_VALIDATE_EMAIL)
) {
    header("Location: index.php");
    exit;
}

require_once ROOT_PATH . "services/EmailScanner.php";
require_once ROOT_PATH . "services/AccountScanner.php";
require_once ROOT_PATH . "services/FootprintScanner.php";
require_once ROOT_PATH . "services/UrlChecker.php";
require_once ROOT_PATH . "services/InterestProfiler.php";
require_once ROOT_PATH . "services/IdentityExposureScanner.php";
require_once ROOT_PATH . "services/SecurityCalculator.php";

$emailResult = scanEmail($email);

$accountsResult = scanAccounts(
    $email,
    $username
);

$interests = analyzeInterests(
    $email,
    $username
);

$identityExposure = analyzeIdentityExposure(
    $email,
    $username
);

$security = calculateSecurityStatus(
    $emailResult,
    $accountsResult,
    $interests,
    $identityExposure
);

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <?php require_once ROOT_PATH . "public/includes/head_audit.php"; ?>
    <title>Raport - ShadowScan</title>
</head>
<body>

<div class="container">

<section class="left-panel">

    <div class="report-header">
        <h1 class="typing-title">Raport audytu</h1>
        <p>Email: <?= htmlspecialchars($email) ?></p>
        <p>Nick: <?= htmlspecialchars($username) ?></p>
    </div>

    <div
        class="main-security-status"
        data-level="<?= (int) $security['main']['level'] ?>"
    >
        <p class="status-label">Stan bezpieczeństwa danych</p>

        <h2>
            <?= htmlspecialchars($security['main']['status']) ?>
        </h2>

        <p class="main-status-message">
            <?= htmlspecialchars($security['main']['message']) ?>
        </p>
    </div>

    <div class="security-grid">

        <article
            class="security-card"
            data-level="<?= (int) $security['breaches']['level'] ?>"
            tabindex="0"
        >
            <h3>Wycieki danych</h3>

            <strong>
                <?= htmlspecialchars($security['breaches']['status']) ?>
            </strong>

            <div class="status-bar">
                <span style="width: <?= (int) $security['breaches']['level'] * 25 ?>%"></span>
            </div>

            <div class="card-tooltip">
                <p>
                    Znalezione wycieki:
                    <?= (int) $security['breaches']['count'] ?>
                </p>

                <p>
                    <?= htmlspecialchars($security['breaches']['message']) ?>
                </p>

                <?php if (!empty($security['breaches']['sensitiveFields'])): ?>
                    <p>
                        Wrażliwe dane:
                        <?= htmlspecialchars(
                            implode(', ', $security['breaches']['sensitiveFields'])
                        ) ?>
                    </p>
                <?php endif; ?>
            </div>
        </article>


        <article
            class="security-card"
            data-level="<?= (int) $security['visibility']['level'] ?>"
            tabindex="0"
        >
            <h3>Widoczność cyfrowa</h3>

            <strong>
                <?= htmlspecialchars($security['visibility']['status']) ?>
            </strong>

            <div class="status-bar">
                <span style="width: <?= (int) $security['visibility']['level'] * 25 ?>%"></span>
            </div>

            <div class="card-tooltip">
                <p>
                    Powiązane profile:
                    <?= (int) $security['visibility']['count'] ?>
                </p>

                <p>
                    Im więcej kont znalezionych po nicku i e-mailu,
                    tym łatwiej powiązać Twoją aktywność online.
                </p>
            </div>
        </article>


        <article
            class="security-card"
            data-level="<?= (int) $security['profiling']['level'] ?>"
            tabindex="0"
        >
            <h3>Możliwość profilowania</h3>

            <strong>
                <?= htmlspecialchars($security['profiling']['status']) ?>
            </strong>

            <div class="status-bar">
                <span style="width: <?= (int) $security['profiling']['level'] * 25 ?>%"></span>
            </div>

            <div class="card-tooltip">
                <p>
                    Wykryte zainteresowania:
                    <?= (int) $security['profiling']['count'] ?>
                </p>

                <?php if (!empty($interests)): ?>
                    <p>
                        Kategorie:
                        <?= htmlspecialchars(
                            implode(
                                ', ',
                                array_map(
                                    fn($interest) => is_array($interest)
                                        ? $interest['name']
                                        : $interest,
                                    $interests
                                )
                            )
                        ) ?>
                    </p>
                <?php endif; ?>
            </div>
        </article>


        <article
            class="security-card"
            data-level="<?= (int) $security['identityExposure']['level'] ?>"
            tabindex="0"
        >
            <h3>Ekspozycja tożsamości</h3>

            <strong>
                <?= htmlspecialchars($security['identityExposure']['status']) ?>
            </strong>

            <div class="status-bar">
                <span style="width: <?= (int) $security['identityExposure']['level'] * 25 ?>%"></span>
            </div>

            <div class="card-tooltip">
                <p>
                    Wykryte sygnały:
                    <?= count($security['identityExposure']['signals']) ?>
                </p>

                <?php if (!empty($security['identityExposure']['signals'])): ?>
                    <ul>
                        <?php foreach ($security['identityExposure']['signals'] as $signal): ?>
                            <li>
                                <?= htmlspecialchars($signal['message']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>
                        Nie wykryto oczywistych wskazówek identyfikacyjnych.
                    </p>
                <?php endif; ?>
            </div>
        </article>

    </div>

    <section class="recommendations">
        <h2>Rekomendacje</h2>

        <ul>
            <?php foreach ($security['recommendations'] as $recommendation): ?>
                <li><?= htmlspecialchars($recommendation) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>

</section>

<section class="right-panel">

    <div class="content">

        <h2 class="typing-heading">
            Gdzie znaleziono konta?
        </h2>

        <div id="auditLoading">
            Trwa analiza...
        </div>

<div id="auditResults" style="display:none;"></div>

    </div>

</section>
</div>
<section class="security-summary">

    <div
        class="main-security-status"
        data-level="<?= (int) $security['main']['level'] ?>"
    >
        <p class="status-label">Stan bezpieczeństwa danych</p>

        <h2>
            <?= htmlspecialchars($security['main']['status']) ?>
        </h2>

        <p>
            <?= htmlspecialchars($security['main']['message']) ?>
        </p>
    </div>

    <div class="security-grid">

        <?php
        $cards = [
            [
                'title' => 'Wycieki danych',
                'data' => $security['breaches'],
                'description' => 'Znalezione wycieki: ' . $security['breaches']['count']
            ],
            [
                'title' => 'Widoczność cyfrowa',
                'data' => $security['visibility'],
                'description' => 'Powiązane profile: ' . $security['visibility']['count']
            ],
            [
                'title' => 'Możliwość profilowania',
                'data' => $security['profiling'],
                'description' => 'Wykryte kategorie: ' . $security['profiling']['count']
            ],
            [
                'title' => 'Ekspozycja tożsamości',
                'data' => $security['identityExposure'],
                'description' => 'Wykryte sygnały: ' .
                    count($security['identityExposure']['signals'])
            ]
        ];
        ?>

        <?php foreach ($cards as $card): ?>
            <article
                class="security-card"
                data-level="<?= (int) $card['data']['level'] ?>"
            >
                <h3><?= htmlspecialchars($card['title']) ?></h3>

                <strong>
                    <?= htmlspecialchars($card['data']['status']) ?>
                </strong>

                <div class="status-bar">
                    <span
                        style="width:
                        <?= (int) $card['data']['level'] * 25 ?>%"
                    ></span>
                </div>

                <p><?= htmlspecialchars($card['description']) ?></p>
            </article>
        <?php endforeach; ?>

    </div>

</section>
<script>

window.addEventListener('load', () => {

    const loading = document.getElementById('auditLoading');
    const results = document.getElementById('auditResults');

    fetch('services/audit_data.php', {

        method: 'POST',

        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },

        body:
            'email=<?= urlencode($email) ?>' +
            '&username=<?= urlencode($username) ?>'

    })

    .then(response => response.text())

    .then(html => {

        loading.style.display = 'none';

        results.innerHTML = html;

        results.style.display = 'block';

    })

    .catch(() => {

        loading.innerHTML =
            'Błąd podczas wykonywania analizy.';

    });

});

</script>
</body>
</html>