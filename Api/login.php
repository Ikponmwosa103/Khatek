
<?php

header("Content-Type: application/json");
session_start();

require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Method not allowed."
    ]);

    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$email = trim($data["email"] ?? "");
$password = $data["password"] ?? "";

if ($email === "" || $password === "") {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Please enter your email and password."
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


/*
|--------------------------------------------------------------------------
| Find account
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT id, full_name, email, phone, password_hash
     FROM users
     WHERE email = ?
     LIMIT 1"
);

$stmt->execute([$email]);

$user = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| No registered account
|--------------------------------------------------------------------------
*/

if (!$user) {

    http_response_code(404);

    echo json_encode([
        "success" => false,
        "error" => "account_not_found",
        "message" => "No registered account was found with this email."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Incorrect password
|--------------------------------------------------------------------------
*/

if (!password_verify($password, $user["password_hash"])) {

    http_response_code(401);

    echo json_encode([
        "success" => false,
        "error" => "incorrect_password",
        "message" => "Incorrect password. Please try again."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Successful login
|--------------------------------------------------------------------------
*/

session_regenerate_id(true);

$_SESSION["user_id"] = $user["id"];
$_SESSION["user_name"] = $user["full_name"];
$_SESSION["user_email"] = $user["email"];


/*
|--------------------------------------------------------------------------
| Return user
|--------------------------------------------------------------------------
*/

echo json_encode([
    "success" => true,
    "message" => "Login successful.",
    "user" => [
        "id" => $user["id"],
        "name" => $user["full_name"],
        "email" => $user["email"],
        "phone" => $user["phone"]
    ]
]);
