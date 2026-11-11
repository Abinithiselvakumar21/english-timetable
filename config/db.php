<?php
// ========================================================
// Database Configuration: Localhost & Cloud DB Adaptive
// File: config/db.php
// ========================================================

// Read environment variables if available (for Render / Vercel), fallback to localhost XAMPP
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_NAME', getenv('DB_NAME') ?: 'english_timetable_db');
define('DB_PORT', getenv('DB_PORT') ?: '3306');

// Dynamically determine host and port (works for localhost, localhost:8080, and vercel/render)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('BASE_URL', $protocol . $host . '/english_timetable/');

// Node AI Endpoint (Environment variable in Cloud, or localhost:5000)
define('NODE_AI_URL', getenv('NODE_AI_URL') ?: 'http://localhost:5000/api/');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // If running in development without online DB, print clean warning
    die("Database Connection Error: " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
