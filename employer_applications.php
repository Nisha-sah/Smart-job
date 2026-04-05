<?php
header('Content-Type: application/json');
require 'db_connect.php';

$job_id = $_GET['job_id'] ?? null;

if (!$job_id) {
    echo json_encode(["success" => false, "message" => "Missing job ID."]);
    exit;
}

try {
    // Join with users table if available; fall back gracefully if not
    $query = "
        SELECT 
            a.id,
            a.user_id,
            a.status,
            a.cover_letter,
            a.resume_path,
            a.applied_at,
            j.title       AS job_title,
            COALESCE(u.name,  CONCAT('Applicant #', a.user_id)) AS applicant_name,
            COALESCE(u.email, '')                                AS applicant_email
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        LEFT JOIN users u ON a.user_id = u.id
        WHERE a.job_id = ?
        ORDER BY a.applied_at DESC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$job_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
