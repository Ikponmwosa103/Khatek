<?php

declare(strict_types=1);

session_start();

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}
session_destroy();

if ($_SERVER["REQUEST_METHOD"] === "POST" || $_SERVER["REQUEST_METHOD"] === "DELETE") {
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode([
        "success" => true,
        "message" => "Logged out successfully."
    ]);
    exit;
}

header("Location: ../auth.html");
exit;