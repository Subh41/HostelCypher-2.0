<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user'])) {
        throw new Exception('Not logged in');
    }

    $username = $_SESSION['user'];
    $profile = getStudentProfile($username);

    if (!$profile) {
        // Return basic user info if profile not found
        $users = readDataFromFile(USERS_FILE);
        foreach ($users as $user) {
            $data = explode('|', $user);
            if ($data[0] === $username) {
                $profile = [
                    'username' => $data[0],
                    'email' => $data[2],
                    'phone' => $data[3],
                    'created_at' => $data[5],
                    'full_name' => '',
                    'department' => '',
                    'year' => '',
                    'address' => '',
                    'parent_name' => '',
                    'parent_phone' => '',
                    'emergency_contact' => '',
                    'room_no' => '',
                    'status' => 'none'
                ];
                break;
            }
        }
    }

    if (!$profile) {
        throw new Exception('Profile not found');
    }

    // Get room status
    $hasRoom = hasRoom($username);
    $isWaiting = isInWaitingList($username);

    echo json_encode([
        'success' => true,
        'profile' => $profile,
        'has_room' => $hasRoom,
        'is_waiting' => $isWaiting
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
