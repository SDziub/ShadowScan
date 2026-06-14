<?php

function fetchIntelX(string $email): array
{
    $apiKey = getenv("INTELX_API_KEY");
    if (!$apiKey) return [];

    $url = "https://2.intelx.io/intelligent/search";
    $payload = [
        "term" => $email,
        "maxresults" => 10,
        "media" => 0
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "x-key: {$apiKey}",
            "Content-Type: application/json"
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 4,
        CURLOPT_CONNECTTIMEOUT => 1
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode !== 200 || !$response) return [];
    $data = json_decode($response, true);
    if (!is_array($data)) return [];
    $results = [];
    foreach (($data['records'] ?? []) as $item) {
        $results[] = [
            "source" => [
                "name" => "IntelligenceX",
                "date" => $item['date'] ?? null,
                "type" => "osint"
            ],
            "fields" => [
                $item['name'] ?? ($item['title'] ?? 'OSINT result')
            ]
        ];
    }
    return $results;
}

function getBreaches(string $email): array
{
    $apiKey = getenv("LEAKCHECK_API_KEY");
    $url = "https://leakcheck.io/api/public?check=" . urlencode($email);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_CONNECTTIMEOUT => 1,
        CURLOPT_HTTPHEADER => [
            "X-API-Key: {$apiKey}"
        ]
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $breaches = [];
    if ($response !== false && $httpCode === 200) {
        $data = json_decode($response, true);
        if (is_array($data) && !empty($data['result'])) {
            foreach ($data['result'] as $item) {
                $breaches[] = [
                    "source" => [
                        "name" => $item['source']['name'] ?? 'LeakCheck',
                        "date" => $item['source']['date'] ?? null,
                        "type" => $item['source']['type'] ?? 'data-breach'
                    ],
                    "fields" => $item['fields'] ?? []
                ];
            }
        }
    }
    $intelx = fetchIntelX($email);
    if (!empty($intelx)) {
        $breaches = array_merge($breaches, $intelx);
    }

    return $breaches;
}