<?php

header("Content-Type: application/json");

session_start();

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


/*
|--------------------------------------------------------------------------
| Check JSON
|--------------------------------------------------------------------------
*/

if (!is_array($data)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request data."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Get form values
|--------------------------------------------------------------------------
*/

$name = trim($data["name"] ?? $data["full_name"] ?? "");
$email = trim($data["email"] ?? "");
$phone = trim($data["phone"] ?? "");
$password = $data["password"] ?? "";


/*
|--------------------------------------------------------------------------
| Required fields
|--------------------------------------------------------------------------
*/

if (
    $name === "" ||
    $email === "" ||
    $phone === "" ||
    $password === ""
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Please complete all fields."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Validate name
|--------------------------------------------------------------------------
*/

if (strlen($name) < 2) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Please enter your full name."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Validate email
|--------------------------------------------------------------------------
*/

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
| Validate phone
|--------------------------------------------------------------------------
*/

if (strlen($phone) < 7) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Please enter a valid phone number."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Validate password
|--------------------------------------------------------------------------
*/

if (strlen($password) < 8) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Password must be at least 8 characters."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Check if email already exists
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare(
        "SELECT id
         FROM users
         WHERE email = ?
         LIMIT 1"
    );

    $stmt->execute([$email]);

    $existingUser = $stmt->fetch();


    if ($existingUser) {

        http_response_code(409);

        echo json_encode([
            "success" => false,
            "error" => "email_exists",
            "message" => "An account with this email already exists."
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Hash password
    |--------------------------------------------------------------------------
    */

    $passwordHash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );


    /*
    |--------------------------------------------------------------------------
    | Insert new account
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare(
        "INSERT INTO users
        (full_name, email, phone, password_hash)
        VALUES (?, ?, ?, ?)"
    );

    $stmt->execute([
        $name,
        $email,
        $phone,
        $passwordHash
    ]);


    /*
    |--------------------------------------------------------------------------
    | Get newly created user ID
    |--------------------------------------------------------------------------
    */

    $userId = $pdo->lastInsertId();


    /*
    |--------------------------------------------------------------------------
    | Registration successful
    |--------------------------------------------------------------------------
    */

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


} catch (PDOException $e) {

    /*
    |--------------------------------------------------------------------------
    | Database error
    |--------------------------------------------------------------------------
    */

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to create your account. Please try again."
    ]);

    exit;
}
