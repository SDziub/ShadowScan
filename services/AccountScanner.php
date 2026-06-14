<?php

require_once ROOT_PATH . 'services/PlatformChecker.php';

function scanAccounts(
    string $email,
    string $username
): array {
    $emailName = explode('@', $email)[0] ?? '';

    /*
     * Sprawdzamy tylko dwie sensowne wartości:
     * - dokładnie podany nick,
     * - część adresu e-mail przed znakiem @.
     */
    $candidates = array_values(
        array_unique(
            array_filter(
                [
                    trim($username),
                    trim($emailName)
                ],
                static fn(string $value): bool => $value !== ''
            )
        )
    );

    $platforms = [
        'GitHub' => 'https://github.com/%s',
        'YouTube' => 'https://www.youtube.com/@%s',
        'Reddit' => 'https://www.reddit.com/user/%s/',
        'Twitch' => 'https://www.twitch.tv/%s',
        'X / Twitter' => 'https://x.com/%s',
        'TikTok' => 'https://www.tiktok.com/@%s',
        'Spotify' => 'https://open.spotify.com/user/%s'
    ];

    $multiHandle = curl_multi_init();
    $requests = [];

    foreach ($platforms as $platform => $pattern) {
        foreach ($candidates as $candidate) {
            $url = sprintf(
                $pattern,
                rawurlencode($candidate)
            );

            $handle = curl_init();

            curl_setopt_array(
                $handle,
                buildPlatformCurlOptions($url, $platform)
            );

            curl_multi_add_handle($multiHandle, $handle);

            $handleId = is_object($handle)
                ? spl_object_id($handle)
                : (int) $handle;

            $requests[$handleId] = [
                'handle' => $handle,
                'platform' => $platform,
                'candidate' => $candidate,
                'url' => $url
            ];
        }
    }

    $running = null;

    do {
        $multiStatus = curl_multi_exec(
            $multiHandle,
            $running
        );

        if ($running > 0) {
            $selected = curl_multi_select(
                $multiHandle,
                0.5
            );

            /*
             * curl_multi_select może czasem zwrócić -1.
             * Krótkie uśpienie zapobiega wtedy pętli obciążającej CPU.
             */
            if ($selected === -1) {
                usleep(10000);
            }
        }
    } while (
        $running > 0 &&
        $multiStatus === CURLM_OK
    );

    $platformChecks = [];

    foreach ($requests as $request) {
        $handle = $request['handle'];

        $html = curl_multi_getcontent($handle);

        $status = (int) curl_getinfo(
            $handle,
            CURLINFO_HTTP_CODE
        );

        $finalUrl = (string) curl_getinfo(
            $handle,
            CURLINFO_EFFECTIVE_URL
        );

        $error = curl_error($handle);

        $check = checkCompletedPlatformResponse(
            $request['platform'],
            $request['candidate'],
            is_string($html) ? $html : '',
            $status,
            $error,
            $finalUrl
        );

        $platformChecks[$request['platform']][] = [
            'platform' => $request['platform'],
            'exists' => $check['exists'] ?? null,
            'confidence' => $check['confidence'] ?? 0,
            'status' => $check['status'] ?? $status,
            'url' => $request['url'],
            'foundAs' => $request['candidate']
        ];

        curl_multi_remove_handle(
            $multiHandle,
            $handle
        );

        curl_close($handle);
    }

    curl_multi_close($multiHandle);

    $results = [];

    foreach ($platforms as $platform => $pattern) {
        $checks = $platformChecks[$platform] ?? [];

        $confirmedResults = array_values(
            array_filter(
                $checks,
                static function (array $result): bool {
                    return
                        ($result['exists'] ?? null) === true &&
                        ($result['confidence'] ?? 0) >= 80;
                }
            )
        );

        if (!empty($confirmedResults)) {
            usort(
                $confirmedResults,
                static function (
                    array $first,
                    array $second
                ): int {
                    return
                        ($second['confidence'] ?? 0)
                        <=>
                        ($first['confidence'] ?? 0);
                }
            );

            $results[] = $confirmedResults[0];
            continue;
        }

        $unknownResults = array_values(
            array_filter(
                $checks,
                static function (array $result): bool {
                    return
                        ($result['exists'] ?? null) === null;
                }
            )
        );

        if (!empty($unknownResults)) {
            $unknown = $unknownResults[0];

            /*
             * Przy wyniku niejednoznacznym nie pokazujemy linku,
             * ponieważ mógłby prowadzić do strony błędu.
             */
            $unknown['url'] = null;
            $unknown['foundAs'] = null;

            $results[] = $unknown;
            continue;
        }

        $notFoundResults = array_values(
            array_filter(
                $checks,
                static function (array $result): bool {
                    return
                        ($result['exists'] ?? null) === false;
                }
            )
        );

        if (!empty($notFoundResults)) {
            $notFound = $notFoundResults[0];
            $notFound['url'] = null;
            $notFound['foundAs'] = null;

            $results[] = $notFound;
            continue;
        }

        $results[] = [
            'platform' => $platform,
            'exists' => null,
            'confidence' => 0,
            'status' => null,
            'url' => null,
            'foundAs' => null
        ];
    }

    return $results;
}
