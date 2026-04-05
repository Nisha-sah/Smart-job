<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/includes/helpers.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$action = $_GET['action'] ?? '';

match($action) {
    'status' => handleUpdateStatus(),
    default => jsonError('Invalid action.', 404),
};

function handleUpdateStatus(): void {
    $user = requireAuth('employer');  // Ensure employer is logged in

    $applicationId = (int)($_GET['id'] ?? 0);
    if (!$applicationId) jsonError('Application ID required.');

    $input = json_decode(file_get_contents('php://input'), true);
    $status = $input['status'] ?? '';
    $allowedStatuses = ['pending', 'accepted', 'rejected'];

    if (!in_array($status, $allowedStatuses)) {
        jsonError('Invalid status value.');
    }

    $db = getDB();

    // Verify application belongs to a job of this employer
    $check = $db->prepare("
        SELECT a.id 
        FROM applications a
        JOIN jobs j ON j.id = a.job_id
        WHERE a.id = ? AND j.employer_id = ?
    ");
    $check->execute([$applicationId, $user['id']]);
    if (!$check->fetch()) jsonError('Access denied.', 403);

    // Update status
    $stmt = $db->prepare("UPDATE applications SET status=? WHERE id=?");
    $stmt->execute([$status, $applicationId]);

    jsonResponse(['message' => 'Status updated successfully']);
}
?>
<table id="applications-table">
  <thead>
    <tr>
      <th>Applicant</th>
      <th>Job Title</th>
      <th>Current Status</th>
      <th>Update Status</th>
    </tr>
  </thead>
  <tbody>
    <!-- Filled dynamically by JS -->
  </tbody>
</table>
<script>
    async function loadApplicants() {
  const tbody = document.querySelector('#applications-table tbody');
  tbody.innerHTML = '<tr><td colspan="4">Loading…</td></tr>';

  try {
    const applicants = await api.get('applications.php?action=list'); // Employer-specific
    tbody.innerHTML = '';

    applicants.forEach(app => {
      let color;
      switch(app.status){
        case 'pending':  color = 'orange'; break;
        case 'accepted': color = 'green';  break;
        case 'rejected': color = 'red';    break;
        default:         color = 'gray';
      }

      const row = document.createElement('tr');
      row.innerHTML = `
        <td>${escapeHtml(app.seeker_name)}</td>
        <td>${escapeHtml(app.job_title)}</td>
        <td style="color:${color}; font-weight:bold">${app.status}</td>
        <td>
          <select onchange="changeStatus(${app.id}, this)">
            <option value="pending"  ${app.status==='pending'?'selected':''}>Pending</option>
            <option value="accepted" ${app.status==='accepted'?'selected':''}>Accepted</option>
            <option value="rejected" ${app.status==='rejected'?'selected':''}>Rejected</option>
          </select>
        </td>
      `;
      tbody.appendChild(row);
    });

  } catch(err){
    tbody.innerHTML = `<tr><td colspan="4">Failed to load applicants: ${err.message}</td></tr>`;
  }
}

// Update status function
async function changeStatus(applicationId, select){
  const newStatus = select.value;

  try {
    const res = await api.put(`applications.php?action=status&id=${applicationId}`, {
      status: newStatus
    });

    if(res.error) throw new Error(res.error);

    alert('Status updated successfully!');
    loadApplicants(); // Refresh table
  } catch(err) {
    alert('Failed to update status: ' + err.message);
  }
}
</script>