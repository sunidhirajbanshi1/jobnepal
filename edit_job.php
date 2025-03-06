<?php
session_start();
include '../auth/config.php';

// Check if the user is logged in and is a company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    header("Location: /jobnepal/auth/login.php");
    exit;
}

// Check if job_id is provided
$job_id = $_GET['job_id'] ?? null;
if (!$job_id) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'Job ID not provided.'];
    header("Location: manage_jobs.php");
    exit;
}

// Fetch job details
try {
    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ? AND company_id = ?");
    $stmt->execute([$job_id, $_SESSION['company_id']]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Job not found or access denied.'];
        header("Location: manage_jobs.php");
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'Error fetching job details: ' . $e->getMessage()];
    header("Location: manage_jobs.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $skills = $_POST['skills'];
    $experience = $_POST['experience'];
    $location = $_POST['location'];
    $salary = $_POST['salary'];
    $job_type = $_POST['job_type'];

    try {
        $stmt = $pdo->prepare("
            UPDATE jobs 
            SET title = ?, description = ?, skills = ?, experience = ?, location = ?, salary = ?, job_type = ?
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([$title, $description, $skills, $experience, $location, $salary, $job_type, $job_id, $_SESSION['company_id']]);

        $_SESSION['message'] = ['type' => 'success', 'text' => 'Job updated successfully.'];
        header("Location: manage_jobs.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Error updating job: ' . $e->getMessage()];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Job</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Your existing CSS styles */
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 90%;
            max-width: 600px;
            margin: 50px auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #673ab7;
            text-align: center;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group textarea {
            resize: vertical;
            height: 100px;
        }

        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
            text-decoration: none;
            color: white;
        }

        .btn-primary {
            background-color: #673ab7;
        }

        .btn-primary:hover {
            background-color: #512da8;
        }

        .message {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Edit Job</h1>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?= htmlspecialchars($_SESSION['message']['type']) ?>">
                <?= htmlspecialchars($_SESSION['message']['text']) ?>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="title">Job Title</label>
                <input type="text" id="title" name="title" value="<?= htmlspecialchars($job['title']) ?>" required>
            </div>

            <div class="form-group">
                <label for="description">Job Description</label>
                <textarea id="description" name="description" required><?= htmlspecialchars($job['description']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="skills">Required Skills</label>
                <input type="text" id="skills" name="skills" value="<?= htmlspecialchars($job['skills']) ?>" required>
            </div>

            <div class="form-group">
                <label for="experience">Experience</label>
                <input type="text" id="experience" name="experience" value="<?= htmlspecialchars($job['experience']) ?>" required>
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
                <label for="job_type">Job Type</label>
                <select id="job_type" name="job_type" required>
                    <option value="Full-time" <?= $job['job_type'] === 'Full-time' ? 'selected' : '' ?>>Full-time</option>
                    <option value="Part-time" <?= $job['job_type'] === 'Part-time' ? 'selected' : '' ?>>Part-time</option>
                    <option value="Remote" <?= $job['job_type'] === 'Remote' ? 'selected' : '' ?>>Remote</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Update Job</button>
        </form>
    </div>
</body>
</html>