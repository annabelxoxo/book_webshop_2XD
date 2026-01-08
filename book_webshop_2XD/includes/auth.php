<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ===== STATUS ===== */

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function currentUserId(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/* ===== GUARDS ===== */

function requireLogin(): void {
    if (!isLoggedIn()) {
        $redirect = basename($_SERVER['REQUEST_URI']);
        header("Location: " . APP_URL . "login.php?redirect=" . urlencode($redirect));
        exit;
    }
}

function requireAdmin(): void {
    if (!isLoggedIn() || !isAdmin()) {
        $redirect = basename($_SERVER['REQUEST_URI']);
        header("Location: " . APP_URL . "login.php?redirect=" . urlencode($redirect));
        exit;
    }
}
