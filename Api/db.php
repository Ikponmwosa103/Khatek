<?php

declare(strict_types=1);

/*
 * Railway MySQL exposes MYSQLHOST, MYSQLPORT, MYSQLUSER, MYSQLPASSWORD, and
 * MYSQLDATABASE. Prefer those service-linked variables. Complete MySQL URLs
 * are supported as a fallback for setups that expose only a URL.
 */
$mysqlHost = trim((string) (getenv("MYSQLHOST") ?: ""));

try {
    if ($mysqlHost !== "") {
        $host = $mysqlHost;
        $port = (int) (getenv("MYSQLPORT") ?: 3306);
        $database = (string) (getenv("MYSQLDATABASE") ?: "");
        $username = (string) (getenv("MYSQLUSER") ?: "");
        $password = (string) (getenv("MYSQLPASSWORD") ?: "");

        if ($database === "" || $username === "") {
            throw new RuntimeException("Railway MySQL variables are incomplete.");
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
    } else {
        $connectionUrl = trim((string) (
            getenv("MYSQL_URL")
            ?: getenv("MYSQL_PUBLIC_URL")
            ?: getenv("DATABASE_URL")
            ?: ""
        ));

        if ($connectionUrl === "") {
            throw new RuntimeException("Railway MySQL variables are missing.");
        }

        $parts = parse_url($connectionUrl);

        if ($parts === false || empty($parts["scheme"]) || empty($parts["host"])) {
            throw new RuntimeException("Invalid database connection URL.");
        }

        $scheme = strtolower((string) $parts["scheme"]);
        if ($scheme !== "mysql") {
            throw new RuntimeException("A Railway MySQL connection is required.");
        }

        $host = (string) $parts["host"];
        $port = (int) ($parts["port"] ?? 3306);
        $database = ltrim((string) ($parts["path"] ?? ""), "/");
        $username = rawurldecode((string) ($parts["user"] ?? ""));
        $password = rawurldecode((string) ($parts["pass"] ?? ""));

        if ($database === "" || $username === "") {
            throw new RuntimeException("Database URL is missing required fields.");
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
    }

    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed."
    ]);
    exit;
}