<?php
// backend/api/jobs.php
// DEV 2 — Job Posting CRUD (Employer) + Job Search (Seeker)
// GET  /jobs.php?action=list          → search/list jobs (public)
// GET  /jobs.php?action=detail&id=X   → single job
// GET  /jobs.php?action=my            → employer's own jobs
// POST /jobs.php?action=create        → create job (employer)
// PUT  /jobs.php?action=update&id=X   → edit job (employer)
// DELETE /jobs.php?action=delete&id=X → delete job (employer)
// POST /jobs.php?action=save&id=X     → save/unsave job (seeker)
// GET  /jobs.php?action=saved         → seeker's saved jobs
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/includes/helpers.php';
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
    global $pdo;

    $keyword  = '%' . sanitize($_GET['keyword'] ?? '') . '%';
    $location = '%' . sanitize($_GET['location'] ?? '') . '%';
    $category = sanitize($_GET['category'] ?? '');
    $type     = sanitize($_GET['type'] ?? '');
    $minSal   = (float)($_GET['salary_min'] ?? 0);
    $maxSal   = (float)($_GET['salary_max'] ?? 0);

    $sql = "SELECT j.*, u.name AS employer_name
            FROM jobs j
            JOIN users u ON j.employer_id = u.id
            WHERE j.status='open'
              AND (j.title LIKE :kw OR j.description LIKE :kw OR j.skills LIKE :kw)
              AND j.location LIKE :location";

    if ($category) $sql .= " AND j.category = :category";
    if ($type)     $sql .= " AND j.job_type = :type";
    if ($minSal)   $sql .= " AND j.salary_min >= :minSal";
    if ($maxSal)   $sql .= " AND j.salary_max <= :maxSal";

    $sql .= " ORDER BY j.created_at DESC LIMIT 50";

    $stmt = $pdo->prepare($sql);

    $stmt->bindValue(':kw', $keyword);
    $stmt->bindValue(':location', $location);

    if ($category) $stmt->bindValue(':category', $category);
    if ($type)     $stmt->bindValue(':type', $type);
    if ($minSal)   $stmt->bindValue(':minSal', $minSal);
    if ($maxSal)   $stmt->bindValue(':maxSal', $maxSal);

    $stmt->execute();
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse(['jobs' => $jobs]);
}
// ── Single Job Detail ─────────────────────────────────────────
function handleDetail() {
    global $pdo;

    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonError('Job ID required.');

    $stmt = $pdo->prepare(
        "SELECT j.*, u.name AS employer_name, u.location AS employer_location
         FROM jobs j
         JOIN users u ON j.employer_id = u.id
         WHERE j.id = :id"
    );

    $stmt->execute(['id' => $id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) jsonError('Job not found.', 404);

    jsonResponse(['job' => $job]);
}
// ── Seeker: Save / Unsave Job ─────────────────────────────────
function handleSave() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('POST required', 405);
    $user  = requireAuth('seeker');
    $jobId = (int)($_GET['id'] ?? 0);
    if (!$jobId) jsonError('Job ID required.');

    $db   = jobapplication();
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

function handleCreate() {
    global $pdo;

    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['title']) || empty($input['description'])) {
        jsonError('Title and description are required.');
    }

    // Get logged-in employer id
    $employer = requireAuth('employer');

    $stmt = $pdo->prepare("
        INSERT INTO jobs 
        (title, description, category, job_type, location, salary_min, salary_max, skills, created_at, status, employer_id)
        VALUES
        (:title, :description, :category, :job_type, :location, :salary_min, :salary_max, :skills, NOW(), 'open', :employer_id)
    ");

    try {
        $stmt->execute([
            ':title'       => $input['title'],
            ':description' => $input['description'],
            ':category'    => $input['category'],
            ':job_type'    => $input['job_type'],
            ':location'    => $input['location'],
            ':salary_min'  => $input['salary_min'],
            ':salary_max'  => $input['salary_max'],
            ':skills'      => $input['skills'],
            ':employer_id' => $employer['id']
        ]);

        jsonResponse(['success' => true, 'job_id' => $pdo->lastInsertId()]);
    } catch (Exception $e) {
        jsonError($e->getMessage());
    }
}