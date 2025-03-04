<?php
session_start();
include '../auth/config.php';

// Check if user is logged in and is a company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    header("Location: ../auth/login.php"); // Redirect to login page if not logged in or not a company
    exit;
}

// Get user data from the session
$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT id, name, company_website, company_description, logo FROM companies WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$company) {
        // If company data not found, handle error (e.g., redirect to login or display an error message)
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Company data not found.'];
        header("Location: ../auth/login.php");
        exit;
    }

    // Set company id to session if not already set.
    if (!isset($_SESSION['company_id'])) {
        $_SESSION['company_id'] = $company['id'];
    }
    $company_id = $_SESSION['company_id'];

    // 2. Get recent job postings for the company
    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE company_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$company['id']]);  // Use $company['id'] here
    $recent_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Get total job seekers
$stmt = $pdo->query("SELECT COUNT(*) FROM job_seekers");
$total_job_seekers = $stmt->fetchColumn();

// Get total jobs posted by the company
$stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE company_id = ?");
$stmt->execute([$company_id]);
$total_jobs = $stmt->fetchColumn();

// Get total applications received
$stmt = $pdo->prepare("SELECT COUNT(*) FROM job_applications WHERE job_id IN (SELECT id FROM jobs WHERE company_id = ?)");
$stmt->execute([$company_id]);
$total_applications = $stmt->fetchColumn();


    // 3. Get application counts for each job
    $application_counts = [];
    foreach ($recent_jobs as $job) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM job_applications WHERE job_id = ?");
        $stmt->execute([$job['id']]);
        $application_counts[$job['id']] = $stmt->fetchColumn();
    }
} catch (PDOException $e) {
    // Handle database errors
    error_log("Database error: " . $e->getMessage());
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'A database error occurred. Please try again later.'];
    header("Location: ../auth/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap"
        rel="stylesheet">
    <style>
        /* General Styles */
        :root {
            --primary-color: #673ab7;
            --secondary-color: #512da8;
            --background-color: #f5f0ff;
            --text-color: #333333;
            --light-gray: #e0e0e0;
            --shadow-color: rgba(0, 0, 0, 0.1);
            --border-radius: 10px;
            --header-bg: #303f9f;
            --nav-bg: #ede7f6;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        /* Dashboard Container */
        /* .dashboard-container {
            display: grid;
            grid-template-columns: 800px 1fr;
            min-height: 100vh;
            transition: all 0.3s ease;
        } */

        /* Sidebar Styles */
        .sidebar {
            background-color: var(--nav-bg);
            padding: 2rem;
            box-shadow: 2px 0 10px var(--shadow-color);
            position: fixed;
            width: 250px;
            height: 100%;
            z-index: 100;
            transition: transform 0.3s ease-in-out;
            overflow-y: auto;
            /* Add scroll for long content */
        }

        .profile-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-pic {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
            box-shadow: 0 4px 8px var(--shadow-color);
        }

        .profile-section h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--secondary-color);
        }

        .profile-section p {
            font-size: 0.9rem;
            color: #777;
        }

        /* Nav Menu */
        .nav-menu {
            list-style: none;
            padding: 0;
        }

        .nav-item {
            margin-bottom: 0.75rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.8rem 1.2rem;
            text-decoration: none;
            color: var(--secondary-color);
            border-radius: var(--border-radius);
            transition: all 0.3s ease;
        }

        .nav-link i {
            font-size: 1.2rem;
            color: var(--primary-color);
            transition: color 0.3s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }

        .nav-link:hover i,
        .nav-link.active i {
            color: white;
        }

        /* Main Content */
        .main-content {
            padding: 3rem;
            margin-left: 250px;
            transition: all 0.3s ease;
            background-color: var(--background-color);
            overflow: auto;
            /* Add scroll for long content */
        }

        .section-title {
            font-size: 2rem;
            margin-bottom: 2rem;
            color: var(--secondary-color);
            position: relative;
            padding-bottom: 0.5rem;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .section-title::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 3px;
            background-color: var(--primary-color);
        }

        /* Recent Jobs */
        .recent-jobs {
            background-color: #fff;
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 8px var(--shadow-color);
            margin-bottom: 2rem;
        }

        .job-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .job-card {
            padding: 1.5rem;
            border: 1px solid var(--light-gray);
            border-radius: var(--border-radius);
            box-shadow: 0 2px 5px var(--shadow-color);
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .job-card:hover {
            transform: translateY(-5px);
        }

        .job-card h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }

        .job-card p {
            font-size: 0.9rem;
            color: #777;
        }

        .application-count {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .application-count i {
            color: var(--primary-color);
        }

        .btn {
            background-color: var(--primary-color);
            color: white;
            padding: 0.75rem 1.2rem;
            border: none;
            border-radius: var(--border-radius);
            text-decoration: none;
            text-align: center;
            display: inline-block;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: var(--secondary-color);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: static;
                width: 100%;
                height: auto;
                padding: 1rem;
                overflow-y: visible;
                /* Remove scroll on smaller screens */
            }

            .main-content {
                margin-left: 0;
                padding: 1.5rem;
            }

            .job-list {
                grid-template-columns: 1fr;
            }
        }

        /* Specific Adjustments for Image and Title */
        .profile-pic {
            max-width: 100px;
            /* Make sure it fits on smaller screens */
            max-height: 100px;
            width: auto;
            /* Maintain aspect ratio */
            height: auto;
        }

        #home-content {
            min-height: 500px;
            /* Adjust as needed */
        }

        /* Styles for the Header 2 */
        .header-container {
            background-color: var(--header-bg);
            color: white;
            padding: 1rem;
            text-align: center;
            width: 100%;
            z-index: 1000;
            position: sticky;
            top: 0;
        }

        .header-container h1 {
            margin: 0;
            font-size: 2rem;
        }

        .delete-job-btn {
            background-color: #dc3545; /* Red color */
            color: white;
            padding: 0.5rem 0.75rem;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 0.5rem;
            font-size: 0.8rem;
        }

        .delete-job-btn:hover {
            background-color: #c82333;
        }
        /* Add styles for the dashboard stats */
        /* Dashboard Stats Section */


/* Dashboard Stats Section */
.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); /* Smaller min-width */
    gap: 1rem; /* Reduced gap */
    margin-bottom: 1.5rem; /* Reduced margin */
}

.stat-card {
    background: #fff;
    padding: 1rem; /* Reduced padding */
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    text-align: center;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-card i {
    font-size: 1.5rem; /* Smaller icon size */
    color: #673ab7;
    margin-bottom: 0.5rem;
}

.stat-card h3 {
    font-size: 1.25rem; /* Smaller heading size */
    margin-bottom: 0.5rem;
    color: #333;
}

.stat-card p {
    font-size: 0.9rem; /* Smaller text size */
    color: #777;
}
        
        
    </style>
   
</head>

<body>

    <?php include '../includes/header.php'; ?>

    <div class="dashboard-container">
        <aside class="sidebar" id="sidebar">
            <div class="profile-section">
                <img src="<?= htmlspecialchars($company['logo'] ?? 'https://img.freepik.com/free-vector/technology-logo-template-with-abstract-shapes_23-2148240852.jpg?t=st=1740262020~exp=1740265620~hmac=a45bacb1f9ab02ac81046f50a1e731b38e2fdc870b99455d2bf9ea8906251911&w=740') ?>"
                    alt="Company Logo" class="profile-pic">
                <h3><?= htmlspecialchars($company['name'] ?? " ") ?></h3>
                <p><?= htmlspecialchars($company['email'] ?? "position@company.com") ?></p>
</div>



            <nav>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="#" class="nav-link active" data-target="home">
                            <i class="fas fa-home"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" data-target="post_job">
                            <i class="fas fa-plus-circle"></i>
                            Post a Job
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" data-target="manage_jobs">
                            <i class="fas fa-briefcase"></i>
                            Manage Jobs
                        </a>
                    </li>
                    <li class="nav-item">
                    <a href="manage_applications.php?job_id=<?= $job['id'] ?>">
                    <i class="fas fa-file-alt"></i>
                           Manage Application
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" data-target="company_profile">
                            <i class="fas fa-building"></i>
                            Company Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" data-target="settings">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                class="bi bi-gear" viewBox="0 0 16 16">
                                <path
                                    d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492M5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0" />
                                <path
                                    d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115z" />
                            </svg>
                            Settings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/jobnepal/auth/logout.php" data-target="logout" class="nav-link">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        
        <main class="main-content" id="main-content">
    <div id="home-content">
        <div style="display: flex; align-items: center; justify-content: start;">
            <h1 class="section-title">Welcome Back, <?= htmlspecialchars($company['name']) ?>!</h1>
        </div>

        <!-- Dashboard Stats -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <h3><?= $total_job_seekers ?></h3>
                <p>Total Job Seekers</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-briefcase"></i>
                <h3><?= $total_jobs ?></h3>
                <p>Total Jobs Posted</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-file-alt"></i>
                <h3><?= $total_applications ?></h3>
                <p>Total Applications</p>
            </div>
        </div>

        <!-- Recent Job Postings -->
        <section class="recent-jobs">
            <h2 class="section-title">Recent Job Postings</h2>
            <div class="job-list">
                <?php if (empty($recent_jobs)): ?>
                    <p>No recent job postings.</p>
                <?php else: ?>
                    <?php foreach ($recent_jobs as $job): ?>
                        <div class="job-card">
                            <div>
                                <h3><?= htmlspecialchars($job['title']) ?></h3>
                                <p><?= htmlspecialchars($job['location']) ?></p>
                                <p>Salary: <?= htmlspecialchars($job['salary']) ?></p>
                            </div>
                            <div class="application-count">
                                <i class="fas fa-users"></i>
                                <span><?= $application_counts[$job['id']] ?? 0 ?> Applications</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>
    </div>

    <script>
        const navLinks = document.querySelectorAll('.nav-link');
        const mainContent = document.getElementById('main-content');

        navLinks.forEach(link => {
            link.addEventListener('click', function (event) {
                event.preventDefault();

                const target = this.getAttribute('data-target');

                navLinks.forEach(link => link.classList.remove('active'));
                this.classList.add('active');

                if (target === 'home') {
                    mainContent.innerHTML = `
                    <div style="display: flex; align-items: center; justify-content: start;">
                        <h1 class="section-title">Welcome Back, <?= htmlspecialchars($company['name']) ?>!</h1>
                    </div>

                    <!-- Recent Job Postings -->
                    <section class="recent-jobs">
                        <h2 class="section-title">Recent Job Postings</h2>
                        <div class="job-list">
                            <?php if (empty($recent_jobs)): ?>
                                <p>No recent job postings.</p>
                            <?php else: ?>
                                <?php foreach ($recent_jobs as $job): ?>
                                    <div class="job-card">
                                        <div>
                                            <h3><?= htmlspecialchars($job['title']) ?></h3>
                                            <p><?= htmlspecialchars($job['location']) ?></p>
                                            <p>Salary: <?= htmlspecialchars($job['salary']) ?></p>
                                        </div>
                                        <div class="application-count">
                                            <i class="fas fa-users"></i>
                                            <span><?= $application_counts[$job['id']] ?? 0 ?> Applications</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>`;
                }

                else if (target == "logout") {
                    fetch('/jobnepal/auth/logout.php')
                        .then(() => {
                            window.location.href = '/jobnepal/'; 
                        })
                        .catch(error => console.error('Logout failed:', error));
                }
                
                else {
                    fetch(target + '.php')
                        .then(response => response.text())
                        .then(data => {
                            mainContent.innerHTML = data;
                        })
                        .catch(error => {
                            console.error('Error loading content:', error);
                            mainContent.innerHTML = '<p>Error loading content.</p>';
                        });
                }
            });
        });

        // Add sidebar toggle functionality (if needed)
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }
        
    </script>
</body>

</html>