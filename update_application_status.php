<?php
session_start();
include '../auth/config.php';

// Check if the user is logged in and is a company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

// Get data from the POST request
$application_id = $_POST['application_id'] ?? null;
$status = $_POST['status'] ?? null;
$job_id = $_POST['job_id'] ?? null;

// Validate input
if (!$application_id || !$status || !$job_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

try {
    // Check if the application belongs to the company's job
    $stmt = $pdo->prepare("
        SELECT ja.id 
        FROM job_applications ja
        JOIN jobs j ON ja.job_id = j.id
        WHERE ja.id = ? AND j.company_id = ?
    ");
    $stmt->execute([$application_id, $_SESSION['company_id']]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        echo json_encode(['success' => false, 'message' => 'Application not found or access denied.']);
        exit;
    }

    // Update the application status
    $stmt = $pdo->prepare("
        UPDATE job_applications 
        SET status = ? 
        WHERE id = ?
    ");
    $stmt->execute([$status, $application_id]);

    // Return success response
    echo json_encode(['success' => true, 'message' => 'Application status updated successfully.']);
} catch (PDOException $e) {
    // Log the error and return an error response
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred. Please try again.']);
}
?>