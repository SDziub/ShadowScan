<?php

function calculateRisk(array $emailResult, array $usernameResult): array
{
    $risk = ($emailResult['risk'] ?? 0) + ($usernameResult['risk'] ?? 0);

    $level = "Niskie ryzyko";

    if ($risk > 40) {
        $level = "Srednie ryzyko";
    }

    if ($risk > 80) {
        $level = "Wysokie ryzyko";
    }

    $score = max(0, 100 - $risk);

    return [
        "privacyScore" => $score,
        "level" => $level,
        "rawRisk" => $risk
    ];
}