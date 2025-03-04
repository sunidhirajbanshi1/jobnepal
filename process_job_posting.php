<?php
session_start();
include 'auth/config.php';
include 'auth/check_company_login.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $company_id = $_SESSION['company_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $skills = $_POST['skills'];
    $experience = $_POST['experience'];
    $location = $_POST['location'];
    $salary = $_POST['salary'];

    try {
        $stmt = $pdo->prepare("INSERT INTO jobs (company_id, title, description,skills, location, salary, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$company_id, $title, $description,$skills,$experience, $location, $salary]);

        // Redirect back to the dashboard with a success message
        header("Location: company_dashboard.php?message=Job posted successfully");
        exit;
    } catch (PDOException $e) {
        // Handle the error
        header("Location: company_dashboard.php?error=Error posting job");
        exit;
    }
} else {
    // Redirect if accessed directly
    header("Location: company_dashboard.php");
    exit;
}
?>