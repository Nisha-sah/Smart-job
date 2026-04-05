<?php
// backend/includes/helpers.php
// Shared utility functions

require_once __DIR__ . '/../config/database.php';

// ── Session ──────────────────────────────────────────────────
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function getAuthUser() {
    startSession();
    return $_SESSION['user'] ?? null;
}

function requireAuth($role = null) {
    $user = getAuthUser();
    if (!$user) {
        jsonError('Unauthorized. Please log in.', 401);
    }
    if ($role && $user['role'] !== $role) {
        jsonError('Forbidden. Insufficient permissions.', 403);
    }
    return $user;
}

// ── JSON Responses ────────────────────────────────────────────
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function jsonError($message, $code = 400) {
    jsonResponse(['error' => $message], $code);
}

function jsonSuccess($message, $data = []) {
    jsonResponse(array_merge(['message' => $message], $data));
}

// ── CORS (dev helper) ─────────────────────────────────────────
function setCorsHeaders() {
    header('Access-Control-Allow-Origin: http://localhost:8080');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }
}

// ── Input Sanitization ────────────────────────────────────────
function sanitize($value) {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

function getJson() {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}
?>