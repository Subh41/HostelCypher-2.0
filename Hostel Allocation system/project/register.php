<?php
session_start();
require_once 'config.php';
require_once 'csrf_utils.php';

// Ensure proper error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'database/error.log');

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        throw new Exception('Invalid security token');
    }

    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');

    // Validate input
    if (empty($username) || empty($password) || empty($email) || empty($phone)) {
        throw new Exception('All fields are required');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        throw new Exception('Invalid phone number format');
    }

    if (!preg_match('/^[a-zA-Z0-9_-]{3,30}$/', $username)) {
        throw new Exception('Invalid username format');
    }

    // Password strength validation
    if (strlen($password) < 8 ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[a-z]/', $password) ||
        !preg_match('/[0-9]/', $password) ||
        !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        throw new Exception('Password does not meet security requirements');
    }

    // Check if username already exists
    $users = readDataFromFile(USERS_FILE);
    foreach ($users as $user) {
        $userData = explode('|', $user);
        if ($userData[0] === $username) {
            throw new Exception('Username already exists');
        }
    }

    // First user is automatically admin
    $isAdmin = empty($users);

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Create user data string
    $userData = implode('|', [
        $username,
        $hashedPassword,
        $email,
        $phone,
        $isAdmin ? '1' : '0',
        date('Y-m-d H:i:s')
    ]);

    // Save user data
    if (!writeDataToFile(USERS_FILE, array_merge($users, [$userData]))) {
        throw new Exception('Failed to save user data');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Registration successful',
        'isAdmin' => $isAdmin
    ]);

} catch (Exception $e) {
    error_log('Registration error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>