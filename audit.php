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

            <h1 class="typing-title">
                Raport audytu
            </h1>

            <p>
                Email:
                <?= htmlspecialchars(
                    $email,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>

            <p>
                Nick:
                <?= htmlspecialchars(
                    $username,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>

        </div>


        <div
            class="main-security-status"
            data-level="<?= (int) $security['main']['level'] ?>"
        >

            <p class="status-label">
                Stan bezpieczeństwa danych
            </p>

            <h2>
                <?= htmlspecialchars(
                    $security['main']['status'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </h2>

            <p class="main-status-message">
                <?= htmlspecialchars(
                    $security['main']['message'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>

        </div>


        <div class="security-grid">

            <!-- WYCIEKI DANYCH -->

            <article
                class="security-card"
                data-level="<?= (int) $security['breaches']['level'] ?>"
                tabindex="0"
            >

                <h3>Wycieki danych</h3>

                <strong>
                    <?= htmlspecialchars(
                        $security['breaches']['status'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>

                <div class="status-bar">
                    <span
                        style="width:
                        <?= (int) round(
                            $security['breaches']['level'] / 3 * 100
                        ) ?>%"
                    ></span>
                </div>

                <div class="card-tooltip">

                    <p>
                        <?= htmlspecialchars(
                            $security['breaches']['message'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                    <p>
                        Liczba wycieków:
                        <?= (int) $security['breaches']['count'] ?>
                    </p>

                    <?php if (
                        !empty($security['breaches']['sensitiveFields'])
                    ): ?>

                        <p>
                            Ujawnione wrażliwe dane:
                            <?= htmlspecialchars(
                                implode(
                                    ', ',
                                    $security['breaches']['sensitiveFields']
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>

                    <?php endif; ?>

                </div>

            </article>


            <!-- WIDOCZNOŚĆ CYFROWA -->

            <article
                class="security-card"
                data-level="<?= (int) $security['visibility']['level'] ?>"
                tabindex="0"
            >

                <h3>Widoczność cyfrowa</h3>

                <strong>
                    <?= htmlspecialchars(
                        $security['visibility']['status'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>

                <div class="status-bar">
                    <span
                        style="width:
                        <?= (int) round(
                            $security['visibility']['level'] / 3 * 100
                        ) ?>%"
                    ></span>
                </div>

                <div class="card-tooltip">

                    <p>
                        <?= htmlspecialchars(
                            $security['visibility']['message'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                    <p>
                        Powiązane profile:
                        <?= (int) $security['visibility']['count'] ?>
                    </p>

                </div>

            </article>


            <!-- PROFILOWANIE -->

            <article
                class="security-card"
                data-level="<?= (int) $security['profiling']['level'] ?>"
                tabindex="0"
            >

                <h3>Możliwość profilowania</h3>

                <strong>
                    <?= htmlspecialchars(
                        $security['profiling']['status'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>

                <div class="status-bar">
                    <span
                        style="width:
                        <?= (int) round(
                            $security['profiling']['level'] / 3 * 100
                        ) ?>%"
                    ></span>
                </div>

                <div class="card-tooltip">

                    <p>
                        <?= htmlspecialchars(
                            $security['profiling']['message'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                    <?php if (
                        !empty($security['profiling']['interests'])
                    ): ?>

                        <p>Możliwe zainteresowania:</p>

                        <ul>
                            <?php foreach (
                                $security['profiling']['interests']
                                as $interest
                            ): ?>

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

            </article>


            <!-- EKSPOZYCJA TOŻSAMOŚCI -->

            <article
                class="security-card"
                data-level="<?=
                    (int) $security['identityExposure']['level']
                ?>"
                tabindex="0"
            >

                <h3>Ekspozycja tożsamości</h3>

                <strong>
                    <?= htmlspecialchars(
                        $security['identityExposure']['status'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>

                <div class="status-bar">
                    <span
                        style="width:
                        <?= (int) round(
                            $security['identityExposure']['level']
                            / 3
                            * 100
                        ) ?>%"
                    ></span>
                </div>

                <div class="card-tooltip">

                    <p>
                        <?= htmlspecialchars(
                            $security['identityExposure']['message'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                    <?php if (
                        !empty(
                            $security['identityExposure']['signals']
                        )
                    ): ?>

                        <ul>
                            <?php foreach (
                                $security['identityExposure']['signals']
                                as $signal
                            ): ?>

                                <li>
                                    <?= htmlspecialchars(
                                        $signal['message'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </li>

                            <?php endforeach; ?>
                        </ul>

                    <?php else: ?>

                        <p>
                            Nie wykryto informacji ułatwiających
                            identyfikację.
                        </p>

                    <?php endif; ?>

                </div>

            </article>

        </div>


        <section class="recommendations">

            <h2>Rekomendacje</h2>

            <ul>
                <?php foreach (
                    $security['recommendations']
                    as $recommendation
                ): ?>

                    <li>
                        <?= htmlspecialchars(
                            $recommendation,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </li>

                <?php endforeach; ?>
            </ul>

        </section>

    </section>


    <section class="right-panel">

        <div class="content">

            <h2 class="typing-heading">
                Gdzie znaleziono konta?
            </h2>

            <div class="profiles-grid">

                <?php foreach ($accountsResult as $account): ?>

                    <div class="profile-item">

                        <strong>
                            <?= htmlspecialchars(
                                $account['platform'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </strong>

                        <?php if ($account['exists']): ?>

                            <p>
                                ✓ Znaleziono

                                <?php if (!empty($account['foundAs'])): ?>

                                    jako:
                                    <?= htmlspecialchars(
                                        $account['foundAs'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                <?php endif; ?>
                            </p>

                            <?php if (!empty($account['url'])): ?>

                                <a
                                    href="<?= htmlspecialchars(
                                        $account['url'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    Otwórz profil
                                </a>

                            <?php endif; ?>

                        <?php else: ?>

                            <p>✗ Nie znaleziono</p>

                        <?php endif; ?>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

    </section>

</div>


<script>
const securityCards = document.querySelectorAll(".security-card");

function toggleCard(selectedCard) {
    const wasOpen = selectedCard.classList.contains("is-open");

    securityCards.forEach((card) => {
        card.classList.remove("is-open");
    });

    if (!wasOpen) {
        selectedCard.classList.add("is-open");
    }
}

securityCards.forEach((card) => {
    card.addEventListener("click", () => {
        toggleCard(card);
    });

    card.addEventListener("keydown", (event) => {
        if (event.key === "Enter" || event.key === " ") {
            event.preventDefault();
            toggleCard(card);
        }
    });
});
</script>

</body>
</html>