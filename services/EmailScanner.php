<?php

function scanEmail($email) {
    $domain = substr(strrchr($email, "@"), 1);

    $risk = 0;
    $warnings = [];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $risk += 40;
        $warnings[] = "Niepoprawny format e-maila";
    }

    if (in_array($domain, ["gmail.com", "wp.pl", "onet.pl"])) {
        $risk += 10;
        $warnings[] = "Popularna domena e-mail";
    }

    return [
        "email" => $email,
        "domain" => $domain,
        "risk" => $risk,
        "warnings" => $warnings
    ];
}