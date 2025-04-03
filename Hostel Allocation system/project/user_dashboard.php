<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.html');
    exit;
}

$username = $_SESSION['user'];
$profile = getStudentProfile($username);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Hostel Allocation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Hostel Allocation System</a>
            <div class="navbar-nav ms-auto">
                <span class="nav-item nav-link text-light">Welcome, <?php echo htmlspecialchars($username); ?></span>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (!$profile): ?>
        <!-- Profile Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h4>Complete Your Profile</h4>
            </div>
            <div class="card-body">
                <form id="profileForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="department" class="form-label">Department</label>
                            <input type="text" class="form-control" id="department" name="department" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="year" class="form-label">Year</label>
                            <select class="form-control" id="year" name="year" required>
                                <option value="">Select Year</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="address" name="address" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="parent_name" class="form-label">Parent/Guardian Name</label>
                            <input type="text" class="form-control" id="parent_name" name="parent_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="parent_phone" class="form-label">Parent/Guardian Phone</label>
                            <input type="tel" class="form-control" id="parent_phone" name="parent_phone" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="emergency_contact" class="form-label">Emergency Contact</label>
                            <input type="tel" class="form-control" id="emergency_contact" name="emergency_contact" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Profile</button>
                </form>
            </div>
        </div>
        <?php else: ?>
        <!-- Profile Information -->
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4>Profile Information</h4>
                    </div>
                    <div class="card-body">
                        <p><strong>Full Name:</strong> <?php echo htmlspecialchars($profile['full_name']); ?></p>
                        <p><strong>Department:</strong> <?php echo htmlspecialchars($profile['department']); ?></p>
                        <p><strong>Year:</strong> <?php echo htmlspecialchars($profile['year']); ?></p>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($profile['address']); ?></p>
                        <p><strong>Parent/Guardian:</strong> <?php echo htmlspecialchars($profile['parent_name']); ?></p>
                        <p><strong>Parent Phone:</strong> <?php echo htmlspecialchars($profile['parent_phone']); ?></p>
                        <p><strong>Emergency Contact:</strong> <?php echo htmlspecialchars($profile['emergency_contact']); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4>Room Status</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($profile['status'] === 'allocated'): ?>
                            <div class="alert alert-success">
                                <h5>Room Allocated</h5>
                                <p>Your room number: <?php echo htmlspecialchars($profile['room_no']); ?></p>
                                <form id="deallocateForm">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <button type="submit" class="btn btn-danger">Request Deallocation</button>
                                </form>
                            </div>
                        <?php elseif ($profile['status'] === 'waiting'): ?>
                            <div class="alert alert-warning">
                                <h5>In Waiting List</h5>
                                <p>Your application is being reviewed by the admin.</p>
                                <form id="cancelForm">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <button type="submit" class="btn btn-secondary">Cancel Application</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <h5>No Room Allocated</h5>
                                <p>You can apply for a room using the button below.</p>
                                <form id="applyForm">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <button type="submit" class="btn btn-primary">Apply for Room</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Profile Form
            const profileForm = document.getElementById('profileForm');
            if (profileForm) {
                profileForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    try {
                        const response = await fetch('user_api.php?action=update_profile', {
                            method: 'POST',
                            body: new FormData(this)
                        });
                        const data = await response.json();
                        if (data.success) {
                            alert(data.message);
                            window.location.reload();
                        } else {
                            alert(data.message);
                        }
                    } catch (error) {
                        alert('An error occurred. Please try again.');
                    }
                });
            }

            // Room Application Form
            const applyForm = document.getElementById('applyForm');
            if (applyForm) {
                applyForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    if (confirm('Are you sure you want to apply for a room?')) {
                        try {
                            const response = await fetch('user_api.php?action=apply', {
                                method: 'POST',
                                body: new FormData(this)
                            });
                            const data = await response.json();
                            if (data.success) {
                                alert(data.message);
                                window.location.reload();
                            } else {
                                alert(data.message);
                            }
                        } catch (error) {
                            alert('An error occurred. Please try again.');
                        }
                    }
                });
            }

            // Cancel Application Form
            const cancelForm = document.getElementById('cancelForm');
            if (cancelForm) {
                cancelForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    if (confirm('Are you sure you want to cancel your application?')) {
                        try {
                            const response = await fetch('user_api.php?action=cancel', {
                                method: 'POST',
                                body: new FormData(this)
                            });
                            const data = await response.json();
                            if (data.success) {
                                alert(data.message);
                                window.location.reload();
                            } else {
                                alert(data.message);
                            }
                        } catch (error) {
                            alert('An error occurred. Please try again.');
                        }
                    }
                });
            }

            // Deallocation Form
            const deallocateForm = document.getElementById('deallocateForm');
            if (deallocateForm) {
                deallocateForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    if (confirm('Are you sure you want to request deallocation?')) {
                        try {
                            const response = await fetch('user_api.php?action=request_deallocation', {
                                method: 'POST',
                                body: new FormData(this)
                            });
                            const data = await response.json();
                            if (data.success) {
                                alert(data.message);
                                window.location.reload();
                            } else {
                                alert(data.message);
                            }
                        } catch (error) {
                            alert('An error occurred. Please try again.');
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>
