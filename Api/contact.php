<?php

declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require dirname(__DIR__) . "/vendor/autoload.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Method not allowed."
    ]);

    exit;
}

$name = trim((string)($_POST["name"] ?? ""));
$email = trim((string)($_POST["email"] ?? ""));
$message = trim((string)($_POST["message"] ?? ""));

if ($name === "" || $email === "" || $message === "") {
    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "Please fill in all fields."
    ]);

    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "Please enter a valid email address."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| MAILTRAP SETTINGS
|--------------------------------------------------------------------------
| These values come from Railway Variables.
*/

$host = getenv("MAILTRAP_HOST") ?: "";
$port = (int)(getenv("MAILTRAP_PORT") ?: 2525);
$username = getenv("MAILTRAP_USERNAME") ?: "";
$password = getenv("MAILTRAP_PASSWORD") ?: "";

$fromEmail = getenv("MAILTRAP_FROM_EMAIL") ?: "";
$toEmail = getenv("MAILTRAP_TO_EMAIL") ?: "";


/*
|--------------------------------------------------------------------------
| Check Mailtrap configuration
|--------------------------------------------------------------------------
*/

if (
    $host === "" ||
    $username === "" ||
    $password === "" ||
    $fromEmail === "" ||
    $toEmail === ""
) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Mail service is not configured."
    ]);

    exit;
}


try {

    $mail = new PHPMailer(true);

    /*
    |--------------------------------------------------------------------------
    | SMTP
    |--------------------------------------------------------------------------
    */

    $mail->isSMTP();

    $mail->Host = $host;
    $mail->SMTPAuth = true;

    $mail->Username = $username;
    $mail->Password = $password;

    $mail->Port = $port;


    /*
    |--------------------------------------------------------------------------
    | Sender
    |--------------------------------------------------------------------------
    */

    $mail->setFrom(
        $fromEmail,
        "Khatek Digital Tech Nig."
    );


    /*
    |--------------------------------------------------------------------------
    | Your Mailtrap inbox
    |--------------------------------------------------------------------------
    */

    $mail->addAddress(
        $toEmail,
        "Khatek Digital Tech Nig."
    );


    /*
    |--------------------------------------------------------------------------
    | Visitor's email
    |--------------------------------------------------------------------------
    |
    | When you click REPLY to the message,
    | your reply will go to the visitor.
    |
    */

    $mail->addReplyTo(
        $email,
        $name
    );


    /*
    |--------------------------------------------------------------------------
    | Email content
    |--------------------------------------------------------------------------
    */

    $mail->isHTML(true);

    $mail->Subject =
        "Inquiry from " . $name . " - Khatek Digital Tech Nig.";


    $safeName = htmlspecialchars(
        $name,
        ENT_QUOTES,
        "UTF-8"
    );

    $safeEmail = htmlspecialchars(
        $email,
        ENT_QUOTES,
        "UTF-8"
    );

    $safeMessage = nl2br(
        htmlspecialchars(
            $message,
            ENT_QUOTES,
            "UTF-8"
        )
    );


    $mail->Body = "
        <div style=\"font-family: Arial, sans-serif; line-height: 1.6;\">

            <h2>New Contact Form Message</h2>

            <p>
                <strong>Name:</strong><br>
                {$safeName}
            </p>

            <p>
                <strong>Email:</strong><br>
                {$safeEmail}
            </p>

            <p>
                <strong>Message:</strong><br>
                {$safeMessage}
            </p>

            <hr>

            <p>
                <small>
                    Sent from the Khatek Digital Tech Nig. website.
                </small>
            </p>

        </div>
    ";


    $mail->AltBody =
        "New Contact Form Message\n\n" .
        "Name: {$name}\n" .
        "Email: {$email}\n\n" .
        "Message:\n{$message}";


    /*
    |--------------------------------------------------------------------------
    | Send
    |--------------------------------------------------------------------------
    */

    $mail->send();


    echo json_encode([
        "success" => true,
        "message" => "Your message has been sent successfully."
    ]);


} catch (Exception $error) {

    error_log(
        "Khatek contact form error: " .
        $error->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to send your message right now. Please try again."
    ]);
}