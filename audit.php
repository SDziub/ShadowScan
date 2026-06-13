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
require_once ROOT_PATH . "services/RiskCalculator.php";
require_once ROOT_PATH . "services/FootprintScanner.php";
require_once ROOT_PATH . "services/UrlChecker.php";
require_once ROOT_PATH . "services/InterestProfiler.php";

$emailResult = scanEmail($email);

$accountsResult = scanAccounts(
    $email,
    $username
);

$interests = analyzeInterests(
    $email,
    $username
);

$risk = calculateRisk(
    $emailResult,
    $accountsResult,
    $interests
);

$footprint = analyzeDigitalFootprint(
    $email,
    $username
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

    <h1 class="typing-title">Raport audytu</h1>

    <p>Email: <?= htmlspecialchars($email) ?></p>
    <p>Nick: <?= htmlspecialchars($username) ?></p>

    <h2>Wynik prywatności</h2>
        <p>
            <?= htmlspecialchars(
                (string) $risk['privacyScore'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>/100
        </p>

        <p>
            <?= htmlspecialchars(
                $risk['level'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>


    <div class="content">

        <h3>Szczegóły audytu</h3>

             <h3>Wycieki danych</h3>

            <?php if (!empty($emailResult['breaches'])): ?>

                <?php foreach ($emailResult['breaches'] as $breach): ?>

                    <div class="audit-card">

                        <h3>
                            <?= htmlspecialchars(
                                $breach['source']['name'] ?? 'Nieznane źródło',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </h3>

                        <p>
                            Data:
                            <?= htmlspecialchars(
                                $breach['source']['date'] ?? 'Brak danych',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>

                        <p>
                            Typ wycieku:
                            <?= htmlspecialchars(
                                $breach['source']['type'] ?? 'Brak danych',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>

                        <?php if (!empty($breach['fields'])): ?>

                            <h4>Ujawnione dane:</h4>

                            <ul>
                                <?php foreach ($breach['fields'] as $field): ?>

                                    <li>
                                        <?= htmlspecialchars(
                                            $field,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </li>

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

                <p>
                    <strong>Ryzyko śledzenia:</strong>

                    <?= htmlspecialchars(
                        (string) ($footprint['risk'] ?? 0),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>/100
                </p>

                <?php if (!empty($footprint['signals'])): ?>

                    <ul>
                        <?php foreach ($footprint['signals'] as $signal): ?>

                            <li>
                                <?= htmlspecialchars(
                                    $signal,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </li>

                        <?php endforeach; ?>
                    </ul>

                <?php else: ?>

                    <p>Brak silnych sygnałów śledzenia.</p>

                <?php endif; ?>

            </div>


            <div class="audit-card">

                <h3>Profilowanie</h3>

                <?php if (!empty($interests)): ?>

                    <p>
                        Na podstawie nazwy użytkownika i adresu e-mail
                        wykryto możliwe zainteresowania:
                    </p>

                    <ul>
                        <?php foreach ($interests as $interest): ?>

                            <li>
                                <?= htmlspecialchars(
                                    is_array($interest)
                                        ? $interest['name']
                                        : $interest,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </li>

                        <?php endforeach; ?>
                    </ul>

                <?php else: ?>

                    <p>
                        Nie wykryto jednoznacznych zainteresowań.
                    </p>

                <?php endif; ?>

            </div>

        </div>

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