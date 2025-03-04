<?php
// profile.php
session_start();
include '../auth/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'job_seeker') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch job seeker profile data
    $stmt = $pdo->prepare("
        SELECT js.*, u.email
        FROM job_seekers js
        JOIN users u ON js.user_id = u.id
        WHERE js.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    // Handle profile update
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Process profile picture upload
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_pic']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                $upload_path = '../uploads/profile_pics/';
                $new_filename = uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_path . $new_filename);
                $profile_pic = $upload_path . $new_filename;
            }
        }

        // Process CV upload
        $cv_path = $profile['resume']; // Default to existing CV path
        if (isset($_FILES['cv']) && $_FILES['cv']['error'] === 0) {
            $allowed_cv = ['pdf', 'doc', 'docx'];
            $cv_filename = $_FILES['cv']['name'];
            $cv_ext = strtolower(pathinfo($cv_filename, PATHINFO_EXTENSION));

            if (in_array($cv_ext, $allowed_cv)) {
                $upload_cv_path = '../uploads/cvs/';
                $new_cv_filename = uniqid() . '.' . $cv_ext;
                move_uploaded_file($_FILES['cv']['tmp_name'], $upload_cv_path . $new_cv_filename);
                $cv_path = $upload_cv_path . $new_cv_filename;
            }
        }

        // Update profile data
        $stmt = $pdo->prepare("
            UPDATE job_seekers 
            SET name = ?, skills = ?, experience = ?, resume = ?
            WHERE user_id = ?
        ");
        $stmt->execute([
            $_POST['name'],
            $_POST['skills'],
            $_POST['experience'],
            $cv_path, // Updated CV path
            $user_id
        ]);

        $_SESSION['message'] = ['type' => 'success', 'text' => 'Profile updated successfully!'];
        header("Location: /jobnepal/user");
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'An error occurred. Please try again.'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Job Applications</title>
    <style>
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }

        .profile-form {
            background: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 4px var(--shadow-color);
        }

        .profile-pic-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .current-profile-pic {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-color);
            box-shadow: 0 2px 4px var(--shadow-color);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-color);
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .form-group input[disabled] {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }
    </style>
</head>

<body>

    <div class="profile-container">
        <h2 class="section-title">My Profile</h2>

        <form action="profile.php" method="POST" enctype="multipart/form-data" class="profile-form">

            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($profile['name']) ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" value="<?= htmlspecialchars($profile['email']) ?>" disabled>
            </div>

            <div class="form-group">
                <label for="skills">Skills</label>
                <textarea id="skills" name="skills" rows="4"><?= htmlspecialchars($profile['skills']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="experience">Experience</label>
                <textarea id="experience" name="experience" rows="6"><?= htmlspecialchars($profile['experience']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="cv">Upload CV (PDF, DOC, DOCX)</label>
                <input type="file" id="cv" name="cv" accept=".pdf,.doc,.docx">
            </div>

            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>

</body>

</html>