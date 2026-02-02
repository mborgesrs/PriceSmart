<?php 
require_once __DIR__ . '/includes/auth_check.php'; 
require_once __DIR__ . '/../config/db.php';

// Fetch existing integrations
$db_integrations = [];
$stmt = $pdo->prepare("SELECT platform, api_key, webhook_url, is_active FROM integrations WHERE company_id = ?");
$stmt->execute([$_SESSION['company_id']]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $db_integrations[$row['platform']] = $row;
}

function getStatusBadge($platform, $db_integrations) {
    if (isset($db_integrations[$platform]) && $db_integrations[$platform]['is_active']) {
        return '<span class="badge badge-success" style="background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 20px;">Conectado</span>';
    }
    return '<span style="color: var(--text-dim);">Não configurado</span>';
}

function getButtonText($platform, $db_integrations) {
    return isset($db_integrations[$platform]) ? 'Configurar' : 'Conectar';
}

function getButtonClass($platform, $db_integrations) {
    return isset($db_integrations[$platform]) ? 'btn-action' : 'btn-action btn-connect';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Integrações | PriceSmart</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
    
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <link rel="stylesheet" href="assets/css/app.css?v=1.3">
    <style>
        .integration-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        .int-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            padding: 2rem;
            border-radius: 24px;
            text-align: center;
            transition: all 0.2s;
            box-shadow: var(--card-shadow);
        }
        .int-card:hover {
            border-color: var(--primary);
            transform: translateY(-5px);
        }
        .int-logo {
            width: 60px;
            height: 60px;
            background: #f1f5f9;
            border-radius: 12px;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: var(--text-dim);
            font-size: 1.25rem;
        }
        .int-status {
            font-size: 0.75rem;
            margin-top: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn-action {
            margin-top: 1.5rem;
            width: 100%;
            padding: 0.75rem;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: #f8fafc;
            color: var(--text-main);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-action:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: #ffffff;
        }
        .btn-connect {
            background: var(--primary);
            color: white;
            border: none;
        }
        .btn-connect:hover {
            background: var(--primary-hover);
            color: white;
            transform: translateY(-2px);
        }
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
            <li class="nav-item"><a href="simulator.php" class="nav-link"><i data-lucide="flask-conical"></i> <span>Simulador Lucro</span></a></li>
            <li class="nav-item"><a href="purchase_simulator.php" class="nav-link"><i data-lucide="shopping-cart"></i> <span>Simulador Compra</span></a></li>
            <li class="nav-item"><a href="integrations.php" class="nav-link active"><i data-lucide="plug-2"></i> <span>Integrações</span></a></li>
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
                    Integrações
                    <div class="btn-help" onclick="showIntegrationsHelp()" title="Ajuda">
                        <i data-lucide="help-circle" style="width: 16px; height: 16px;"></i>
                    </div>
                </h1>
                <p style="color: var(--text-dim);">Conecte seus canais de venda e ERPs para automação total.</p>
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
                    <i data-lucide="lock" style="width: 40px; height: 40px; color: var(--primary);"></i>
                </div>
                <h2 style="font-family: 'Outfit'; margin-bottom: 1rem;">Integrações Automáticas (SME / PRO)</h2>
                <p style="color: var(--text-dim); max-width: 500px; margin: 0 auto 2rem;">
                    As integrações com Bling, Tiny, Mercado Livre e outros canais estão disponíveis apenas nos planos pagos. 
                    No plano Free, você pode gerenciar seu catálogo manualmente.
                </p>
                <a href="settings.php" class="btn btn-primary">Fazer Upgrade Agora</a>
            </div>
        <?php else: ?>
            <div class="integration-grid">
            <!-- Bling -->
            <div class="int-card">
                <div class="int-logo" style="color: #f59e0b;">BL</div>
                <h3>Bling ERP</h3>
                <p style="font-size: 0.875rem; color: var(--text-dim); margin-top: 0.5rem;">Sincronize estoque e custos automaticamente.</p>
                <div class="int-status"><?= getStatusBadge('Bling', $db_integrations) ?></div>
                <div style="display: flex; gap: 0.5rem;">
                    <button class="<?= getButtonClass('Bling', $db_integrations) ?>" onclick="openModal('integrationModal', 'Bling', '<?= $db_integrations['Bling']['api_key'] ?? '' ?>', '<?= $db_integrations['Bling']['webhook_url'] ?? '' ?>')"><?= getButtonText('Bling', $db_integrations) ?></button>
                    <?php if (isset($db_integrations['Bling'])): ?>
                        <button class="btn-action" style="background: #fffbeb; border-color: #fef3c7;" onclick="syncProducts('Bling')" title="Sincronizar Produtos"><i data-lucide="refresh-cw" style="width: 16px; height: 16px;"></i></button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tiny -->
            <div class="int-card">
                <div class="int-logo" style="color: #3b82f6;">TI</div>
                <h3>Tiny ERP</h3>
                <p style="font-size: 0.875rem; color: var(--text-dim); margin-top: 0.5rem;">Integração fluida para fabricantes e lojistas.</p>
                <div class="int-status"><?= getStatusBadge('Tiny', $db_integrations) ?></div>
                <div style="display: flex; gap: 0.5rem;">
                    <button class="<?= getButtonClass('Tiny', $db_integrations) ?>" onclick="openModal('integrationModal', 'Tiny', '<?= $db_integrations['Tiny']['api_key'] ?? '' ?>', '<?= $db_integrations['Tiny']['webhook_url'] ?? '' ?>')"><?= getButtonText('Tiny', $db_integrations) ?></button>
                    <?php if (isset($db_integrations['Tiny'])): ?>
                        <button class="btn-action" style="background: #eff6ff; border-color: #dbeafe;" onclick="syncProducts('Tiny')" title="Sincronizar Produtos"><i data-lucide="refresh-cw" style="width: 16px; height: 16px;"></i></button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Mercado Livre -->
            <div class="int-card">
                <div class="int-logo" style="color: #facc15;">ML</div>
                <h3>Mercado Livre</h3>
                <p style="font-size: 0.875rem; color: var(--text-dim); margin-top: 0.5rem;">Atualização de preços dinâmica em tempo real.</p>
                <div class="int-status"><?= getStatusBadge('MercadoLivre', $db_integrations) ?></div>
                <button class="<?= getButtonClass('MercadoLivre', $db_integrations) ?>" onclick="openModal('integrationModal', 'MercadoLivre', '<?= $db_integrations['MercadoLivre']['api_key'] ?? '' ?>', '<?= $db_integrations['MercadoLivre']['webhook_url'] ?? '' ?>')"><?= getButtonText('MercadoLivre', $db_integrations) ?></button>
            </div>

            <!-- Shopify -->
            <div class="int-card">
                <div class="int-logo" style="color: #10b981;">SH</div>
                <h3>Shopify</h3>
                <p style="font-size: 0.875rem; color: var(--text-dim); margin-top: 0.5rem;">Gestão de preços para sua loja virtual.</p>
                <div class="int-status"><?= getStatusBadge('Shopify', $db_integrations) ?></div>
                <button class="<?= getButtonClass('Shopify', $db_integrations) ?>" onclick="openModal('integrationModal', 'Shopify', '<?= $db_integrations['Shopify']['api_key'] ?? '' ?>', '<?= $db_integrations['Shopify']['webhook_url'] ?? '' ?>')"><?= getButtonText('Shopify', $db_integrations) ?></button>
            </div>

            <!-- Amazon -->
            <div class="int-card">
                <div class="int-logo" style="color: #f97316;">AM</div>
                <h3>Amazon</h3>
                <p style="font-size: 0.875rem; color: var(--text-dim); margin-top: 0.5rem;">Reprecificação automática para Buy Box.</p>
                <div class="int-status"><?= getStatusBadge('Amazon', $db_integrations) ?></div>
                <button class="<?= getButtonClass('Amazon', $db_integrations) ?>" onclick="openModal('integrationModal', 'Amazon', '<?= $db_integrations['Amazon']['api_key'] ?? '' ?>', '<?= $db_integrations['Amazon']['webhook_url'] ?? '' ?>')"><?= getButtonText('Amazon', $db_integrations) ?></button>
            </div>

            <!-- API -->
            <div class="int-card" style="opacity: 0.7;">
                <div class="int-logo"><i data-lucide="code"></i></div>
                <h3>API Customizada</h3>
                <p style="font-size: 0.875rem; color: var(--text-dim); margin-top: 0.5rem;">Para sistemas próprios e integrações via Webhooks.</p>
                <div class="int-status"><span>Apenas Plano Pro</span></div>
                <button class="btn-action" onclick="showToast('Documentação API em breve!', 'info')">Ver Doc</button>
            </div>
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

    <!-- Modal Integração -->
    <div id="integrationModal" class="modal">
        <div class="modal-content">
            <div class="card-title" id="modalTitle">Conectar Integração</div>
            <p id="modalDesc" style="color: var(--text-dim); font-size: 0.85rem; margin-bottom: 2rem;">Insira suas credenciais para sincronizar os dados.</p>
            
            <form id="integrationForm">
                <input type="hidden" id="intPlatform">
                <div class="form-group">
                    <label id="keyLabel">Chave API / Token</label>
                    <input type="password" id="intApiKey" class="form-control" placeholder="••••••••••••••••" required>
                </div>
                <div class="form-group">
                    <label>URL do Webhook (Opcional)</label>
                    <input type="text" id="intWebhook" class="form-control" placeholder="https://seu-sistema.com/api/v1">
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="button" class="btn btn-outline" style="flex: 1;" onclick="closeModal('integrationModal')">Cancelar</button>
                    <button type="button" id="btnTest" class="btn btn-outline" style="flex: 1;" onclick="testConnection()">Validar</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="toast-container"></div>

    <script>
        let currentPlatform = '';

        function showHelp(title, html) {
            document.getElementById('helpContent').innerHTML = `<h3>${title}</h3>${html}`;
            document.getElementById('helpModal').classList.add('active');
        }

        function closeHelp() {
            document.getElementById('helpModal').classList.remove('active');
        }

        function showIntegrationsHelp() {
            showHelp('Conectando seu E-commerce', `
                <p>As integrações permitem que a PriceSmart trabalhe no piloto automático:</p>
                <ul>
                    <li><strong>Sincronização de Estoque:</strong> Buscamos os níveis atuais direto da sua loja.</li>
                    <li><strong>Importação de Pedidos:</strong> Entenda seu volume de vendas para refinar as sugestões da IA.</li>
                    <li><strong>Atualização de Preços:</strong> Envie as novas sugestões de preço direto para os canais.</li>
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
            }, 5000);
        }

        function openModal(id, platform, key = '', webhook = '') {
            currentPlatform = platform;
            document.getElementById('modalTitle').innerText = 'Configurar: ' + platform;
            document.getElementById('intPlatform').value = platform;
            document.getElementById('intApiKey').value = key;
            document.getElementById('intWebhook').value = webhook;
            
            if (platform === 'Bling') {
                document.getElementById('keyLabel').innerText = 'Chave API V3 (Bearer Token)';
            } else if (platform === 'Tiny') {
                document.getElementById('keyLabel').innerText = 'Token API V2';
            } else {
                document.getElementById('keyLabel').innerText = 'Chave API / Token';
            }

            document.getElementById(id).classList.add('active');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        async function testConnection() {
            const btn = document.getElementById('btnTest');
            const platform = currentPlatform;
            const apiKey = document.getElementById('intApiKey').value;

            if (!apiKey) {
                showToast('Insira a chave API para testar', 'error');
                return;
            }

            const originalText = btn.innerText;
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader-2" class="spin"></i>';
            lucide.createIcons();

            try {
                const response = await fetch('api/actions.php?action=test_integration', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ platform, api_key: apiKey })
                });
                const result = await response.json();
                
                if (result.success) {
                    showToast(result.message, 'success');
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('Erro ao testar conexão', 'error');
            } finally {
                btn.disabled = false;
                btn.innerText = originalText;
                lucide.createIcons();
            }
        }

        async function syncProducts(platform) {
            showToast(`Iniciando sincronização com ${platform}...`, 'loading');
            
            try {
                const response = await fetch(`api/erp_import.php?platform=${platform}`);
                const result = await response.json();
                
                if (result.success) {
                    showToast(`${result.message} (${result.imported} novos, ${result.updated} atualizados)`, 'success');
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('Erro ao sincronizar produtos', 'error');
            }
        }

        document.getElementById('integrationForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            const formData = {
                platform: currentPlatform,
                api_key: document.getElementById('intApiKey').value,
                webhook_url: document.getElementById('intWebhook').value
            };

            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader-2" class="spin"></i>';
            lucide.createIcons();

            try {
                const response = await fetch('api/actions.php?action=save_integration', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                const result = await response.json();
                
                if (result.success) {
                    showToast(result.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('Erro ao salvar integração', 'error');
            } finally {
                btn.disabled = false;
                btn.innerText = 'Salvar';
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>
