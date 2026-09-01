<?php

declare(strict_types=1);

/*
 * Render PostgreSQL exposes DATABASE_URL. The fallback variables keep the
 * application usable with either Render component variables or a local
 * MySQL/PostgreSQL installation.
 */
$databaseUrl = trim((string) (getenv("DATABASE_URL") ?: ""));
$dsn = "";
$username = "";
$password = "";

try {
    if ($databaseUrl !== "") {
        $parts = parse_url($databaseUrl);

        if ($parts === false || empty($parts["scheme"]) || empty($parts["host"])) {
            throw new RuntimeException("Invalid DATABASE_URL.");
        }

        $scheme = strtolower((string) $parts["scheme"]);
        $host = (string) $parts["host"];
        $port = (int) ($parts["port"] ?? ($scheme === "mysql" ? 3306 : 5432));
        $database = ltrim((string) ($parts["path"] ?? ""), "/");
        $username = rawurldecode((string) ($parts["user"] ?? ""));
        $password = rawurldecode((string) ($parts["pass"] ?? ""));

        if ($database === "" || $username === "") {
            throw new RuntimeException("DATABASE_URL is missing required fields.");
        }

        if ($scheme === "postgres" || $scheme === "postgresql") {
            $query = [];
            parse_str((string) ($parts["query"] ?? ""), $query);
            $query["sslmode"] ??= "require";
            $queryString = http_build_query($query, "", ";");
            $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
            if ($queryString !== "") {
                $dsn .= ";" . $queryString;
            }
        } elseif ($scheme === "mysql") {
            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
        } else {
            throw new RuntimeException("Unsupported database scheme.");
        }
    } else {
        $mysqlHost = trim((string) (getenv("MYSQLHOST") ?: ""));
        $useMysql = $mysqlHost !== "" || getenv("MYSQLDATABASE") !== false;

        if ($useMysql) {
            $host = $mysqlHost ?: "127.0.0.1";
            $port = (int) (getenv("MYSQLPORT") ?: 3306);
            $database = (string) (getenv("MYSQLDATABASE") ?: "khatek_digital");
            $username = (string) (getenv("MYSQLUSER") ?: "root");
            $password = (string) (getenv("MYSQLPASSWORD") ?: "");
            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
        } else {
            $host = (string) (getenv("DB_HOST") ?: "127.0.0.1");
            $port = (int) (getenv("DB_PORT") ?: 5432);
            $database = (string) (getenv("DB_NAME") ?: "khatek_digital");
            $username = (string) (getenv("DB_USER") ?: "postgres");
            $password = (string) (getenv("DB_PASSWORD") ?: "");
            $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
        }
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