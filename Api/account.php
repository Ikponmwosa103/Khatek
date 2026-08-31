<?php

session_start();

header("Content-Type: application/json; charset=UTF-8");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* =========================
   DATABASE
========================= */

$host = getenv("DB_HOST") ?: "localhost";
$username = getenv("DB_USER") ?: "root";
$password = getenv("DB_PASSWORD") ?: "";
$database = getenv("DB_NAME") ?: "khatek_digital";
$port = (int) (getenv("DB_PORT") ?: 3306);

try {

    $conn = new mysqli(
        $host,
        $username,
        $password,
        $database,
        $port
    );

    $conn->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Database connection failed."
    ]);

    exit;
}


/* =========================
   CHECK LOGIN
========================= */

if (!isset($_SESSION["user_id"])) {

    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "You are not logged in."
    ]);

    exit;
}


$userId = (int) $_SESSION["user_id"];


/* =========================
   GET ACCOUNT
========================= */

if ($_SERVER["REQUEST_METHOD"] === "GET") {

    try {

        $stmt = $conn->prepare("
            SELECT
                id,
                full_name,
                email,
                phone,
                created_at,
                updated_at
            FROM users
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $userId);

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 0) {

            http_response_code(404);

            echo json_encode([
                "success" => false,
                "message" => "Account not found."
            ]);

            exit;
        }

        $user = $result->fetch_assoc();

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

        $stmt->close();

    } catch (mysqli_sql_exception $e) {

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" => "Unable to load your account."
        ]);
    }

    exit;
}


/* =========================
   DELETE ACCOUNT
========================= */

if ($_SERVER["REQUEST_METHOD"] === "DELETE") {

    try {

        $stmt = $conn->prepare("
            DELETE FROM users
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $userId);

        $stmt->execute();

        if ($stmt->affected_rows === 0) {

            http_response_code(404);

            echo json_encode([
                "success" => false,
                "message" => "Account not found."
            ]);

            exit;
        }

        /* Destroy login session */

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

        $stmt->close();

    } catch (mysqli_sql_exception $e) {

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" => "Unable to delete account."
        ]);
    }

    exit;
}


/* =========================
   METHOD NOT ALLOWED
========================= */

http_response_code(405);

echo json_encode([
    "success" => false, 
    "message" => "Method not allowed."
]);

$conn->close();