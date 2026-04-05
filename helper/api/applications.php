<?php
// ============================================================
// applications.php - For 4.2 and 4.3
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:8080');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/includes/helpers.php';

startSession();

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'job':
            // 4.2 - REVIEW APPLICATIONS
            handleGetApplications();
            break;
        case 'status':
            // 4.3 - UPDATE APPLICATION STATUS
            handleUpdateStatus();
            break;
        default:
            jsonError('Invalid action', 404);
    }
} catch (Exception $e) {
    jsonError($e->getMessage(), 500);
}

// ============================================================
// 4.2 - REVIEW APPLICATIONS (Employer view applicants per job)
// ============================================================
function handleGetApplications() {
    global $pdo;
    
    if (!isset($_SESSION['user'])) {
        jsonError('Please login first', 401);
    }
    
    $user = $_SESSION['user'];
    $jobId = $_GET['job_id'] ?? 0;
    
    if (!$jobId) {
        jsonError('Job ID required');
    }
    
    // Verify job belongs to this employer
    $check = $pdo->prepare("SELECT employer_id FROM jobs WHERE id = ?");
    $check->execute([$jobId]);
    $job = $check->fetch();
    
    if (!$job || $job['employer_id'] != $user['id']) {
        jsonError('Access denied', 403);
    }
    
    // Get all applications for this job
    $stmt = $pdo->prepare("
        SELECT a.*, u.name, u.email, u.phone, u.location
        FROM applications a
        JOIN users u ON a.seeker_id = u.id
        WHERE a.job_id = ?
        ORDER BY a.applied_at DESC
    ");
    $stmt->execute([$jobId]);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    jsonResponse(['applications' => $applications]);
}

// ============================================================
// 4.3 - UPDATE APPLICATION STATUS
// ============================================================
function handleUpdateStatus() {
    global $pdo;
    
    if (!isset($_SESSION['user'])) {
        jsonError('Please login first', 401);
    }
    
    $user = $_SESSION['user'];
    $applicationId = $_GET['id'] ?? 0;
    
    if (!$applicationId) {
        jsonError('Application ID required');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $status = $input['status'] ?? '';
    
    $allowed = ['pending', 'accepted', 'rejected'];
    if (!in_array($status, $allowed)) {
        jsonError('Invalid status. Allowed: pending, accepted, rejected');
    }
    
    // Verify employer owns the job for this application
    $check = $pdo->prepare("
        SELECT j.employer_id 
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        WHERE a.id = ?
    ");
    $check->execute([$applicationId]);
    $result = $check->fetch();
    
    if (!$result || $result['employer_id'] != $user['id']) {
        jsonError('Access denied', 403);
    }
    
    // Update status
    $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
    $stmt->execute([$status, $applicationId]);
    
    jsonResponse(['success' => true, 'message' => 'Status updated successfully']);
}
?>