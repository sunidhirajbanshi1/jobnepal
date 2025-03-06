<?php
session_start();
include '../auth/config.php';

// Check if the user is logged in and is a company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    header("Location: /jobnepal/auth/login.php");
    exit;
}

// Fetch all jobs posted by the company
try {
    $company_id = $_SESSION['company_id'];
    $sql = "SELECT
                j.id,
                j.title,
                j.description,
                j.skills,
                j.experience,
                j.location,
                j.salary,
                j.job_type,
                j.created_at,
                (SELECT COUNT(*) FROM job_applications ja WHERE ja.job_id = j.id) AS application_count
            FROM jobs j
            WHERE j.company_id = ?
            ORDER BY j.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$company_id]);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'Error fetching jobs: ' . $e->getMessage()];
    error_log("Error fetching jobs: " . $e->getMessage());
    $jobs = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Jobs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Your existing CSS styles */
        /* General Styles */
:root {
    --primary-color: #673ab7; /* Purple */
    --secondary-color: #512da8; /* Darker Purple */
    --background-color: #f8f9fa; /* Light Gray */
    --card-background: #fff; /* White */
    --text-color: #333; /* Dark Gray */
    --light-text-color: #777; /* Light Gray */
    --border-radius: 8px; /* Rounded corners */
    --shadow-color: rgba(0, 0, 0, 0.1); /* Subtle shadow */
}

body {
    font-family: 'Roboto', sans-serif;
    background-color: var(--background-color);
    color: var(--text-color);
    margin: 0;
    padding: 0;
}

h1 {
    color: var(--primary-color);
    text-align: center;
    margin-bottom: 20px;
}

.container {
    width: 140%;
    max-width: 1200px;
    margin: 50px auto;
    background-color: var(--card-background);
    padding: 20px;
    border-radius: var(--border-radius);
    box-shadow: 0 4px 8px var(--shadow-color);
}

/* Table Styles */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th, td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

th {
    background-color: var(--primary-color);
    color: white;
    font-weight: bold;
}

tr:hover {
    background-color: #f5f5f5;
}

/* Buttons and Actions */
.actions {
    display: flex;
    gap: 10px;
}

.btn {
    padding: 8px 12px;
    border: none;
    border-radius: var(--border-radius);
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.3s ease;
    text-decoration: none;
    color: white;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.btn-edit {
    background-color: #28a745; /* Green */
}

.btn-edit:hover {
    background-color: #218838; /* Darker Green */
}

.btn-delete {
    background-color: #dc3545; /* Red */
}

.btn-delete:hover {
    background-color: #c82333; /* Darker Red */
}

.btn-view {
    background-color: var(--primary-color);
}

.btn-view:hover {
    background-color: var(--secondary-color);
}

.btn-view i {
    margin-right: 5px;
}

/* Message Styles */
.message {
    padding: 12px;
    margin-bottom: 20px;
    border-radius: var(--border-radius);
    text-align: center;
}

.message.success {
    background-color: #d4edda; /* Light Green */
    color: #155724; /* Dark Green */
    border: 1px solid #c3e6cb;
}

.message.danger {
    background-color: #f8d7da; /* Light Red */
    color: #721c24; /* Dark Red */
    border: 1px solid #f5c6cb;
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        width: 95%;
        padding: 10px;
    }

    table {
        display: block;
        overflow-x: auto;
    }

    .actions {
        flex-direction: column;
        gap: 5px;
    }

    .btn {
        width: 100%;
        justify-content: center;
    }
}
    </style>
</head>
<body>
    <div class="container">
        <h1>Manage Jobs</h1>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?= htmlspecialchars($_SESSION['message']['type']) ?>">
                <?= htmlspecialchars($_SESSION['message']['text']) ?>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <?php if (empty($jobs)): ?>
            <p>No jobs found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Location</th>
                        <th>Salary</th>
                        <th>Description</th>
                        <th>Skills</th>
                        <th>Experience</th>
                        <th>Type</th>
                        <th>Created At</th>
                        <th>Actions</th>
                        <th> Manage Applications</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jobs as $job): ?>
                        <tr>
                            <td><?= htmlspecialchars($job['title']) ?></td>
                            <td><?= htmlspecialchars($job['location']) ?></td>
                            <td>Rs. <?= htmlspecialchars(number_format($job['salary'], 2)) ?></td>
                            <td><?= htmlspecialchars($job['description']) ?></td>
                            <td><?= htmlspecialchars($job['skills']) ?></td>
                            <td><?= htmlspecialchars($job['experience']) ?></td>
                            <td><?= htmlspecialchars($job['job_type']) ?></td>
                            <td><?= htmlspecialchars(date('Y-m-d', strtotime($job['created_at']))) ?></td>
                            
                            <td class="actions">
                                <a href="edit_job.php?job_id=<?= htmlspecialchars($job['id']) ?>" class="btn btn-edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form method="post" action="delete_job.php" style="display: inline;">
                                    <input type="hidden" name="delete_job" value="<?= htmlspecialchars($job['id']) ?>">
                                    <button type="submit" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this job?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                    <td>
                                <a href="manage_applications.php?job_id=<?= htmlspecialchars($job['id']) ?>" class="btn btn-view">
                                    <i class="fas fa-eye"></i> View (<?= htmlspecialchars($job['application_count']) ?>)
                                </a>
                            </td>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>