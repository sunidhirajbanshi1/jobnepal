<?php
session_start();
include '../auth/config.php';

// Check if the user is logged in and is a company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    header("Location: /jobnepal/auth/login.php");
    exit;
}

// Check if the job ID is provided in the request
if (!isset($_POST['delete_job'])) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'Invalid request.'];
    header("Location: manage_jobs.php");
    exit;
}

$job_id = $_POST['delete_job'];
$company_id = $_SESSION['company_id'];

try {
    // Verify that the job belongs to the company before deleting
    $sql = "SELECT id FROM jobs WHERE id = ? AND company_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$job_id, $company_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Job not found or you do not have permission to delete this job.'];
        header("Location: manage_jobs.php");
        exit;
    }

    // Delete the job
    $delete_sql = "DELETE FROM jobs WHERE id = ?";
    $delete_stmt = $pdo->prepare($delete_sql);
    $delete_stmt->execute([$job_id]);

    $_SESSION['message'] = ['type' => 'success', 'text' => 'Job deleted successfully.'];
    header("Location: manage_jobs.php");
    exit;

} catch (PDOException $e) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'Error deleting job: ' . $e->getMessage()];
    error_log("Error deleting job: " . $e->getMessage());
    header("Location: manage_jobs.php");
    exit;
}
?>