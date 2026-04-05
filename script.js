// 1. Fetch and Display Jobs with Search Filters
async function fetchJobs() {
    const keyword  = document.getElementById('keyword').value;
    const location = document.getElementById('location').value;
    const category = document.getElementById('category').value;
    const type     = document.getElementById('job_type').value;
    const salary   = document.getElementById('min_salary').value;

    const res  = await fetch(`search.php?keyword=${keyword}&location=${location}&category=${category}&type=${type}&salary=${salary}`);
    const jobs = await res.json();

    const container = document.getElementById('job-list');
    container.innerHTML = '';

    if (jobs.length === 0) {
        container.innerHTML = '<p style="color:#888; padding:20px;">No matching jobs found.</p>';
        return;
    }

    jobs.forEach(job => {
        const jobData = JSON.stringify(job).replace(/"/g, '&quot;');
        container.innerHTML += `
            <div class="job-card">
                <div>
                    <h3>${job.title}</h3>
                    <small>${job.location} | ${job.job_type} | ${job.category}</small><br>
                    <strong style="font-size:0.88rem;">Max Salary: $${job.salary_max}</strong>
                </div>
                <div class="btn-group">
                    <button class="fav-btn" onclick="saveJob(${jobData})">❤️ Save</button>
                    <button class="apply-btn" onclick="goToApplyPage(${job.id}, '${encodeURIComponent(job.title)}')">
                        Apply Now
                    </button>
                </div>
            </div>`;
    });
}

// 2. Redirect to Application Page
function goToApplyPage(id, title) {
    window.location.href = `apply.html?id=${id}&title=${title}`;
}

// 3. Save Job to Favorites
async function saveJob(job) {
    try {
        const response = await fetch('save_favorite.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                user_id:    1,
                job_id:     job.id,
                title:      job.title,
                category:   job.category,
                location:   job.location,
                job_type:   job.job_type,
                salary_max: job.salary_max
            })
        });
        const result = await response.json();
        alert(result.message);
    } catch (error) {
        console.error("Save failed:", error);
    }
}

// Initial load
window.onload = () => {
    if (document.getElementById('job-list')) fetchJobs();
};
