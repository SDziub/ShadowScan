<?php
header("Content-Type: application/json; charset=utf-8");

require_once "path.php";
require_once ROOT_PATH . "services/EmailScanner.php";
require_once ROOT_PATH . "services/AccountScanner.php";
require_once ROOT_PATH . "services/RiskCalculator.php";
require_once ROOT_PATH . "services/FootprintScanner.php";
require_once ROOT_PATH . "services/UrlChecker.php";
require_once ROOT_PATH . "services/InterestProfiler.php";

try {
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');

    if ($email === '') {
        throw new Exception("Brakuje emaila.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Niepoprawny adres email.");
    }

    if ($username === '') {
        throw new Exception("Brakuje nicku.");
    }

    $emailResult = [];
    $usernameResult = [];
    $footprintResult = [];
    $risk = 0;

    if (function_exists('scanEmail')) {
        $emailResult = scanEmail($email);
    }

    if (function_exists('scanUsername')) {
        $usernameResult = scanUsername($username);
    }

    if (function_exists('analyzeDigitalFootprint')) {
        $footprintResult = analyzeDigitalFootprint($email, $username);
    }
    $risk = 0;

    if (function_exists('calculateRisk')) {
        $risk = calculateRisk(
            $emailResult ?? [],
            $accountsResult ?? [],
            $interests ?? []
        );
    }
        $response = [
            "success" => true,
            "email" => $emailResult,
            "username" => $usernameResult,
            "footprint" => $footprintResult,
            "risk" => $risk
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

    } catch (Throwable $e) {

        http_response_code(400);
        echo json_encode(["success" => false, "error" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }