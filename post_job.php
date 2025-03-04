<?php
// post_job.php
session_start();
include '../auth/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    header("Location: ../auth/login.php");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO jobs (
                company_id, title, description,skills, location, 
                salary, job_type
            ) VALUES (?, ?, ?, ?, ?, ?,?)
        ");
        $stmt->execute([
            $_SESSION['company_id'],
            $_POST['title'],
            $_POST['description'],
            $_POST['skills'],
            $_POST['location'],
            $_POST['salary'],
            $_POST['job_type']
        ]);

        $_SESSION['message'] = ['type' => 'success', 'text' => 'Job posted successfully!'];
        header("Location: /jobnepal/company");
        exit;
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Error posting job. Please try again.'];
    }
}
?>

<style>
    .post-job-container {
        max-width: 800px;
        margin: 20px auto;
        padding: 30px;
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .section-title {
        font-size: 28px;
        margin-bottom: 25px;
        color: #333;
        text-align: center;
    }

    .message {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
    }

    .success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .job-form {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    label {
        font-size: 16px;
        margin-bottom: 5px;
        color: #555;
    }

    input[type="text"],
    input[type="number"],
    select,
    textarea {
        padding: 12px;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 16px;
    }

    input[type="text"]:focus,
    input[type="number"]:focus,
    select:focus,
    textarea:focus {
        outline: none;
        border-color: #007bff;
        box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
    }

    textarea {
        resize: vertical;
        height: 150px;
    }

    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 5px;
        font-size: 18px;
        cursor: pointer;
        transition: background-color 0.3s ease;
        text-decoration: none;
        text-align: center;
    }

    .btn-primary {
        background-color: #007bff;
        color: #fff;
    }

    .btn-primary:hover {
        background-color: #0056b3;
    }
</style>

<div class="post-job-container">
    <h2 class="section-title">Post a New Job</h2>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="message <?= $_SESSION['message']['type'] ?>">
            <?= $_SESSION['message']['text'] ?>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <form action="post_job.php" method="POST" class="job-form">
        <div class="form-group">
            <label for="title">Job Title</label>
            <input type="text" id="title" name="title" required>
        </div>

        <div class="form-group">
            <label for="location">Location</label>
            <input type="text" id="location" name="location" required>
        </div>

        <div class="form-group">
            <label for="job_type">Job Type</label>
            <select id="job_type" name="job_type" required>
                <option value="full-time">Full Time</option>
                <option value="part-time">Part Time</option>
                <option value="contract">Contract</option>
                <option value="internship">Internship</option>
            </select>
        </div>

        <div class="form-group">
            <label for="salary">Salary (Annual)</label>
            <input type="number" id="salary" name="salary" min="0" step="1000">
        </div>

        <div class="form-group">
            <label for="description">Job Description</label>
            <textarea id="description" name="description" rows="10" required></textarea>
        </div>
        <div class="form-group">
            <label for="skills">Skills</label>
            <textarea id="skills" name="skills" rows="10" required></textarea>
        </div>
        <div class="form-group">
            <label for="experience">Experience</label>
            <textarea id="experience" name="experience" rows="5" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Post Job</button>
    </form>
</div>