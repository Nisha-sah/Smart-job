// ============================================================
//  Smart Job Portal — Frontend/js/app.js
//  Fixed: API_BASE, api object, Auth, renderNav,
//         setLoading, statusBadge, showAlert, formatSalary,
//         formatDate, saveJob
// ============================================================


const API_BASE = 'http://localhost:8080/job-portal-system/helper/api';

// ── HTTP helpers ──────────────────────────────────────────────────────────────
const api = {
    get(url) {
        return fetch(`${API_BASE}/${url}`, {
            credentials: 'include',
        }).then(r => r.json());
    },
    post(url, data) {
        return fetch(`${API_BASE}/${url}`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
        }).then(r => r.json());
    },
    put(url, data) {
        return fetch(`${API_BASE}/${url}`, {
            method: 'PUT',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
        }).then(r => r.json());
    },
    delete(url) {
        return fetch(`${API_BASE}/${url}`, {
            method: 'DELETE',
            credentials: 'include',
        }).then(r => r.json());
    },
};

// ── Auth helpers ──────────────────────────────────────────────────────────────
const Auth = {
    getUser() {
        try {
            return JSON.parse(sessionStorage.getItem('user') || 'null');
        } catch {
            return null;
        }
    },
    setUser(user) {
        sessionStorage.setItem('user', JSON.stringify(user));
    },
    logout() {
        sessionStorage.removeItem('user');
    },
    // Redirects to `redirectUrl` if not logged in or wrong role.
    // Returns the user object on success, null otherwise.
    requireRole(role, redirectUrl) {
        const user = Auth.getUser();
        if (!user) {
            window.location.href = redirectUrl;
            return null;
        }
        if (role && user.role !== role) {
            window.location.href = redirectUrl;
            return null;
        }
        return user;
    },
};

// ── Render navbar based on login state ───────────────────────────────────────
function renderNav() {
    const user    = Auth.getUser();
    const links   = document.getElementById('nav-links');
    const actions = document.getElementById('nav-actions');
    if (!links || !actions) return;

    if (!user) {
        links.innerHTML = `
            <a href="index.html">Home</a>
            <a href="jobs.html">Jobs</a>
            <a href="contact.html">Contact Us</a>`;
        actions.innerHTML = `
            <a href="login.html"    class="btn btn-outline btn-sm">Login</a>
            <a href="register.html" class="btn btn-primary  btn-sm">Register</a>`;
        return;
    }

    if (user.role === 'employer') {
        links.innerHTML = `
            <a href="employer-dashboard.html" class="active">Dashboard</a>
            <a href="post-job.html">Post Job</a>`;
        actions.innerHTML = `
            <span style="font-size:13px;color:var(--text-sec);padding:0 8px">
                ${escapeHtml(user.name)}
            </span>
            <button class="btn btn-outline btn-sm"
                onclick="handleLogout()">Logout</button>`;
    } else {
        links.innerHTML = `
            <a href="seeker-dashboard.html" class="active">Dashboard</a>
            <a href="jobs.html">Browse Jobs</a>`;
        actions.innerHTML = `
            <span style="font-size:13px;color:var(--text-sec);padding:0 8px">
                ${escapeHtml(user.name)}
            </span>
            <button class="btn btn-outline btn-sm"
                onclick="handleLogout()">Logout</button>`;
    }
}

async function handleLogout() {
    try {
        await api.get('auth.php?action=logout');
    } catch (_) { /* ignore */ }
    Auth.logout();
    window.location.href = 'login.html';
}

// ── UI Helpers ────────────────────────────────────────────────────────────────

/**
 * Show / hide a loading state on a button.
 * Saves the original text so it can be restored.
 */
function setLoading(btn, loading, loadingText = 'Loading…') {
    btn.disabled = loading;
    if (loading) {
        btn._originalText = btn.textContent;
        btn.textContent   = loadingText;
    } else {
        btn.textContent = btn._originalText || 'Submit';
    }
}

/**
 * Show a temporary alert inside a container.
 * containerRef can be a CSS selector string OR a DOM element.
 */
function showAlert(containerRef, message, type = 'info', timeout = 4000) {
    const el = typeof containerRef === 'string'
        ? document.querySelector(containerRef)
        : containerRef;
    if (!el) return;
    el.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
    if (timeout) {
        setTimeout(() => { el.innerHTML = ''; }, timeout);
    }
}

/**
 * Return an HTML badge string for a job or application status.
 */
function statusBadge(status) {
    const map = {
        open:     'badge-green',
        closed:   'badge-gray',
        pending:  'badge-yellow',
        accepted: 'badge-green',
        rejected: 'badge-red',
    };
    const cls   = map[status] || 'badge-gray';
    const label = status
        ? status.charAt(0).toUpperCase() + status.slice(1)
        : '—';
    return `<span class="badge ${cls}">${label}</span>`;
}

/**
 * Format a salary range for display.
 */
function formatSalary(min, max) {
    if (!min || min <= 0) return 'Competitive';
    const fmt = n => '$' + Number(n).toLocaleString();
    return max && max > 0 ? `${fmt(min)} – ${fmt(max)}` : `${fmt(min)}+`;
}

/**
 * Format an ISO date string to a readable date.
 */
function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString(undefined, {
        year: 'numeric', month: 'short', day: 'numeric',
    });
}

/**
 * Escape HTML to prevent XSS when inserting user data into innerHTML.
 */
function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/**
 * Save / unsave a job for the current seeker.
 */
async function saveJob(jobId) {
    try {
        const data = await api.post(`saved-jobs.php?action=toggle&job_id=${jobId}`, {});
        if (data.error) { alert(data.error); return; }
        alert(data.message);
    } catch (e) {
        alert('Could not save job. Please log in first.');
    }
}