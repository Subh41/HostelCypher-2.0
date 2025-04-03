<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
        throw new Exception('Not logged in');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (empty($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        throw new Exception('Invalid security token');
    }

    $username = $_SESSION['user'];
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';

    // Validate input
    if (empty($email) || empty($phone)) {
        throw new Exception('Email and phone are required');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    if (!preg_match('/^\d{10}$/', $phone)) {
        throw new Exception('Phone number must be 10 digits');
    }

    // Read users file
    $users = readDataFromFile(USERS_FILE);
    if ($users === false) {
        throw new Exception('Failed to read user data');
    }

    $updated = false;
    $newUsers = [];

    foreach ($users as $user) {
        $userData = explode('|', $user);
        if ($userData[0] === $username) {
            // Update user data
            $userData[2] = $email;
            $userData[3] = $phone;
            if (!empty($newPassword)) {
                $userData[1] = password_hash($newPassword, PASSWORD_DEFAULT);
            }
            $updated = true;
        }
        $newUsers[] = implode('|', $userData);
    }

    if (!$updated) {
        throw new Exception('User not found');
    }

    // Write back to file
    if (!writeDataToFile(USERS_FILE, $newUsers)) {
        throw new Exception('Failed to update user data');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully'
    ]);

} catch (Exception $e) {
    error_log('Profile update error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
