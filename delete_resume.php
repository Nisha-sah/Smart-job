<?php
header('Content-Type: application/json');
require 'db_connect.php';

$data    = json_decode(file_get_contents("php://input"), true);
$user_id = $data['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(["success" => false, "message" => "Missing user ID."]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT resume_path FROM resumes WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && $row['resume_path'] && file_exists($row['resume_path'])) {
        unlink($row['resume_path']);
    }

    $del = $pdo->prepare("DELETE FROM resumes WHERE user_id = ?");
    $del->execute([$user_id]);

    echo json_encode(["success" => true, "message" => "Resume deleted."]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
