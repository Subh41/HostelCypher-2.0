<?php
session_start();
require_once 'config.php';
require_once 'csrf_utils.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
        throw new Exception('Not logged in');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        throw new Exception('Invalid security token');
    }

    $username = $_SESSION['user'];
    $issueType = sanitizeInput($_POST['issue_type'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $priority = sanitizeInput($_POST['priority'] ?? '');

    // Validate input
    if (empty($issueType) || empty($description) || empty($priority)) {
        throw new Exception('All fields are required');
    }

    // Validate issue type
    $validIssueTypes = ['electrical', 'plumbing', 'furniture', 'cleaning', 'other'];
    if (!in_array($issueType, $validIssueTypes)) {
        throw new Exception('Invalid issue type');
    }

    // Validate priority
    $validPriorities = ['low', 'medium', 'high'];
    if (!in_array($priority, $validPriorities)) {
        throw new Exception('Invalid priority');
    }

    // Create maintenance request
    $maintenanceRequest = implode('|', [
        $username,
        $issueType,
        $description,
        $priority,
        date('Y-m-d H:i:s')
    ]);

    // Save to maintenance requests file
    $maintenanceFile = __DIR__ . '/database/maintenance_requests.txt';
    if (!file_exists($maintenanceFile)) {
        file_put_contents($maintenanceFile, "# Maintenance requests - Format: username|issue_type|description|priority|created_at\n");
    }

    if (file_put_contents($maintenanceFile, $maintenanceRequest . "\n", FILE_APPEND) === false) {
        throw new Exception('Failed to save maintenance request');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Maintenance request submitted successfully'
    ]);

} catch (Exception $e) {
    error_log('Maintenance request error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
