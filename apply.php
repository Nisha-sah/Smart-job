<?php
header('Content-Type: application/json');
require 'db_connect.php';

$job_id       = $_POST['job_id']       ?? null;
$user_id      = $_POST['user_id']      ?? null;
$cover_letter = $_POST['cover_letter'] ?? '';

if (!$job_id || !$user_id) {
    echo json_encode(["success" => false, "message" => "Missing job or user information."]);
    exit;
}

try {
    // Duplicate application check
    $check = $pdo->prepare("SELECT id FROM applications WHERE job_id = ? AND user_id = ?");
    $check->execute([$job_id, $user_id]);
    if ($check->fetch()) {
        echo json_encode(["success" => false, "message" => "You have already applied for this job."]);
        exit;
    }

    $resume_path = null;

    // Handle new file upload
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/resumes/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

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

        $filename    = 'resume_' . $user_id . '_' . $job_id . '_' . time() . '.' . $ext;
        $resume_path = $upload_dir . $filename;
        if (!move_uploaded_file($_FILES['resume']['tmp_name'], $resume_path)) {
            echo json_encode(["success" => false, "message" => "Failed to save resume file."]);
            exit;
        }

        // Also update the user's saved resume record
        $existing = $pdo->prepare("SELECT id FROM resumes WHERE user_id = ?");
        $existing->execute([$user_id]);
        if ($existing->fetch()) {
            $pdo->prepare("UPDATE resumes SET resume_path=?, original_name=?, uploaded_at=NOW() WHERE user_id=?")
                ->execute([$resume_path, $_FILES['resume']['name'], $user_id]);
        } else {
            $pdo->prepare("INSERT INTO resumes (user_id, resume_path, original_name, uploaded_at) VALUES (?,?,?,NOW())")
                ->execute([$user_id, $resume_path, $_FILES['resume']['name']]);
        }

    } else {
        // Fall back to the user's saved resume on file
        $saved = $pdo->prepare("SELECT resume_path FROM resumes WHERE user_id = ?");
        $saved->execute([$user_id]);
        $row = $saved->fetch(PDO::FETCH_ASSOC);
        if ($row) $resume_path = $row['resume_path'];
    }

    $stmt = $pdo->prepare(
        "INSERT INTO applications (job_id, user_id, cover_letter, resume_path, status, applied_at)
         VALUES (?, ?, ?, ?, 'pending', NOW())"
    );
    $stmt->execute([$job_id, $user_id, $cover_letter, $resume_path]);

    echo json_encode(["success" => true, "message" => "Application submitted successfully!"]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
