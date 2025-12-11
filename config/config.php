<?php
/**
 * ระบบแจ้งซ่อมเครื่องจักร - Configuration File
 * Version: 2.0
 */

// Timezone Setting
date_default_timezone_set('Asia/Bangkok');

// Error Reporting (Development)
// เปลี่ยนเป็น 0 เมื่อใช้งานจริง (Production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => false, // เปลี่ยนเป็น true เมื่อใช้ HTTPS
        'use_strict_mode' => true,
    ]);
}

// System Configuration
define('SYSTEM_NAME', 'ระบบแจ้งซ่อมเครื่องจักร');
define('SYSTEM_VERSION', '2.0');
define('SYSTEM_AUTHOR', 'MT Department');

// Path Configuration
define('BASE_PATH', dirname(__FILE__));
define('ASSETS_PATH', BASE_PATH . '/assets');
define('CONFIG_PATH', BASE_PATH . '/config');

// URL Configuration
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_url = $protocol . $host . '/mt/';
define('BASE_URL', $base_url);

// Status Constants
define('STATUS_PENDING_APPROVAL', 10);  // รออนุมัติ
define('STATUS_REJECTED', 11);           // ไม่อนุมัติ
define('STATUS_PENDING', 20);            // รอดำเนินการ
define('STATUS_WAITING_PARTS', 30);      // รออะไหล่
define('STATUS_COMPLETED', 40);          // ซ่อมเสร็จสิ้น

/**
 * Get status text in Thai
 * @param int $status_code
 * @return string
 */
function get_status_text($status_code) {
    switch ((int)$status_code) {
        case STATUS_PENDING_APPROVAL:
            return 'รออนุมัติ';
        case STATUS_REJECTED:
            return 'ไม่อนุมัติ';
        case STATUS_PENDING:
            return 'รอดำเนินการ';
        case STATUS_WAITING_PARTS:
            return 'รออะไหล่';
        case STATUS_COMPLETED:
            return 'ซ่อมเสร็จสิ้น';
        default:
            return 'ไม่ทราบสถานะ';
    }
}

/**
 * Get status badge class for Bootstrap
 * @param int $status_code
 * @return string
 */
function get_status_badge_class($status_code) {
    switch ((int)$status_code) {
        case STATUS_PENDING_APPROVAL:
            return 'badge-secondary';
        case STATUS_REJECTED:
            return 'badge-danger';
        case STATUS_PENDING:
            return 'badge-warning';
        case STATUS_WAITING_PARTS:
            return 'badge-info';
        case STATUS_COMPLETED:
            return 'badge-success';
        default:
            return 'badge-dark';
    }
}

// Helper Functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function json_response($success, $message, $data = null) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit();
}
?>
