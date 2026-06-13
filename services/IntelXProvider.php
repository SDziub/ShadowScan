<?php

function fetchIntelX(string $query): array
{
    $apiKey2 = getenv("INTELX_API_KEY");

    if (!$apiKey2) return [];

    $url = "https://2.intelx.io/intelligent/search";

    $payload = [
        "term" => $query,
        "maxresults" => 20,
        "media" => 0
    ];

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "x-key: {$apiKey2}",
            "Content-Type: application/json"
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 15
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($httpCode !== 200 || !$response) return [];

    $data = json_decode($response, true);

    if (!is_array($data)) return [];

    $results = [];

    foreach (($data['records'] ?? []) as $r) {
        $results[] = [
            "source" => [
                "name" => "IntelligenceX",
                "date" => $r['date'] ?? null,
                "type" => $r['type'] ?? "osint"
            ],
            "fields" => [
                $r['name'] ?? ($r['title'] ?? 'OSINT result')
            ]
        ];
    }

    return $results;
}