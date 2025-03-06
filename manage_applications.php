<?php
session_start();
include '../auth/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    header("Location: ../auth/login.php");
    exit;
}

$job_id = $_GET['job_id'] ?? null;

if (!$job_id) {
    echo "Job ID not provided.";
    exit;
}

try {
    // Fetch job details
    $stmt = $pdo->prepare("
        SELECT * FROM jobs 
        WHERE id = ? AND company_id = ?
    ");
    $stmt->execute([$job_id, $_SESSION['company_id']]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        echo "Job not found or access denied.";
        exit;
    }

    // Fetch applications with applicant details
    $stmt = $pdo->prepare("
        SELECT 
            ja.id as application_id,
            ja.status,
            ja.applied_at,
            js.name as applicant_name,
            js.profile_pic,
            js.resume,
            js.skills,
            js.experience
        FROM job_applications ja
        JOIN job_seekers js ON ja.job_seeker_id = js.id
        WHERE ja.job_id = ?
        ORDER BY ja.applied_at DESC
    ");
    $stmt->execute([$job_id]);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle status updates
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['application_id'], $_POST['status'])) {
        $stmt = $pdo->prepare("
            UPDATE job_applications 
            SET status = ? 
            WHERE id = ? AND job_id = ?
        ");
        $stmt->execute([$_POST['status'], $_POST['application_id'], $job_id]);

        header("Location: manage_applications.php?job_id=" . $job_id);
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo "An error occurred. Please try again.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Job Applications</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
        integrity="YOUR_SRI_HASH" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* Your existing CSS styles */
        /* Reset some default styling */
body, h2, table {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    color: #333;
    line-height: 1.6;
    padding: 20px;
}

/* Container for applications */
.applications-container {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

/* Header */
h2 {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 20px;
    color: #333;
}

/* Table Styling */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

table th, table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

table th {
    background-color: #f4f4f4;
    color: #555;
    font-size: 14px;
}

table td {
    font-size: 14px;
    color: #333;
}

/* Status badge colors */
.status-badge {
    padding: 6px 12px;
    border-radius: 12px;
    font-size: 12px;
    text-transform: capitalize;
    font-weight: bold;
}

.status-badge.accepted {
    background-color: #4caf50;
    color: white;
}

.status-badge.rejected {
    background-color: #f44336;
    color: white;
}

.status-badge.pending {
    background-color: #ff9800;
    color: white;
}

/* Empty state styling */
.empty-state {
    text-align: center;
    padding: 50px 0;
    color: #777;
}

.empty-state i {
    color: #ddd;
}

.empty-state p {
    font-size: 18px;
    margin-top: 10px;
}

/* Action buttons */
.action-buttons {
    display: flex;
    align-items: center;
    gap: 10px;
}

.action-buttons button,
.action-buttons a {
    padding: 8px 16px;
    border-radius: 4px;
    text-align: center;
    font-size: 14px;
    text-decoration: none;
}

.action-buttons button {
    cursor: pointer;
    border: none;
    transition: background-color 0.3s ease;
}

.action-buttons button.accept {
    background-color: #4caf50;
    color: white;
}

.action-buttons button.reject {
    background-color: #f44336;
    color: white;
}

.action-buttons button.accept:hover {
    background-color: #45a049;
}

.action-buttons button.reject:hover {
    background-color: #d32f2f;
}

.action-buttons a.view-resume {
    background-color: #2196f3;
    color: white;
    padding: 8px 16px;
    border-radius: 4px;
    text-align: center;
}

.action-buttons a.view-resume:hover {
    background-color: #1976d2;
}

/* Responsive Styling */
@media (max-width: 768px) {
    table th, table td {
        font-size: 12px;
        padding: 10px;
    }

    .action-buttons {
        flex-direction: column;
        gap: 5px;
    }

    .action-buttons button,
    .action-buttons a {
        width: 100%;
        padding: 10px;
    }
}

        
    </style>
</head>

<body>
    <div class="applications-container">
        <h2>Applications for <?= htmlspecialchars($job['title']) ?></h2>

        <?php if (empty($applications)): ?>
            <div class="empty-state">
                <i class="fas fa-file-alt fa-3x"></i>
                <p>No applications received yet.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Applicant Name</th>
                        <th>Skills</th>
                        <th>Experience</th>
                        <th>Applied At</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $application): ?>
                        <tr>
                            <td><?= htmlspecialchars($application['applicant_name']) ?></td>
                            <td><?= htmlspecialchars($application['skills']) ?></td>
                            <td><?= htmlspecialchars($application['experience']) ?></td>
                            <td><?= date('M d, Y', strtotime($application['applied_at'])) ?></td>
                            <td>
                                <span class="status-badge <?= strtolower($application['status']) ?>">
                                    <?= htmlspecialchars($application['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <form method="POST" action="update_application_status.php" style="display: inline;">
                                        <input type="hidden" name="application_id" value="<?= $application['application_id'] ?>">
                                        <input type="hidden" name="job_id" value="<?= $job_id ?>">
                                        <button type="submit" name="status" value="accepted" class="btn accept">Accept</button>
                                        <button type="submit" name="status" value="rejected" class="btn reject">Reject</button>
                                    </form>
                                    <a href="../uploads/resumes/<?= htmlspecialchars($application['resume']) ?>" class="btn view-resume" download>Download Resume</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>

</html>