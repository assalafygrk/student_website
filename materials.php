<?php
require_once 'config/database.php';
requireLogin();

$user = getCurrentUser();
$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// Handle file upload
if ($_POST && isset($_FILES['file'])) {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $department = $_POST['department'] ?? $user['department'];
    
    $file = $_FILES['file'];
    $upload_dir = 'uploads/materials/';
    
    // Create upload directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            $query = "INSERT INTO materials (title, description, file_name, file_path, file_size, uploaded_by, department, subject) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$title, $description, $file['name'], $file_path, $file['size'], $user['id'], $department, $subject])) {
                $message = 'File uploaded successfully!';
            } else {
                $error = 'Failed to save file information.';
            }
        } else {
            $error = 'Failed to upload file.';
        }
    } else {
        $error = 'File upload error.';
    }
}

// Get materials
$department_filter = $_GET['department'] ?? '';
$subject_filter = $_GET['subject'] ?? '';

$query = "SELECT m.*, u.username FROM materials m 
          JOIN users u ON m.uploaded_by = u.id";
$params = [];

if ($department_filter) {
    $query .= " WHERE m.department = ?";
    $params[] = $department_filter;
}

if ($subject_filter) {
    if ($department_filter) {
        $query .= " AND m.subject = ?";
    } else {
        $query .= " WHERE m.subject = ?";
    }
    $params[] = $subject_filter;
}

$query .= " ORDER BY m.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique departments and subjects for filters
$dept_query = "SELECT DISTINCT department FROM materials ORDER BY department";
$dept_stmt = $db->prepare($dept_query);
$dept_stmt->execute();
$departments = $dept_stmt->fetchAll(PDO::FETCH_COLUMN);

$subj_query = "SELECT DISTINCT subject FROM materials ORDER BY subject";
$subj_stmt = $db->prepare($subj_query);
$subj_stmt->execute();
$subjects = $subj_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materials - StudyHub</title>
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
                <h2>Upload Material</h2>
                
                <?php if ($message): ?>
                    <div class="message success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="message error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">Title:</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject:</label>
                        <input type="text" id="subject" name="subject" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="department">Department:</label>
                        <select id="department" name="department">
                            <option value="<?php echo htmlspecialchars($user['department']); ?>"><?php echo htmlspecialchars($user['department']); ?></option>
                            <option value="All Departments">All Departments</option>
                        </select>
                    </div>
                    
                    <div class="file-upload">
                        <input type="file" id="file" name="file" required>
                        <label for="file">Choose File to Upload</label>
                    </div>
                    
                    <button type="submit" class="btn">Upload Material</button>
                </form>
            </div>

            <div class="card">
                <h2>Browse Materials</h2>
                
                <!-- Filters -->
                <form method="GET" style="margin-bottom: 2rem;">
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <select name="department">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>" 
                                        <?php echo $department_filter === $dept ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <select name="subject">
                            <option value="">All Subjects</option>
                            <?php foreach ($subjects as $subj): ?>
                                <option value="<?php echo htmlspecialchars($subj); ?>" 
                                        <?php echo $subject_filter === $subj ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subj); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <button type="submit" class="btn">Filter</button>
                        <a href="materials.php" class="btn btn-secondary">Clear</a>
                    </div>
                </form>
                
                <!-- Materials List -->
                <?php if ($materials): ?>
                    <?php foreach ($materials as $material): ?>
                        <div style="border: 1px solid #ddd; border-radius: 5px; padding: 1rem; margin-bottom: 1rem;">
                            <h3><?php echo htmlspecialchars($material['title']); ?></h3>
                            <p><?php echo htmlspecialchars($material['description']); ?></p>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem;">
                                <div>
                                    <small>
                                        <strong>Subject:</strong> <?php echo htmlspecialchars($material['subject']); ?> | 
                                        <strong>Department:</strong> <?php echo htmlspecialchars($material['department']); ?> | 
                                        <strong>Uploaded by:</strong> <?php echo htmlspecialchars($material['username']); ?> | 
                                        <strong>Date:</strong> <?php echo date('M j, Y', strtotime($material['created_at'])); ?>
                                    </small>
                                </div>
                                <a href="download.php?id=<?php echo $material['id']; ?>" class="btn">Download</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No materials found.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2024 StudyHub. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // File upload preview
        document.getElementById('file').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                document.querySelector('.file-upload label').textContent = fileName;
            }
        });
    </script>
</body>
</html>
