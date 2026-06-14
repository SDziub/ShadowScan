<?php

function platformNeedsBody(string $platform): bool
{
    return in_array(
        $platform,
        ['YouTube', 'Reddit', 'Twitch'],
        true
    );
}

function buildPlatformCurlOptions(string $url, string $platform): array {
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 4,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        CURLOPT_ENCODING => ''
    ];
    if (!platformNeedsBody($platform)) {
        $options[CURLOPT_NOBODY] = true;
    }
    return $options;
}

function unknownPlatformResult(int $status = 0): array
{
    return [
        'exists' => null,
        'confidence' => 0,
        'status' => $status
    ];
}

function checkCompletedPlatformResponse(
    string $platform,
    string $username,
    string $html,
    int $status,
    string $error,
    string $finalUrl
): array {
    if ($error !== '' || $status === 0) {
        return unknownPlatformResult($status);
    }
    if ($status === 403 || $status === 429 || $status >= 500) {
        return unknownPlatformResult($status);
    }
    return match ($platform) {
        'GitHub' => interpretGitHubResponse($status),
        'YouTube' => interpretYouTubeResponse($html, $status),
        'Twitch' => interpretTwitchResponse(
            $html,
            $status,
            $username
        ),
        'X / Twitter',
        'TikTok',
        'Spotify' => interpretGenericResponse(
            $status,
            $finalUrl
        ),
        default => interpretGenericResponse(
            $status,
            $finalUrl
        )
    };
}

function interpretGitHubResponse(int $status): array
{
    if ($status === 200) {
        return [
            'exists' => true,
            'confidence' => 95,
            'status' => $status
        ];
    }

    if ($status === 404) {
        return [
            'exists' => false,
            'confidence' => 100,
            'status' => $status
        ];
    }
    return unknownPlatformResult($status);
}

function interpretYouTubeResponse(string $html, int $status): array {
    if ($status === 404) {
        return [
            'exists' => false,
            'confidence' => 100,
            'status' => $status
        ];
    }
    $notFoundSignals = [
        'This channel does not exist',
        '404 Not Found',
        "This page isn't available",
        'Nie znaleziono tego kanału'
    ];
    foreach ($notFoundSignals as $signal) {
        if (stripos($html, $signal) !== false) {
            return [
                'exists' => false,
                'confidence' => 95,
                'status' => $status
            ];
        }
    }
    $channelSignals = [
        '"channelId"',
        '"vanityChannelUrl"',
        '"canonicalBaseUrl"',
        'ytInitialData'
    ];
    foreach ($channelSignals as $signal) {
        if (stripos($html, $signal) !== false) {
            return [
                'exists' => true,
                'confidence' => 85,
                'status' => $status
            ];
        }
    }
    return unknownPlatformResult($status);
}

function interpretTwitchResponse(
    string $html,
    int $status,
    string $username
): array {
    if ($status === 404) {
        return [
            'exists' => false,
            'confidence' => 100,
            'status' => $status
        ];
    }
    $notFoundSignals = [
        "Sorry. Unless you've got a time machine",
        'This channel is unavailable',
        'Content is unavailable'
    ];
    foreach ($notFoundSignals as $signal) {
        if (stripos($html, $signal) !== false) {
            return [
                'exists' => false,
                'confidence' => 95,
                'status' => $status
            ];
        }
    }
    $containsUsername =
        $username !== '' &&
        stripos($html, $username) !== false;
    $containsProfileMetadata =
        stripos($html, 'og:title') !== false ||
        stripos($html, 'twitter:title') !== false;
    if (
        $status === 200 &&
        $containsUsername &&
        $containsProfileMetadata
    ) {
        return [
            'exists' => true,
            'confidence' => 80,
            'status' => $status
        ];
    }
    return unknownPlatformResult($status);
}

function interpretGenericResponse(int $status, string $finalUrl): array {
    if ($status === 404) {
        return [
            'exists' => false,
            'confidence' => 100,
            'status' => $status,
            'finalUrl' => $finalUrl
        ];
    }
    if ($status >= 200 && $status < 300) {
        return [
            'exists' => null,
            'confidence' => 30,
            'status' => $status,
            'finalUrl' => $finalUrl
        ];
    }
    return unknownPlatformResult($status);
}
