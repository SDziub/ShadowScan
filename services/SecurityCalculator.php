<?php

function getSensitiveLeakInformation(array $breaches): array
{
    $sensitiveKeywords = [
        'password',
        'passwords',
        'hash',
        'phone',
        'address',
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

    $interestCount = count($interests);

    $identitySignals = $identityExposure['signals'] ?? [];
    $identitySignalCount = count($identitySignals);
    $identityScore = $identityExposure['score'] ?? 0;

    /*
     * 1. WYCIEKI DANYCH
     */

    if (!$scanSuccess) {
        $breachStatus = 'Nieznany';
        $breachLevel = 0;
        $breachMessage =
            'Nie udało się połączyć z bazą LeakCheck. Wynik nie może zostać potwierdzony.';
    } elseif ($breachCount === 0) {
        $breachStatus = 'Bezpieczny';
        $breachLevel = 1;
        $breachMessage =
            'Nie znaleziono podanego adresu e-mail w danych zwróconych przez LeakCheck.';
    } elseif ($breachCount === 1 && !$hasSensitiveLeak) {
        $breachStatus = 'Umiarkowany';
        $breachLevel = 2;
        $breachMessage =
            'Adres e-mail występuje w jednym wycieku, ale nie wykryto ujawnienia szczególnie wrażliwych danych.';
    } else {
        $breachStatus = 'Zagrożony';
        $breachLevel = 3;

        if ($hasSensitiveLeak) {
            $breachMessage =
                'W wykrytych wyciekach mogły zostać ujawnione wrażliwe dane, takie jak hasło, telefon, adres lub adres IP.';
        } else {
            $breachMessage =
                'Adres e-mail występuje w kilku publicznie znanych wyciekach danych.';
        }
    }

    /*
     * 2. WIDOCZNOŚĆ CYFROWA
     */

    if ($foundAccounts === 0) {
        $visibilityStatus = 'Bezpieczny';
        $visibilityLevel = 1;
        $visibilityMessage =
            'Nie znaleziono publicznych profili powiązanych z podanym nickiem lub adresem e-mail.';
    } elseif ($foundAccounts <= 2) {
        $visibilityStatus = 'Umiarkowany';
        $visibilityLevel = 2;
        $visibilityMessage =
            'Znaleziono niewielką liczbę profili. Powiązanie aktywności użytkownika jest możliwe, ale ograniczone.';
    } else {
        $visibilityStatus = 'Zagrożony';
        $visibilityLevel = 3;
        $visibilityMessage =
            'Ten sam nick lub część adresu e-mail występuje na wielu platformach, co ułatwia powiązanie kont.';
    }

    /*
     * 3. MOŻLIWOŚĆ PROFILOWANIA
     */

    if ($interestCount === 0) {
        $profilingStatus = 'Bezpieczny';
        $profilingLevel = 1;
        $profilingMessage =
            'Na podstawie nicku i adresu e-mail nie wykryto jednoznacznych zainteresowań.';
    } elseif ($interestCount <= 2) {
        $profilingStatus = 'Umiarkowany';
        $profilingLevel = 2;
        $profilingMessage =
            'Podane dane ujawniają część możliwych zainteresowań użytkownika.';
    } else {
        $profilingStatus = 'Zagrożony';
        $profilingLevel = 3;
        $profilingMessage =
            'Na podstawie podanych danych można zbudować wyraźniejszy profil zainteresowań użytkownika.';
    }

    /*
     * 4. EKSPOZYCJA TOŻSAMOŚCI
     */

    if ($identitySignalCount === 0) {
        $identityStatus = 'Bezpieczny';
        $identityLevel = 1;
        $identityMessage =
            'Nick i adres e-mail nie zawierają oczywistych wskazówek dotyczących tożsamości.';
    } elseif ($identitySignalCount === 1 && $identityScore <= 3) {
        $identityStatus = 'Umiarkowany';
        $identityLevel = 2;
        $identityMessage =
            'Wykryto pojedynczą informację, która może sugerować zainteresowanie lub zawód';
    } else {
        $identityStatus = 'Zagrożony';
        $identityLevel = 3;
        $identityMessage =
            'Podane dane zawierają kilka wskazówek mogących ułatwić identyfikację użytkownika.';
    }

    /*
     * 5. STATUS GŁÓWNY
     */

    $highestLevel = max(
        $breachLevel,
        $visibilityLevel,
        $profilingLevel,
        $identityLevel
    );

    if ($breachLevel === 3) {
        $mainStatus = 'ZAGROŻONY';
        $mainLevel = 3;
        $mainMessage =
            'Adres e-mail występuje w publicznych bazach wycieków lub ujawniono wrażliwe dane.';
    } elseif ($highestLevel === 3) {
        $mainStatus = 'ZAGROŻONY';
        $mainLevel = 3;
        $mainMessage =
            'Nie wykryto krytycznego wycieku, ale podane dane powodują wysoki poziom ekspozycji lub profilowania.';
    } elseif ($highestLevel === 2) {
        $mainStatus = 'UMIARKOWANY';
        $mainLevel = 2;
        $mainMessage =
            'Wykryto sygnały zwiększające widoczność, możliwość profilowania lub identyfikację użytkownika.';
    } else {
        $mainStatus = 'BEZPIECZNY';
        $mainLevel = 1;
        $mainMessage =
            'Nie wykryto znanych wycieków ani istotnych sygnałów zwiększonej ekspozycji.';
    }

    /*
     * 6. REKOMENDACJE
     */

    $recommendations = [];

    if ($breachLevel >= 2) {
        $recommendations[] =
            'Zmień hasło w serwisach powiązanych z tym adresem e-mail.';
        $recommendations[] =
            'Włącz uwierzytelnianie dwuskładnikowe.';
    }

    if ($visibilityLevel === 3) {
        $recommendations[] =
            'Rozważ stosowanie różnych nazw użytkownika na różnych platformach.';
    }

    if ($profilingLevel >= 2) {
        $recommendations[] =
            'Unikaj umieszczania zainteresowań bezpośrednio w nicku lub adresie e-mail.';
    }

    if ($identityLevel >= 2) {
        $recommendations[] =
            'Usuń z nicku lub e-maila rok, lokalizację albo inne informacje osobiste.';
    }

    if (empty($recommendations)) {
        $recommendations[] =
            'Kontynuuj stosowanie różnych haseł oraz uwierzytelniania dwuskładnikowego.';
    }

    return [
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
            'message' => $visibilityMessage,
            'count' => $foundAccounts
        ],

        'profiling' => [
            'status' => $profilingStatus,
            'level' => $profilingLevel,
            'message' => $profilingMessage,
            'count' => $interestCount,
            'interests' => $interests
        ],

        'identityExposure' => [
            'status' => $identityStatus,
            'level' => $identityLevel,
            'message' => $identityMessage,
            'count' => $identitySignalCount,
            'signals' => $identitySignals
        ],

        'recommendations' => array_values(
            array_unique($recommendations)
        )
    ];
}