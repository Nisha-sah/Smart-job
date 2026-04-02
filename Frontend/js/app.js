// =============================================================
// ── DEV 2: Employer & Job Posting Functions ───────────────────
// WRITTEN BY: Developer 2
// Covers:
//   - Job posting CRUD (create, read, update, delete)
//   - Applicant review (view, accept, reject)
//   - Employer dashboard rendering (stats, job table, applicant cards)
// =============================================================

// ── Job Posting API Calls ─────────────────────────────────────

// Create a new job posting (employer)
async function createJob(jobData) {
  return await api.post('jobs.php?action=create', jobData);
}

// Update an existing job posting (employer)
async function updateJob(jobId, jobData) {
  return await api.put(`jobs.php?action=update&id=${jobId}`, jobData);
}

// Delete a job posting (employer)
async function deleteJob(jobId) {
  return await api.delete(`jobs.php?action=delete&id=${jobId}`);
}

// Get all jobs posted by the currently logged-in employer
async function getMyJobs() {
  return await api.get('jobs.php?action=my');
}

// ── Applicant Review API Calls ────────────────────────────────

// Get all applicants for a specific job (employer only)
async function getJobApplicants(jobId) {
  return await api.get(`applications.php?action=job&job_id=${jobId}`);
}

// Update application status: accepted / rejected / pending (employer only)
async function updateApplicationStatus(appId, status) {
  return await api.put(`applications.php?action=status&id=${appId}`, { status });
}

// ── Employer Dashboard Stats ──────────────────────────────────

// Calculate and display the 4 stat numbers at the top of employer dashboard
function renderEmployerStats(jobs) {
  const total      = jobs.length;
  const open       = jobs.filter(j => j.status === 'open').length;
  const closed     = jobs.filter(j => j.status === 'closed').length;
  const applicants = jobs.reduce((sum, j) => sum + Number(j.applicant_count), 0);

  document.getElementById('stat-total').textContent      = total;
  document.getElementById('stat-open').textContent       = open;
  document.getElementById('stat-closed').textContent     = closed;
  document.getElementById('stat-applicants').textContent = applicants;
}

// ── Employer Job Table Renderer ───────────────────────────────

// Build the HTML table of all jobs posted by this employer
function renderEmployerJobTable(jobs) {
  if (!jobs.length) {
    return `
      <div class="empty-state">
        <div class="icon">📋</div>
        <h3>No jobs posted yet</h3>
        <p>Create your first listing to start receiving applications</p>
        <a href="post-job.html" class="btn btn-primary mt-16">Post a Job</a>
      </div>`;
  }

  return `
    <div class="table-wrap card" style="padding:0">
      <table>
        <thead>
          <tr>
            <th>Job Title</th>
            <th>Type</th>
            <th>Location</th>
            <th>Salary</th>
            <th>Applicants</th>
            <th>Status</th>
            <th>Posted</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          ${jobs.map(job => `
            <tr>
              <td>
                <strong>${job.title}</strong>
                ${job.category
                  ? `<div class="text-muted" style="font-size:12px">${job.category}</div>`
                  : ''}
              </td>
              <td><span class="badge badge-gray">${job.job_type}</span></td>
              <td>${job.location || '—'}</td>
              <td>${formatSalary(job.salary_min, job.salary_max)}</td>
              <td><span class="badge badge-accent">${job.applicant_count}</span></td>
              <td>${statusBadge(job.status)}</td>
              <td class="text-muted">${formatDate(job.created_at)}</td>
              <td>
                <div style="display:flex; gap:6px">
                  <button class="btn btn-sm btn-outline"
                    onclick="openEditModal(${job.id})">Edit</button>
                  <button class="btn btn-sm btn-danger"
                    onclick="confirmDeleteJob(${job.id}, this)">Delete</button>
                </div>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    </div>`;
}

// ── Applicant Cards Renderer ──────────────────────────────────

// Build the applicant review cards for a selected job
function renderApplicantCards(applicants) {
  if (!applicants.length) {
    return `
      <div class="empty-state">
        <div class="icon">👥</div>
        <h3>No applicants yet</h3>
        <p>Share this job to attract candidates</p>
      </div>`;
  }

  return `
    <div class="jobs-grid">
      ${applicants.map(a => `
        <div class="card">
          <div style="display:flex; justify-content:space-between;
            align-items:flex-start; flex-wrap:wrap; gap:12px">
            <div>
              <div style="font-weight:700; font-size:16px">${a.name}</div>
              <div class="text-sec" style="font-size:13px">
                ${a.email}${a.phone ? ' · ' + a.phone : ''}
              </div>
              ${a.location
                ? `<div class="text-muted" style="font-size:13px">📍 ${a.location}</div>`
                : ''}
              <div style="margin-top:8px; font-size:13px; color:var(--text-muted)">
                Applied ${formatDate(a.applied_at)}
              </div>
            </div>
            <div style="display:flex; flex-direction:column;
              align-items:flex-end; gap:8px">
              ${statusBadge(a.status)}
              ${a.resume_path
                ? `<a href="../${a.resume_path}" target="_blank"
                    class="btn btn-outline btn-sm">📄 Resume</a>`
                : '<span class="text-muted" style="font-size:12px">No resume</span>'}
            </div>
          </div>
          ${a.cover_letter ? `
            <div style="margin-top:12px; padding-top:12px;
              border-top:1px solid var(--border);
              font-size:13px; color:var(--text-sec)">
              ${a.cover_letter}
            </div>` : ''}
          <div style="margin-top:16px; display:flex; gap:8px; flex-wrap:wrap">
            <button class="btn btn-sm"
              style="background:rgba(0,200,150,0.15); color:var(--green);
                border:1px solid rgba(0,200,150,0.3)"
              onclick="confirmStatusUpdate(${a.id}, 'accepted', this)">
              ✓ Accept
            </button>
            <button class="btn btn-sm btn-danger"
              onclick="confirmStatusUpdate(${a.id}, 'rejected', this)">
              ✕ Reject
            </button>
            <button class="btn btn-sm btn-outline"
              onclick="confirmStatusUpdate(${a.id}, 'pending', this)">
              ⟳ Mark Pending
            </button>
          </div>
        </div>
      `).join('')}
    </div>`;
}

// ── Button Action Handlers ────────────────────────────────────

// Handle accept / reject / pending button click on applicant card
async function confirmStatusUpdate(appId, status, btn) {
  setLoading(btn, true, '…');
  try {
    await updateApplicationStatus(appId, status);
    // Reload applicant list for the currently selected job
    const jobId = document.getElementById('job-select')?.value;
    if (jobId) {
      const data = await getJobApplicants(jobId);
      document.getElementById('applicants-list').innerHTML =
        renderApplicantCards(data.applications);
    }
  } catch (err) {
    alert(err.message);
    setLoading(btn, false);
  }
}

// Handle delete job button click — confirms before deleting
async function confirmDeleteJob(jobId, btn) {
  if (!confirm('Delete this job posting? This cannot be undone.')) return;
  setLoading(btn, true, '…');
  try {
    await deleteJob(jobId);
    // Reload and re-render the full job table and stats
    const data = await getMyJobs();
    renderEmployerStats(data.jobs);
    document.getElementById('jobs-list').innerHTML =
      renderEmployerJobTable(data.jobs);
  } catch (err) {
    alert(err.message);
    setLoading(btn, false);
  }
}

