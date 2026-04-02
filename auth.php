<?php
// backend/api/auth.php
// DEV 1 — User Authentication & Profile Management
// Endpoints: POST /register | POST /login | POST /logout | GET /me | PUT /profile

require_once __DIR__ . '/../includes/helpers.php';
setCorsHeaders();
startSession();

$method = $_SERVER['REQUEST_METHOD'];
// Simple routing via ?action=
$action = $_GET['action'] ?? '';

match ($action) {
    'register' => handleRegister(),
    'login'    => handleLogin(),
    'logout'   => handleLogout(),
    'me'       => handleMe(),
    'profile'  => handleProfile(),
    default    => jsonError('Unknown action', 404),
};

// ── Register ─────────────────────────────────────────────────
function handleRegister() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('POST required', 405);

    $data = getJson();
    $name  = sanitize($data['name']  ?? '');
    $email = sanitize($data['email'] ?? '');
    $pass  = $data['password'] ?? '';
    $role  = $data['role']     ?? '';

    if (!$name || !$email || !$pass || !$role) jsonError('All fields are required.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))  jsonError('Invalid email address.');
    if (!in_array($role, ['seeker', 'employer']))    jsonError('Invalid role.');
    if (strlen($pass) < 8) jsonError('Password must be at least 8 characters.');

    $db   = getDB();
    $check = $db->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param('s', $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) jsonError('Email already registered.');

    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $stmt = $db->prepare(
        "INSERT INTO users (name, email, password, role) VALUES (?,?,?,?)"
    );
    $stmt->bind_param('ssss', $name, $email, $hash, $role);
    $stmt->execute();
    $userId = $db->insert_id;

    $_SESSION['user'] = ['id' => $userId, 'name' => $name, 'email' => $email, 'role' => $role];
    jsonSuccess('Registration successful.', ['user' => $_SESSION['user']]);
}

// ── Login ─────────────────────────────────────────────────────
function handleLogin() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('POST required', 405);

    $data  = getJson();
    $email = sanitize($data['email'] ?? '');
    $pass  = $data['password'] ?? '';

    if (!$email || !$pass) jsonError('Email and password are required.');

    $db   = getDB();
    $stmt = $db->prepare("SELECT id, name, email, role, password FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user || !password_verify($pass, $user['password'])) {
        jsonError('Invalid email or password.', 401);
    }

    unset($user['password']);
    $_SESSION['user'] = $user;
    jsonSuccess('Login successful.', ['user' => $user]);
}

// ── Logout ────────────────────────────────────────────────────
function handleLogout() {
    session_destroy();
    jsonSuccess('Logged out successfully.');
}

// ── Me (get current user) ─────────────────────────────────────
function handleMe() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') jsonError('GET required', 405);
    $user = requireAuth();

    $db   = getDB();
    $stmt = $db->prepare(
        "SELECT id, name, email, role, phone, location, bio, avatar, created_at FROM users WHERE id = ?"
    );
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();

    jsonResponse(['user' => $profile]);
}

// ── Update Profile ────────────────────────────────────────────
function handleProfile() {
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') jsonError('PUT required', 405);
    $user = requireAuth();

    $data     = getJson();
    $name     = sanitize($data['name']     ?? '');
    $phone    = sanitize($data['phone']    ?? '');
    $location = sanitize($data['location'] ?? '');
    $bio      = sanitize($data['bio']      ?? '');

    if (!$name) jsonError('Name is required.');

    $db   = getDB();
    $stmt = $db->prepare(
        "UPDATE users SET name=?, phone=?, location=?, bio=? WHERE id=?"
    );
    $stmt->bind_param('ssssi', $name, $phone, $location, $bio, $user['id']);
    $stmt->execute();

    // Refresh session
    $_SESSION['user']['name'] = $name;
    jsonSuccess('Profile updated successfully.');
}
