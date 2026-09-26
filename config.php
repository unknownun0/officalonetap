<?php
/**
 * Database configuration and connection
 * Adjust credentials for your XAMPP setup
 */

// Database credentials - adjust for your XAMPP setup
define('DB_HOST', 'localhost');
define('DB_NAME', 'nfc_app');
define('DB_USER', 'root');
define('DB_PASS', ''); // XAMPP default is empty password

// Application settings
define('APP_URL', 'http://localhost/officalonetap'); // Change if your folder name differs
define('ADMIN_EMAIL', 'admin@example.com'); // Admin email for contact form

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get PDO database connection
 * @return PDO
 * @throws Exception
 */
function getDb(): PDO {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
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
 * Send email using PHP mail()
 * @param string $to
 * @param string $subject
 * @param string $body
 * @param array $headers
 * @return bool
 */
function sendEmail(string $to, string $subject, string $body, array $headers = []): bool {
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
    
    return mail($to, $subject, $body, $headerString);
}

/**
 * Generate QR code URL using phpqrcode library
 * @param string $data
 * @param int $size
 * @return string
 */
function generateQrCodeUrl(string $data, int $size = 300): string {
    // Using local phpqrcode library
    $qrPath = __DIR__ . '/phpqrcode/qrlib.php';
    if (file_exists($qrPath)) {
        $filename = 'qr_' . md5($data) . '.png';
        $filepath = __DIR__ . '/qr_codes/' . $filename;
        
        // Create directory if not exists
        if (!is_dir(__DIR__ . '/qr_codes')) {
            mkdir(__DIR__ . '/qr_codes', 0755, true);
        }
        
        require_once $qrPath;
        QRcode::png($data, $filepath, QR_ECLEVEL_L, 10, 2);
        
        return APP_URL . '/qr_codes/' . $filename;
    }
    
    // Fallback to external API if library not found
    return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($data);
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