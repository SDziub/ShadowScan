<?php

function getSensitiveLeakInformation(array $breaches): array
{
    $sensitiveKeywords = [
        'password',
        'passwords',
        'password hash',
        'hashed password',
        'hash',
        'phone',
        'phone number',
        'address',
        'physical address',
        'credit card',
        'bank account',
        'date of birth',
        'ip address'
    ];

    $sensitiveFields = [];

    foreach ($breaches as $breach) {
        foreach (($breach['fields'] ?? []) as $field) {
            $normalizedField = strtolower(trim((string) $field));

            foreach ($sensitiveKeywords as $keyword) {
                if (str_contains($normalizedField, $keyword)) {
                    $sensitiveFields[] = (string) $field;
                    break;
                }
            }
        }
    }

    return array_values(array_unique($sensitiveFields));
}

function calculateSecurityStatus(
    array $emailResult,
    array $accountsResult,
    array $interests,
    array $identityExposure
): array {
    $breaches = $emailResult['breaches'] ?? [];
    $breachCount = count($breaches);

    $scanSuccess = $emailResult['scanSuccess'] ?? true;

    $sensitiveFields = getSensitiveLeakInformation($breaches);
    $hasSensitiveLeak = !empty($sensitiveFields);

    $foundAccounts = count(
        array_filter(
            $accountsResult,
            function (array $account): bool {
                return $account['exists'] ?? false;
            }
        )
    );

    $similarAccountsCount = $foundAccounts;

    $interestCount = count($interests);

    if (!$scanSuccess) {
        $breachStatus = 'Nieznany';
        $breachLevel = 0;
        $breachMessage = 'Nie udało się sprawdzić danych w bazie LeakCheck.';
    } elseif ($breachCount === 0) {
        $breachStatus = 'Bezpieczny';
        $breachLevel = 1;
        $breachMessage = 'Nie znaleziono adresu e-mail w danych zwróconych przez LeakCheck.';
    } elseif ($breachCount === 1 && !$hasSensitiveLeak) {
        $breachStatus = 'Wymaga uwagi';
        $breachLevel = 2;
        $breachMessage = 'Adres e-mail występuje w jednym znanym wycieku.';
    } elseif ($breachCount <= 3 && !$hasSensitiveLeak) {
        $breachStatus = 'Zagrożony';
        $breachLevel = 3;
        $breachMessage = 'Adres e-mail występuje w kilku znanych wyciekach.';
    } else {
        $breachStatus = 'Krytyczny';
        $breachLevel = 4;
        $breachMessage = 'Wykryto wiele wycieków lub ujawnienie wrażliwych danych.';
    }

    if ($foundAccounts === 0) {
        $visibilityStatus = 'Niska';
        $visibilityLevel = 1;
    } elseif ($foundAccounts <= 2) {
        $visibilityStatus = 'Umiarkowana';
        $visibilityLevel = 2;
    } elseif ($foundAccounts <= 4) {
        $visibilityStatus = 'Wysoka';
        $visibilityLevel = 3;
    } else {
        $visibilityStatus = 'Bardzo wysoka';
        $visibilityLevel = 4;
    }

    if ($interestCount === 0) {
        $profilingStatus = 'Niska';
        $profilingLevel = 1;
    } elseif ($interestCount <= 2) {
        $profilingStatus = 'Umiarkowana';
        $profilingLevel = 2;
    } else {
        $profilingStatus = 'Wysoka';
        $profilingLevel = 3;
    }

    if ($breachLevel === 4) {
        $mainStatus = 'KRYTYCZNY';
        $mainLevel = 4;
        $mainMessage = 'Adres e-mail występuje w wielu wyciekach lub ujawniono wrażliwe dane.';
    } elseif ($breachLevel === 3) {
        $mainStatus = 'ZAGROŻONY';
        $mainLevel = 3;
        $mainMessage = 'Adres e-mail występuje w publicznych bazach wycieków.';
    } elseif (
        $breachLevel === 2 ||
        $visibilityLevel >= 3 ||
        $profilingLevel >= 3 ||
        ($identityExposure['level'] ?? 1) >= 3
    ) {
        $mainStatus = 'UMIARKOWANY';
        $mainLevel = 2;
        $mainMessage = 'Wykryto sygnały zwiększające widoczność lub możliwość profilowania.';
    } elseif ($breachLevel === 0) {
        $mainStatus = 'NIEZNANY';
        $mainLevel = 0;
        $mainMessage = 'Nie udało się ukończyć sprawdzania wycieków danych.';
    } else {
        $mainStatus = 'BEZPIECZNY';
        $mainLevel = 1;
        $mainMessage = 'Nie znaleziono znanych wycieków i wykryto niski poziom ekspozycji.';
    }

    $recommendations = [];

    if ($breachCount > 0) {
        $recommendations[] = 'Zmień hasło w serwisach powiązanych z tym adresem e-mail.';
        $recommendations[] = 'Nie używaj tego samego hasła w wielu serwisach.';
        $recommendations[] = 'Włącz uwierzytelnianie dwuskładnikowe.';
    }

    if ($hasSensitiveLeak) {
        $recommendations[] = 'Sprawdź, jakie dokładnie dane zostały ujawnione.';
    }

    if ($foundAccounts >= 3) {
        $recommendations[] = 'Rozważ używanie różnych nazw użytkownika w różnych serwisach.';
    }

    if ($interestCount >= 2) {
        $recommendations[] = 'Nick i e-mail ujawniają informacje przydatne do profilowania.';
    }

    if (($identityExposure['level'] ?? 1) >= 3) {
        $recommendations[] = 'Usuń z nicku lub e-maila rok, lokalizację albo dane osobowe.';
    }

    if (empty($recommendations)) {
        $recommendations[] = 'Kontynuuj stosowanie różnych haseł i uwierzytelniania dwuskładnikowego.';
    }

    $identityLevel = $identityExposure['level'] ?? 1;

$combinedLevel = max(
    $profilingLevel,
    $identityLevel
);

$combinedStatus = match ($combinedLevel) {
    1 => 'Niska',
    2 => 'Umiarkowana',
    3 => 'Podwyższona',
    default => 'Wysoka'
};

    return [
        'similarAccounts' => [
    'status' => $foundAccounts === 0
        ? 'Brak'
        : ($foundAccounts <= 2
            ? 'Kilka'
            : ($foundAccounts <= 5
                ? 'Wiele'
                : 'Bardzo wiele')),
    'level' => min(
        max($foundAccounts, 1),
        4
    ),
    'count' => $foundAccounts
],
        'profilingExposure' => [
    'status' => $combinedStatus,
    'level' => $combinedLevel,
    'interestsCount' => $interestCount,
    'signalsCount' => count(
        $identityExposure['signals'] ?? []
    )
],
        'main' => [
            'status' => $mainStatus,
            'level' => $mainLevel,
            'message' => $mainMessage
        ],
        'breaches' => [
            'status' => $breachStatus,
            'level' => $breachLevel,
            'message' => $breachMessage,
            'count' => $breachCount,
            'sensitiveFields' => $sensitiveFields
        ],
        'visibility' => [
            'status' => $visibilityStatus,
            'level' => $visibilityLevel,
            'count' => $foundAccounts
        ],
        'profiling' => [
            'status' => $profilingStatus,
            'level' => $profilingLevel,
            'count' => $interestCount
        ],
        'identityExposure' => $identityExposure,
        'recommendations' => array_values(array_unique($recommendations))
    ];
}