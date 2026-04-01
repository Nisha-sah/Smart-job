// frontend/js/app.js
// Shared JS: API client, auth state, notifications, navbar


// ── Auth Helpers ──────────────────────────────────────────────
const Auth = {
  _user: null,

  async getUser() {
    if (this._user) return this._user;
    try {
      const data = await api.get('auth.php?action=me');
      this._user = data.user;
      return this._user;
    } catch {
      return null;
    }
  },

  async requireAuth(redirectTo = 'login.html') {
    const user = await this.getUser();
    if (!user) { window.location.href = redirectTo; return null; }
    return user;
  },

  async requireRole(role, redirectTo = 'index.html') {
    const user = await this.requireAuth();
    if (user && user.role !== role) { window.location.href = redirectTo; return null; }
    return user;
  },

  async logout() {
    await api.post('auth.php?action=logout', {});
    this._user = null;
    window.location.href = 'login.html';
  }
};

// ── Notifications ─────────────────────────────────────────────
const Notifications = {
  async load() {
    try {
      const data = await api.get('notifications.php?action=list');
      this.updateBadge(data.unread_count);
      this.renderPanel(data.notifications);
    } catch {}
  },

  updateBadge(count) {
    const badge = document.getElementById('notif-badge');
    if (!badge) return;
    badge.textContent = count;
    badge.style.display = count > 0 ? 'flex' : 'none';
  },

  renderPanel(notifications) {
    const list = document.getElementById('notif-list');
    if (!list) return;
    if (!notifications.length) {
      list.innerHTML = '<div class="empty-state" style="padding:32px"><p>No notifications yet</p></div>';
      return;
    }
    list.innerHTML = notifications.map(n => `
      <div class="notif-item ${n.is_read ? '' : 'unread'}" onclick="Notifications.markRead(${n.id}, this)">
        ${!n.is_read ? '<div class="notif-dot"></div>' : '<div style="width:8px"></div>'}
        <div>
          <div class="notif-text">${n.message}</div>
          <div class="notif-time">${timeAgo(n.created_at)}</div>
        </div>
      </div>
    `).join('');
  },

  async markRead(id, el) {
    await api.post(`notifications.php?action=read&id=${id}`, {});
    el.classList.remove('unread');
    el.querySelector('.notif-dot')?.remove();
    const badge = document.getElementById('notif-badge');
    if (badge) {
      const current = parseInt(badge.textContent) - 1;
      badge.textContent = current;
      if (current <= 0) badge.style.display = 'none';
    }
  },

  async markAllRead() {
    await api.post('notifications.php?action=read-all', {});
    document.querySelectorAll('.notif-item.unread').forEach(el => {
      el.classList.remove('unread');
      el.querySelector('.notif-dot')?.remove();
    });
    const badge = document.getElementById('notif-badge');
    if (badge) badge.style.display = 'none';
  }
};

// ── UI Helpers ────────────────────────────────────────────────
function showAlert(container, message, type = 'error') {
  const el = document.getElementById(container) || document.querySelector(container);
  if (!el) return;
  el.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
  setTimeout(() => el.innerHTML = '', 5000);
}

function setLoading(btn, loading, text = null) {
  if (loading) {
    btn._origText = btn.textContent;
    btn.textContent = text || 'Loading…';
    btn.disabled = true;
  } else {
    btn.textContent = btn._origText || btn.textContent;
    btn.disabled = false;
  }
}

function timeAgo(dateStr) {
  const diff = (Date.now() - new Date(dateStr)) / 1000;
  if (diff < 60)   return 'just now';
  if (diff < 3600) return `${Math.floor(diff/60)}m ago`;
  if (diff < 86400)return `${Math.floor(diff/3600)}h ago`;
  return `${Math.floor(diff/86400)}d ago`;
}

function formatSalary(min, max) {
  if (!min && !max) return 'Salary not specified';
  const fmt = n => '$' + Number(n).toLocaleString();
  if (min && max) return `${fmt(min)} – ${fmt(max)}`;
  if (min) return `From ${fmt(min)}`;
  return `Up to ${fmt(max)}`;
}

function formatDate(dateStr) {
  return new Date(dateStr).toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric' });
}

function statusBadge(status) {
  const map = {
    pending:  'badge-yellow',
    accepted: 'badge-green',
    rejected: 'badge-red',
    open:     'badge-green',
    closed:   'badge-gray',
  };
  return `<span class="badge ${map[status] || 'badge-gray'}">${status}</span>`;
}

// ── Navbar Init ───────────────────────────────────────────────
async function initNavbar() {
  const user = await Auth.getUser();
  const navLinks  = document.getElementById('nav-links');
  const navActions = document.getElementById('nav-actions');

  if (user) {
    if (navLinks) {
      if (user.role === 'seeker') {
        navLinks.innerHTML = `
          <a href="index.html">Home</a>
          <a href="jobs.html">Find Jobs</a>
          <a href="seeker-dashboard.html">Dashboard</a>
        `;
      } else {
        navLinks.innerHTML = `
          <a href="index.html">Home</a>
          <a href="post-job.html">Post Job</a>
          <a href="employer-dashboard.html">Dashboard</a>
        `;
      }
    }

    if (navActions) {
      navActions.innerHTML = `
        <div style="position:relative">
          <button class="notif-btn" id="notif-toggle" onclick="toggleNotifPanel()">🔔
            <span class="notif-badge" id="notif-badge" style="display:none">0</span>
          </button>
          <div class="notif-panel" id="notif-panel">
            <div class="notif-panel-header">
              <span>Notifications</span>
              <button class="btn btn-sm btn-outline" onclick="Notifications.markAllRead()">Mark all read</button>
            </div>
            <div id="notif-list"><div class="loader"></div></div>
          </div>
        </div>
        <a href="profile.html" class="btn btn-outline btn-sm">👤 ${user.name.split(' ')[0]}</a>
        <button class="btn btn-outline btn-sm" onclick="Auth.logout()">Log Out</button>
      `;
      Notifications.load();
    }
  } else {
    if (navActions) {
      navActions.innerHTML = `
        <a href="login.html" class="btn btn-outline btn-sm">Log In</a>
        <a href="register.html" class="btn btn-primary btn-sm">Sign Up</a>
      `;
    }
  }

  // Highlight active link
  document.querySelectorAll('.nav-links a').forEach(a => {
    if (a.href === window.location.href) a.classList.add('active');
  });
}

function toggleNotifPanel() {
  document.getElementById('notif-panel')?.classList.toggle('open');
}
document.addEventListener('click', e => {
  if (!e.target.closest('#notif-toggle') && !e.target.closest('#notif-panel')) {
    document.getElementById('notif-panel')?.classList.remove('open');
  }
});

// ── Auto-init ─────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', initNavbar);
