<?php

function normalizeProfileText(string $text): string
{
    $text = strtolower($text);

    $text = preg_replace('/[_\-.0-9]+/', ' ', $text);
    $text = preg_replace('/[^a-ząćęłńóśźż ]/u', '', $text);

    return trim($text);
}

function analyzeInterests(string $email, string $username): array
{
    $emailName = explode('@', $email)[0] ?? '';

    $usernameText = normalizeProfileText($username);
    $emailText = normalizeProfileText($emailName);

    $text = $usernameText . ' ' . $emailText;

    $interestKeywords = [
        'programowanie' => [
            'developer',
            'dev',
            'programmer',
            'programowanie',
            'code',
            'coder',
            'coding',
            'cpp',
            'cplusplus',
            'python',
            'java',
            'javascript',
            'php',
            'html',
            'css',
            'backend',
            'frontend',
            'software'
        ],

        'gry' => [
            'gamer',
            'gaming',
            'game',
            'minecraft',
            'valorant',
            'fortnite',
            'league',
            'lol',
            'counterstrike',
            'steam',
            'xbox',
            'playstation'
        ],

        'sport' => [
            'sport',
            'fitness',
            'fit',
            'gym',
            'running',
            'runner',
            'football',
            'soccer',
            'basketball',
            'cycling'
        ],

        'kryptowaluty' => [
            'crypto',
            'bitcoin',
            'btc',
            'ethereum',
            'blockchain',
            'webthree',
            'nft'
        ],

        'muzyka' => [
            'music',
            'muzyka',
            'dj',
            'producer',
            'beat',
            'beats',
            'rap',
            'rock',
            'metal',
            'guitar'
        ],

        'sztuczna inteligencja' => [
            'artificialintelligence',
            'machinelearning',
            'deeplearning',
            'datascience',
            'neural',
            'chatgpt',
            'openai'
        ],

        'fotografia' => [
            'photo',
            'photography',
            'fotografia',
            'camera',
            'canon',
            'nikon',
            'photoeditor'
        ],

        'podróże' => [
            'travel',
            'traveler',
            'trip',
            'journey',
            'wander',
            'explorer',
            'podroze'
        ],

        'motoryzacja' => [
            'car',
            'cars',
            'auto',
            'motor',
            'motorcycle',
            'bmw',
            'audi',
            'mercedes',
            'tuning'
        ],

        'film i seriale' => [
            'movie',
            'movies',
            'film',
            'cinema',
            'series',
            'netflix',
            'anime'
        ]
    ];

    $scores = [];

    foreach ($interestKeywords as $interest => $keywords) {
        $scores[$interest] = 0;

        foreach ($keywords as $keyword) {
            if (str_contains($text, strtolower($keyword))) {
                $scores[$interest]++;
            }
        }
    }

    $interests = [];

    foreach ($scores as $interest => $score) {
        if ($score > 0) {
            $interests[] = [
                'name' => $interest,
                'score' => $score
            ];
        }
    }

    usort($interests, function (array $a, array $b): int {
        return $b['score'] <=> $a['score'];
    });

    return $interests;
    $result = [];

foreach ($scores as $interest => $score) {
    if ($score > 0) {
        $result[] = $interest;
    }
}

return $result;
}