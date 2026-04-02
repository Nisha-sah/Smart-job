<?php

require_once __DIR__ . '/../includes/helpers.php';
setCorsHeaders();
startSession();

$action = $_GET['action'] ?? '';

match ($action) {
    'apply'  => handleApply(),
    'my'     => handleMyApplications(),
    'job'    => handleJobApplications(),
    'status' => handleUpdateStatus(),
    default  => jsonError('Unknown action', 404),
};

// ── Seeker: Apply for a Job ───────────────────────────────────
function handleApply() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('POST required', 405);
    $user  = requireAuth('seeker');
    $jobId = (int)($_GET['job_id'] ?? 0);
    if (!$jobId) jsonError('Job ID required.');

    $data  = getJson();
    $cover = sanitize($data['cover_letter'] ?? '');

    $db = getDB();

    // Check job exists and is open
    $jobStmt = $db->prepare("SELECT id, employer_id, title FROM jobs WHERE id=? AND status='open'");
    $jobStmt->bind_param('i', $jobId);
    $jobStmt->execute();
    $job = $jobStmt->get_result()->fetch_assoc();
    if (!$job) jsonError('Job not found or no longer accepting applications.', 404);

    // Check not already applied
    $dupStmt = $db->prepare("SELECT id FROM applications WHERE job_id=? AND seeker_id=?");
    $dupStmt->bind_param('ii', $jobId, $user['id']);
    $dupStmt->execute();
    if ($dupStmt->get_result()->num_rows > 0) jsonError('You have already applied for this job.');

    $insStmt = $db->prepare("INSERT INTO applications (job_id, seeker_id, cover_letter) VALUES (?,?,?)");
    $insStmt->bind_param('iis', $jobId, $user['id'], $cover);
    $insStmt->execute();
    $appId = $db->insert_id;

    // Notify employer of new applicant
    createNotification(
        $job['employer_id'],
        'new_applicant',
        "New application received for: {$job['title']}",
        $appId
    );

    jsonSuccess('Application submitted successfully.', ['application_id' => $appId]);
}

// ── Seeker: My Applications ───────────────────────────────────
function handleMyApplications() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') jsonError('GET required', 405);
    $user = requireAuth('seeker');
    $db   = getDB();

    $stmt = $db->prepare(
        "SELECT a.id, a.status, a.applied_at, a.updated_at, a.cover_letter,
                j.title, j.location, j.job_type, j.salary_min, j.salary_max,
                u.name AS employer_name
         FROM applications a
         JOIN jobs j  ON a.job_id  = j.id
         JOIN users u ON j.employer_id = u.id
         WHERE a.seeker_id = ?
         ORDER BY a.applied_at DESC"
    );
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    jsonResponse(['applications' => $applications]);
}

// ── Employer: Applications for a Job ──────────────────────────
function handleJobApplications() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') jsonError('GET required', 405);
    $user  = requireAuth('employer');
    $jobId = (int)($_GET['job_id'] ?? 0);
    if (!$jobId) jsonError('Job ID required.');

    $db = getDB();

    // Verify job belongs to employer
    $check = $db->prepare("SELECT id FROM jobs WHERE id=? AND employer_id=?");
    $check->bind_param('ii', $jobId, $user['id']);
    $check->execute();
    if (!$check->get_result()->num_rows) jsonError('Job not found or access denied.', 403);

    $stmt = $db->prepare(
        "SELECT a.id, a.status, a.applied_at, a.cover_letter,
                u.id AS seeker_id, u.name, u.email, u.phone, u.location,
                r.filepath AS resume_path, r.filename AS resume_name
         FROM applications a
         JOIN users u ON a.seeker_id = u.id
         LEFT JOIN resumes r ON r.user_id = u.id
         WHERE a.job_id = ?
         ORDER BY a.applied_at DESC"
    );
    $stmt->bind_param('i', $jobId);
    $stmt->execute();
    $applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    jsonResponse(['applications' => $applications]);
}

// ── Employer: Update Application Status ───────────────────────
function handleUpdateStatus() {
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') jsonError('PUT required', 405);
    $user  = requireAuth('employer');
    $appId = (int)($_GET['id'] ?? 0);
    if (!$appId) jsonError('Application ID required.');

    $data   = getJson();
    $status = sanitize($data['status'] ?? '');
    if (!in_array($status, ['pending','accepted','rejected'])) jsonError('Invalid status.');

    $db = getDB();

    // Make sure the application belongs to this employer's job
    $check = $db->prepare(
        "SELECT a.id, a.seeker_id, j.title
         FROM applications a
         JOIN jobs j ON a.job_id = j.id
         WHERE a.id=? AND j.employer_id=?"
    );
    $check->bind_param('ii', $appId, $user['id']);
    $check->execute();
    $app = $check->get_result()->fetch_assoc();
    if (!$app) jsonError('Application not found or access denied.', 403);

    $upd = $db->prepare("UPDATE applications SET status=? WHERE id=?");
    $upd->bind_param('si', $status, $appId);
    $upd->execute();

    // Notify the seeker
    $messages = [
        'accepted' => "Congratulations! Your application for \"{$app['title']}\" has been accepted.",
        'rejected' => "Your application for \"{$app['title']}\" was not selected.",
        'pending'  => "Your application for \"{$app['title']}\" is under review.",
    ];
    createNotification($app['seeker_id'], 'application_status', $messages[$status], $appId);

    jsonSuccess("Application status updated to $status.");
}
