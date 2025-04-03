<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'get_stats':
            $stats = [
                'total_students' => 0,
                'rooms_allocated' => 0,
                'waiting_count' => 0,
                'available_rooms' => 0,
                'single_rooms' => 0,
                'double_rooms' => 0,
                'total_rooms' => 0
            ];

            // Count students and room allocations
            if (file_exists('database/student_profiles.txt')) {
                $lines = file('database/student_profiles.txt');
                foreach ($lines as $line) {
                    if (strpos($line, '#') === 0) continue;
                    $data = explode('|', trim($line));
                    if (count($data) >= 10) {
                        $stats['total_students']++;
                        if ($data[9] === 'allocated') {
                            $stats['rooms_allocated']++;
                        } elseif ($data[9] === 'waiting') {
                            $stats['waiting_count']++;
                        }
                    }
                }
            }

            // Count rooms by type
            if (file_exists('database/rooms.txt')) {
                $lines = file('database/rooms.txt');
                foreach ($lines as $line) {
                    if (strpos($line, '#') === 0) continue;
                    $data = explode('|', trim($line));
                    if (count($data) >= 2) {
                        $stats['total_rooms']++;
                        if ($data[1] === 'single') {
                            $stats['single_rooms']++;
                        } elseif ($data[1] === 'double') {
                            $stats['double_rooms']++;
                        }
                    }
                }
            }

            $stats['available_rooms'] = $stats['total_rooms'] - $stats['rooms_allocated'];

            echo json_encode(['success' => true, 'stats' => $stats]);
            break;

        case 'get_waiting_list':
            $waiting_list = [];
            if (file_exists('database/student_profiles.txt')) {
                $lines = file('database/student_profiles.txt');
                foreach ($lines as $line) {
                    if (strpos($line, '#') === 0) continue;
                    $data = explode('|', trim($line));
                    if (count($data) >= 10 && $data[9] === 'waiting') {
                        $waiting_list[] = [
                            'username' => $data[0],
                            'full_name' => $data[1],
                            'department' => $data[2],
                            'year' => $data[3],
                            'date' => date('Y-m-d')
                        ];
                    }
                }
            }
            echo json_encode(['success' => true, 'waiting_list' => $waiting_list]);
            break;

        case 'get_allocations':
            $allocations = [];
            if (file_exists('database/student_profiles.txt')) {
                $lines = file('database/student_profiles.txt');
                foreach ($lines as $line) {
                    if (strpos($line, '#') === 0) continue;
                    $data = explode('|', trim($line));
                    if (count($data) >= 10 && $data[9] === 'allocated') {
                        $allocations[] = [
                            'room_no' => $data[8],
                            'student_name' => $data[1],
                            'department' => $data[2],
                            'date' => date('Y-m-d')
                        ];
                    }
                }
            }
            echo json_encode(['success' => true, 'allocations' => $allocations]);
            break;

        case 'approve_application':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            if (empty($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
                throw new Exception('Invalid security token');
            }

            $username = $_POST['username'] ?? '';
            if (empty($username)) {
                throw new Exception('Username is required');
            }

            // Find next available room number
            $next_room = 101;
            $taken_rooms = [];
            if (file_exists('database/student_profiles.txt')) {
                $lines = file('database/student_profiles.txt');
                foreach ($lines as $line) {
                    if (strpos($line, '#') === 0) continue;
                    $data = explode('|', trim($line));
                    if (count($data) >= 9 && $data[8] !== '') {
                        $taken_rooms[] = intval($data[8]);
                    }
                }
                if (!empty($taken_rooms)) {
                    sort($taken_rooms);
                    $next_room = end($taken_rooms) + 1;
                }
            }

            // Update student profile with room number
            $updated = false;
            $lines = file('database/student_profiles.txt');
            $new_lines = [];
            foreach ($lines as $line) {
                if (strpos($line, '#') === 0) {
                    $new_lines[] = $line;
                    continue;
                }
                $data = explode('|', trim($line));
                if ($data[0] === $username) {
                    $data[8] = $next_room;
                    $data[9] = 'allocated';
                    $new_lines[] = implode('|', $data) . "\n";
                    $updated = true;
                } else {
                    $new_lines[] = $line;
                }
            }

            if (!$updated) {
                throw new Exception('Student not found');
            }

            file_put_contents('database/student_profiles.txt', implode('', $new_lines));
            echo json_encode(['success' => true, 'message' => 'Application approved successfully']);
            break;

        case 'reject_application':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            if (empty($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
                throw new Exception('Invalid security token');
            }

            $username = $_POST['username'] ?? '';
            if (empty($username)) {
                throw new Exception('Username is required');
            }

            // Update student profile to rejected status
            $updated = false;
            $lines = file('database/student_profiles.txt');
            $new_lines = [];
            foreach ($lines as $line) {
                if (strpos($line, '#') === 0) {
                    $new_lines[] = $line;
                    continue;
                }
                $data = explode('|', trim($line));
                if ($data[0] === $username) {
                    $data[9] = 'rejected';
                    $new_lines[] = implode('|', $data) . "\n";
                    $updated = true;
                } else {
                    $new_lines[] = $line;
                }
            }

            if (!$updated) {
                throw new Exception('Student not found');
            }

            file_put_contents('database/student_profiles.txt', implode('', $new_lines));
            echo json_encode(['success' => true, 'message' => 'Application rejected successfully']);
            break;

        case 'deallocate_room':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            if (empty($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
                throw new Exception('Invalid security token');
            }

            $room_no = $_POST['room_no'] ?? '';
            if (empty($room_no)) {
                throw new Exception('Room number is required');
            }

            // Update student profile to remove room allocation
            $updated = false;
            $lines = file('database/student_profiles.txt');
            $new_lines = [];
            foreach ($lines as $line) {
                if (strpos($line, '#') === 0) {
                    $new_lines[] = $line;
                    continue;
                }
                $data = explode('|', trim($line));
                if ($data[8] === $room_no) {
                    $data[8] = '';
                    $data[9] = 'waiting';
                    $new_lines[] = implode('|', $data) . "\n";
                    $updated = true;
                } else {
                    $new_lines[] = $line;
                }
            }

            if (!$updated) {
                throw new Exception('Room not found or not allocated');
            }

            file_put_contents('database/student_profiles.txt', implode('', $new_lines));
            echo json_encode(['success' => true, 'message' => 'Room deallocated successfully']);
            break;

        case 'add_room':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            if (empty($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
                throw new Exception('Invalid security token');
            }

            $room_no = $_POST['room_no'] ?? '';
            $room_type = $_POST['room_type'] ?? '';

            if (empty($room_no) || empty($room_type)) {
                throw new Exception('Room number and type are required');
            }

            if (!in_array($room_type, ['single', 'double'])) {
                throw new Exception('Invalid room type');
            }

            // Check if room already exists
            if (file_exists('database/rooms.txt')) {
                $lines = file('database/rooms.txt');
                foreach ($lines as $line) {
                    if (strpos($line, '#') === 0) continue;
                    $data = explode('|', trim($line));
                    if ($data[0] === $room_no) {
                        throw new Exception('Room already exists');
                    }
                }
            }

            // Add new room
            $new_room = "$room_no|$room_type\n";
            file_put_contents('database/rooms.txt', $new_room, FILE_APPEND);
            echo json_encode(['success' => true, 'message' => 'Room added successfully']);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
