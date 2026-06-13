<?php
session_start();
require_once "path.php";

$email = $_POST['email'] ?? '';
$username = $_POST['username'] ?? '';

if(empty($email) || empty($username)) {
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

<h1>Raport audytu</h1>

<p>Email: <?= htmlspecialchars($email) ?></p>
<p>Nick: <?= htmlspecialchars($username) ?></p>

<h2>Wynik prywatności</h2>

<p><?= $risk['privacyScore'] ?>/100</p>
<p><?= $risk['level'] ?></p>

</body>
</html>