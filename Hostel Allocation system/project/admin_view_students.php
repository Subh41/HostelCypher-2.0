<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - Admin Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .tab {
            padding: 10px 20px;
            background: #f0f0f0;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .tab.active {
            background: #4CAF50;
            color: white;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .table th {
            background-color: #f5f5f5;
        }
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
        }
        .view-btn {
            background: #2196F3;
            color: white;
        }
        .approve-btn {
            background: #4CAF50;
            color: white;
        }
        .reject-btn {
            background: #f44336;
            color: white;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }
        .modal-content {
            background: white;
            margin: 10% auto;
            padding: 20px;
            width: 80%;
            max-width: 600px;
            border-radius: 8px;
        }
        .close {
            float: right;
            cursor: pointer;
            font-size: 24px;
        }
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 20px;
        }
        .profile-item {
            padding: 10px;
            background: #f5f5f5;
            border-radius: 4px;
        }
        .profile-label {
            font-weight: bold;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Student Management</h1>
        
        <div class="tabs">
            <button class="tab active" onclick="showTab('all')">All Students</button>
            <button class="tab" onclick="showTab('allocated')">Allocated</button>
            <button class="tab" onclick="showTab('waiting')">Waiting List</button>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Year</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="studentsTable">
                <tr>
                    <td colspan="5">Loading...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div id="profileModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Student Profile</h2>
            <div id="profileContent"></div>
        </div>
    </div>

    <script>
        let currentTab = 'all';
        let students = [];

        function showTab(tab) {
            currentTab = tab;
            document.querySelectorAll('.tab').forEach(t => {
                t.classList.remove('active');
            });
            event.target.classList.add('active');
            filterStudents();
        }

        function loadStudents() {
            fetch('admin_api.php?action=get_students')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        students = data.students;
                        filterStudents();
                    }
                });
        }

        function filterStudents() {
            let filtered = students;
            if (currentTab === 'allocated') {
                filtered = students.filter(s => s.status === 'allocated');
            } else if (currentTab === 'waiting') {
                filtered = students.filter(s => s.status === 'waiting');
            }
            updateTable(filtered);
        }

        function updateTable(students) {
            const tbody = document.getElementById('studentsTable');
            tbody.innerHTML = students.map(student => `
                <tr>
                    <td>${student.full_name}</td>
                    <td>${student.department}</td>
                    <td>${student.year}</td>
                    <td>${student.status}</td>
                    <td>
                        <button class="action-btn view-btn" onclick="viewProfile('${student.username}')">
                            View Profile
                        </button>
                        ${student.status === 'waiting' ? `
                            <button class="action-btn approve-btn" onclick="approveApplication('${student.username}')">
                                Approve
                            </button>
                            <button class="action-btn reject-btn" onclick="rejectApplication('${student.username}')">
                                Reject
                            </button>
                        ` : ''}
                        ${student.status === 'allocated' ? `
                            <button class="action-btn reject-btn" onclick="deallocateRoom('${student.username}')">
                                Deallocate
                            </button>
                        ` : ''}
                    </td>
                </tr>
            `).join('');
        }

        function viewProfile(username) {
            fetch(`admin_api.php?action=get_student_profile&username=${username}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const profile = data.profile;
                        document.getElementById('profileContent').innerHTML = `
                            <div class="profile-grid">
                                <div class="profile-item">
                                    <div class="profile-label">Full Name</div>
                                    <div>${profile.full_name}</div>
                                </div>
                                <div class="profile-item">
                                    <div class="profile-label">Department</div>
                                    <div>${profile.department}</div>
                                </div>
                                <div class="profile-item">
                                    <div class="profile-label">Year</div>
                                    <div>${profile.year}</div>
                                </div>
                                <div class="profile-item">
                                    <div class="profile-label">Room Number</div>
                                    <div>${profile.room_no || 'Not allocated'}</div>
                                </div>
                                <div class="profile-item">
                                    <div class="profile-label">Address</div>
                                    <div>${profile.address}</div>
                                </div>
                                <div class="profile-item">
                                    <div class="profile-label">Parent Name</div>
                                    <div>${profile.parent_name}</div>
                                </div>
                                <div class="profile-item">
                                    <div class="profile-label">Parent Phone</div>
                                    <div>${profile.parent_phone}</div>
                                </div>
                                <div class="profile-item">
                                    <div class="profile-label">Emergency Contact</div>
                                    <div>${profile.emergency_contact}</div>
                                </div>
                            </div>
                        `;
                        document.getElementById('profileModal').style.display = 'block';
                    }
                });
        }

        function closeModal() {
            document.getElementById('profileModal').style.display = 'none';
        }

        function approveApplication(username) {
            if (confirm('Are you sure you want to approve this application?')) {
                fetch(`admin_api.php?action=approve_application&username=${username}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            loadStudents();
                        } else {
                            alert(data.message);
                        }
                    });
            }
        }

        function rejectApplication(username) {
            if (confirm('Are you sure you want to reject this application?')) {
                fetch(`admin_api.php?action=reject_application&username=${username}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            loadStudents();
                        } else {
                            alert(data.message);
                        }
                    });
            }
        }

        function deallocateRoom(username) {
            if (confirm('Are you sure you want to deallocate this room?')) {
                fetch(`admin_api.php?action=deallocate_room&username=${username}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            loadStudents();
                        } else {
                            alert(data.message);
                        }
                    });
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('profileModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        // Load students on page load
        loadStudents();
    </script>
</body>
</html>
