<?php

function calculateRisk($emailResult, $usernameResult) {
    $score = 100;

    $score -= $emailResult["risk"];
    $score -= $usernameResult["risk"];

    if ($score < 0) $score = 0;

    return [
        "privacyScore" => $score,
        "level" => $score >= 75 ? "Niskie ryzyko" : ($score >= 45 ? "Średnie ryzyko" : "Wysokie ryzyko")
    ];
}