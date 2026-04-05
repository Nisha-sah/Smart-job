<script>
async function loadJobs() {
  const list = document.getElementById('jobs-list');
  list.innerHTML = '<div class="loader"></div>';

  try {
    const data = await api.get('jobs.php?action=my');  // Fetch employer's jobs
    allJobs = data.jobs || [];

    // Update stat cards
    document.getElementById('stat-total').textContent      = allJobs.length;
    document.getElementById('stat-open').textContent       = allJobs.filter(j => j.status === 'open').length;
    document.getElementById('stat-closed').textContent     = allJobs.filter(j => j.status === 'closed').length;
    document.getElementById('stat-applicants').textContent = allJobs.reduce((sum, j) => sum + Number(j.applicant_count || 0), 0);

    // Render jobs table
    list.innerHTML = `
      <div class="table-wrap card" style="padding:0; overflow:hidden">
        <table>
          <thead> ... </thead>
          <tbody>
            ${allJobs.map(j => ` ...`).join('')}
          </tbody>
        </table>
      </div>`;
  } catch (err) {
    list.innerHTML = `<div class="alert alert-error">${err.message}</div>`;
  }
}
</script>