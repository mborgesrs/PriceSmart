<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Check if REALLY overdue (double check to prevent direct access if not needed, although harmless)
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento Pendente | PriceSmart</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            background-color: #f1f5f9;
            font-family: 'Inter', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            color: #1e293b;
        }
        .card {
            background: white;
            padding: 3rem;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            text-align: center;
            max-width: 500px;
            width: 90%;
            border: 1px solid #e2e8f0;
        }
        .icon-container {
            width: 80px;
            height: 80px;
            background: #fee2e2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
        }
        h1 {
            font-family: 'Outfit', sans-serif;
            margin-bottom: 1rem;
            color: #991b1b;
        }
        p {
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        .btn {
            background: #3b82f6;
            color: white;
            padding: 1rem 2rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            width: 100%;
            box-sizing: border-box;
        }
        .btn:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }
        .logout-link {
            display: block;
            margin-top: 1.5rem;
            color: #64748b;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .logout-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-container">
            <i data-lucide="alert-octagon" style="width: 40px; height: 40px; color: #ef4444;"></i>
        </div>
        <h1>Pagamento Pendente</h1>
        <p>Identificamos que a renovação da sua assinatura não foi processada. Para continuar utilizando o PriceSmart e acessar seus dados, por favor regularize seu plano.</p>
        
        <a href="billing.php" class="btn">Regularizar Agora</a>
        <a href="auth/auth_handler.php?action=logout" class="logout-link">Sair e resolver depois</a>
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
