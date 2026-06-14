<?php

function analyzeIdentityExposure(
    string $email,
    string $username
): array {
    $emailName = explode('@', $email)[0] ?? '';
    $emailName = strtolower($emailName);
    $username = strtolower($username);
    $combined = $emailName . ' ' . $username;
    $signals = [];

    if (preg_match('/(?:19[5-9][0-9]|20[0-1][0-9])/', $combined)) {
        $signals[] = [
            'type' => 'birth_year',
            'message' => 'Nazwa może zawierać rok urodzenia.',
            'severity' => 3
        ];
    }
    if (
        preg_match(
            '/^[a-ząćęłńóśźż]{3,}[._-][a-ząćęłńóśźż]{3,}$/u',
            $emailName
        )
    ) {
        $signals[] = [
            'type' => 'full_name',
            'message' => 'Adres e-mail może zawierać imię i nazwisko.',
            'severity' => 4
        ];
    }
    $locations = [
        'lodz', 'łódź', 'warszawa', 'krakow', 'kraków',
        'wroclaw', 'wrocław', 'poznan', 'poznań',
        'gdansk', 'gdańsk'
    ];
    foreach ($locations as $location) {
        if (str_contains($combined, $location)) {
            $signals[] = [
                'type' => 'location',
                'message' => 'Nazwa może sugerować lokalizację.',
                'severity' => 3
            ];
            break;
        }
    }
    $occupationKeywords = [
        'dev', 'developer', 'programmer', 'student',
        'teacher', 'doctor', 'designer', 'admin'
    ];
    foreach ($occupationKeywords as $keyword) {
        if (str_contains($combined, $keyword)) {
            $signals[] = [
                'type' => 'occupation',
                'message' => 'Nazwa może ujawniać zawód lub zajęcie.',
                'severity' => 2
            ];
            break;
        }
    }
    $interestKeywords = [
        'gamer', 'gaming', 'fit', 'gym', 'music',
        'dj', 'crypto', 'photo', 'travel'
    ];
    foreach ($interestKeywords as $keyword) {
        if (str_contains($combined, $keyword)) {
            $signals[] = [
                'type' => 'interest',
                'message' => 'Nazwa ujawnia możliwe zainteresowania.',
                'severity' => 2
            ];
            break;
        }
    }

    $severityScore = array_sum(
        array_column($signals, 'severity')
    );
    if ($severityScore === 0) {
        $status = 'Niska';
        $level = 1;
    } elseif ($severityScore <= 3) {
        $status = 'Umiarkowana';
        $level = 2;
    } elseif ($severityScore <= 6) {
        $status = 'Podwyższona';
        $level = 3;
    } else {
        $status = 'Wysoka';
        $level = 4;
    }
    return [
        'status' => $status,
        'level' => $level,
        'score' => min($severityScore, 10),
        'signals' => $signals
    ];
}