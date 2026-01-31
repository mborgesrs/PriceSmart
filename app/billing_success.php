<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../vendor/autoload.php';

// 1. Fetch Stripe Secret from DB
$stmt = $pdo->prepare("SELECT conf_value FROM system_config WHERE conf_key = 'stripe_secret_key'");
$stmt->execute();
$stripe_secret = $stmt->fetchColumn();

if (!$stripe_secret) die("Erro de configuração.");

\Stripe\Stripe::setApiKey($stripe_secret);

$session_id = $_GET['session_id'] ?? null;
$customer_name = "";

if ($session_id) {
    try {
        $session = \Stripe\Checkout\Session::retrieve($session_id);
        $customer_name = $session->customer_details->name;
        
        // Optionally, we could update the DB here too, but Webhook is more reliable.
        // We'll just force a refresh of the session data in auth_check
    } catch (Exception $e) {
        // Silent fail or log
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Sucesso! | PriceSmart</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Outfit:wght@700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { 
            background: #0a0b10; 
            color: white; 
            font-family: 'Inter', sans-serif; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            height: 100vh; 
            margin: 0; 
        }
        .card { 
            background: rgba(255,255,255,0.05); 
            padding: 3rem; 
            border-radius: 24px; 
            text-align: center; 
            max-width: 400px;
            border: 1px solid rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
        }
        .icon { color: #22c55e; margin-bottom: 1.5rem; }
        h1 { font-family: 'Outfit'; margin-bottom: 1rem; }
        p { color: #94a3b8; margin-bottom: 2rem; }
        .btn { 
            background: #3b82f6; 
            color: white; 
            text-decoration: none; 
            padding: 0.75rem 2rem; 
            border-radius: 12px; 
            font-weight: 600; 
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon"><i data-lucide="check-circle" size="64"></i></div>
        <h1>Assinatura Ativa!</h1>
        <p>Parabéns <?= htmlspecialchars($customer_name) ?>! Sua conta foi atualizada e você já tem acesso total aos recursos do seu plano.</p>
        <a href="index.php" class="btn">Ir para o Dashboard</a>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>
