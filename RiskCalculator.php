<?php

function calculateRisk(
    array $emailResult,
    array $accountsResult,
    array $interests
): array {
    $risk = $emailResult['risk'] ?? 0;

    $foundAccounts = count(
        array_filter(
            $accountsResult,
            function (array $account): bool {
                return $account['exists'] ?? false;
            }
        )
    );

    $risk += $foundAccounts * 10;
    $risk += count($interests) * 5;

    $risk = min($risk, 100);

    if ($risk <= 40) {
        $level = "Niskie ryzyko";
    } elseif ($risk <= 80) {
        $level = "Średnie ryzyko";
    } else {
        $level = "Wysokie ryzyko";
    }

    $score = 100 - $risk;

    return [
        "privacyScore" => $score,
        "level" => $level,
        "rawRisk" => $risk,
        "foundAccounts" => $foundAccounts,
        "interestCount" => count($interests)
    ];
}