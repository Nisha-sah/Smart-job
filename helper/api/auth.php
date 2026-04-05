<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/includes/helpers.php';

setCorsHeaders();

// Start session
startSession();

// ── Logout ─────────────────────────────────────
if (($_GET['action'] ?? '') === 'logout') {
    session_destroy();
    jsonSuccess('Logged out successfully.');
}

// ── Only POST allowed for login ────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('POST required.', 405);
}

// ── Get input ─────────────────────────────────
$data = getJson();
$email = sanitize($data['email'] ?? '');
$password = $data['password'] ?? '';
$role = sanitize($data['role'] ?? '');

if (!$email || !$password) {
    jsonError('Email and password are required.');
}

// ── DB (PDO) ──────────────────────────────────
global $pdo;

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    jsonError('No account found.', 401);
}

// Role check
if ($role && $user['role'] !== $role) {
    jsonError('Wrong role.', 403);
}

// Password check
if (!password_verify($password, $user['password'])) {
    jsonError('Incorrect password.', 401);
}

// ── Store session ─────────────────────────────
$_SESSION['user'] = [
    'id' => $user['id'],
    'name' => $user['name'],
    'email' => $user['email'],
    'role' => $user['role'],
];

// ── SUCCESS ───────────────────────────────────
jsonSuccess('Login successful.', [
    'user' => $_SESSION['user']
]);
?>