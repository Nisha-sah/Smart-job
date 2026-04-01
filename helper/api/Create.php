<?php
// ── Employer: Create Job ──────────────────────────────────────
function handleCreate() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('POST required', 405);
    $user = requireAuth('employer');
    $data = getJson();

    $title    = sanitize($data['title']    ?? '');
    $desc     = sanitize($data['description'] ?? '');
    $category = sanitize($data['category'] ?? '');
    $location = sanitize($data['location'] ?? '');
    $type     = sanitize($data['job_type'] ?? 'full-time');
    $skills   = sanitize($data['skills']   ?? '');
    $salMin   = (float)($data['salary_min'] ?? 0);
    $salMax   = (float)($data['salary_max'] ?? 0);

    if (!$title || !$desc) jsonError('Title and description are required.');

    $db   = getDB();
    $stmt = $db->prepare(
        "INSERT INTO jobs (employer_id,title,description,category,location,job_type,salary_min,salary_max,skills)
         VALUES (?,?,?,?,?,?,?,?,?)"
    );
    $stmt->bind_param('isssssdds', $user['id'], $title, $desc, $category, $location, $type, $salMin, $salMax, $skills);
    $stmt->execute();
    $jobId = $db->insert_id;

    // Notify seekers of new job (simplified: notify all seekers)
    $seekers = $db->query("SELECT id FROM users WHERE role='seeker'")->fetch_all(MYSQLI_ASSOC);
    foreach ($seekers as $s) {
        createNotification($s['id'], 'new_job', "New job posted: $title in $location", $jobId);
    }

    jsonSuccess('Job posted successfully.', ['job_id' => $jobId]);
}
?>