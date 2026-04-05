<?php
// ── Employer: Delete Job ──────────────────────────────────────
function handleDelete() {

    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        jsonError('DELETE request required', 405);
    }

    // Check logged-in employer
    $user = requireAuth('employer');

    $jobId = (int)($_GET['id'] ?? 0);

    if (!$jobId) {
        jsonError('Job ID required');
    }

    $db = getDB();

    // Delete only if employer owns the job
    $stmt = $db->prepare("DELETE FROM jobs WHERE id=? AND employer_id=?");
    $stmt->bind_param("ii", $jobId, $user['id']);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        jsonError("Job not found or access denied", 403);
    }

    echo json_encode([
        "success" => true,
        "message" => "Job deleted successfully"
    ]);
}
?>
<!-- DELETE JOB MODAL -->
<div class="modal-overlay" id="delete-modal" style="display:none;">
  <div class="modal">

    <h3>Delete Job</h3>

    <p>
      Are you sure you want to delete
      <strong id="delete-job-title"></strong>?
    </p>

    <div style="margin-top:15px;">
      <button class="btn btn-secondary" onclick="closeDeleteModal()">
        Cancel
      </button>

      <button class="btn btn-danger" onclick="confirmDelete()">
        Delete Job
      </button>
    </div>

  </div>
</div>
<script>
    let pendingDeleteId = null;


// OPEN DELETE MODAL
function openDelete(jobId, jobTitle) {

    pendingDeleteId = jobId;

    document.getElementById("delete-job-title").textContent = jobTitle;

    document.getElementById("delete-modal").style.display = "block";
}


// CLOSE MODAL
function closeDeleteModal(){

    document.getElementById("delete-modal").style.display = "none";

    pendingDeleteId = null;
}


// CONFIRM DELETE
async function confirmDelete(){

    if(!pendingDeleteId) return;

    try{

        const res = await fetch(
            `../../helper/api/jobs.php?action=delete&id=${pendingDeleteId}`,
            { method:"DELETE" }
        );

        const data = await res.json();

        if(data.error){
            alert(data.error);
            return;
        }

        alert("Job deleted successfully");

        closeDeleteModal();

        loadJobs(); // refresh job list immediately

    }catch(err){

        alert("Delete failed");

    }
}
</script>