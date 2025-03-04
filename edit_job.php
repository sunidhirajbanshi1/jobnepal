<?php
session_start();
include '../auth/config.php';

// Check if the user is logged in and is a company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    header("Location: /jobnepal/auth/login_company.php");
    exit;
}

// Check if the job ID is provided in the query string
if (!isset($_GET['job_id'])) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'Job ID is missing.'];
    header("Location: /jobnepal/company/manage_jobs.php");
    exit;
}

$job_id = $_GET['job_id'];
$company_id = $_SESSION['company_id']; // Ensure the company ID is set in the session

try {
    // Fetch the job details
    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ? AND company_id = ?");
    $stmt->execute([$job_id, $company_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Job not found or you do not have permission to edit it.'];
        header("Location: /jobnepal/company/manage_jobs.php");
        exit;
    }

    // Handle form submission for updating the job
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $salary = $_POST['skills'];
        $location = $_POST['location'];
        $experience = $_POST['experience'];
        $salary = $_POST['salary'];
        $job_type = $_POST['job_type'];

        // Update the job in the database
        $stmt = $pdo->prepare("
            UPDATE jobs 
            SET title = ?, description = ?, skills = ?, location = ?, salary = ?, job_type = ?
            WHERE id = ?
        ");
        $stmt->execute([$title, $description, $skills,$experience, $location, $salary, $job_type, $job_id]);

        $_SESSION['message'] = ['type' => 'success', 'text' => 'Job updated successfully!'];
        header("Location: /jobnepal/company/manage_jobs.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'An error occurred while updating the job.'];
    header("Location: /jobnepal/company/manage_jobs.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Job</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .edit-job-form {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #673ab7;
        }

        .btn {
            background-color: #673ab7;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #512da8;
        }
    </style>
</head>
<body>
    <div class="edit-job-form">
        <h2>Edit Job</h2>
        <form method="POST" action="edit_job.php?job_id=<?= htmlspecialchars($job_id) ?>">
            <div class="form-group">
                <label for="title">Job Title</label>
                <input type="text" id="title" name="title" value="<?= htmlspecialchars($job['title']) ?>" required>
            </div>

            <div class="form-group">
                <label for="description">Job Description</label>
                <textarea id="description" name="description" rows="6" required><?= htmlspecialchars($job['description']) ?></textarea>
            </div>
            <div class="form-group">
                <label for="skills">Skills</label>
                <input type="text" id="skills" name="skills" value="<?= htmlspecialchars($job['skills']) ?>" required>
            </div>

            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" id="location" name="location" value="<?= htmlspecialchars($job['location']) ?>" required>
            </div>

            <div class="form-group">
                <label for="salary">Salary</label>
                <input type="number" id="salary" name="salary" value="<?= htmlspecialchars($job['salary']) ?>" required>
            </div>
            <div class="form-group">
                <label for="experience">Experience</label>
                <input type="text" id="experience" name="experience" value="<?= htmlspecialchars($job['experience']) ?>" required>
            </div>


            <div class="form-group">
                <label for="job_type">Job Type</label>
                <select id="job_type" name="job_type" required>
                    <option value="Full-time" <?= $job['job_type'] === 'Full-time' ? 'selected' : '' ?>>Full-time</option>
                    <option value="Part-time" <?= $job['job_type'] === 'Part-time' ? 'selected' : '' ?>>Part-time</option>
                    <option value="Contract" <?= $job['job_type'] === 'Contract' ? 'selected' : '' ?>>Contract</option>
                    <option value="Internship" <?= $job['job_type'] === 'Internship' ? 'selected' : '' ?>>Internship</option>
                </select>
            </div>

            <button type="submit" class="btn">Update Job</button>
        </form>
    </div>
</body>
</html>