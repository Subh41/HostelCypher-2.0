<?php
session_start();
require_once 'config.php';
require_once 'admin_credentials.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        throw new Exception('Invalid security token');
    }

    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate input
    if (empty($username) || empty($password)) {
        throw new Exception('Username and password are required');
    }

    // First check if it's the admin
    if ($username === DEFAULT_ADMIN_USERNAME) {
        if (password_verify($password, DEFAULT_ADMIN_PASSWORD_HASH)) {
            $_SESSION['user'] = $username;
            $_SESSION['is_admin'] = true;
            echo json_encode([
                'success' => true,
                'message' => 'Admin login successful',
                'redirect' => 'admin_dashboard.php'
            ]);
            exit;
        }
        throw new Exception('Invalid admin credentials');
    }

    // Check regular users
    $users = readDataFromFile(USERS_FILE);
    $loggedIn = false;

    foreach ($users as $user) {
        if (strpos($user, '#') === 0) continue; // Skip comments
        
        $data = explode('|', $user);
        if ($data[0] === $username) {
            if (password_verify($password, $data[1])) {
                $_SESSION['user'] = $username;
                $_SESSION['is_admin'] = false;
                $loggedIn = true;
                break;
            } else {
                throw new Exception('Invalid password');
            }
        }
    }

    if (!$loggedIn) {
        throw new Exception('User not found');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'redirect' => 'user_dashboard.php'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>