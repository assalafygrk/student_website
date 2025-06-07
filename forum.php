<?php
require_once 'config/database.php';
requireLogin();

$user = getCurrentUser();
$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// Handle new post
if ($_POST && isset($_POST['create_post'])) {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $department = $_POST['department'] ?? '';
    
    if ($title && $content) {
        $query = "INSERT INTO forum_posts (title, content, author_id, department) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$title, $content, $user['id'], $department])) {
            $message = 'Post created successfully!';
        } else {
            $error = 'Failed to create post.';
        }
    }
}

// Handle reply
if ($_POST && isset($_POST['reply_post'])) {
    $post_id = $_POST['post_id'] ?? '';
    $content = $_POST['reply_content'] ?? '';
    
    if ($post_id && $content) {
        $query = "INSERT INTO forum_replies (post_id, content, author_id) VALUES (?, ?, ?)";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$post_id, $content, $user['id']])) {
            $message = 'Reply posted successfully!';
        } else {
            $error = 'Failed to post reply.';
        }
    }
}

// Get forum posts
$department_filter = $_GET['department'] ?? '';
$query = "SELECT f.*, u.username FROM forum_posts f 
          JOIN users u ON f.author_id = u.id";
$params = [];

if ($department_filter) {
    $query .= " WHERE f.department = ?";
    $params[] = $department_filter;
}

$query .= " ORDER BY f.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get replies for each post
foreach ($posts as &$post) {
    $reply_query = "SELECT r.*, u.username FROM forum_replies r 
                    JOIN users u ON r.author_id = u.id 
                    WHERE r.post_id = ? ORDER BY r.created_at ASC";
    $reply_stmt = $db->prepare($reply_query);
    $reply_stmt->execute([$post['id']]);
    $post['replies'] = $reply_stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum - StudyHub</title>
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
                <h2>Create New Post</h2>
                
                <?php if ($message): ?>
                    <div class="message success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="message error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <input type="hidden" name="create_post" value="1">
                    
                    <div class="form-group">
                        <label for="title">Title:</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="department">Department:</label>
                        <select id="department" name="department">
                            <option value="<?php echo htmlspecialchars($user['department']); ?>"><?php echo htmlspecialchars($user['department']); ?></option>
                            <option value="General">General</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Content:</label>
                        <textarea id="content" name="content" rows="5" required></textarea>
                    </div>
                    
                    <button type="submit" class="btn">Create Post</button>
                </form>
            </div>

            <div class="card">
                <h2>Forum Posts</h2>
                
                <!-- Filter -->
                <form method="GET" style="margin-bottom: 2rem;">
                    <select name="department">
                        <option value="">All Departments</option>
                        <option value="General" <?php echo $department_filter === 'General' ? 'selected' : ''; ?>>General</option>
                        <option value="Computer Science" <?php echo $department_filter === 'Computer Science' ? 'selected' : ''; ?>>Computer Science</option>
                        <option value="Engineering" <?php echo $department_filter === 'Engineering' ? 'selected' : ''; ?>>Engineering</option>
                        <option value="Business" <?php echo $department_filter === 'Business' ? 'selected' : ''; ?>>Business</option>
                    </select>
                    <button type="submit" class="btn">Filter</button>
                    <a href="forum.php" class="btn btn-secondary">Clear</a>
                </form>
                
                <!-- Posts -->
                <?php if ($posts): ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="forum-post">
                            <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                            <div class="forum-meta">
                                Posted by <?php echo htmlspecialchars($post['username']); ?> 
                                in <?php echo htmlspecialchars($post['department']); ?> 
                                on <?php echo date('M j, Y g:i A', strtotime($post['created_at'])); ?>
                            </div>
                            <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                            
                            <!-- Replies -->
                            <?php if ($post['replies']): ?>
                                <h4>Replies:</h4>
                                <?php foreach ($post['replies'] as $reply): ?>
                                    <div class="reply">
                                        <div class="forum-meta">
                                            <?php echo htmlspecialchars($reply['username']); ?> - 
                                            <?php echo date('M j, Y g:i A', strtotime($reply['created_at'])); ?>
                                        </div>
                                        <p><?php echo nl2br(htmlspecialchars($reply['content'])); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <!-- Reply Form -->
                            <form method="POST" style="margin-top: 1rem;">
                                <input type="hidden" name="reply_post" value="1">
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                
                                <div class="form-group">
                                    <label for="reply_content_<?php echo $post['id']; ?>">Reply:</label>
                                    <textarea id="reply_content_<?php echo $post['id']; ?>" name="reply_content" rows="3" required></textarea>
                                </div>
                                
                                <button type="submit" class="btn">Post Reply</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No posts found.</p>
                <?php endif; ?>
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
