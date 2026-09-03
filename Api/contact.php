<?php

declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

use PHPMailer\PHPMailer\PHPMailer;

$autoloadCandidates = [
    dirname(__DIR__) . "/vendor/autoload.php",
];

// DOCUMENT_ROOT can differ from the project directory on shared hosting.
if (!empty($_SERVER["DOCUMENT_ROOT"])) {
    $autoloadCandidates[] = rtrim(
        (string)$_SERVER["DOCUMENT_ROOT"],
        "/\\"
    ) . "/vendor/autoload.php";
}

$autoloadPath = null;
foreach ($autoloadCandidates as $candidate) {
    if (is_file($candidate) && is_readable($candidate)) {
        $autoloadPath = $candidate;
        break;
    }
}

if ($autoloadPath === null) {
    http_response_code(500);

    error_log(
        "Khatek contact form error: Composer autoloader not found. " .
        "Run 'composer install --no-dev --optimize-autoloader' " .
        "in the project root before serving the application."
    );

    echo json_encode([
        "success" => false,
        "message" => "The mail service is temporarily unavailable."
    ]);

    exit;
}

require $autoloadPath;

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

$host = trim((string)(getenv("MAILTRAP_HOST") ?: ""));
$port = (int)(getenv("MAILTRAP_PORT") ?: 2525);
$username = trim((string)(getenv("MAILTRAP_USERNAME") ?: ""));
$password = getenv("MAILTRAP_PASSWORD") ?: "";
$encryption = strtolower(trim(getenv("MAILTRAP_ENCRYPTION") ?: "tls"));

$fromEmail = trim((string)(getenv("MAILTRAP_FROM_EMAIL") ?: ""));
$toEmail = trim((string)(getenv("MAILTRAP_TO_EMAIL") ?: ""));


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


$mail = null;

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
    $mail->Timeout = 15;

    if ($encryption === "tls") {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    } elseif ($encryption === "ssl") {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($encryption !== "none") {
        throw new RuntimeException(
            "MAILTRAP_ENCRYPTION must be tls, ssl, or none."
        );
    }


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


} catch (\Throwable $error) {

    $mailError = $mail instanceof PHPMailer ? $mail->ErrorInfo : "";

    error_log(
        "Khatek contact form error: " .
        $error->getMessage() .
        " [SMTP host={$host}; port={$port}; encryption={$encryption}; " .
        "mail_error={$mailError}]"
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to send your message right now. Please try again."
    ]);
}