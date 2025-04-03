<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
        throw new Exception('Not logged in');
    }

    $username = $_SESSION['user'];
    
    // Initialize files if they don't exist
    if (!file_exists(ROOMS_FILE)) {
        $initialRooms = [];
        for ($i = 101; $i <= 120; $i++) {
            $initialRooms[] = "$i|single|available|";  // room_no|type|status|occupant
        }
        file_put_contents(ROOMS_FILE, implode("\n", $initialRooms));
    }
    
    if (!file_exists(ALLOCATIONS_FILE)) {
        file_put_contents(ALLOCATIONS_FILE, "");
    }
    
    if (!file_exists(WAITING_LIST_FILE)) {
        file_put_contents(WAITING_LIST_FILE, "");
    }

    $rooms = readDataFromFile(ROOMS_FILE);
    $allocations = readDataFromFile(ALLOCATIONS_FILE);
    $waitingList = readDataFromFile(WAITING_LIST_FILE);

    // Check user's current status
    $userStatus = 'none';
    $userRoom = null;
    $waitingDate = null;

    // Check if user has a room
    foreach ($rooms as $room) {
        $data = explode('|', $room);
        if (isset($data[3]) && $data[3] === $username) {
            $userStatus = 'allocated';
            $userRoom = $data[0];
            break;
        }
    }

    // Check if user is in waiting list
    if ($userStatus === 'none') {
        foreach ($waitingList as $waiting) {
            $data = explode('|', $waiting);
            if ($data[0] === $username) {
                $userStatus = 'waiting';
                $waitingDate = $data[1];
                break;
            }
        }
    }

    // Build HTML content
    $html = '<div class="room-status">';
    
    // Show user's current status
    $html .= '<div class="status-card">';
    switch ($userStatus) {
        case 'allocated':
            $html .= '<div class="status-header">
                        <h3>Your Room</h3>
                        <span class="badge badge-success">Allocated</span>
                    </div>
                    <div class="room-info">
                        <p><strong>Room Number:</strong> ' . htmlspecialchars($userRoom) . '</p>
                        <button class="btn btn-danger" onclick="requestDeallocation()">Request Deallocation</button>
                    </div>';
            break;
            
        case 'waiting':
            $html .= '<div class="status-header">
                        <h3>Waiting List</h3>
                        <span class="badge badge-warning">In Queue</span>
                    </div>
                    <div class="room-info">
                        <p><strong>Application Date:</strong> ' . htmlspecialchars($waitingDate) . '</p>
                        <button class="btn btn-danger" onclick="cancelApplication()">Cancel Application</button>
                    </div>';
            break;
            
        default:
            $html .= '<div class="status-header">
                        <h3>No Room</h3>
                        <span class="badge badge-secondary">Not Applied</span>
                    </div>
                    <div class="room-info">
                        <p>You haven\'t applied for a room yet.</p>
                        <button class="btn btn-primary" onclick="applyForRoom()">Apply for Room</button>
                    </div>';
    }
    $html .= '</div>';

    // Show room statistics
    $totalRooms = count($rooms);
    $occupiedRooms = 0;
    foreach ($rooms as $room) {
        $data = explode('|', $room);
        if (isset($data[2]) && $data[2] === 'occupied') {
            $occupiedRooms++;
        }
    }
    
    $html .= '<div class="stats-card">
                <h3>Room Statistics</h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-label">Total Rooms</span>
                        <span class="stat-value">' . $totalRooms . '</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Available</span>
                        <span class="stat-value">' . ($totalRooms - $occupiedRooms) . '</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Occupied</span>
                        <span class="stat-value">' . $occupiedRooms . '</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Waiting List</span>
                        <span class="stat-value">' . count($waitingList) . '</span>
                    </div>
                </div>
            </div>';

    $html .= '</div>';

    // Add JavaScript for room actions
    $html .= '<script>
    function applyForRoom() {
        fetch("user_api.php?action=apply")
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message || "Failed to apply for room");
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("An error occurred. Please try again.");
        });
    }

    function cancelApplication() {
        if (confirm("Are you sure you want to cancel your application?")) {
            fetch("user_api.php?action=cancel")
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message || "Failed to cancel application");
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("An error occurred. Please try again.");
            });
        }
    }

    function requestDeallocation() {
        if (confirm("Are you sure you want to request room deallocation?")) {
            fetch("user_api.php?action=request_deallocation")
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message || "Failed to request deallocation");
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("An error occurred. Please try again.");
            });
        }
    }
    </script>';

    // Add CSS styles
    $html .= '<style>
        .room-status {
            display: grid;
            gap: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        .status-card, .stats-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
        .badge-secondary { background: #f5f5f5; color: #616161; }
        .room-info {
            margin-top: 15px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        .stat-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-label {
            display: block;
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .stat-value {
            display: block;
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: opacity 0.2s;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .btn-primary {
            background: #2196F3;
            color: white;
        }
        .btn-danger {
            background: #f44336;
            color: white;
        }
    </style>';

    echo json_encode([
        'success' => true,
        'html' => $html
    ]);

} catch (Exception $e) {
    error_log('Room status error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
