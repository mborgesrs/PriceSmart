<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$company_id = $_SESSION['company_id'];

// Fetch ALL Global Template costs (No product associated)
$stmtAll = $pdo->prepare("SELECT * FROM costs WHERE company_id = ? ORDER BY created_at DESC");
$stmtAll->execute([$company_id]);
$costs = $stmtAll->fetchAll();

// Fetch Company details
$stmtComp = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
$stmtComp->execute([$company_id]);
$company = $stmtComp->fetch();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custos Gerais | PriceSmart</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
    
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <link rel="stylesheet" href="assets/css/app.css?v=1.3">
    <style>
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-main); font-size: 0.85rem; }
        .form-control { width: 100%; padding: 0.75rem 1rem; border-radius: 12px; border: 1px solid var(--border); background: #f8fafc; font-family: inherit; transition: all 0.2s; }
        .form-control:focus { border-color: var(--primary); background: white; outline: none; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); z-index: 9999; justify-content: center; align-items: center; backdrop-filter: blur(4px); }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 2.5rem; border-radius: 24px; width: 90%; max-width: 500px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); animation: modalIn 0.3s ease-out; }
        @keyframes modalIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        .btn-new { background: var(--primary); color: white; padding: 0.75rem 1.5rem; border-radius: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; transition: all 0.2s; border: none; }
        .btn-new:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }

        .app-table { width: 100%; border-collapse: collapse; margin-top: 0.5rem; }
        .app-table th { text-align: left; padding: 0.6rem 1rem; color: var(--text-dim); font-weight: 600; border-bottom: 2px solid var(--border); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; }
        .app-table td { padding: 0.4rem 1rem; border-bottom: 1px solid #f1f5f9; color: var(--text-main); font-size: 0.9rem; }
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
            <li class="nav-item"><a href="costs.php" class="nav-link active"><i data-lucide="dollar-sign"></i> <span>Custos Gerais</span></a></li>
            <li class="nav-item"><a href="sku_costs.php" class="nav-link"><i data-lucide="list-checks"></i> <span>Custos por SKU</span></a></li>
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
                <h1 style="font-family: 'Outfit'; margin-bottom: 0.25rem;">Custos Gerais e Taxas</h1>
                <p style="color: var(--text-dim);">Gerencie seus custos operacionais e impostos que servirão de base para o sistema.</p>
            </div>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <div class="search-box" style="position: relative;">
                    <i data-lucide="search" style="position: absolute; left: 12px; top: 10px; width: 16px; color: var(--text-dim);"></i>
                    <input type="text" id="costSearch" class="form-control" placeholder="Buscar custo..." style="padding-left: 36px; height: 38px; font-size: 0.85rem; width: 200px;">
                </div>
                <button class="btn btn-primary" onclick="openModal('costModal')" style="padding: 0.5rem 1rem; font-size: 0.85rem;">
                    <i data-lucide="plus" style="width: 16px;"></i> Novo Custo
                </button>
                <div class="user-profile">
                    <div class="avatar"><?= substr($_SESSION['user_name'],0,1) ?></div>
                </div>
            </div>
        </header>

        <div class="card-section">
            <div class="card-title">Listagem de Custos Cadastrados</div>
            <table class="app-table">
                <thead>
                    <tr>
                        <th>Nome do Custo</th>
                        <th>Tipo</th>
                        <th>Valor</th>
                        <th>Incidência</th>
                        <th style="text-align: right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($costs)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 3rem; color: var(--text-dim);">Nenhum custo cadastrado no momento.</td></tr>
                    <?php else: ?>
                        <?php foreach ($costs as $c): ?>
                        <tr>
                            <td><strong><?= $c['name'] ?></strong></td>
                            <td>
                                <span class="badge" style="background: #f1f5f9; color: #475569;">
                                    <?= $c['type'] === 'Fixed' ? 'Fixo' : ($c['type'] === 'Variable' ? 'Variável' : 'Imposto') ?>
                                </span>
                            </td>
                            <td>
                                <?= $c['is_percentage'] ? number_format($c['value'], 2, ',', '.') . '%' : 'R$ ' . number_format($c['value'], 2, ',', '.') ?>
                            </td>
                            <td>
                                <?= ($c['incidence'] ?? 'sale') === 'purchase' ? 'Entrada (Compra)' : 'Saída (Venda)' ?>
                            </td>
                            <td style="text-align: right; display: flex; gap: 0.5rem; justify-content: flex-end;">
                                <button class="btn-icon" style="color: var(--primary);" onclick='editCost(<?= json_encode($c) ?>)' title="Editar"><i data-lucide="edit-3" style="width: 16px;"></i></button>
                                <button class="btn-icon" style="color: #ef4444;" onclick="deleteCost(<?= $c['id'] ?>)" title="Excluir"><i data-lucide="trash-2" style="width: 16px;"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Global Cost Modal -->
    <div id="costModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle" style="font-family: 'Outfit'; margin-bottom: 1.5rem;">Cadastrar Novo Custo</h2>
            <form id="costForm">
                <input type="hidden" name="id" id="costId">
                <div class="form-group">
                    <label>Nome do Custo / Taxa</label>
                    <input type="text" name="name" id="costName" class="form-control" placeholder="Ex: Simples Nacional, Aluguel, etc." required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>Tipo</label>
                        <select name="type" id="costType" class="form-control" onchange="toggleIncidence()">
                            <option value="Variable">Variável</option>
                            <option value="Fixed">Fixo</option>
                            <option value="Tax">Imposto / Taxa</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Valor</label>
                        <input type="number" name="value" id="costValue" step="0.01" class="form-control" placeholder="0.00" required>
                    </div>
                </div>
                <div class="form-group" style="display: flex; align-items: center; gap: 0.75rem;">
                    <input type="checkbox" name="is_percentage" id="isPerc" style="width: 18px; height: 18px;">
                    <label for="isPerc" style="margin-bottom: 0;">O valor é percentual (%)?</label>
                </div>
                <div class="form-group" id="incidenceGroup">
                    <label>Incidência</label>
                    <select name="incidence" id="costIncidence" class="form-control">
                        <option value="sale">Venda (Deduz do Preço)</option>
                        <option value="purchase">Compra (Acrescenta ao Custo)</option>
                    </select>
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 2rem; justify-content: flex-end;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('costModal')">Cancelar</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Salvar Registro</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-content" style="max-width: 450px;">
            <div style="text-align: center;">
                <div style="width: 64px; height: 64px; background: #fee2e2; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
                    <i data-lucide="alert-triangle" style="width: 32px; height: 32px; color: #ef4444;"></i>
                </div>
                <h3 style="font-family: 'Outfit'; margin-bottom: 0.75rem; color: var(--text-main);">Excluir Registro?</h3>
                <p style="color: var(--text-dim); line-height: 1.6; margin-bottom: 2rem;">
                    Esta ação não pode ser desfeita. Este custo/taxa será removido permanentemente e deixará de impactar os cálculos automáticos.
                </p>
            </div>
            <div style="display: flex; gap: 1rem;">
                <button class="btn btn-outline" style="flex: 1;" onclick="closeConfirmModal()">Cancelar</button>
                <button class="btn btn-primary" style="flex: 1; background: #ef4444; border-color: #ef4444;" onclick="confirmDelete()">Sim, Excluir</button>
            </div>
        </div>
    </div>

    <div id="toast-container"></div>

    <script>
        lucide.createIcons();

        // Real-time search for costs
        document.getElementById('costSearch').addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.app-table tbody tr');
            rows.forEach(row => {
                const text = row.querySelector('td:first-child').innerText.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });

        function openModal(id) { 
            if(id === 'costModal') {
                document.getElementById('costForm').reset();
                document.getElementById('costId').value = '';
                document.getElementById('modalTitle').innerText = 'Cadastrar Novo Custo';
            }
            document.getElementById(id).classList.add('active'); 
        }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }

        function toggleIncidence() {
            // Logic to show/hide incidence if needed
        }

        async function editCost(c) {
            document.getElementById('modalTitle').innerText = 'Editar Registro';
            document.getElementById('costId').value = c.id;
            document.getElementById('costName').value = c.name;
            document.getElementById('costType').value = c.type;
            document.getElementById('costValue').value = c.value;
            document.getElementById('isPerc').checked = c.is_percentage == 1;
            document.getElementById('costIncidence').value = c.incidence || 'sale';
            document.getElementById('costModal').classList.add('active');
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

        document.getElementById('costForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            data.is_percentage = document.getElementById('isPerc').checked ? 1 : 0;

            showToast('Salvando registro...', 'loading');

            const res = await fetch('api/actions.php?action=save_cost', {
                method: 'POST',
                body: JSON.stringify(data),
                headers: {'Content-Type': 'application/json'}
            });
            const result = await res.json();
            if(result.success) {
                showToast('Registro salvo com sucesso!', 'success');
                setTimeout(() => location.reload(), 1200);
            } else {
                showToast('Erro: ' + result.message, 'error');
            }
        });

        let pendingDeleteId = null;

        function deleteCost(id) {
            pendingDeleteId = id;
            document.getElementById('confirmModal').classList.add('active');
            lucide.createIcons();
        }

        function closeConfirmModal() {
            document.getElementById('confirmModal').classList.remove('active');
            pendingDeleteId = null;
        }

        async function confirmDelete() {
            if (!pendingDeleteId) return;

            const costId = pendingDeleteId;
            closeConfirmModal();
            showToast('Processando exclusão...', 'loading');

            try {
                const res = await fetch('api/actions.php?action=delete_cost', {
                    method: 'POST',
                    body: JSON.stringify({id: costId}),
                    headers: {'Content-Type': 'application/json'}
                });
                const result = await res.json();
                if(result.success) {
                    showToast('Custo excluído com sucesso!', 'success');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showToast('Erro: ' + result.message, 'error');
                }
            } catch (err) {
                showToast('Erro ao excluir custo', 'error');
            }
        }
    </script>
</body>
</html>
