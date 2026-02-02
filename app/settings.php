<?php 
require_once __DIR__ . '/includes/auth_check.php'; 
require_once __DIR__ . '/../config/db.php';

$company_id = $_SESSION['company_id'];
$stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
$stmt->execute([$company_id]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    $company = [
        'name' => 'Minha Loja', 
        'cnpj' => '', 
        'tax_regime' => 'Simples Nacional', 
        'base_tax_rate' => 6.00, 
        'target_margin' => 30.00
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações | PriceSmart</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
    
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="assets/css/app.css?v=1.4">
    <style>
        .settings-container {
            max-width: 900px;
        }
        .main-card {
            background: var(--bg-card);
            padding: 1.75rem;
            border-radius: 20px;
            border: 1px solid var(--border);
            box-shadow: var(--card-shadow);
        }
        .section-subtitle {
            font-family: 'Outfit';
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .section-subtitle i {
            color: var(--primary);
            width: 18px;
        }
        .form-divider {
            height: 1px;
            background: #f1f5f9;
            margin: 1.25rem 0;
            border: none;
        }
        .input-group {
            margin-bottom: 1rem;
        }
        .input-group label {
            display: block;
            margin-bottom: 0.4rem;
            color: var(--text-main);
            font-size: 0.85rem;
            font-weight: 700;
        }
        .form-control {
            width: 100%;
            padding: 0.65rem 0.85rem;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            color: var(--text-main);
            outline: none;
            box-sizing: border-box;
            font-family: inherit;
            font-size: 0.9rem;
        }
        .form-control:focus {
            border-color: var(--primary);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }
        .btn-save {
            background: var(--primary);
            color: white;
            padding: 1rem 2.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-save:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3);
        }
        .toggle-switch {
            display: flex;
            align-items: center;
            gap: 1rem;
            cursor: pointer;
            font-weight: 600;
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
            <li class="nav-item"><a href="costs.php" class="nav-link"><i data-lucide="dollar-sign"></i> <span>Custos Gerais</span></a></li>
            <li class="nav-item"><a href="sku_costs.php" class="nav-link"><i data-lucide="list-checks"></i> <span>Custos por SKU</span></a></li>
            <li class="nav-item"><a href="simulator.php" class="nav-link"><i data-lucide="flask-conical"></i> <span>Simulador Lucro</span></a></li>
            <li class="nav-item"><a href="purchase_simulator.php" class="nav-link"><i data-lucide="shopping-cart"></i> <span>Simulador Compra</span></a></li>
            <li class="nav-item"><a href="integrations.php" class="nav-link"><i data-lucide="plug-2"></i> <span>Integrações</span></a></li>
        </ul>
        <div class="nav-footer">
            <a href="settings.php" class="nav-link active">
                <i data-lucide="settings"></i> <span>Configurações</span>
            </a>
            <a href="auth/auth_handler.php?action=logout" class="nav-link" style="color: #ef4444;">
                <i data-lucide="log-out"></i> <span>Sair</span>
            </a>
        </div>
    </aside>

    <main class="main-content">
        <?php include __DIR__ . '/includes/trial_banner.php'; ?>
        <header class="app-header">
            <div>
                <h1 style="font-family: 'Outfit'; margin-bottom: 0.25rem;">
                    Configurações da Conta
                    <div class="btn-help" onclick="showSettingsHelp()" title="Ajuda">
                        <i data-lucide="help-circle" style="width: 16px; height: 16px;"></i>
                    </div>
                </h1>
                <p style="color: var(--text-dim);">Gerencie os detalhes da sua empresa e preferências.</p>
            </div>
            <div class="user-profile">
                <span class="badge badge-primary" style="margin-right: 0.5rem; background: rgba(59, 130, 246, 0.1); color: var(--primary); border: 1px solid rgba(59, 130, 246, 0.2);"><?= $_SESSION['user_plan'] ?? 'SME' ?></span>
                <span><?= $_SESSION['user_name'] ?></span>
                <div class="avatar"><?= substr($_SESSION['user_name'],0,1) ?></div>
            </div>
        </header>

        <div class="settings-container">
            <form id="settingsForm">
                <div class="main-card">
                    <!-- Section: Company -->
                    <div class="section-subtitle">
                        <i data-lucide="building"></i>
                        Dados da Empresa
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="input-group" style="grid-column: span 2;">
                            <label>Razão Social / Nome Fantasia</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($company['name']) ?>" required>
                        </div>
                        <div class="input-group">
                            <label>CNPJ</label>
                            <input type="text" name="cnpj" id="cnpj" class="form-control" value="<?= htmlspecialchars($company['cnpj']) ?>" maxlength="18">
                        </div>
                        <div class="input-group">
                            <label>Regime Tributário</label>
                            <select name="tax_regime" class="form-control">
                                <option value="Simples Nacional" <?= $company['tax_regime'] == 'Simples Nacional' ? 'selected' : '' ?>>Simples Nacional</option>
                                <option value="Mei" <?= $company['tax_regime'] == 'Mei' ? 'selected' : '' ?>>MEI</option>
                                <option value="Lucro Presumido" <?= $company['tax_regime'] == 'Lucro Presumido' ? 'selected' : '' ?>>Lucro Presumido</option>
                                <option value="Lucro Real" <?= $company['tax_regime'] == 'Lucro Real' ? 'selected' : '' ?>>Lucro Real</option>
                            </select>
                        </div>
                    </div>

                    <hr class="form-divider">

                    <!-- Section: Tax & Margin -->
                    <div class="section-subtitle">
                        <i data-lucide="percent"></i>
                        Metas e Impostos
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="input-group">
                            <label>Alíquota de Imposto de Venda (%)</label>
                            <input type="number" name="base_tax_rate" class="form-control" value="<?= htmlspecialchars($company['base_tax_rate']) ?>" step="0.01">
                        </div>
                        <div class="input-group">
                            <label>Margem de Lucro Alvo (%)</label>
                            <input type="number" name="target_margin" class="form-control" value="<?= htmlspecialchars($company['target_margin']) ?>" step="0.01">
                            <p style="font-size: 0.75rem; color: var(--text-dim); margin-top: 0.5rem; line-height: 1.3;">
                                <i data-lucide="info" style="width: 12px; vertical-align: middle;"></i> 
                                Usado como padrão para produtos sem margem definida e nas sugestões da IA.
                            </p>
                        </div>
                    </div>

                    <hr class="form-divider">

                    <!-- Section: AI Engine -->
                    <div class="section-subtitle">
                        <i data-lucide="brain-circuit"></i>
                        Motor de Inteligência Artificial
                    </div>
                    <div class="input-group">
                        <label class="toggle-switch">
                            <input type="checkbox" checked style="width: 20px; height: 20px;">
                            Ativar Precificação Dinâmica Automática
                        </label>
                        <p style="font-size: 0.82rem; color: var(--text-dim); margin-top: 0.4rem; margin-left: 2.25rem;">
                            Ao ativar esta opção, o sistema irá recalcular as sugestões para o seu inventário a cada hora com base no mercado.
                        </p>
                    </div>

                    <div style="margin-top: 1.25rem; display: flex; justify-content: flex-end; pt-2;">
                        <button type="submit" class="btn-save">
                            <i data-lucide="save" style="width: 20px;"></i>
                            Salvar Alterações
                        </button>
                    </div>
                </div>
            </form>
        </div>
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

        function maskCNPJ(v) {
            v = v.replace(/\D/g, "");
            if (v.length > 14) v = v.substring(0, 14);
            v = v.replace(/^(\d{2})(\d)/, "$1.$2");
            v = v.replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3");
            v = v.replace(/\.(\d{3})(\d)/, ".$1/$2");
            v = v.replace(/(\d{4})(\d)/, "$1-$2");
            return v;
        }

        const cnpjInput = document.getElementById('cnpj');
        if (cnpjInput) {
            // Apply on load
            if (cnpjInput.value) {
                cnpjInput.value = maskCNPJ(cnpjInput.value);
            }

            cnpjInput.addEventListener('input', (e) => {
                e.target.value = maskCNPJ(e.target.value);
            });
        }

        function closeHelp() {
            document.getElementById('helpModal').classList.remove('active');
        }

        function showSettingsHelp() {
            showHelp('Configurações do Sistema', `
                <p>Aqui você personaliza a experiência da sua empresa na PriceSmart:</p>
                <ul>
                    <li><strong>Perfil da Empresa:</strong> Altere o nome e o logo que aparece nos relatórios.</li>
                    <li><strong>Dados de Contato:</strong> Mantenha seu e-mail e telefone atualizados para suporte.</li>
                    <li><strong>Segurança:</strong> Em breve você poderá gerenciar usuários e permissões aqui.</li>
                </ul>
            `);
        }

        lucide.createIcons();

        function isValidCNPJ(cnpj) {
            cnpj = cnpj.replace(/[^\d]+/g, '');
            if (cnpj == '') return true; // Allow empty
            if (cnpj.length != 14) return false;

            // Eliminate known invalid CNPJs
            if (/^(\d)\1+$/.test(cnpj)) return false;

            // Validate DVs
            let size = cnpj.length - 2;
            let numbers = cnpj.substring(0, size);
            let digits = cnpj.substring(size);
            let sum = 0;
            let pos = size - 7;
            for (let i = size; i >= 1; i--) {
                sum += numbers.charAt(size - i) * pos--;
                if (pos < 2) pos = 9;
            }
            let result = sum % 11 < 2 ? 0 : 11 - (sum % 11);
            if (result != digits.charAt(0)) return false;

            size = size + 1;
            numbers = cnpj.substring(0, size);
            sum = 0;
            pos = size - 7;
            for (let i = size; i >= 1; i--) {
                sum += numbers.charAt(size - i) * pos--;
                if (pos < 2) pos = 9;
            }
            result = sum % 11 < 2 ? 0 : 11 - (sum % 11);
            if (result != digits.charAt(1)) return false;

            return true;
        }

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

        document.getElementById('settingsForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            
            if (!isValidCNPJ(data.cnpj)) {
                showToast('CNPJ Inválido. Por favor, verifique os números.', 'error');
                return;
            }

            showToast('Salvando configurações...', 'loading');
            
            try {
                const response = await fetch('api/actions.php?action=save_settings', {
                    method: 'POST',
                    body: JSON.stringify(data),
                    headers: { 'Content-Type': 'application/json' }
                });
                const result = await response.json();
                if (result.success) {
                    showToast(result.message, 'success');
                } else {
                    showToast(result.message, 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Erro ao comunicar com o servidor', 'error');
            }
        });
    </script>
</body>
</html>
