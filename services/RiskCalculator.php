<?php

function calculateRisk(array $emailResult, array $usernameResult): array
{
    $risk = ($emailResult['risk'] ?? 0) + ($usernameResult['risk'] ?? 0);

    $level = "LOW";

    if ($risk > 40) {
        $level = "MEDIUM";
    }

    if ($risk > 80) {
        $level = "HIGH";
    }

    $score = max(0, 100 - $risk);

    return [
        "privacyScore" => $score,
        "level" => $level,
        "rawRisk" => $risk
    ];
}