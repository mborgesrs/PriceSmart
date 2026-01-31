<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo | PriceSmart</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
    
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="../assets/css/app.css"> <!-- Reusing main app CSS -->
    <style>
        body {
            display: block !important;
            margin: 0;
            background-color: #f1f5f9;
        }
        .admin-nav {
            background: #1e293b;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-menu {
            display: flex;
            gap: 2rem;
        }
        .admin-link {
            color: #bdc3c7;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.2s;
        }
        .admin-link:hover, .admin-link.active {
            color: white;
        }
        .admin-container {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        .badge-status {
            padding: 4px 12px;
            border-radius: 99px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-active { background: #dcfce7; color: #166534; }
        .status-free { background: #f1f5f9; color: #475569; }
        .status-past_due { background: #fee2e2; color: #991b1b; }
        .status-canceled { background: #fef2f2; color: #ef4444; }
    </style>
</head>
<body>

    <nav class="admin-nav">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <i data-lucide="shield-check" style="color: #60a5fa;"></i>
            <span style="font-family: 'Outfit'; font-weight: 800; font-size: 1.25rem;">PriceSmart <span style="font-weight: 400; opacity: 0.7;">Admin</span></span>
        </div>
        
        <div class="admin-menu">
            <a href="index.php" class="admin-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                <i data-lucide="layout-grid" size="18"></i> Visão Geral
            </a>
            <a href="stripe_config.php" class="admin-link <?= basename($_SERVER['PHP_SELF']) == 'stripe_config.php' ? 'active' : '' ?>">
                <i data-lucide="credit-card" size="18"></i> Configuração Stripe
            </a>
        </div>

        <div style="display: flex; align-items: center; gap: 1.5rem;">
            <div style="text-align: right; line-height: 1.2;">
                <div style="font-size: 0.85rem; font-weight: 600; color: white;"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></div>
                <div style="font-size: 0.7rem; color: #94a3b8;">Super Administrador</div>
            </div>
            <a href="../index.php" class="admin-link">
                <i data-lucide="log-out" size="18"></i> Voltar ao App
            </a>
        </div>
    </nav>

    <div class="admin-container">
