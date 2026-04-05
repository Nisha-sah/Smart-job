<?php
// ============================================================
// jobs.php - Complete backend for job management
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:8080');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/includes/helpers.php';

startSession();

$action = $_GET['action'] ?? 'list';
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($action) {
        case 'list':
            handleListJobs();
            break;
        case 'detail':
            handleJobDetail();
            break;
        case 'my':
            // 2.4 - VIEW POSTED JOBS (Employer's own jobs)
            handleMyJobs();
            break;
        case 'create':
            if ($method === 'POST') handleCreateJob();
            else jsonError('POST required', 405);
            break;
        case 'update':
            // 2.2 - EDIT JOB POST
            if ($method === 'PUT') handleUpdateJob();
            else jsonError('PUT required', 405);
            break;
        case 'delete':
            // 2.3 - DELETE JOB POST
            if ($method === 'DELETE') handleDeleteJob();
            else jsonError('DELETE required', 405);
            break;
        default:
            jsonError('Unknown action', 404);
    }
} catch (Exception $e) {
    jsonError($e->getMessage(), 500);
}

// ============================================================
// 2.4 - VIEW POSTED JOBS (Employer's own jobs)
// ============================================================
function handleMyJobs() {
    global $pdo;
    
    // Check if user is logged in
    if (!isset($_SESSION['user'])) {
        jsonError('Please login first', 401);
    }
    
    $user = $_SESSION['user'];
    
    // Only employers can view their jobs
    if ($user['role'] !== 'employer') {
        jsonError('Access denied. Employers only.', 403);
    }
    
    $employerId = $user['id'];
    
    // Get all jobs posted by this employer with applicant count
    $stmt = $pdo->prepare("
        SELECT 
            j.*,
            (SELECT COUNT(*) FROM applications WHERE job_id = j.id) as applicant_count
        FROM jobs j
        WHERE j.employer_id = ?
        ORDER BY j.created_at DESC
    ");
    $stmt->execute([$employerId]);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    jsonResponse(['jobs' => $jobs]);
}

// ============================================================
// 2.2 - EDIT JOB POST (Update job)
// ============================================================
function handleUpdateJob() {
    global $pdo;
    
    if (!isset($_SESSION['user'])) {
        jsonError('Please login first', 401);
    }
    
    $user = $_SESSION['user'];
    $jobId = $_GET['id'] ?? 0;
    
    if (!$jobId) {
        jsonError('Job ID required');
    }
    
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Verify job belongs to this employer
    $check = $pdo->prepare("SELECT employer_id FROM jobs WHERE id = ?");
    $check->execute([$jobId]);
    $job = $check->fetch();
    
    if (!$job) {
        jsonError('Job not found', 404);
    }
    
    if ($job['employer_id'] != $user['id']) {
        jsonError('You can only edit your own jobs', 403);
    }
    
    // Update the job
    $stmt = $pdo->prepare("
        UPDATE jobs 
        SET title = ?, description = ?, category = ?, job_type = ?, 
            location = ?, salary_min = ?, salary_max = ?, skills = ?, status = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $input['title'],
        $input['description'],
        $input['category'] ?? '',
        $input['job_type'] ?? 'full-time',
        $input['location'] ?? '',
        $input['salary_min'] ?? 0,
        $input['salary_max'] ?? 0,
        $input['skills'] ?? '',
        $input['status'] ?? 'open',
        $jobId
    ]);
    
    jsonResponse(['success' => true, 'message' => 'Job updated successfully']);
}

// ============================================================
// 2.3 - DELETE JOB POST
// ============================================================
function handleDeleteJob() {
    global $pdo;
    
    if (!isset($_SESSION['user'])) {
        jsonError('Please login first', 401);
    }
    
    $user = $_SESSION['user'];
    $jobId = $_GET['id'] ?? 0;
    
    if (!$jobId) {
        jsonError('Job ID required');
    }
    
    // Verify job belongs to this employer
    $check = $pdo->prepare("SELECT employer_id FROM jobs WHERE id = ?");
    $check->execute([$jobId]);
    $job = $check->fetch();
    
    if (!$job) {
        jsonError('Job not found', 404);
    }
    
    if ($job['employer_id'] != $user['id']) {
        jsonError('You can only delete your own jobs', 403);
    }
    
    // Delete applications first (foreign key constraint)
    $pdo->prepare("DELETE FROM applications WHERE job_id = ?")->execute([$jobId]);
    
    // Delete the job
    $stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ?");
    $stmt->execute([$jobId]);
    
    jsonResponse(['success' => true, 'message' => 'Job deleted successfully']);
}

// ============================================================
// CREATE JOB
// ============================================================
function handleCreateJob() {
    global $pdo;
    
    if (!isset($_SESSION['user'])) {
        jsonError('Please login first', 401);
    }
    
    $user = $_SESSION['user'];
    
    if ($user['role'] !== 'employer') {
        jsonError('Only employers can post jobs', 403);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['title']) || empty($input['description'])) {
        jsonError('Title and description are required');
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO jobs (employer_id, title, description, category, job_type, location, salary_min, salary_max, skills, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'open', NOW())
    ");
    
    $stmt->execute([
        $user['id'],
        $input['title'],
        $input['description'],
        $input['category'] ?? '',
        $input['job_type'] ?? 'full-time',
        $input['location'] ?? '',
        $input['salary_min'] ?? 0,
        $input['salary_max'] ?? 0,
        $input['skills'] ?? ''
    ]);
    
    jsonResponse(['success' => true, 'message' => 'Job posted successfully', 'job_id' => $pdo->lastInsertId()]);
}

// ============================================================
// LIST ALL JOBS (Public)
// ============================================================
function handleListJobs() {
    global $pdo;
    
    $sql = "SELECT j.*, u.name as employer_name 
            FROM jobs j 
            JOIN users u ON j.employer_id = u.id 
            WHERE j.status = 'open'
            ORDER BY j.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    jsonResponse(['jobs' => $jobs]);
}

// ============================================================
// JOB DETAIL
// ============================================================
function handleJobDetail() {
    global $pdo;
    
    $jobId = $_GET['id'] ?? 0;
    
    if (!$jobId) {
        jsonError('Job ID required');
    }
    
    $stmt = $pdo->prepare("
        SELECT j.*, u.name as employer_name 
        FROM jobs j 
        JOIN users u ON j.employer_id = u.id 
        WHERE j.id = ?
    ");
    $stmt->execute([$jobId]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job) {
        jsonError('Job not found', 404);
    }
    
    jsonResponse(['job' => $job]);
}
?>