<?php  

// ── Employer:view Jobs ─────────────────────────────────────────
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

?>