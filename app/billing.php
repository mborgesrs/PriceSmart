<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$company_id = $_SESSION['company_id'];

// Get Detailed Subscription Info
$stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
$stmt->execute([$company_id]);
$company = $stmt->fetch();

$plan = $company['plan_type'];
$status = $company['plan_status'];
$billing_date = $company['next_billing_at'] ? date('d/m/Y', strtotime($company['next_billing_at'])) : '--/--/----';

$status_colors = [
    'Free' => 'gray',
    'Active' => 'success',
    'Past_Due' => 'danger',
    'Canceled' => 'warning'
];
$status_badge = $status_colors[$status] ?? 'gray';

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Assinatura | PriceSmart</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
    
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="assets/css/app.css?v=1.4">
    <style>
        .billing-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            border: 1px solid var(--border);
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }
        .plan-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .plan-name {
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            color: var(--text-main);
        }
        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-active { background: #dcfce7; color: #166534; }
        .status-danger { background: #fee2e2; color: #991b1b; }
        .status-warning { background: #fef9c3; color: #854d0e; }
        .status-gray { background: #f1f5f9; color: #64748b; }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .info-item label {
            display: block;
            color: var(--text-dim);
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
        }
        .info-item div {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .stripe-button {
            background: #635bff; /* Stripe Blurple */
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }
        .stripe-button:hover { background: #4e46e5; }

        .pricing-section {
            margin-top: 3rem;
        }
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        .pricing-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            transition: all 0.2s;
        }
        .pricing-card.featured {
            border-color: var(--primary);
            box-shadow: 0 10px 30px -10px rgba(59, 130, 246, 0.4);
            transform: translateY(-10px);
        }
        .price {
            font-size: 2.5rem;
            font-family: 'Outfit';
            color: var(--text-main);
            margin: 1rem 0;
        }
        .period { font-size: 1rem; color: var(--text-dim); font-weight: 400; }
        .features {
            list-style: none;
            padding: 0;
            margin: 2rem 0;
            text-align: left;
        }
        .features li {
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-dim);
        }
        .features li i { color: #10b981; width: 16px; }

    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-logo">
            <i data-lucide="zap"></i>
            <span>PriceSmart</span>
        </div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="index.php" class="nav-link"><i data-lucide="layout-dashboard"></i> <span>Dashboard</span></a></li>
            <li class="nav-item"><a href="products.php" class="nav-link"><i data-lucide="package"></i> <span>Catálogo</span></a></li>
            <li class="nav-item"><a href="pricing.php" class="nav-link"><i data-lucide="tags"></i> <span>Precificação IA</span></a></li>
            <li class="nav-item"><a href="costs.php" class="nav-link"><i data-lucide="dollar-sign"></i> <span>Custos Gerais</span></a></li>
            <li class="nav-item"><a href="sku_costs.php" class="nav-link"><i data-lucide="list-checks"></i> <span>Custos por SKU</span></a></li>
            <li class="nav-item"><a href="simulator.php" class="nav-link"><i data-lucide="flask-conical"></i> <span>Simulador</span></a></li>
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
                <h1 style="font-family: 'Outfit'; margin-bottom: 0.25rem;">Minha Assinatura</h1>
                <p style="color: var(--text-dim);">Gerencie seu plano e método de pagamento.</p>
            </div>
            <div class="user-profile">
                <span><?= $_SESSION['user_name'] ?></span>
                <div class="avatar"><?= substr($_SESSION['user_name'],0,1) ?></div>
            </div>
        </header>

        <div class="billing-card">
            <div class="plan-header">
                <div>
                    <div class="plan-name"><?= $plan == 'SME' ? 'Plano SME (Pequenas Empresas)' : ($plan == 'Enterprise' ? 'Plano Enterprise' : 'Plano Gratuito') ?></div>
                    <div style="margin-top: 0.5rem;">
                        <span class="status-badge status-<?= $status == 'Past_Due' ? 'danger' : ($status == 'Active' ? 'active' : 'gray') ?>">
                            <?= $status == 'Past_Due' ? 'Pagamento Pendente' : ($status == 'Active' ? 'Ativo' : 'Gratuito / Inativo') ?>
                        </span>
                    </div>
                </div>
                <div>
                   <?php if($status !== 'Free'): ?>
                        <button class="stripe-button" onclick="openStripePortal()">
                            <i data-lucide="credit-card"></i> Gerenciar Pagamento
                        </button>
                   <?php endif; ?>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <label>Próxima Cobrança</label>
                    <div><?= $billing_date ?></div>
                </div>
                <div class="info-item">
                    <label>Valor Mensal</label>
                    <div style="font-family: monospace;">
                        <?= $plan == 'SME' ? 'R$ 197,00' : ($plan == 'Enterprise' ? 'Sob Consulta' : 'R$ 0,00') ?>
                    </div>
                </div>
                <div class="info-item">
                    <label>ID do Cliente</label>
                    <div style="font-size: 0.9rem; font-family: monospace; color: var(--text-dim);">
                        <?= $company['external_customer_id'] ?? 'N/A' ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if($status === 'Past_Due'): ?>
        <div style="background: #fee2e2; border: 1px solid #fca5a5; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; display: flex; gap: 1rem; align-items: center;">
            <i data-lucide="alert-triangle" style="color: #991b1b;"></i>
            <div style="flex: 1;">
                <h4 style="margin: 0 0 0.25rem 0; color: #991b1b;">Regularize sua conta</h4>
                <p style="margin: 0; color: #7f1d1d; font-size: 0.9rem;">
                    Seu pagamento está pendente. Evite o bloqueio dos seus dados atualizando seu cartão.
                </p>
            </div>
            <button class="btn btn-primary" style="background: #991b1b;" onclick="openStripePortal()">Regularizar</button>
        </div>
        <?php endif; ?>

        <div class="pricing-section">
            <h3 class="card-title">Mudar de Plano</h3>
            <div class="pricing-grid">
                <!-- Free -->
                <div class="pricing-card" style="background: #0f172a; border-color: #1e293b; color: white;">
                    <h3 style="color: white; font-weight: 700; font-size: 1.25rem;">Plano Free</h3>
                    <div class="price" style="color: white;">R$ 0<span class="period" style="color: #94a3b8;">/mês</span></div>
                    <ul class="features" style="color: #94a3b8;">
                        <li><i data-lucide="check" style="color: #94a3b8;"></i> Até 10 produtos</li>
                        <li><i data-lucide="check" style="color: #94a3b8;"></i> Precificação Básica</li>
                        <li><i data-lucide="check" style="color: #94a3b8;"></i> Dashboard de Custos</li>
                        <li><i data-lucide="check" style="color: #94a3b8;"></i> Suporte via Comunidade</li>
                    </ul>
                    <?php if($plan === 'Free'): ?>
                        <button class="btn btn-outline" disabled style="width: 100%; border-color: #334155; color: #64748b;">Plano Atual</button>
                    <?php else: ?>
                        <button class="btn btn-outline" style="width: 100%; border-color: #334155; color: white;" onclick="alert('Entre em contato para downgrade.')">Começar Agora</button>
                    <?php endif; ?>
                </div>

                <!-- SME -->
                <div class="pricing-card" style="background: #0f172a; border: 2px solid #3b82f6; position: relative; color: white;">
                    <div style="position: absolute; top: 12px; right: -30px; background: #3b82f6; color: white; font-size: 0.6rem; padding: 4px 30px; transform: rotate(45deg); font-weight: 800; text-transform: uppercase;">Mais Popular</div>
                    <h3 style="color: white; font-weight: 700; font-size: 1.25rem;">Plano SME</h3>
                    <div class="price" style="color: white;">R$ 197<span class="period" style="color: #94a3b8;">/mês</span></div>
                    <ul class="features" style="color: #94a3b8;">
                        <li><i data-lucide="check" style="color: #94a3b8;"></i> Catálogo Completo</li>
                        <li><i data-lucide="check" style="color: #94a3b8;"></i> Relatórios Avançados</li>
                        <li><i data-lucide="check" style="color: #94a3b8;"></i> Integração Marketplaces</li>
                        <li><i data-lucide="check" style="color: #94a3b8;"></i> Integração ERP (Bling/Tiny)</li>
                    </ul>
                    <?php if($plan === 'SME'): ?>
                        <button class="btn btn-primary" disabled style="width: 100%; background: #1e293b; color: #64748b; border: none;">Plano Atual</button>
                    <?php else: ?>
                        <button class="btn btn-primary" style="width: 100%; background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%); border: none; box-shadow: 0 0 20px rgba(59, 130, 246, 0.5);" onclick="startCheckout('SME', '197,00', 'SME')">Assinar Agora</button>
                    <?php endif; ?>
                </div>

                <!-- Pro -->
                <div class="pricing-card" style="background: #0f172a; border-color: #1e293b; color: white;">
                    <h3 style="color: white; font-weight: 700; font-size: 1.25rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                        Plano Pro <span style="background: #1d4ed8; font-size: 0.7rem; padding: 2px 6px; border-radius: 4px;">novo</span>
                    </h3>
                    <div class="price" style="color: white;">R$ 497<span class="period" style="color: #94a3b8;">/mês</span></div>
                    <ul class="features" style="color: #94a3b8;">
                        <li><i data-lucide="check" style="color: #94a3b8;"></i> Precificação Dinâmica + IA</li>
                        <li><i data-lucide="check" style="color: #94a3b8;"></i> API de Integração</li>
                        <li><i data-lucide="check" style="color: #94a3b8;"></i> Multi-empresas</li>
                        <li><i data-lucide="check" style="color: #94a3b8;"></i> Suporte Premium 24/7</li>
                    </ul>
                    <?php if($plan === 'Pro'): ?>
                         <button class="btn btn-outline" disabled style="width: 100%;">Plano Atual</button>
                    <?php else: ?>
                        <button class="btn btn-outline" style="width: 100%; border-color: #334155; color: white;" onclick="startCheckout('Pro', '497,00', 'Pro')">Quero ser Pro</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Checkout Confirmation Modal -->
        <div id="checkoutModal" class="modal">
            <div class="modal-content" style="max-width: 450px;">
                <div style="text-align: center;">
                    <div style="width: 64px; height: 64px; background: rgba(59, 130, 246, 0.1); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
                        <i data-lucide="credit-card" style="width: 32px; height: 32px; color: var(--primary);"></i>
                    </div>
                    <h3 style="font-family: 'Outfit'; margin-bottom: 0.75rem; color: var(--text-main);">Confirmar Assinatura?</h3>
                    <p style="color: var(--text-dim); line-height: 1.6; margin-bottom: 2rem;">
                        Você será redirecionado para o ambiente seguro da Stripe para finalizar a assinatura do <strong id="modalPlanName">Plano SME</strong>.<br>
                        Valor: <strong id="modalPlanPrice" style="color: var(--text-main);">R$ 197,00/mês</strong>
                    </p>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button class="btn btn-outline" style="flex: 1;" onclick="closeCheckoutModal()">Cancelar</button>
                    <button class="btn btn-primary" style="flex: 1;" onclick="proceedToStripe()">Sim, Assinar</button>
                </div>
            </div>
        </div>

        <!-- Portal Confirmation Modal -->
        <div id="portalModal" class="modal">
            <div class="modal-content" style="max-width: 450px;">
                <div style="text-align: center;">
                    <div style="width: 64px; height: 64px; background: rgba(16, 185, 129, 0.1); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
                        <i data-lucide="user-check" style="width: 32px; height: 32px; color: #10b981;"></i>
                    </div>
                    <h3 style="font-family: 'Outfit'; margin-bottom: 0.75rem; color: var(--text-main);">Acessar Portal do Cliente</h3>
                    <p style="color: var(--text-dim); line-height: 1.6; margin-bottom: 2rem;">
                         Você será redirecionado para o portal seguro da Stripe onde poderá atualizar cartões, baixar faturas e alterar dados de cobrança.
                         <br><br>
                         <em style="font-size: 0.85rem; color: #64748b;">(Simulação de Ambiente de Teste)</em>
                    </p>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button class="btn btn-outline" style="flex: 1;" onclick="closeModal('portalModal')">Cancelar</button>
                    <button class="btn btn-primary" style="flex: 1; background: #10b981; border: none;" onclick="confirmPortalRedirect()">Acessar Portal</button>
                </div>
            </div>
        </div>

    </main>

    <div id="toast-container"></div>

    <script>
        lucide.createIcons();
        let selectedPlanType = '';

        // Helper to open generic modals
        function openModal(id) { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }

        function openStripePortal() {
            openModal('portalModal');
        }

        function confirmPortalRedirect() {
            closeModal('portalModal');
            showToast('Redirecionando para Stripe...', 'loading');
            setTimeout(() => {
                showToast('Portal aberto em nova aba (Simulado)', 'success');
            }, 1500);
        }

        function startCheckout(planName, price, type) {
            selectedPlanType = type;
            document.getElementById('modalPlanName').innerText = 'Plano ' + planName;
            document.getElementById('modalPlanPrice').innerText = 'R$ ' + price + '/mês';
            document.getElementById('checkoutModal').classList.add('active');
            lucide.createIcons();
        }

        function closeCheckoutModal() {
            document.getElementById('checkoutModal').classList.remove('active');
        }

        function proceedToStripe() {
            closeCheckoutModal();
            showToast('Iniciando Checkout Seguro...', 'loading');
            
            setTimeout(() => {
                // Simulating Success
                updateLocalPlan(selectedPlanType, 'Active');
            }, 1500);
        }

        async function updateLocalPlan(plan, status) {
            try {
                // In production this would wait for Stripe Webhook
                showToast('Assinatura Confirmada!', 'success');
                setTimeout(() => {
                   window.location.reload();
                }, 1000);
            } catch(e) {
                console.error(e);
            }
        }

        function showToast(message, type = 'info') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            let icon = 'info';
            if(type === 'success') icon = 'check-circle';
            if(type === 'loading') icon = 'loader-2';
            
            toast.innerHTML = `<i data-lucide="${icon}" class="${type === 'loading' ? 'spin' : ''}"></i> <span>${message}</span>`;
            container.appendChild(toast);
            lucide.createIcons();
            setTimeout(() => toast.remove(), 4000);
        }
    </script>
</body>
</html>
