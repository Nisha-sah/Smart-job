<?php
header('Content-Type: application/json');
require 'db_connect.php';

$user_id = $_GET['user_id'] ?? 1;

try {
    $query = "
        SELECT 
            a.id,
            a.status,
            a.cover_letter,
            a.resume_path,
            a.applied_at,
            j.title,
            j.location
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        WHERE a.user_id = ?
        ORDER BY a.applied_at DESC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
