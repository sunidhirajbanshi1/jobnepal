<?php
session_start();
include '../auth/config.php';

// Check if the user is logged in and is a company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    header("Location: /jobnepal/auth/login.php");
    exit;
}

// Check if job_id is provided
if (!isset($_POST['delete_job'])) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'Job ID not provided.'];
    header("Location: manage_jobs.php");
    exit;
}

$job_id = $_POST['delete_job'];

try {
    // Delete the job
    $stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ? AND company_id = ?");
    $stmt->execute([$job_id, $_SESSION['company_id']]);

    $_SESSION['message'] = ['type' => 'success', 'text' => 'Job deleted successfully.'];
} catch (PDOException $e) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'Error deleting job: ' . $e->getMessage()];
}

header("Location: manage_jobs.php");
exit;
?>