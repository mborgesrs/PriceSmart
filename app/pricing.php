<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$company_id = $_SESSION['company_id'];

// Suggestions with product details
$stmt = $pdo->prepare("
    SELECT s.*, p.name as product_name, p.current_price, p.stock_quantity, p.sku, p.category
    FROM ai_suggestions s
    JOIN products p ON s.product_id = p.id
    WHERE p.company_id = ? AND s.status = 'Pending'
    ORDER BY s.created_at DESC
");
$stmt->execute([$company_id]);
$suggestions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Precificação IA | PriceSmart</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="assets/css/app.css?v=1.3">
    <style>
        .pricing-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: #eff6ff;
            padding: 1.5rem;
            border-radius: 20px;
            border: 1px solid #dbeafe;
        }
        .ai-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #10b981;
            font-weight: 600;
        }
        .pulse {
            width: 10px; height: 10px; background: #10b981; border-radius: 50%;
            animation: pulse-green 2s infinite;
        }
        @keyframes pulse-green {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
        .suggestion-chip {
            background: #dbeafe;
            color: #1d4ed8;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-weight: 700;
        }
        .reason-box {
            font-size: 0.8rem;
            color: var(--text-dim);
            max-width: 250px;
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-logo"><i data-lucide="zap"></i> <span>PriceSmart</span></div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="index.php" class="nav-link"><i data-lucide="layout-dashboard"></i> <span>Dashboard</span></a></li>
            <li class="nav-item"><a href="products.php" class="nav-link"><i data-lucide="package"></i> <span>Catálogo</span></a></li>
            <li class="nav-item"><a href="pricing.php" class="nav-link active"><i data-lucide="tags"></i> <span>Precificação IA</span></a></li>
            <li class="nav-item">
                <a href="costs.php" class="nav-link">
                    <i data-lucide="dollar-sign"></i>
                    <span>Custos Gerais</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="sku_costs.php" class="nav-link">
                    <i data-lucide="list-checks"></i>
                    <span>Custos por SKU</span>
                </a>
            </li>
            <li class="nav-item"><a href="simulator.php" class="nav-link"><i data-lucide="flask-conical"></i> <span>Simulador</span></a></li>
            <li class="nav-item"><a href="integrations.php" class="nav-link"><i data-lucide="plug-2"></i> <span>Integrações</span></a></li>
        </ul>
        <div class="nav-footer">
            <a href="settings.php" class="nav-link"><i data-lucide="settings"></i> <span>Configurações</span></a>
            <a href="auth/auth_handler.php?action=logout" class="nav-link" style="color: #ef4444;"><i data-lucide="log-out"></i> <span>Sair</span></a>
        </div>
    </aside>

    <main class="main-content">
        <?php include __DIR__ . '/includes/trial_banner.php'; ?>
        <header class="app-header">
            <div>
                <h1 style="font-family: 'Outfit'; margin-bottom: 0.25rem;">
                    Inteligência de Precificação
                    <div class="btn-help" onclick="showPricingHelp()" title="Ajuda">
                        <i data-lucide="help-circle" style="width: 16px; height: 16px;"></i>
                    </div>
                </h1>
                <p style="color: var(--text-dim);">Sugestões da IA baseadas em concorrência e margem real.</p>
            </div>
            <div class="user-profile">
                <span class="badge badge-primary" style="margin-right: 0.5rem; background: rgba(59, 130, 246, 0.1); color: var(--primary); border: 1px solid rgba(59, 130, 246, 0.2);"><?= $_SESSION['user_plan'] ?? 'SME' ?></span>
                <span><?= $_SESSION['user_name'] ?></span>
                <div class="avatar"><?= substr($_SESSION['user_name'],0,1) ?></div>
            </div>
        </header>

        <?php if ($_SESSION['user_plan'] === 'Free'): ?>
            <div class="card-section" style="text-align: center; padding: 4rem 2rem;">
                <div style="background: rgba(59, 130, 246, 0.1); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                    <i data-lucide="brain-circuit" style="width: 40px; height: 40px; color: var(--primary);"></i>
                </div>
                <h2 style="font-family: 'Outfit'; margin-bottom: 1rem;">Sugestões de IA (SME / PRO)</h2>
                <p style="color: var(--text-dim); max-width: 500px; margin: 0 auto 2rem;">
                    O motor de inteligência que analisa a concorrência e sugere preços automáticos está disponível a partir do plano SME.
                </p>
                <a href="billing.php" class="btn btn-primary">Migrar para SME</a>
            </div>
        <?php else: ?>
            <div class="pricing-controls">
                <div>
                    <div class="ai-status"><div class="pulse"></div> Motor IA Online</div>
                    <p style="font-size: 0.875rem; color: var(--text-dim); margin-top: 0.25rem;">Monitorando <?= count($suggestions) ?> oportunidades agora.</p>
                </div>
                <button class="btn btn-primary" id="btnReanalyze" onclick="reanalyzeAll()">Reanalisar Tudo</button>
            </div>

            <div class="card-section">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Preço Atual</th>
                        <th>Sugestão</th>
                        <th>Motivo</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suggestions as $s): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: var(--text-main);"><?= $s['product_name'] ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-dim);">SKU: <?= $s['sku'] ?></div>
                        </td>
                        <td>R$ <?= number_format($s['current_price'], 2, ',', '.') ?></td>
                        <td><span class="suggestion-chip">R$ <?= number_format($s['suggested_price'], 2, ',', '.') ?></span></td>
                        <td><div class="reason-box"><?= $s['reason'] ?></div></td>
                        <td>
                            <button class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.8rem;" onclick="applySuggestion(<?= $s['id'] ?>, this)">
                                <i data-lucide="check" style="width: 14px;"></i> Aceitar
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </main>

    <!-- Help Modal -->
    <div id="helpModal" class="modal">
        <div class="modal-content">
            <div id="helpContent" class="help-modal-content"></div>
            <div style="margin-top: 2rem; text-align: right;">
                <button class="btn btn-primary" onclick="closeHelp()">Entendi</button>
            </div>
        </div>
    </div>

    <div id="toast-container"></div>

    <script>
        function showHelp(title, html) {
            document.getElementById('helpContent').innerHTML = `<h3>${title}</h3>${html}`;
            document.getElementById('helpModal').classList.add('active');
        }

        function closeHelp() {
            document.getElementById('helpModal').classList.remove('active');
        }

        function showPricingHelp() {
            showHelp('IA de Precificação', `
                <p>Nesta tela, a nossa inteligência analisa cada SKU individualmente:</p>
                <ul>
                    <li><strong>Análise de Margem:</strong> Calculamos se o preço atual cobre seus custos fixos e impostos.</li>
                    <li><strong>Sugestão da IA:</strong> Proposta de novo preço para maximizar lucro ou volume.</li>
                    <li><strong>Ações Rápidas:</strong> Aceite a sugestão com um clique para atualizar seus canais.</li>
                    <li><strong>Reanalisar Tudo:</strong> Força a IA a reprocessar todo o catálogo com os dados mais recentes.</li>
                </ul>
            `);
        }

        lucide.createIcons();

        function showToast(message, type = 'info') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            
            let icon = 'info';
            if(type === 'success') icon = 'check-circle';
            if(type === 'error') icon = 'alert-circle';
            if(type === 'loading') icon = 'loader-2';

            toast.innerHTML = `<i data-lucide="${icon}" class="${type === 'loading' ? 'spin' : ''}"></i> <span>${message}</span>`;
            container.appendChild(toast);
            lucide.createIcons();
            
            setTimeout(() => {
                toast.classList.add('hiding');
                setTimeout(() => toast.remove(), 500);
            }, 5000); // 5 segundos conforme solicitado
        }

        async function reanalyzeAll() {
            const btn = document.getElementById('btnReanalyze');
            const originalText = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader-2" class="spin"></i> Analisando...';
            lucide.createIcons();
            showToast('IA iniciou o reprocessamento...', 'loading');

            try {
                const response = await fetch('api/actions.php?action=reanalyze_all', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                const result = await response.json();
                
                if (result.success) {
                    showToast('Análise concluída! Sugestões atualizadas.', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast('Erro na análise: ' + result.message, 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Erro de conexão com o servidor', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
                lucide.createIcons();
            }
        }

        async function applySuggestion(id, btn) {
            if (!confirm('Aplicar agora?')) return;
            try {
                const response = await fetch('api/actions.php?action=apply_suggestion', {
                    method: 'POST',
                    body: JSON.stringify({ id: id }),
                    headers: { 'Content-Type': 'application/json' }
                });
                const result = await response.json();
                if (result.success) {
                    btn.innerText = 'OK!';
                    btn.style.background = '#10b981';
                    btn.style.color = 'white';
                    setTimeout(() => window.location.reload(), 800);
                }
            } catch (err) { console.error(err); }
        }
    </script>
</body>
</html>
