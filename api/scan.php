<?php
header("Content-Type: application/json");

require_once "../services/EmailScanner.php";
require_once "../services/UsernameScanner.php";
require_once "../services/RiskCalculator.php";

$email = "test@gmail.com";
$username = "Silvernax";

if (!$email) {
    echo json_encode([
        "success" => false,
        "error" => "Brakuje emaila."
    ]);
    exit;
}

if (!$username) {
    echo json_encode([
        "success" => false,
        "error" => "Brakuje nicku."
    ]);
    exit;
}

$emailResult = scanEmail($email);
$usernameResult = scanUsername($username);
$risk = calculateRisk($emailResult, $usernameResult);

echo json_encode([
    "success" => true,
    "email" => $emailResult,
    "username" => $usernameResult,
    "risk" => $risk
]);