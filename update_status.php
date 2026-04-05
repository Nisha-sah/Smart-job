<?php
header('Content-Type: application/json');
require 'db_connect.php';

$data = json_decode(file_get_contents("php://input"), true);

$app_id     = $data['app_id']     ?? null;
$new_status = $data['new_status'] ?? null;
$allowed    = ['pending', 'reviewed', 'accepted', 'rejected'];

if (!$app_id || !$new_status || !in_array($new_status, $allowed)) {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $app_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => true, "message" => "Status updated to '{$new_status}'."]);
    } else {
        echo json_encode(["success" => false, "message" => "Application not found."]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
