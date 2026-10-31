<?php

declare(strict_types=1);

$caFile = null;

try {
    /*
     * 1. AIVEN MYSQL — PRIMARY DATABASE
     */
    $aivenHost = trim((string) (getenv("AIVEN_HOST") ?: ""));

    if ($aivenHost !== "") {
        $host = $aivenHost;
        $port = (int) (getenv("AIVEN_PORT") ?: 13254);
        $database = trim((string) (getenv("AIVEN_DATABASE") ?: ""));
        $username = (string) (getenv("AIVEN_USER") ?: "");
        $password = (string) (getenv("AIVEN_PASSWORD") ?: "");
        $caCertificate = trim((string) (getenv("AIVEN_CA_CERT") ?: ""));

        if (
            $database === "" ||
            $username === "" ||
            $password === "" ||
            $caCertificate === ""
        ) {
            throw new RuntimeException("Aiven database configuration is incomplete.");
        }

        // Support certificates stored with escaped newline characters.
        $caCertificate = str_replace(
            ["\\r\\n", "\\n", "\\r"],
            ["\n", "\n", "\r"],
            $caCertificate
        );

        if (strpos($caCertificate, "-----BEGIN CERTIFICATE-----") === false) {
            throw new RuntimeException("Invalid Aiven CA certificate.");
        }

        $caFile = tempnam(sys_get_temp_dir(), "aiven-ca-");

        if ($caFile === false || file_put_contents($caFile, $caCertificate . "\n") === false) {
            throw new RuntimeException("Unable to prepare Aiven CA certificate.");
        }

        @chmod($caFile, 0600);

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_SSL_CA => $caFile,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
        ];

    } else {
        /*
         * 2. LEGACY MYSQL ENVIRONMENT VARIABLES — FALLBACK
         */
        $mysqlHost = trim((string) (
            getenv("MYSQLHOST")
            ?: getenv("DB_HOST")
            ?: ""
        ));

        if ($mysqlHost !== "") {
            $host = $mysqlHost;
            $port = (int) (getenv("MYSQLPORT") ?: getenv("DB_PORT") ?: 3306);
            $database = (string) (getenv("MYSQLDATABASE") ?: getenv("DB_NAME") ?: "");
            $username = (string) (getenv("MYSQLUSER") ?: getenv("DB_USER") ?: "");
            $password = (string) (getenv("MYSQLPASSWORD") ?: getenv("DB_PASSWORD") ?: "");

            if ($database === "" || $username === "") {
                throw new RuntimeException("MySQL environment variables are incomplete.");
            }

        } else {
            /*
             * 3. DATABASE URL — FINAL FALLBACK
             */
            $connectionUrl = trim((string) (
                getenv("MYSQL_URL")
                ?: getenv("MYSQL_PUBLIC_URL")
                ?: getenv("DATABASE_URL")
                ?: ""
            ));

            if ($connectionUrl === "") {
                throw new RuntimeException("Database environment variables are missing.");
            }

            $parts = parse_url($connectionUrl);

            if ($parts === false || empty($parts["scheme"]) || empty($parts["host"])) {
                throw new RuntimeException("Invalid database connection URL.");
            }

            if (strtolower((string) $parts["scheme"]) !== "mysql") {
                throw new RuntimeException("A MySQL connection is required.");
            }

            $host = (string) $parts["host"];
            $port = (int) ($parts["port"] ?? 3306);
            $database = ltrim((string) ($parts["path"] ?? ""), "/");
            $username = rawurldecode((string) ($parts["user"] ?? ""));
            $password = rawurldecode((string) ($parts["pass"] ?? ""));

            if ($database === "" || $username === "") {
                throw new RuntimeException("Database URL is missing required fields.");
            }
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
    }

    $pdo = new PDO($dsn, $username, $password, $options);

} catch (Throwable $error) {
    error_log("Database connection failed: " . get_class($error));

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Database connection failed."
    ]);

    exit;

} finally {
    if ($caFile !== null && is_file($caFile)) {
        @unlink($caFile);
    }
}
