<?php
session_start();
require_once 'config.php';

// Skip maintenance check for admin users and admin-related pages
$isAdminUser = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
$isAdminPage = strpos($_SERVER['PHP_SELF'], 'admin_') !== false;

if (!$isAdminUser && !$isAdminPage) {
    $maintenanceData = readDataFromFile(MAINTENANCE_FILE);
    if (!empty($maintenanceData)) {
        list($status, $endTime) = explode('|', $maintenanceData[0]);
        
        if ($status === '1' && time() < (int)$endTime) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                // AJAX request
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'maintenance' => true,
                    'message' => 'System is under maintenance. Please try again later.',
                    'end_time' => (int)$endTime
                ]);
                exit;
            } else {
                // Regular page request
                header('Content-Type: text/html');
                echo '<!DOCTYPE html>
                <html>
                <head>
                    <title>System Maintenance</title>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            display: flex;
                            justify-content: center;
                            align-items: center;
                            height: 100vh;
                            margin: 0;
                            background-color: #f5f5f5;
                        }
                        .maintenance-box {
                            background-color: white;
                            padding: 2rem;
                            border-radius: 8px;
                            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                            text-align: center;
                            max-width: 400px;
                        }
                        h1 {
                            color: #f0ad4e;
                            margin-bottom: 1rem;
                        }
                        p {
                            color: #666;
                            margin-bottom: 1rem;
                        }
                        #timer {
                            font-size: 1.2rem;
                            font-weight: bold;
                            color: #333;
                        }
                    </style>
                </head>
                <body>
                    <div class="maintenance-box">
                        <h1>System Maintenance</h1>
                        <p>We are currently performing system maintenance.</p>
                        <p>Please try again in:</p>
                        <div id="timer">--:--</div>
                    </div>
                    <script>
                        function updateTimer() {
                            const endTime = ' . $endTime . ';
                            const now = Math.floor(Date.now() / 1000);
                            const remaining = endTime - now;
                            
                            if (remaining <= 0) {
                                location.reload();
                                return;
                            }
                            
                            const minutes = Math.floor(remaining / 60);
                            const seconds = remaining % 60;
                            document.getElementById("timer").textContent = 
                                `${minutes.toString().padStart(2, "0")}:${seconds.toString().padStart(2, "0")}`;
                            
                            setTimeout(updateTimer, 1000);
                        }
                        updateTimer();
                    </script>
                </body>
                </html>';
                exit;
            }
        }
    }
}
?>
