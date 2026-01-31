<?php
// app/auth/auth_handler.php
session_start();
require_once __DIR__ . '/../../config/db.php';

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'login') {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['company_id'] = $user['company_id'];
            
            // Fetch real plan from DB
            $stmtC = $pdo->prepare("SELECT plan_type FROM companies WHERE id = ?");
            $stmtC->execute([$user['company_id']]);
            $_SESSION['user_plan'] = $stmtC->fetchColumn() ?: 'Free';
            
            header("Location: ../index.php");
            exit;
        } else {
            header("Location: login.php?error=invalid_credentials");
            exit;
        }
    }

    if ($action === 'register') {
        $name = $_POST['name'] ?? '';
        $company_name = $_POST['company_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $cnpj = $_POST['cnpj'] ?? '';

        try {
            $pdo->beginTransaction();

            // 1. Create Company with 14-day trial for SME
            // 1. Create Company with Free Plan (Limit 10 prod, No Trial)
            $stmtC = $pdo->prepare("INSERT INTO companies (name, cnpj, plan_type, trial_expires_at) VALUES (?, ?, 'Free', NULL)");
            $stmtC->execute([$company_name, $cnpj]);
            $company_id = $pdo->lastInsertId();

            // 2. Create User
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmtU = $pdo->prepare("INSERT INTO users (company_id, name, email, password) VALUES (?, ?, ?, ?)");
            $stmtU->execute([$company_id, $name, $email, $hashed_password]);
            $user_id = $pdo->lastInsertId();

            // 3. Optional: Seed some initial products for new users
            $stmtP = $pdo->prepare("INSERT INTO products (company_id, sku, name, current_price, stock_quantity, category) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtP->execute([$company_id, 'DEMO-001', 'Produto de Exemplo', 99.90, 10, 'Geral']);

            $pdo->commit();

            // 4. Auto-Login after registration
            $stmtL = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmtL->execute([$user_id]);
            $user = $stmtL->fetch();

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['company_id'] = $user['company_id'];
            $_SESSION['user_plan'] = 'Free';

            // 5. Redirect to Stripe Checkout if a paid plan was chosen
            $selected_plan = $_POST['selected_plan'] ?? '';
            if ($selected_plan === 'sme' || $selected_plan === 'pro') {
                header("Location: ../billing/checkout.php?plan=" . $selected_plan);
            } else {
                header("Location: login.php?success=registered");
            }
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            header("Location: register.php?error=" . urlencode($e->getMessage()));
            exit;
        }
    }
}

// Quick Access from Landing Page
if ($action === 'quick_plan') {
    $plan = $_GET['plan'] ?? 'free';
    
    // Find the default demo user (created during setup)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute(['admin@pricesmart.com']);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['company_id'] = $user['company_id'];
        $_SESSION['user_plan'] = ucfirst($plan); // Save plan for UI differentiation

        // Update plan in DB for demo purposes
        $dbPlan = ucfirst($plan);
        $pdo->prepare("UPDATE companies SET plan_type = ? WHERE id = ?")
            ->execute([$dbPlan, $user['company_id']]);

        // Customize the company "Target Margin" based on plan selection to show impact
        $margin = 20.00; // Free
        if ($plan === 'sme') $margin = 35.00;
        if ($plan === 'pro') $margin = 50.00;

        $pdo->prepare("UPDATE companies SET target_margin = ? WHERE id = ?")
            ->execute([$margin, $user['company_id']]);

        header("Location: ../index.php");
        exit;
    } else {
        // Fallback to regular login if demo user is missing
        header("Location: login.php");
        exit;
    }
}

if ($action === 'logout') {
    session_destroy();
    header("Location: login.php");
    exit;
}
