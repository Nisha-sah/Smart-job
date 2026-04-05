<?php
header('Content-Type: application/json');
require 'db_connect.php';

$user_id = $_POST['user_id'] ?? null;
$label   = $_POST['label']   ?? '';

if (!$user_id) {
    echo json_encode(["success" => false, "message" => "Missing user ID."]);
    exit;
}

if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["success" => false, "message" => "No file uploaded or upload error."]);
    exit;
}

$ext     = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
$allowed = ['pdf', 'doc', 'docx'];

if (!in_array($ext, $allowed)) {
    echo json_encode(["success" => false, "message" => "Invalid file type. Only PDF, DOC, DOCX allowed."]);
    exit;
}
if ($_FILES['resume']['size'] > 5 * 1024 * 1024) {
    echo json_encode(["success" => false, "message" => "File exceeds 5MB limit."]);
    exit;
}

$upload_dir = 'uploads/resumes/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

// Delete old file if exists
try {
    $old = $pdo->prepare("SELECT resume_path FROM resumes WHERE user_id = ?");
    $old->execute([$user_id]);
    $row = $old->fetch(PDO::FETCH_ASSOC);
    if ($row && $row['resume_path'] && file_exists($row['resume_path'])) {
        unlink($row['resume_path']);
    }
} catch (Exception $e) { /* ignore */ }

$filename      = 'resume_user' . $user_id . '_' . time() . '.' . $ext;
$resume_path   = $upload_dir . $filename;
$original_name = $_FILES['resume']['name'];

if (!move_uploaded_file($_FILES['resume']['tmp_name'], $resume_path)) {
    echo json_encode(["success" => false, "message" => "Failed to save file."]);
    exit;
}

try {
    $check = $pdo->prepare("SELECT id FROM resumes WHERE user_id = ?");
    $check->execute([$user_id]);

    if ($check->fetch()) {
        $pdo->prepare("UPDATE resumes SET resume_path=?, original_name=?, label=?, uploaded_at=NOW() WHERE user_id=?")
            ->execute([$resume_path, $original_name, $label, $user_id]);
    } else {
        $pdo->prepare("INSERT INTO resumes (user_id, resume_path, original_name, label, uploaded_at) VALUES (?,?,?,?,NOW())")
            ->execute([$user_id, $resume_path, $original_name, $label]);
    }

    echo json_encode(["success" => true, "message" => "Resume uploaded successfully!"]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "DB error: " . $e->getMessage()]);
}
?>
