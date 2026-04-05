<?php
header('Content-Type: application/json');
require 'db_connect.php';

$json = file_get_contents("php://input");
$data = json_decode($json, true);

if (!array_key_exists('job_id', $data) || !array_key_exists('user_id', $data)) {
    echo json_encode(["success" => false, "message" => "Incomplete data."]);
    exit;
}

try {
    $check = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND job_id = ?");
    $check->execute([$data['user_id'], $data['job_id']]);
    if ($check->fetch()) {
        echo json_encode(["success" => false, "message" => "Job already saved to favorites!"]);
        exit;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO favorites (user_id, job_id, title, category, location, job_type, salary_max)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $data['user_id'],
        $data['job_id'],
        $data['title']      ?? '',
        $data['category']   ?? '',
        $data['location']   ?? '',
        $data['job_type']   ?? '',
        $data['salary_max'] ?? 0
    ]);

    echo json_encode(["success" => true, "message" => "Job saved to favorites!"]);

} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo json_encode(["success" => false, "message" => "Job already saved to favorites!"]);
    } else {
        echo json_encode(["success" => false, "message" => "DB Error: " . $e->getMessage()]);
    }
}
?>
