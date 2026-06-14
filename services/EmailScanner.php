<?php

require_once ROOT_PATH . "services/BreachScanner.php";

function scanEmail(string $email): array
{
    $domain = substr(strrchr($email, "@"), 1);
    $breaches = getBreaches($email);
    $risk = 0;

    if (!empty($breaches)) {
        $risk += min(count($breaches) * 15, 60);
    }
    return [
        "email" => $email,
        "domain" => $domain,
        "breaches" => $breaches,
        "risk" => $risk
    ];
}