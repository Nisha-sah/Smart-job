<?php
header('Content-Type: application/json');
require 'db_connect.php';

$json = file_get_contents("php://input");
$data = json_decode($json, true);

if (isset($data['job_id']) && isset($data['title'])) {
    try {
        $query = "INSERT INTO favorites (user_id, job_id, title, category, location, job_type, salary_max) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $data['user_id'], 
            $data['job_id'], 
            $data['title'], 
            $data['category'], 
            $data['location'], 
            $data['job_type'], 
            $data['salary_max']
        ]);
        
        echo json_encode(["success" => true, "message" => "Full job details saved to favorites!"]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "DB Error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Incomplete data."]);
}
?>