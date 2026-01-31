<?php
// app/includes/auth_check.php
session_start();

if (!isset($_SESSION['user_id'])) {
    // Determine path to login
    $path = (basename(dirname($_SERVER['PHP_SELF'])) === 'app') ? 'auth/login.php' : '../auth/login.php';
    header("Location: $path");
    exit;
}

require_once __DIR__ . '/../../config/db.php';

// Refresh User Info from Database
$stmtU = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmtU->execute([$_SESSION['user_id']]);
$user_data = $stmtU->fetch();

if ($user_data) {
    $_SESSION['user_name'] = $user_data['name'];
}

$user_name = $_SESSION['user_name'];
$company_id = $_SESSION['company_id'];

// Refresh Plan Info from Database
$stmt = $pdo->prepare("SELECT plan_type, trial_expires_at, plan_status, next_billing_at FROM companies WHERE id = ?");
$stmt->execute([$company_id]);
$company_plan = $stmt->fetch();

if ($company_plan) {
    $_SESSION['user_plan'] = $company_plan['plan_type'];
    
    // Check Billing Status (Past Due)
    $current_script = basename($_SERVER['PHP_SELF']);
    $allowed_scripts = ['billing_overdue.php', 'auth_handler.php', 'billing.php'];
    
    if ($company_plan['plan_status'] === 'Past_Due' && !in_array($current_script, $allowed_scripts)) {
        $billing_date = strtotime($company_plan['next_billing_at']);
        $grace_period = 3 * 24 * 60 * 60; // 3 days grace
        
        // If (Billing Date + Grace Period) < Now, then BLOCK
        if (($billing_date + $grace_period) < time()) {
            header("Location: billing_overdue.php");
            exit;
        }
    }

    // Check Trial Expiration
    if ($company_plan['trial_expires_at']) {
        $expiry = strtotime($company_plan['trial_expires_at']);
        $now = time();
        
        if ($expiry < $now) {
            $_SESSION['trial_expired'] = true;
            $_SESSION['trial_days_left'] = 0;
        } else {
            $_SESSION['trial_expired'] = false;
            $diff = $expiry - $now;
            $_SESSION['trial_days_left'] = ceil($diff / (60 * 60 * 24));
        }
    } else {
        $_SESSION['trial_expired'] = false;
        $_SESSION['trial_days_left'] = null;
    }
}
?>
