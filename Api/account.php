<?php

declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");
session_start();
require_once __DIR__ . "/db.php";

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "You are not logged in."
    ]);
    exit;
}

$userId = (int) $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    try {
        $stmt = $pdo->prepare(
            "SELECT id, full_name, email, phone, created_at, updated_at
             FROM users
             WHERE id = ?
             LIMIT 1"
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "message" => "Account not found."
            ]);
            exit;
        }

        echo json_encode([
            "success" => true,
            "user" => [
                "id" => (int) $user["id"],
                "full_name" => $user["full_name"],
                "email" => $user["email"],
                "phone" => $user["phone"],
                "created_at" => $user["created_at"],
                "updated_at" => $user["updated_at"]
            ]
        ]);
        exit;
    } catch (Throwable $error) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Unable to load your account."
        ]);
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "DELETE") {
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "message" => "Account not found."
            ]);
            exit;
        }

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

        echo json_encode([
            "success" => true,
            "message" => "Account deleted successfully."
        ]);
        exit;
    } catch (Throwable $error) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Unable to delete account."
        ]);
        exit;
    }
}

http_response_code(405);
echo json_encode([
    "success" => false,
    "message" => "Method not allowed."
]);