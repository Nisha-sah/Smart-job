
<?php
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
?>