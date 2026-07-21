<?php
// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

function getDatabaseConnection() {
    $envPath = null;
    // Check possible locations for .env file
    $possiblePaths = [
        __DIR__ . '/../../.env', // One level above public_html (recommended)
        __DIR__ . '/../.env',    // Inside public_html/
        __DIR__ . '/.env'        // Inside api/ folder
    ];

    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $envPath = $path;
            break;
        }
    }

    $dbUrl = null;

    if ($envPath) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) continue;
            
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $name = trim($parts[0]);
                $value = trim($parts[1], " \t\n\r\0\x0B\"'");
                
                if ($name === 'DATABASE_URL') {
                    $dbUrl = $value;
                    break;
                }
            }
        }
    }

    // Fallback default url if no .env is found
    if (!$dbUrl) {
        $dbUrl = "mysql://u889453186_pra:DildilPakistan786_786@195.35.59.84:3306/u889453186_pra";
    }

    // Parse the connection URL: mysql://username:password@host:port/database
    $parsed = parse_url($dbUrl);
    if (!$parsed || !isset($parsed['scheme']) || $parsed['scheme'] !== 'mysql') {
        throw new Exception("Invalid DATABASE_URL config");
    }

    $host = $parsed['host'] ?? 'localhost';
    $port = $parsed['port'] ?? 3306;
    $user = $parsed['user'] ?? 'root';
    $pass = $parsed['pass'] ?? '';
    $dbname = ltrim($parsed['path'] ?? '', '/');

    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database connection failed: " . $e->getMessage()]);
        exit;
    }
}
?>
