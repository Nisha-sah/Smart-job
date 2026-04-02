<?php

require_once __DIR__ . '/../includes/helpers.php';
setCorsHeaders();
startSession();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

match ($action) {
    'list'   => handleList(),
    'detail' => handleDetail(),
    'my'     => handleMyJobs(),
    'create' => handleCreate(),
    'update' => handleUpdate(),
    'delete' => handleDelete(),
    'save'   => handleSave(),
    'saved'  => handleSaved(),
    default  => jsonError('Unknown action', 404),
};

// ── List / Search Jobs (public) ───────────────────────────────
function handleList() {
    $db = getDB();

    $keyword  = '%' . sanitize($_GET['keyword']  ?? '') . '%';
    $location = '%' . sanitize($_GET['location'] ?? '') . '%';
    $category = sanitize($_GET['category'] ?? '');
    $type     = sanitize($_GET['type']     ?? '');
    $minSal   = (float)($_GET['salary_min'] ?? 0);
    $maxSal   = (float)($_GET['salary_max'] ?? 0);

    $sql = "SELECT j.*, u.name AS employer_name
            FROM jobs j
            JOIN users u ON j.employer_id = u.id
            WHERE j.status='open'
              AND (j.title LIKE ? OR j.description LIKE ? OR j.skills LIKE ?)
              AND j.location LIKE ?";
    $params = [$keyword, $keyword, $keyword, $location];
    $types  = 'ssss';

    if ($category) { $sql .= " AND j.category = ?"; $params[] = $category; $types .= 's'; }
    if ($type)     { $sql .= " AND j.job_type  = ?"; $params[] = $type;     $types .= 's'; }
    if ($minSal)   { $sql .= " AND j.salary_min >= ?"; $params[] = $minSal; $types .= 'd'; }
    if ($maxSal)   { $sql .= " AND j.salary_max <= ?"; $params[] = $maxSal; $types .= 'd'; }

    $sql .= " ORDER BY j.created_at DESC LIMIT 50";

    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $jobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    jsonResponse(['jobs' => $jobs]);
}

// ── Single Job Detail ─────────────────────────────────────────
function handleDetail() {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonError('Job ID required.');

    $db   = getDB();
    $stmt = $db->prepare(
        "SELECT j.*, u.name AS employer_name, u.location AS employer_location
         FROM jobs j JOIN users u ON j.employer_id = u.id WHERE j.id=?"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $job = $stmt->get_result()->fetch_assoc();

    if (!$job) jsonError('Job not found.', 404);
    jsonResponse(['job' => $job]);
}

// ── Employer: My Jobs ─────────────────────────────────────────
function handleMyJobs() {
    $user = requireAuth('employer');
    $db   = getDB();

    $stmt = $db->prepare(
        "SELECT j.*, COUNT(a.id) AS applicant_count
         FROM jobs j
         LEFT JOIN applications a ON a.job_id = j.id
         WHERE j.employer_id = ?
         GROUP BY j.id
         ORDER BY j.created_at DESC"
    );
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $jobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    jsonResponse(['jobs' => $jobs]);
}

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

// ── Employer: Delete Job ──────────────────────────────────────
function handleDelete() {
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') jsonError('DELETE required', 405);
    $user  = requireAuth('employer');
    $jobId = (int)($_GET['id'] ?? 0);
    if (!$jobId) jsonError('Job ID required.');

    $db   = getDB();
    $stmt = $db->prepare("DELETE FROM jobs WHERE id=? AND employer_id=?");
    $stmt->bind_param('ii', $jobId, $user['id']);
    $stmt->execute();

    if ($stmt->affected_rows === 0) jsonError('Job not found or access denied.', 403);
    jsonSuccess('Job deleted successfully.');
}

// ── Seeker: Save / Unsave Job ─────────────────────────────────
function handleSave() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('POST required', 405);
    $user  = requireAuth('seeker');
    $jobId = (int)($_GET['id'] ?? 0);
    if (!$jobId) jsonError('Job ID required.');

    $db   = getDB();
    $check = $db->prepare("SELECT id FROM saved_jobs WHERE user_id=? AND job_id=?");
    $check->bind_param('ii', $user['id'], $jobId);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        $del = $db->prepare("DELETE FROM saved_jobs WHERE user_id=? AND job_id=?");
        $del->bind_param('ii', $user['id'], $jobId);
        $del->execute();
        jsonSuccess('Job removed from saved list.');
    } else {
        $ins = $db->prepare("INSERT INTO saved_jobs (user_id, job_id) VALUES (?,?)");
        $ins->bind_param('ii', $user['id'], $jobId);
        $ins->execute();
        jsonSuccess('Job saved successfully.');
    }
}

// ── Seeker: Get Saved Jobs ────────────────────────────────────
function handleSaved() {
    $user = requireAuth('seeker');
    $db   = getDB();

    $stmt = $db->prepare(
        "SELECT j.*, u.name AS employer_name
         FROM saved_jobs s
         JOIN jobs j ON s.job_id = j.id
         JOIN users u ON j.employer_id = u.id
         WHERE s.user_id=?
         ORDER BY s.saved_at DESC"
    );
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $jobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    jsonResponse(['jobs' => $jobs]);
}
