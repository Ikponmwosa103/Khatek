<?php

declare(strict_types=1);

// The browser reaches Render through Netlify's same-origin /Api proxy.
// Always issue secure, HTTP-only, same-site session cookies.
ini_set("session.use_only_cookies", "1");
ini_set("session.use_strict_mode", "1");

session_set_cookie_params([
    "lifetime" => 0,
    "path" => "/",
    "domain" => "",
    "secure" => true,
    "httponly" => true,
    "samesite" => "Lax",
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
