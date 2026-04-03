async function fetchJobs() {
    const keyword = document.getElementById('keyword').value;
    const location = document.getElementById('location').value;
    const category = document.getElementById('category').value;
    const type = document.getElementById('job_type').value;
    const salary = document.getElementById('min_salary').value;

    const res = await fetch(`search.php?keyword=${keyword}&location=${location}&category=${category}&type=${type}&salary=${salary}`);
    const jobs = await res.json();

    const container = document.getElementById('job-list');
    container.innerHTML = '';

    if (jobs.length === 0) {
        container.innerHTML = '<p>No matching jobs found.</p>';
        return;
    }

    jobs.forEach(job => {
        // We stringify the job object so the button can send all data at once
        const jobData = JSON.stringify(job).replace(/"/g, '&quot;');
        
        container.innerHTML += `
            <div class="job-card">
                <div>
                    <h3 style="margin:0;">${job.title}</h3>
                    <small>${job.location} | ${job.job_type} | ${job.category}</small><br>
                    <strong>Max Salary: $${job.salary_max}</strong>
                </div>
                <!-- Pass the whole job object to the function -->
                <button class="fav-btn" onclick="saveJob(${jobData})">❤️ Save</button>
            </div>`;
    });
}

async function saveJob(job) {
    try {
        const response = await fetch('save_favorite.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                user_id: 1,
                job_id: job.id,
                title: job.title,
                category: job.category,
                location: job.location,
                job_type: job.job_type,
                salary_max: job.salary_max
            })
        });

        const result = await response.json();
        if (result.success) {
            alert(result.message);
        } else {
            alert("Error: " + result.message);
        }
    } catch (error) {
        console.error("Save failed:", error);
    }
}

window.onload = fetchJobs;