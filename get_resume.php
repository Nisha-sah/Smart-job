<?php
header('Content-Type: application/json');
require 'db_connect.php';

$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(["success" => false, "message" => "Missing user ID."]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT resume_path, original_name, label, uploaded_at FROM resumes WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        echo json_encode(["success" => true] + $row);
    } else {
        echo json_encode(["success" => false, "message" => "No resume found."]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
