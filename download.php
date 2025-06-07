<?php
require_once 'config/database.php';
requireLogin();

if (!isset($_GET['id'])) {
    header('Location: materials.php');
    exit();
}

$material_id = $_GET['id'];
$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM materials WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$material_id]);
$material = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$material || !file_exists($material['file_path'])) {
    header('Location: materials.php?error=File not found');
    exit();
}

// Set headers for file download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $material['file_name'] . '"');
header('Content-Length: ' . filesize($material['file_path']));

// Output file
readfile($material['file_path']);
exit();
?>
