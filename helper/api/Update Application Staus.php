<?php

// ── Employer: Update Application Status (User Story 4.3) ─────────────────────
function handleUpdateStatus(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') jsonError('PUT required.', 405);

    $user  = requireAuth('employer');
    $appId = (int)($_GET['id'] ?? 0);
    if (!$appId) jsonError('Application ID required.');

    $data   = getJson();
    $status = sanitize($data['status'] ?? '');

    // Validate allowed statuses (acceptance criteria 4.3)
    $allowed = ['pending', 'accepted', 'rejected'];
    if (!in_array($status, $allowed, true)) {
        jsonError('Status must be: pending, accepted, or rejected.');
    }

    $db = getDB();

    // Verify employer owns the job linked to this application
    $check = $db->prepare(
        "SELECT a.id, a.seeker_id, j.title
         FROM applications a
         JOIN jobs j ON j.id = a.job_id
         WHERE a.id = ? AND j.employer_id = ?"
    );
    $check->execute([$appId, $user['id']]);
    $app = $check->fetch();
    if (!$app) jsonError('Application not found or access denied.', 403);

    // Update status
    $stmt = $db->prepare("UPDATE applications SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $appId]);

    // Notify the applicant
    $msg = "Your application for \"{$app['title']}\" has been {$status}.";
    createNotification((int)$app['seeker_id'], 'application_status', $msg, $appId);

    jsonSuccess('Application status updated.', ['status' => $status]);
    // FIXED: removed the extra stray } that was here causing a PHP parse error
}

// ── Seeker: Submit Application ────────────────────────────────────────────────
function handleApply(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('POST required.', 405);

    $user  = requireAuth('seeker');
    $data  = getJson();
    $jobId = (int)($data['job_id'] ?? 0);
    if (!$jobId) jsonError('Job ID required.');

    $db = getDB();

    // Check job exists and is open
    $job = $db->prepare("SELECT id, title, employer_id FROM jobs WHERE id = ? AND status = 'open'");
    $job->execute([$jobId]);
    $jobRow = $job->fetch();
    if (!$jobRow) jsonError('Job not found or no longer accepting applications.', 404);

    // Prevent duplicate applications
    $dup = $db->prepare("SELECT id FROM applications WHERE job_id = ? AND seeker_id = ?");
    $dup->execute([$jobId, $user['id']]);
    if ($dup->fetch()) jsonError('You have already applied for this job.');

    $coverLetter = sanitize($data['cover_letter'] ?? '');
    $resumePath  = sanitize($data['resume_path']  ?? '');

    $stmt = $db->prepare(
        "INSERT INTO applications (job_id, seeker_id, cover_letter, resume_path, status, applied_at)
         VALUES (?, ?, ?, ?, 'pending', NOW())"
    );
    $stmt->execute([$jobId, $user['id'], $coverLetter, $resumePath]);
    $appId = (int)$db->lastInsertId();

    // Notify the employer
    createNotification(
        (int)$jobRow['employer_id'],
        'new_application',
        "{$user['name']} applied for \"{$jobRow['title']}\"",
        $appId
    );

    jsonSuccess('Application submitted successfully.', ['application_id' => $appId]);
}