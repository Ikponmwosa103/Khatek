<?php

declare(strict_types=1);

try {
    $connectionUrl = trim((string) (getenv("DATABASE_URL") ?: ""));

    if ($connectionUrl === "") {
        throw new RuntimeException("DATABASE_URL is not configured.");
    }

    $parts = parse_url($connectionUrl);

    if (
        $parts === false ||
        !isset($parts["scheme"], $parts["host"], $parts["user"], $parts["path"]) ||
        !in_array(strtolower((string) $parts["scheme"]), ["postgres", "postgresql"], true)
    ) {
        throw new RuntimeException("DATABASE_URL must be a valid PostgreSQL connection URL.");
    }

    $host = (string) $parts["host"];
    $port = (int) ($parts["port"] ?? 5432);
    $database = rawurldecode(ltrim((string) $parts["path"], "/"));
    $username = rawurldecode((string) $parts["user"]);
    $password = rawurldecode((string) ($parts["pass"] ?? ""));

    if ($host === "" || $database === "" || $username === "") {
        throw new RuntimeException("DATABASE_URL is missing required connection details.");
    }

    // Neon connections must use TLS. Keep sslmode=require even if the URL
    // omits the query parameter.
    $dsn = sprintf(
        "pgsql:host=%s;port=%d;dbname=%s;sslmode=require",
        $host,
        $port,
        $database
    );

    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Throwable $error) {
    error_log("Database connection failed: " . get_class($error));

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Database connection failed."
    ]);

    exit;
}
