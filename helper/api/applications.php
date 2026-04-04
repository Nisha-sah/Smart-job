<?php
// ============================================================
//  Smart Job Portal — helper/api/applications.php
//  Fixed: Removed extra } at end of handleUpdateStatus()
//         which caused a PHP parse error (500 on all requests)
// ============================================================
header('Content-Type: application/json');

require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/includes/helpers.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$action = $_GET['action'] ?? '';

match ($action) {
    'job'    => handleListApplications(),
    'status' => handleUpdateStatus(),
    'apply'  => handleApply(),
    default  => jsonError('Invalid action.', 404),
};

// ── Employer: List Applicants Per Job (User Story 4.2) ────────────────────────
function handleListApplications(): void
{
    $user  = requireAuth('employer');
    $jobId = (int)($_GET['job_id'] ?? 0);
    if (!$jobId) jsonError('Job ID required.');

    $db = getDB();

    // Verify the job belongs to this employer
    $check = $db->prepare("SELECT id FROM jobs WHERE id = ? AND employer_id = ?");
    $check->execute([$jobId, $user['id']]);
    if (!$check->fetch()) jsonError('Job not found or access denied.', 403);

    $stmt = $db->prepare(
        "SELECT
            a.id,
            a.status,
            a.cover_letter,
            a.resume_path,
            a.applied_at,
            u.id       AS seeker_id,
            u.name,
            u.email,
            u.phone,
            u.location,
            u.bio
         FROM applications a
         JOIN users u ON u.id = a.seeker_id
         WHERE a.job_id = ?
         ORDER BY a.applied_at DESC"
    );
    $stmt->execute([$jobId]);
    $applications = $stmt->fetchAll();

    jsonResponse([
        'applications' => $applications,
        'total'        => count($applications),
    ]);
}

