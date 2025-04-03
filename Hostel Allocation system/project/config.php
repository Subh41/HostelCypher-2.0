<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/database/error.log');

// Database directory
define('DB_DIR', __DIR__ . '/database');

// Create database directory if it doesn't exist
if (!file_exists(DB_DIR)) {
    mkdir(DB_DIR, 0777, true);
}

// Database files
define('USERS_FILE', __DIR__ . '/database/users.txt');
define('ROOMS_FILE', __DIR__ . '/database/rooms.txt');
define('ALLOCATIONS_FILE', __DIR__ . '/database/allocations.txt');
define('WAITING_LIST_FILE', __DIR__ . '/database/waiting_list.txt');
define('STUDENT_PROFILES_FILE', __DIR__ . '/database/student_profiles.txt');
define('MAINTENANCE_FILE', __DIR__ . '/database/maintenance.txt');

// Room configuration
define('TOTAL_FLOORS', 4);
define('ROOMS_PER_FLOOR', 10);
define('BEDS_PER_ROOM', 1);

// Default admin credentials
define('DEFAULT_ADMIN_USERNAME', 'admin');
define('DEFAULT_ADMIN_PASSWORD', 'admin123');

// Create files if they don't exist
$files = [USERS_FILE, ROOMS_FILE, ALLOCATIONS_FILE, WAITING_LIST_FILE, STUDENT_PROFILES_FILE, MAINTENANCE_FILE];
foreach ($files as $file) {
    if (!file_exists($file)) {
        file_put_contents($file, '');
    }
}

// Helper functions
function readDataFromFile($file) {
    if (!file_exists($file)) {
        file_put_contents($file, '');
        return [];
    }
    $content = file_get_contents($file);
    return $content ? explode("\n", trim($content)) : [];
}

function writeDataToFile($file, $data) {
    return file_put_contents($file, implode("\n", $data));
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function getStudentProfile($username) {
    $profiles = readDataFromFile(STUDENT_PROFILES_FILE);
    foreach ($profiles as $profile) {
        $data = explode('|', $profile);
        if ($data[0] === $username) {
            return [
                'username' => $data[0],
                'full_name' => $data[1] ?? '',
                'department' => $data[2] ?? '',
                'year' => $data[3] ?? '',
                'address' => $data[4] ?? '',
                'parent_name' => $data[5] ?? '',
                'parent_phone' => $data[6] ?? '',
                'emergency_contact' => $data[7] ?? '',
                'room_no' => $data[8] ?? '',
                'status' => $data[9] ?? 'none'
            ];
        }
    }
    return null;
}

function hasRoom($username) {
    $allocations = readDataFromFile(ALLOCATIONS_FILE);
    foreach ($allocations as $allocation) {
        list($room_no, $user) = explode('|', $allocation);
        if ($user === $username) {
            return true;
        }
    }
    return false;
}

function isInWaitingList($username) {
    $waitingList = readDataFromFile(WAITING_LIST_FILE);
    foreach ($waitingList as $item) {
        list($user) = explode('|', $item);
        if ($user === $username) {
            return true;
        }
    }
    return false;
}

function checkMaintenanceMode() {
    $maintenanceData = readDataFromFile(MAINTENANCE_FILE);
    if (empty($maintenanceData)) {
        return false;
    }
    
    list($status, $endTime) = explode('|', $maintenanceData[0]);
    return $status === '1' && time() < (int)$endTime;
}

function getRoomStats() {
    $allocations = readDataFromFile(ALLOCATIONS_FILE);
    $waitingList = readDataFromFile(WAITING_LIST_FILE);
    
    $totalRooms = TOTAL_FLOORS * ROOMS_PER_FLOOR;
    $occupiedRooms = count($allocations);
    
    return [
        'total' => $totalRooms,
        'occupied' => $occupiedRooms,
        'available' => $totalRooms - $occupiedRooms,
        'waiting' => count($waitingList)
    ];
}

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);

// Initialize session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
