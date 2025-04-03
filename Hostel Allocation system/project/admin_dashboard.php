<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Hostel Allocation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        .stats-card {
            transition: transform 0.3s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .action-buttons button {
            min-width: 100px;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-building"></i> Admin Dashboard</a>
            <div class="navbar-nav ms-auto">
                <span class="nav-item nav-link text-light">Welcome, Admin</span>
                <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-users"></i> Total Students</h5>
                        <h2 class="card-text" id="totalStudents">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-door-open"></i> Rooms Allocated</h5>
                        <h2 class="card-text" id="roomsAllocated">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-clock"></i> Waiting List</h5>
                        <h2 class="card-text" id="waitingCount">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-door-closed"></i> Available Rooms</h5>
                        <h2 class="card-text" id="availableRooms">0</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Waiting List -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h4 class="mb-0"><i class="fas fa-list"></i> Waiting List</h4>
                    </div>
                    <div class="card-body">
                        <div id="waitingList" class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Department</th>
                                        <th>Year</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="4" class="text-center">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Current Allocations -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h4 class="mb-0"><i class="fas fa-check-circle"></i> Current Allocations</h4>
                    </div>
                    <div class="card-body">
                        <div id="allocations" class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Room</th>
                                        <th>Student Name</th>
                                        <th>Department</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="4" class="text-center">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Room Management -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h4 class="mb-0"><i class="fas fa-cog"></i> Room Management</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <form id="addRoomForm" class="needs-validation" novalidate>
                                    <div class="mb-3">
                                        <label for="roomNumber" class="form-label">Room Number</label>
                                        <input type="number" class="form-control" id="roomNumber" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="roomType" class="form-label">Room Type</label>
                                        <select class="form-select" id="roomType" required>
                                            <option value="single">Single</option>
                                            <option value="double">Double</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Add Room</button>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <div id="roomStats">
                                    <h5>Room Statistics</h5>
                                    <div class="progress mb-3">
                                        <div class="progress-bar" role="progressbar" style="width: 0%" id="occupancyRate">0%</div>
                                    </div>
                                    <div class="row">
                                        <div class="col">
                                            <small>Single Rooms: <span id="singleRooms">0</span></small>
                                        </div>
                                        <div class="col">
                                            <small>Double Rooms: <span id="doubleRooms">0</span></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to load dashboard statistics
        async function loadStats() {
            try {
                const response = await fetch('admin_api.php?action=get_stats');
                const data = await response.json();
                if (data.success) {
                    document.getElementById('totalStudents').textContent = data.stats.total_students;
                    document.getElementById('roomsAllocated').textContent = data.stats.rooms_allocated;
                    document.getElementById('waitingCount').textContent = data.stats.waiting_count;
                    document.getElementById('availableRooms').textContent = data.stats.available_rooms;
                    
                    // Update room statistics
                    document.getElementById('singleRooms').textContent = data.stats.single_rooms;
                    document.getElementById('doubleRooms').textContent = data.stats.double_rooms;
                    
                    // Update occupancy rate
                    const occupancyRate = (data.stats.rooms_allocated / data.stats.total_rooms * 100).toFixed(1);
                    const occupancyBar = document.getElementById('occupancyRate');
                    occupancyBar.style.width = occupancyRate + '%';
                    occupancyBar.textContent = occupancyRate + '% Occupied';
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        // Function to load waiting list
        async function loadWaitingList() {
            try {
                const response = await fetch('admin_api.php?action=get_waiting_list');
                const data = await response.json();
                if (data.success) {
                    const waitingList = document.querySelector('#waitingList table tbody');
                    if (data.waiting_list.length === 0) {
                        waitingList.innerHTML = '<tr><td colspan="4" class="text-center">No students in waiting list</td></tr>';
                        return;
                    }

                    waitingList.innerHTML = data.waiting_list.map(item => `
                        <tr>
                            <td>${item.full_name}</td>
                            <td>${item.department}</td>
                            <td>${item.year}</td>
                            <td class="action-buttons">
                                <button class="btn btn-sm btn-success approve-btn" data-username="${item.username}">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                                <button class="btn btn-sm btn-danger reject-btn" data-username="${item.username}">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </td>
                        </tr>
                    `).join('');

                    // Add event listeners for approve/reject buttons
                    addWaitingListEventListeners();
                }
            } catch (error) {
                console.error('Error loading waiting list:', error);
            }
        }

        // Function to load allocations
        async function loadAllocations() {
            try {
                const response = await fetch('admin_api.php?action=get_allocations');
                const data = await response.json();
                if (data.success) {
                    const allocations = document.querySelector('#allocations table tbody');
                    if (data.allocations.length === 0) {
                        allocations.innerHTML = '<tr><td colspan="4" class="text-center">No rooms allocated</td></tr>';
                        return;
                    }

                    allocations.innerHTML = data.allocations.map(item => `
                        <tr>
                            <td>${item.room_no}</td>
                            <td>${item.student_name}</td>
                            <td>${item.department}</td>
                            <td>
                                <button class="btn btn-sm btn-warning deallocate-btn" data-room="${item.room_no}">
                                    <i class="fas fa-door-open"></i> Deallocate
                                </button>
                            </td>
                        </tr>
                    `).join('');

                    // Add event listeners for deallocate buttons
                    addAllocationEventListeners();
                }
            } catch (error) {
                console.error('Error loading allocations:', error);
            }
        }

        // Add event listeners for waiting list buttons
        function addWaitingListEventListeners() {
            document.querySelectorAll('.approve-btn').forEach(button => {
                button.addEventListener('click', async () => {
                    if (confirm('Are you sure you want to approve this application?')) {
                        const username = button.dataset.username;
                        try {
                            const response = await fetch('admin_api.php?action=approve_application', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `username=${username}&csrf_token=<?php echo $_SESSION['csrf_token']; ?>`
                            });
                            const data = await response.json();
                            alert(data.message);
                            if (data.success) {
                                loadWaitingList();
                                loadAllocations();
                                loadStats();
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            alert('An error occurred. Please try again.');
                        }
                    }
                });
            });

            document.querySelectorAll('.reject-btn').forEach(button => {
                button.addEventListener('click', async () => {
                    if (confirm('Are you sure you want to reject this application?')) {
                        const username = button.dataset.username;
                        try {
                            const response = await fetch('admin_api.php?action=reject_application', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `username=${username}&csrf_token=<?php echo $_SESSION['csrf_token']; ?>`
                            });
                            const data = await response.json();
                            alert(data.message);
                            if (data.success) {
                                loadWaitingList();
                                loadStats();
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            alert('An error occurred. Please try again.');
                        }
                    }
                });
            });
        }

        // Add event listeners for allocation buttons
        function addAllocationEventListeners() {
            document.querySelectorAll('.deallocate-btn').forEach(button => {
                button.addEventListener('click', async () => {
                    if (confirm('Are you sure you want to deallocate this room?')) {
                        const roomNo = button.dataset.room;
                        try {
                            const response = await fetch('admin_api.php?action=deallocate_room', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `room_no=${roomNo}&csrf_token=<?php echo $_SESSION['csrf_token']; ?>`
                            });
                            const data = await response.json();
                            alert(data.message);
                            if (data.success) {
                                loadAllocations();
                                loadStats();
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            alert('An error occurred. Please try again.');
                        }
                    }
                });
            });
        }

        // Add Room Form Submission
        document.getElementById('addRoomForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const roomNumber = document.getElementById('roomNumber').value;
            const roomType = document.getElementById('roomType').value;

            try {
                const response = await fetch('admin_api.php?action=add_room', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `room_no=${roomNumber}&room_type=${roomType}&csrf_token=<?php echo $_SESSION['csrf_token']; ?>`
                });
                const data = await response.json();
                alert(data.message);
                if (data.success) {
                    document.getElementById('addRoomForm').reset();
                    loadStats();
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            }
        });

        // Load initial data
        document.addEventListener('DOMContentLoaded', () => {
            loadStats();
            loadWaitingList();
            loadAllocations();
        });

        // Refresh data every 30 seconds
        setInterval(() => {
            loadStats();
            loadWaitingList();
            loadAllocations();
        }, 30000);
    </script>
</body>
</html>
