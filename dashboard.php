<?php
require_once 'config/database.php';
requireLogin();

$user = getCurrentUser();
$database = new Database();
$db = $database->getConnection();

// Get recent materials
$query = "SELECT m.*, u.username FROM materials m 
          JOIN users u ON m.uploaded_by = u.id 
          ORDER BY m.created_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent announcements
$query = "SELECT a.*, u.username FROM announcements a 
          JOIN users u ON a.created_by = u.id 
          ORDER BY a.created_at DESC LIMIT 3";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent forum posts
$query = "SELECT f.*, u.username FROM forum_posts f 
          JOIN users u ON f.author_id = u.id 
          ORDER BY f.created_at DESC LIMIT 3";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - StudyHub</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1>StudyHub</h1>
                </div>
                <nav>
                    <ul>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="materials.php">Materials</a></li>
                        <li><a href="forum.php">Forum</a></li>
                        <li><a href="assignments.php">Assignments</a></li>
                        <li><a href="announcements.php">Announcements</a></li>
                        <li><a href="timetable.php">Timetable</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="card">
                <h2>Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h2>
                <p>Department: <?php echo htmlspecialchars($user['department']); ?></p>
            </div>

            <div class="grid">
                <div class="card">
                    <h2>Recent Materials</h2>
                    <?php if ($recent_materials): ?>
                        <?php foreach ($recent_materials as $material): ?>
                            <div style="border-bottom: 1px solid #eee; padding: 0.5rem 0;">
                                <strong><?php echo htmlspecialchars($material['title']); ?></strong><br>
                                <small>by <?php echo htmlspecialchars($material['username']); ?> - 
                                <?php echo date('M j, Y', strtotime($material['created_at'])); ?></small>
                            </div>
                        <?php endforeach; ?>
                        <a href="materials.php" class="btn" style="margin-top: 1rem;">View All Materials</a>
                    <?php else: ?>
                        <p>No materials uploaded yet.</p>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <h2>Recent Announcements</h2>
                    <?php if ($recent_announcements): ?>
                        <?php foreach ($recent_announcements as $announcement): ?>
                            <div style="border-bottom: 1px solid #eee; padding: 0.5rem 0;">
                                <strong><?php echo htmlspecialchars($announcement['title']); ?></strong><br>
                                <small><?php echo date('M j, Y', strtotime($announcement['created_at'])); ?></small>
                            </div>
                        <?php endforeach; ?>
                        <a href="announcements.php" class="btn" style="margin-top: 1rem;">View All Announcements</a>
                    <?php else: ?>
                        <p>No announcements yet.</p>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <h2>Recent Forum Posts</h2>
                    <?php if ($recent_posts): ?>
                        <?php foreach ($recent_posts as $post): ?>
                            <div style="border-bottom: 1px solid #eee; padding: 0.5rem 0;">
                                <strong><?php echo htmlspecialchars($post['title']); ?></strong><br>
                                <small>by <?php echo htmlspecialchars($post['username']); ?> - 
                                <?php echo date('M j, Y', strtotime($post['created_at'])); ?></small>
                            </div>
                        <?php endforeach; ?>
                        <a href="forum.php" class="btn" style="margin-top: 1rem;">View Forum</a>
                    <?php else: ?>
                        <p>No forum posts yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2024 StudyHub. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
