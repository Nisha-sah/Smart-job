<!-- EDIT JOB MODAL -->
<div class="modal-overlay" id="edit-modal" style="display:none;">
  <div class="modal-content">

    <h2>Edit Job</h2>

    <input type="hidden" id="edit-job-id">

    <div class="form-group">
      <label>Job Title</label>
      <input type="text" id="edit-title" class="form-control">
    </div>

    <div class="form-group">
      <label>Description</label>
      <textarea id="edit-description" class="form-control"></textarea>
    </div>

    <div class="form-group">
      <label>Category</label>
      <input type="text" id="edit-category" class="form-control">
    </div>

    <div class="form-group">
      <label>Location</label>
      <input type="text" id="edit-location" class="form-control">
    </div>

    <div class="form-group">
      <label>Job Type</label>
      <select id="edit-type" class="form-control">
        <option value="full-time">Full Time</option>
        <option value="part-time">Part Time</option>
        <option value="contract">Contract</option>
      </select>
    </div>

    <div class="form-group">
      <label>Salary Min</label>
      <input type="number" id="edit-salary-min" class="form-control">
    </div>

    <div class="form-group">
      <label>Salary Max</label>
      <input type="number" id="edit-salary-max" class="form-control">
    </div>

    <div class="form-group">
      <label>Skills</label>
      <input type="text" id="edit-skills" class="form-control">
    </div>

    <div class="form-group">
      <label>Status</label>
      <select id="edit-status" class="form-control">
        <option value="open">Open</option>
        <option value="closed">Closed</option>
      </select>
    </div>

    <div style="margin-top:15px;">
      <button onclick="closeEditModal()" class="btn btn-secondary">Cancel</button>
      <button onclick="saveEdit()" class="btn btn-primary">Save Changes</button>
    </div>

  </div>
</div>
<script>
  // OPEN EDIT MODAL
function openEdit(jobId) {

    const job = allJobs.find(j => j.id == jobId);

    if(!job) return;

    document.getElementById("edit-job-id").value = job.id;
    document.getElementById("edit-title").value = job.title;
    document.getElementById("edit-description").value = job.description;
    document.getElementById("edit-category").value = job.category;
    document.getElementById("edit-location").value = job.location;
    document.getElementById("edit-type").value = job.job_type;
    document.getElementById("edit-salary-min").value = job.salary_min;
    document.getElementById("edit-salary-max").value = job.salary_max;
    document.getElementById("edit-skills").value = job.skills;
    document.getElementById("edit-status").value = job.status;

    document.getElementById("edit-modal").style.display = "block";
}


// CLOSE MODAL
function closeEditModal(){
    document.getElementById("edit-modal").style.display = "none";
}


// SAVE EDIT
async function saveEdit(){

    const jobId = document.getElementById("edit-job-id").value;

    const payload = {
        title: document.getElementById("edit-title").value,
        description: document.getElementById("edit-description").value,
        category: document.getElementById("edit-category").value,
        location: document.getElementById("edit-location").value,
        job_type: document.getElementById("edit-type").value,
        salary_min: document.getElementById("edit-salary-min").value,
        salary_max: document.getElementById("edit-salary-max").value,
        skills: document.getElementById("edit-skills").value,
        status: document.getElementById("edit-status").value
    };

    try{

        const res = await fetch(`../../helper/api/jobs.php?action=update&id=${jobId}`,{
            method:"PUT",
            headers:{ "Content-Type":"application/json"},
            body: JSON.stringify(payload)
        });

        const data = await res.json();

        if(data.error){
            alert(data.error);
            return;
        }

        alert("Job updated successfully");

        closeEditModal();

        loadJobs(); // refresh dashboard immediately

    }catch(err){
        alert("Update failed");
    }
}
</script>
<?php  
function handleUpdate(){

    if($_SERVER['REQUEST_METHOD'] !== 'PUT'){
        jsonError("PUT request required");
    }

    $user = requireAuth('employer');

    $jobId = (int)($_GET['id'] ?? 0);

    $data = json_decode(file_get_contents("php://input"), true);

    $db = db();

    // Check job ownership
    $stmt = $db->prepare("SELECT employer_id FROM jobs WHERE id=?");
    $stmt->execute([$jobId]);
    $job = $stmt->fetch();

    if(!$job){
        jsonError("Job not found");
    }

    if($job['employer_id'] != $user['id']){
        jsonError("Unauthorized",403);
    }

    $stmt = $db->prepare("
        UPDATE jobs SET
        title=?,
        description=?,
        category=?,
        location=?,
        job_type=?,
        salary_min=?,
        salary_max=?,
        skills=?,
        status=?
        WHERE id=?
    ");

    $stmt->execute([
        $data['title'],
        $data['description'],
        $data['category'],
        $data['location'],
        $data['job_type'],
        $data['salary_min'],
        $data['salary_max'],
        $data['skills'],
        $data['status'],
        $jobId
    ]);

    echo json_encode([
        "success"=>true,
        "message"=>"Job updated"
    ]);
}
?>