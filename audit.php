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

$emailResult = scanEmail($email);
$usernameResult = scanUsername($username);
$risk = calculateRisk($emailResult, $usernameResult);
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

    <h2>Wykryte profile</h2>

    <?php foreach ($usernameResult["profiles"] as $profile): ?>
        <div class="profile-item">

            <strong><?= htmlspecialchars($profile["platform"]) ?></strong>

            <?php if ($profile["exists"]): ?>
                <span class="found">Znaleziono</span>
                <br>
                <a href="<?= htmlspecialchars($profile["url"]) ?>" target="_blank">
                    Zobacz profil
                </a>
            <?php else: ?>
                <span class="not-found">✗ Nie znaleziono</span>
            <?php endif; ?>

        </div>
    <?php endforeach; ?>

</section>

<section class="right-panel">

    <div class="content">

        <h3 class="typing-heading">Szczegóły audytu</h3>

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
            <h3>Cookies i śledzenie</h3>
            <p>
                Użytkownik korzystający z tego samego nicku na wielu platformach może być łatwiej śledzony.
            </p>
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

</div>

</body>
</html>