<?php

header("Content-Type: application/json");

require_once __DIR__ . "/session.php";

require_once __DIR__ . "/db.php";


/*
|--------------------------------------------------------------------------
| Only allow POST requests
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Method not allowed."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Get JSON data from JavaScript
|--------------------------------------------------------------------------
*/

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!is_array($data)) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request data."
    ]);

    exit;
}

$name = trim($data["name"] ?? $data["full_name"] ?? "");
$email = trim($data["email"] ?? "");
$phone = trim($data["phone"] ?? "");
$password = $data["password"] ?? "";

if ($name === "" || $email === "" || $phone === "" || $password === "") {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Please complete all fields."
    ]);

    exit;
}

if (strlen($name) < 2) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Please enter your full name."
    ]);

    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Please enter a valid email address."
    ]);

    exit;
}

if (strlen($phone) < 7) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Please enter a valid phone number."
    ]);

    exit;
}

if (strlen($password) < 8) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Password must be at least 8 characters."
    ]);

    exit;
}

try {
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        "INSERT INTO users (full_name, email, phone, password_hash)
         VALUES (?, ?, ?, ?)
         RETURNING id"
    );

    $stmt->execute([
        $name,
        $email,
        $phone,
        $passwordHash
    ]);

    $userId = (int) $stmt->fetchColumn();

    http_response_code(201);

    echo json_encode([
        "success" => true,
        "message" => "Registered successfully.",
        "user" => [
            "id" => $userId,
            "name" => $name,
            "email" => $email,
            "phone" => $phone
        ]
    ]);

    exit;
} catch (PDOException $error) {
    // PostgreSQL unique_violation: avoid exposing database details.
    if ($error->getCode() === "23505") {
        http_response_code(409);

        echo json_encode([
            "success" => false,
            "error" => "email_exists",
            "message" => "An account with this email already exists."
        ]);

        exit;
    }

    error_log("Registration database error: " . $error->getCode());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to create your account. Please try again."
    ]);

    exit;
}
