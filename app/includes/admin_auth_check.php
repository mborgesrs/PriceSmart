<?php
// app/includes/admin_auth_check.php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Verify Admin Status fresh from DB (security)
$stmt = $pdo->prepare("SELECT is_super_admin FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$is_admin = $stmt->fetchColumn();

if (!$is_admin) {
    // Log unauthorized attempt?
    header("Location: ../index.php?error=unauthorized_admin");
    exit;
}

// If we are here, user is SUPER ADMIN
?>
