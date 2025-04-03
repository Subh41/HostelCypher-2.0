<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
        throw new Exception('Not logged in');
    }

    $username = $_SESSION['user'];
    $maintenanceData = readDataFromFile(MAINTENANCE_FILE);
    $maintenanceStatus = explode('|', $maintenanceData[0] ?? '0|0');
    $isUnderMaintenance = $maintenanceStatus[0] === '1';
    $maintenanceEndTime = $maintenanceStatus[1];

    // Build HTML content
    $html = '<div class="maintenance-status">';
    
    // Show maintenance request form
    $html .= '<div class="maintenance-card">
        <h3>Submit Maintenance Request</h3>
        <form id="maintenanceForm" class="maintenance-form">
            <input type="hidden" name="csrf_token" id="maintenance_csrf_token">
            <div class="form-group">
                <label for="issue_type">Type of Issue:</label>
                <select id="issue_type" name="issue_type" required>
                    <option value="">Select Issue Type</option>
                    <option value="electrical">Electrical</option>
                    <option value="plumbing">Plumbing</option>
                    <option value="furniture">Furniture</option>
                    <option value="cleaning">Cleaning</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="4" required></textarea>
            </div>
            <div class="form-group">
                <label for="priority">Priority:</label>
                <select id="priority" name="priority" required>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Submit Request</button>
        </form>
        <div id="maintenance_message" class="message"></div>
    </div>';

    // Show maintenance status
    $html .= '<div class="status-card' . ($isUnderMaintenance ? ' maintenance-active' : '') . '">
        <div class="status-header">
            <h3>Maintenance Status</h3>
            <span class="badge badge-' . ($isUnderMaintenance ? 'warning' : 'success') . '">
                ' . ($isUnderMaintenance ? 'Under Maintenance' : 'Normal Operation') . '
            </span>
        </div>';
    
    if ($isUnderMaintenance && $maintenanceEndTime > time()) {
        $remainingTime = $maintenanceEndTime - time();
        $hours = floor($remainingTime / 3600);
        $minutes = floor(($remainingTime % 3600) / 60);
        
        $html .= '<div class="maintenance-info">
            <p><strong>Expected Duration:</strong> ' . 
            ($hours > 0 ? $hours . ' hour(s) ' : '') . 
            ($minutes > 0 ? $minutes . ' minute(s)' : '') . ' remaining</p>
        </div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';

    // Add CSS styles
    $html .= '<style>
        .maintenance-status {
            display: grid;
            gap: 20px;
        }
        .maintenance-card, .status-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .maintenance-form {
            margin-top: 15px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }
        .form-group select, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-group textarea {
            resize: vertical;
        }
        .status-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
        }
        .badge-success { background: #e8f5e9; color: #2e7d32; }
        .badge-warning { background: #fff3e0; color: #ef6c00; }
        .maintenance-active {
            border: 2px solid #ff9800;
        }
        .maintenance-info {
            margin-top: 15px;
            padding: 15px;
            background: #fff3e0;
            border-radius: 4px;
        }
        .message {
            margin-top: 10px;
            padding: 10px;
            border-radius: 4px;
            display: none;
        }
        .message.error {
            background: #ffebee;
            color: #c62828;
        }
        .message.success {
            background: #e8f5e9;
            color: #2e7d32;
        }
    </style>';

    // Add JavaScript
    $html .= '<script>
    document.getElementById("maintenanceForm").addEventListener("submit", function(e) {
        e.preventDefault();
        
        const messageDiv = document.getElementById("maintenance_message");
        messageDiv.textContent = "Submitting request...";
        messageDiv.className = "message";
        messageDiv.style.display = "block";
        
        const formData = new FormData(this);
        
        fetch("submit_maintenance.php", {
            method: "POST",
            body: formData,
            credentials: "same-origin"
        })
        .then(response => response.json())
        .then(data => {
            messageDiv.textContent = data.message;
            messageDiv.className = "message " + (data.success ? "success" : "error");
            
            if (data.success) {
                this.reset();
            }
        })
        .catch(error => {
            messageDiv.textContent = "An error occurred. Please try again.";
            messageDiv.className = "message error";
        });
    });

    // Get CSRF token
    fetch("get_csrf_token.php")
        .then(response => response.json())
        .then(data => {
            document.getElementById("maintenance_csrf_token").value = data.token;
        });
    </script>';

    echo json_encode([
        'success' => true,
        'html' => $html
    ]);

} catch (Exception $e) {
    error_log('Maintenance status error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
