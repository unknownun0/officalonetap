<?php
/**
 * Database configuration and connection
 * Vercel-compatible with environment variables and database-backed sessions
 */

// Load environment variables
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
}

// Database Configuration
// Supports both individual vars and DATABASE_URL (PlanetScale, Neon, etc.)
if (getenv('DATABASE_URL')) {
    $dbUrl = parse_url(getenv('DATABASE_URL'));
    define('DB_HOST', $dbUrl['host'] ?? 'localhost');
    define('DB_NAME', ltrim($dbUrl['path'] ?? '', '/'));
    define('DB_USER', $dbUrl['user'] ?? 'root');
    define('DB_PASS', $dbUrl['pass'] ?? '');
    define('DB_SSL', true);
} else {
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_NAME', getenv('DB_NAME') ?: 'nfc_app');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') ?: '');
    define('DB_SSL', filter_var(getenv('DB_SSL'), FILTER_VALIDATE_BOOLEAN) ?: false);
}

// Application Settings
define('APP_URL', getenv('APP_URL') ?: (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
define('ADMIN_EMAIL', getenv('ADMIN_EMAIL') ?: 'admin@example.com');

// Session Configuration for Serverless (Vercel)
// Use database-backed sessions since filesystem is ephemeral
define('SESSION_TABLE', 'sessions');
define('SESSION_LIFETIME', (int)(getenv('SESSION_LIFETIME') ?: 7200)); // 2 hours

// Security settings for production
$secureCookie = filter_var(getenv('SESSION_SECURE_COOKIE'), FILTER_VALIDATE_BOOLEAN) ?: (isset($_SERVER['HTTPS']));
$httpOnly = filter_var(getenv('SESSION_HTTP_ONLY'), FILTER_VALIDATE_BOOLEAN) ?: true;
$sameSite = getenv('SESSION_SAME_SITE') ?: 'Lax';

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', $httpOnly ? '1' : '0');
ini_set('session.cookie_secure', $secureCookie ? '1' : '0');
ini_set('session.cookie_samesite', $sameSite);
ini_set('session.gc_maxlifetime', (string)SESSION_LIFETIME);

// Custom Session Handler (Database-backed)
class DatabaseSessionHandler implements SessionHandlerInterface {
    private PDO $db;
    private int $lifetime;

    public function __construct(PDO $db, int $lifetime = 7200) {
        $this->db = $db;
        $this->lifetime = $lifetime;
        $this->initTable();
    }

    private function initTable(): void {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS " . SESSION_TABLE . " (
                id VARCHAR(128) NOT NULL PRIMARY KEY,
                data TEXT NOT NULL,
                expires INT UNSIGNED NOT NULL,
                INDEX idx_expires (expires)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function open($savePath, $sessionName): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read($id): string|false {
        $stmt = $this->db->prepare("SELECT data FROM " . SESSION_TABLE . " WHERE id = ? AND expires > ?");
        $stmt->execute([$id, time()]);
        $row = $stmt->fetch();
        return $row ? $row['data'] : '';
    }

    public function write($id, $data): bool {
        $expires = time() + $this->lifetime;
        $stmt = $this->db->prepare("
            INSERT INTO " . SESSION_TABLE . " (id, data, expires) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE data = VALUES(data), expires = VALUES(expires)
        ");
        return $stmt->execute([$id, $data, $expires]);
    }

    public function destroy($id): bool {
        $stmt = $this->db->prepare("DELETE FROM " . SESSION_TABLE . " WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function gc($maxLifetime): int|false {
        $stmt = $this->db->prepare("DELETE FROM " . SESSION_TABLE . " WHERE expires < ?");
        $stmt->execute([time()]);
        return $stmt->rowCount();
    }
}

// Initialize database-backed session handler
$db = getDb();
$handler = new DatabaseSessionHandler($db, SESSION_LIFETIME);
session_set_save_handler($handler, true);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get PDO database connection with SSL support
 * @return PDO
 * @throws Exception
 */
function getDb(): PDO {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ];
            
            // Add SSL options for managed databases (PlanetScale, Neon, etc.)
            if (DB_SSL) {
                $options[PDO::MYSQL_ATTR_SSL_CA] = __DIR__ . '/certs/ca.pem';
                // For PlanetScale, you may need to download their CA cert
                // Or use MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false for testing
            }
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed. Please check your configuration.");
        }
    }
    
    return $pdo;
}

/**
 * Sanitize output for HTML
 * @param string $string
 * @return string
 */
function e(string $string): string {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a secure random activation code
 * @return string
 */
function generateActivationCode(): string {
    return bin2hex(random_bytes(16));
}

/**
 * Send email - configure with SMTP service in production (SendGrid, Mailgun, etc.)
 * @param string $to
 * @param string $subject
 * @param string $body
 * @param array $headers
 * @return bool
 */
function sendEmail(string $to, string $subject, string $body, array $headers = []): bool {
    // In production, use a proper SMTP service
    // This is a placeholder - integrate with SendGrid, Mailgun, etc.
    $defaultHeaders = [
        'From' => ADMIN_EMAIL,
        'Reply-To' => ADMIN_EMAIL,
        'Content-Type' => 'text/html; charset=UTF-8',
        'X-Mailer' => 'PHP/' . phpversion()
    ];
    
    $allHeaders = array_merge($defaultHeaders, $headers);
    $headerString = '';
    foreach ($allHeaders as $key => $value) {
        $headerString .= "$key: $value\r\n";
    }
    
    // Log email in development
    if ((getenv('APP_ENV') ?: 'development') !== 'production') {
        error_log("EMAIL TO: $to\nSUBJECT: $subject\nBODY: $body\nHEADERS: $headerString");
        return true;
    }
    
    return mail($to, $subject, $body, $headerString);
}

/**
 * Generate QR code URL using external API (filesystem is read-only on Vercel)
 * @param string $data
 * @param int $size
 * @return string
 */
function generateQrCodeUrl(string $data, int $size = 300): string {
    // Use external QR code API since we can't write files on Vercel
    // Options: api.qrserver.com, chart.googleapis.com, goqr.me, etc.
    return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($data) . '&format=png&margin=2';
}

/**
 * Check if admin is logged in
 * @return bool
 */
function isAdminLoggedIn(): bool {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Check if customer is logged in
 * @return bool
 */
function isCustomerLoggedIn(): bool {
    return isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id']);
}

/**
 * Get logged in customer ID
 * @return int|null
 */
function getCustomerId(): ?int {
    return $_SESSION['customer_id'] ?? null;
}

/**
 * Get logged in admin ID
 * @return int|null
 */
function getAdminId(): ?int {
    return $_SESSION['admin_id'] ?? null;
}

/**
 * Redirect to URL
 * @param string $url
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Set flash message
 * @param string $type
 * @param string $message
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'][$type] = $message;
}

/**
 * Get and clear flash messages
 * @return array
 */
function getFlashes(): array {
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/**
 * Generate a full URL for the application
 * @param string $path
 * @return string
 */
function appUrl(string $path = ''): string {
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
}