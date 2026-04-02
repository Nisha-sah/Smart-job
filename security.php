<?php
// backend/api/security.php
// DEV 1 — Security: Forgot Password & Reset Password

require_once __DIR__ . '/../includes/helpers.php';
setCorsHeaders();

$action = $_GET['action'] ?? '';

match ($action) {
    'forgot-password' => handleForgot(),
    'reset-password'  => handleReset(),
    default           => jsonError('Unknown action', 404),
};

// ── Forgot Password ───────────────────────────────────────────
function handleForgot() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('POST required', 405);

    $data  = getJson();
    $email = sanitize($data['email'] ?? '');
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) jsonError('Valid email required.');

    $db   = getDB();
    $stmt = $db->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    // Always succeed to prevent email enumeration
    if ($user) {
        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $upd = $db->prepare("UPDATE users SET reset_token=?, reset_token_exp=? WHERE id=?");
        $upd->bind_param('ssi', $token, $expires, $user['id']);
        $upd->execute();

        // In production: send email with reset link
        // mail($email, 'Reset your password', "Link: http://yoursite.com/reset?token=$token");
        // For development, we return the token directly:
        jsonSuccess('Reset link sent. Check your email.', ['dev_token' => $token]);
    }

    jsonSuccess('If that email is registered, a reset link has been sent.');
}

// ── Reset Password ────────────────────────────────────────────
function handleReset() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('POST required', 405);

    $data     = getJson();
    $token    = sanitize($data['token']    ?? '');
    $password = $data['password'] ?? '';

    if (!$token || !$password) jsonError('Token and new password are required.');
    if (strlen($password) < 8) jsonError('Password must be at least 8 characters.');

    $db   = getDB();
    $stmt = $db->prepare(
        "SELECT id FROM users WHERE reset_token=? AND reset_token_exp > NOW()"
    );
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) jsonError('Invalid or expired reset token.', 400);

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $upd  = $db->prepare(
        "UPDATE users SET password=?, reset_token=NULL, reset_token_exp=NULL WHERE id=?"
    );
    $upd->bind_param('si', $hash, $user['id']);
    $upd->execute();

    jsonSuccess('Password reset successfully. You can now log in.');
}
