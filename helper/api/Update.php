<?php 
// ── Employer: Update Job ──────────────────────────────────────
function handleUpdate() {
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') jsonError('PUT required', 405);
    $user  = requireAuth('employer');
    $jobId = (int)($_GET['id'] ?? 0);
    if (!$jobId) jsonError('Job ID required.');

    $data = getJson();
    $title    = sanitize($data['title']       ?? '');
    $desc     = sanitize($data['description'] ?? '');
    $category = sanitize($data['category']    ?? '');
    $location = sanitize($data['location']    ?? '');
    $type     = sanitize($data['job_type']    ?? 'full-time');
    $skills   = sanitize($data['skills']      ?? '');
    $salMin   = (float)($data['salary_min']   ?? 0);
    $salMax   = (float)($data['salary_max']   ?? 0);
    $status   = sanitize($data['status']      ?? 'open');

    if (!$title || !$desc) jsonError('Title and description are required.');

    $db   = getDB();
    // Verify ownership
    $check = $db->prepare("SELECT id FROM jobs WHERE id=? AND employer_id=?");
    $check->bind_param('ii', $jobId, $user['id']);
    $check->execute();
    if (!$check->get_result()->num_rows) jsonError('Job not found or access denied.', 403);

    $stmt = $db->prepare(
        "UPDATE jobs SET title=?,description=?,category=?,location=?,job_type=?,salary_min=?,salary_max=?,skills=?,status=?
         WHERE id=? AND employer_id=?"
    );
    $stmt->bind_param('sssssddssii', $title,$desc,$category,$location,$type,$salMin,$salMax,$skills,$status,$jobId,$user['id']);
    $stmt->execute();

    jsonSuccess('Job updated successfully.');
}
 ?>