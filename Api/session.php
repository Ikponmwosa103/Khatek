<?php

declare(strict_types=1);

// Requests arrive through the Netlify same-origin /Api proxy.
// The browser therefore stores this secure, host-only session cookie for Netlify.
ini_set("session.use_only_cookies", "1");
ini_set("session.use_strict_mode", "1");

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        "lifetime" => 0,
        "path" => "/",
        "domain" => "",
        "secure" => true,
        "httponly" => true,
        "samesite" => "Lax",
    ]);
    session_start();
}
