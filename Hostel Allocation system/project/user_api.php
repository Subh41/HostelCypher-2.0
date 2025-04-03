<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user'])) {
        throw new Exception('Not logged in');
    }

    $username = $_SESSION['user'];
    $action = $_GET['action'] ?? '';

    // Get student profile
    $profile = getStudentProfile($username);
    if (!$profile && $action !== 'update_profile') {
        throw new Exception('Please complete your profile before applying for a room');
    }

    switch ($action) {
        case 'update_profile':
            if (empty($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
                throw new Exception('Invalid security token');
            }

            $fullName = sanitizeInput($_POST['full_name'] ?? '');
            $department = sanitizeInput($_POST['department'] ?? '');
            $year = sanitizeInput($_POST['year'] ?? '');
            $address = sanitizeInput($_POST['address'] ?? '');
            $parentName = sanitizeInput($_POST['parent_name'] ?? '');
            $parentPhone = sanitizeInput($_POST['parent_phone'] ?? '');
            $emergencyContact = sanitizeInput($_POST['emergency_contact'] ?? '');

            // Validate required fields
            if (empty($fullName) || empty($department) || empty($year) || empty($address) || 
                empty($parentName) || empty($parentPhone) || empty($emergencyContact)) {
                throw new Exception('All fields are required');
            }

            // Validate year
            if (!in_array($year, ['1', '2', '3', '4'])) {
                throw new Exception('Invalid year');
            }

            // Update or create profile
            $profiles = readDataFromFile(STUDENT_PROFILES_FILE);
            $newProfiles = [];
            $updated = false;

            foreach ($profiles as $p) {
                $data = explode('|', $p);
                if ($data[0] === $username) {
                    // Keep room status if exists
                    $roomNo = $data[8] ?? '';
                    $status = $data[9] ?? 'none';
                    
                    $p = implode('|', [
                        $username, $fullName, $department, $year, $address,
                        $parentName, $parentPhone, $emergencyContact, $roomNo, $status
                    ]);
                    $updated = true;
                }
                $newProfiles[] = $p;
            }

            if (!$updated) {
                $newProfiles[] = implode('|', [
                    $username, $fullName, $department, $year, $address,
                    $parentName, $parentPhone, $emergencyContact, '', 'none'
                ]);
            }

            if (!writeDataToFile(STUDENT_PROFILES_FILE, $newProfiles)) {
                throw new Exception('Failed to update profile');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Profile updated successfully'
            ]);
            break;

        case 'apply':
            if (empty($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
                throw new Exception('Invalid security token');
            }

            // Check if user already has a room
            if (hasRoom($username)) {
                throw new Exception('You already have a room allocated');
            }

            // Check if user is in waiting list
            if (isInWaitingList($username)) {
                throw new Exception('You are already in the waiting list');
            }

            // Add to waiting list
            $waitingList = readDataFromFile(WAITING_LIST_FILE);
            $waitingList[] = $username . '|' . date('Y-m-d H:i:s');
            
            if (!writeDataToFile(WAITING_LIST_FILE, $waitingList)) {
                throw new Exception('Failed to add to waiting list');
            }

            // Update student profile
            $profiles = readDataFromFile(STUDENT_PROFILES_FILE);
            $newProfiles = [];
            foreach ($profiles as $p) {
                $data = explode('|', $p);
                if ($data[0] === $username) {
                    $data[9] = 'waiting';
                }
                $newProfiles[] = implode('|', $data);
            }
            writeDataToFile(STUDENT_PROFILES_FILE, $newProfiles);

            echo json_encode([
                'success' => true,
                'message' => 'Your application has been submitted. Please wait for admin approval.'
            ]);
            break;

        case 'cancel':
            if (empty($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
                throw new Exception('Invalid security token');
            }

            if (!isInWaitingList($username)) {
                throw new Exception('You are not in the waiting list');
            }

            $waitingList = readDataFromFile(WAITING_LIST_FILE);
            $newWaitingList = [];
            foreach ($waitingList as $entry) {
                list($user) = explode('|', $entry);
                if ($user !== $username) {
                    $newWaitingList[] = $entry;
                }
            }

            if (!writeDataToFile(WAITING_LIST_FILE, $newWaitingList)) {
                throw new Exception('Failed to cancel application');
            }

            // Update student profile
            $profiles = readDataFromFile(STUDENT_PROFILES_FILE);
            $newProfiles = [];
            foreach ($profiles as $p) {
                $data = explode('|', $p);
                if ($data[0] === $username) {
                    $data[9] = 'none';
                }
                $newProfiles[] = implode('|', $data);
            }
            writeDataToFile(STUDENT_PROFILES_FILE, $newProfiles);

            echo json_encode([
                'success' => true,
                'message' => 'Application cancelled successfully'
            ]);
            break;

        case 'request_deallocation':
            if (empty($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
                throw new Exception('Invalid security token');
            }

            if (!hasRoom($username)) {
                throw new Exception('You do not have a room allocated');
            }

            $allocations = readDataFromFile(ALLOCATIONS_FILE);
            $newAllocations = [];
            $deallocatedRoom = null;

            foreach ($allocations as $allocation) {
                list($roomNo, $user) = explode('|', $allocation);
                if ($user !== $username) {
                    $newAllocations[] = $allocation;
                } else {
                    $deallocatedRoom = $roomNo;
                }
            }

            if (!writeDataToFile(ALLOCATIONS_FILE, $newAllocations)) {
                throw new Exception('Failed to deallocate room');
            }

            // Update student profile
            $profiles = readDataFromFile(STUDENT_PROFILES_FILE);
            $newProfiles = [];
            foreach ($profiles as $p) {
                $data = explode('|', $p);
                if ($data[0] === $username) {
                    $data[8] = '';
                    $data[9] = 'none';
                }
                $newProfiles[] = implode('|', $data);
            }
            writeDataToFile(STUDENT_PROFILES_FILE, $newProfiles);

            echo json_encode([
                'success' => true,
                'message' => 'Room deallocated successfully'
            ]);
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
