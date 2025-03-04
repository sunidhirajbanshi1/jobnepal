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
        /* General Styles */
        :root {
            --primary-color: #673ab7;
            --secondary-color: #512da8;
            --tertiary-color: #f39c12;
            --text-color: #333;
            --light-text-color: #777;
            --background-color: #f9f9f9;
            --card-background: #fff;
            --border-radius: 8px;
            --shadow-color: rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: Arial, sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
        }

        h2 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 2rem;
        }

        /* Table Styles */
        .applications-container {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: var(--card-background);
            box-shadow: 0 2px 4px var(--shadow-color);
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        th, td {
            padding: 1rem;
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

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }

        .status-badge.pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-badge.accepted {
            background-color: #d4edda;
            color: #155724;
        }

        .status-badge.rejected {
            background-color: #f8d7da;
            color: #721c24;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.3s ease;
        }

        .btn.accept {
            background-color: #4caf50;
            color: white;
        }

        .btn.reject {
            background-color: #f44336;
            color: white;
        }

        .btn.view-resume {
            background-color: var(--primary-color);
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            background: var(--card-background);
            border-radius: var(--border-radius);
            box-shadow: 0 2px 4px var(--shadow-color);
        }

        .empty-state i {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            position: relative;
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: var(--border-radius);
        }

        .close {
            position: absolute;
            top: 0;
            right: 0;
            padding: 10px;
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .resume-content {
            white-space: pre-wrap;
            word-wrap: break-word;
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
                                    <button class="btn view-resume" onclick="viewResume('<?= htmlspecialchars($application['resume']) ?>')">View Resume</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Modal for Resume -->
    <div id="resumeModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">×</span>
            <h3>Resume</h3>
            <div class="resume-content" id="resumeContent"></div>
        </div>
    </div>

    <script>
        function viewResume(resume) {
            const modal = document.getElementById('resumeModal');
            const resumeContent = document.getElementById('resumeContent');
            resumeContent.textContent = resume;
            modal.style.display = "flex";
        }

        function closeModal() {
            const modal = document.getElementById('resumeModal');
            modal.style.display = "none";
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            const modal = document.getElementById('resumeModal');
            if (event.target === modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>

</html>