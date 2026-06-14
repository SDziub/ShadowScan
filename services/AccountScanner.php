<?php

require_once ROOT_PATH . "services/PlatformChecker.php";

function scanAccounts(string $email, string $username): array
{
    $emailName = explode('@', $email)[0];

    $platforms = [
        "GitHub"      => "https://github.com/%s",
        "YouTube"     => "https://www.youtube.com/@%s",
        "Twitch"      => "https://www.twitch.tv/%s",
        "X / Twitter" => "https://x.com/%s",
        "TikTok"      => "https://www.tiktok.com/@%s",
        "Spotify"     => "https://open.spotify.com/user/%s"
    ];

    $multi = curl_multi_init();
    $handles = [];

    foreach ($platforms as $platform => $pattern) {

        $usernameUrl = sprintf($pattern, rawurlencode($username));
        $emailUrl    = sprintf($pattern, rawurlencode($emailName));

        foreach ([
            'username' => $usernameUrl,
            'email'    => $emailUrl
        ] as $type => $url) {

            $ch = curl_init();

$options = [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 3,
    CURLOPT_CONNECTTIMEOUT => 1,
    CURLOPT_USERAGENT => 'Mozilla/5.0'
];

if ($platform !== 'Twitch') {
    $options[CURLOPT_NOBODY] = true;
}

curl_setopt_array($ch, $options);

            curl_multi_add_handle($multi, $ch);

            $handles[(int)$ch] = [
                'handle'   => $ch,
                'platform' => $platform,
                'type'     => $type,
                'url'      => $url
            ];
        }
    }

    $running = null;

    do {
        curl_multi_exec($multi, $running);
        curl_multi_select($multi, 1);
    } while ($running > 0);

    $responses = [];

    foreach ($handles as $data) {

$status = curl_getinfo($data['handle'], CURLINFO_HTTP_CODE);

$exists = ($status >= 200 && $status < 300);

if ($data['platform'] === 'Twitch') {

    $html = curl_multi_getcontent($data['handle']);

    if (
        str_contains($html, 'Sorry. Unless you\'ve got a time machine')
        || str_contains($html, 'This channel is unavailable')
    ) {
        $exists = false;
    }
}

$responses[$data['platform']][$data['type']] = [
    'status' => $status,
    'exists' => $exists
];
        curl_multi_remove_handle($multi, $data['handle']);
        curl_close($data['handle']);
    }

    curl_multi_close($multi);

    $results = [];

    foreach ($platforms as $platform => $pattern) {

        $usernameRes = $responses[$platform]['username'] ?? [
            'exists' => false,
            'status' => 0
        ];

        $emailRes = $responses[$platform]['email'] ?? [
            'exists' => false,
            'status' => 0
        ];

        $usernameUrl = sprintf($pattern, rawurlencode($username));
        $emailUrl    = sprintf($pattern, rawurlencode($emailName));

        if ($usernameRes['exists'] && $emailRes['exists']) {

            $results[] = [
                'platform'   => $platform,
                'exists'     => true,
                'confidence' => 100,
                'url'        => $usernameUrl,
                'foundAs'    => 'username+email',
                'status'     => 200
            ];

            continue;
        }

        if ($emailRes['exists']) {

            $results[] = [
                'platform'   => $platform,
                'exists'     => true,
                'confidence' => 90,
                'url'        => $emailUrl,
                'foundAs'    => $emailName,
                'status'     => $emailRes['status']
            ];

            continue;
        }

        if ($usernameRes['exists']) {

            $results[] = [
                'platform'   => $platform,
                'exists'     => true,
                'confidence' => 80,
                'url'        => $usernameUrl,
                'foundAs'    => $username,
                'status'     => $usernameRes['status']
            ];

            continue;
        }

        $results[] = [
            'platform'   => $platform,
            'exists'     => false,
            'confidence' => 0,
            'url'        => null,
            'foundAs'    => null,
            'status'     => max(
                $usernameRes['status'],
                $emailRes['status']
            )
        ];
    }

    return $results;
}