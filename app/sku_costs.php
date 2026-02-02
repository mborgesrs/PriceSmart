<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$company_id = $_SESSION['company_id'];

// Fetch all SKUs
$stmtSKUs = $pdo->prepare("SELECT * FROM products WHERE company_id = ? ORDER BY sku ASC");
$stmtSKUs->execute([$company_id]);
$skus = $stmtSKUs->fetchAll();

// Fetch ALL product specifically associated costs
$stmtPC = $pdo->prepare("SELECT * FROM product_costs WHERE company_id = ?");
$stmtPC->execute([$company_id]);
$all_product_costs = $stmtPC->fetchAll();

// Fetch Global costs (as templates/reference)
$stmtGlobal = $pdo->prepare("SELECT * FROM costs WHERE company_id = ?");
$stmtGlobal->execute([$company_id]);
$global_costs = $stmtGlobal->fetchAll();

// Fetch Company details
$stmtComp = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
$stmtComp->execute([$company_id]);
$company = $stmtComp->fetch();
$base_tax = $company['base_tax_rate'] ?? 0;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custos por SKU | PriceSmart</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
    
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <link rel="stylesheet" href="assets/css/app.css?v=1.4">
    <style>
        .sku-card { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 1.5rem; transition: all 0.2s; }
        .sku-card:hover { border-color: var(--primary); box-shadow: var(--card-shadow); }
        
        .app-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .app-table th { text-align: left; padding: 0.6rem 1rem; color: var(--text-dim); font-weight: 600; border-bottom: 2px solid var(--border); }
        .app-table td { padding: 0.4rem 1rem; border-bottom: 1px solid #f1f5f9; color: var(--text-main); }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); z-index: 9999; justify-content: center; align-items: center; backdrop-filter: blur(4px); }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 2.5rem; border-radius: 24px; width: 90%; max-width: 650px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }

        .form-control { width: 100%; padding: 0.6rem 0.8rem; border-radius: 10px; border: 1px solid var(--border); background: #f8fafc; font-family: inherit; }
        .form-control:focus { border-color: var(--primary); background: white; outline: none; }

        .badge-pill { padding: 0.25rem 0.75rem; border-radius: 50px; font-size: 0.75rem; font-weight: 700; }
        .bg-success-dim { background: #dcfce7; color: #166534; }
        .bg-warning-dim { background: #fef9c3; color: #854d0e; }
        .bg-danger-dim { background: #fee2e2; color: #991b1b; }

        /* Icon Buttons */
        .btn-icon {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: white;
            color: var(--text-dim);
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-icon:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: rgba(59, 130, 246, 0.05);
        }
        .btn-icon.delete:hover {
            border-color: #ef4444;
            color: #ef4444;
            background: #fef2f2;
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
            <li class="nav-item"><a href="sku_costs.php" class="nav-link active"><i data-lucide="list-checks"></i> <span>Custos por SKU</span></a></li>
            <li class="nav-item"><a href="simulator.php" class="nav-link"><i data-lucide="flask-conical"></i> <span>Simulador Lucro</span></a></li>
            <li class="nav-item"><a href="purchase_simulator.php" class="nav-link"><i data-lucide="shopping-cart"></i> <span>Simulador Compra</span></a></li>
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
                <h1 style="font-family: 'Outfit'; margin-bottom: 0.25rem;">Custos por SKU</h1>
                <p style="color: var(--text-dim);">Associe impostos, comissões de marketplace e fretes a cada produto.</p>
            </div>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <div class="search-box" style="position: relative;">
                    <i data-lucide="search" style="position: absolute; left: 12px; top: 10px; width: 16px; color: var(--text-dim);"></i>
                    <input type="text" id="skuSearch" class="form-control" placeholder="Buscar por SKU ou Nome..." style="padding-left: 36px; height: 38px; font-size: 0.85rem; width: 250px;">
                </div>
                <div class="user-profile">
                    <span class="badge badge-primary" style="margin-right: 0.5rem;"><?= $_SESSION['user_plan'] ?? 'SME' ?></span>
                    <div class="avatar"><?= substr($_SESSION['user_name'],0,1) ?></div>
                </div>
            </div>
        </header>

        <div class="card-section">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>SKU / Produto</th>
                        <th>Custo Base (Aquisição)</th>
                        <th>Custo Total (Incidentes)</th>
                        <th>Preço Venda</th>
                        <th style="cursor: pointer;" onclick="showHelp('Margem Bruta Real', '<p>Diferente da margem simples, este cálculo considera todos os custos incidentes no produto.</p><div style=\'background:#f1f5f9; padding:1rem; border-radius:8px; margin:1rem 0; font-family:monospace; font-weight:bold;\'>((Preço Venda - (Custo Total + Impostos)) / Preço Venda) * 100</div><p><b>Custo Total:</b> Aquisição + Fretes e Adicionais.<br><b>Impostos:</b> Taxas de venda e tributos do SKU.</p>')">
                            <div style="display: flex; align-items: center; gap: 4px;">
                                Margem Bruta Real <i data-lucide="help-circle" style="width: 14px; color: var(--text-dim);"></i>
                            </div>
                        </th>
                        <th style="text-align: right;">Gestão</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($skus as $s): 
                        $base_cost = floatval($s['cost_price'] ?? 0);
                        $price = floatval($s['current_price'] ?? 0);
                        
                        $sku_specific_costs = array_filter($all_product_costs, fn($c) => $c['product_id'] == $s['id']);
                        
                        $add_cost_val = 0;
                        $sale_tax_val = 0;
                        $cost_count = count($sku_specific_costs);
                        
                        foreach ($sku_specific_costs as $c) {
                            $val = floatval($c['value']);
                            if ($c['type'] === 'Variable') {
                                $add_cost_val += $val;
                            } elseif ($c['type'] === 'Tax') {
                                if (($c['incidence'] ?? 'sale') === 'purchase') {
                                    $add_cost_val += $c['is_percentage'] ? ($base_cost * ($val / 100)) : $val;
                                } else {
                                    $sale_tax_val += $c['is_percentage'] ? ($price * ($val / 100)) : $val;
                                }
                            }
                        }
                        
                        $total_cost = $base_cost + $add_cost_val;
                        $contribution = $price - $total_cost - $sale_tax_val;
                        $margin_perc = $price > 0 ? ($contribution / $price) * 100 : 0;
                    ?>
                    <tr>
                        <td>
                            <div style="font-weight: 700; color: var(--text-main);"><?= $s['sku'] ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-dim);"><?= $s['name'] ?></div>
                        </td>
                        <td>R$ <?= number_format($base_cost, 2, ',', '.') ?></td>
                        <td>
                            <div style="font-weight: 600;">R$ <?= number_format($total_cost, 2, ',', '.') ?></div>
                            <div style="font-size: 0.75rem; color: var(--primary); display: flex; flex-wrap: wrap; gap: 4px; margin-top: 4px;">
                                <?php if($cost_count > 0): ?>
                                    <?php foreach($sku_specific_costs as $c): ?>
                                        <span class="badge" style="font-size: 0.65rem; background: rgba(59,130,246,0.1); color: var(--primary); padding: 2px 6px; border-radius: 4px; display: inline-block;">
                                            <?= $c['name'] ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span style="color: var(--text-dim); font-size: 0.65rem;">Sem custos adicionais</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>R$ <?= number_format($price, 2, ',', '.') ?></td>
                        <td>
                            <span class="badge-pill <?= $margin_perc > 25 ? 'bg-success-dim' : ($margin_perc > 10 ? 'bg-warning-dim' : 'bg-danger-dim') ?>">
                                <?= number_format($margin_perc, 1) ?>%
                            </span>
                        </td>
                        <td style="text-align: right; display: flex; gap: 0.5rem; justify-content: flex-end;">
                            <button class="btn-icon" title="Editar Impostos/Taxas" onclick='openSKUCosts(<?= json_encode($s) ?>)'>
                                <i data-lucide="settings-2" style="width: 16px;"></i>
                            </button>
                            <button class="btn-icon" title="Ver Detalhamento" onclick='evaluateSKU(<?= json_encode($s) ?>, <?= json_encode(array_values($sku_specific_costs)) ?>)'>
                                <i data-lucide="eye" style="width: 16px;"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Modal Gerenciar Custos do SKU -->
    <div id="manageSKUCostsModal" class="modal">
        <div class="modal-content">
            <h2 id="manageSKUCostsTitle" style="font-family: 'Outfit'; margin-bottom: 1.5rem;">Impostos e Taxas do SKU</h2>
            
            <div style="background: #f1f5f9; padding: 1.25rem; border-radius: 16px; margin-bottom: 2rem;">
                <h4 style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-dim); margin-bottom: 1rem;">Adicionar Novo Incidente</h4>
                <form id="addSKUCostForm" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 0.75rem; align-items: flex-end;">
                    <input type="hidden" name="product_id" id="skuCostProductId">
                    <div>
                        <label style="font-size: 0.7rem; font-weight: 700; margin-bottom: 4px; display: block;">Descrição</label>
                        <select name="name" id="taxNameSelect" class="form-control" onchange="handleTemplateSelect(this.value)">
                            <option value="">Selecione um custo...</option>
                            <?php if(!empty($global_costs)): ?>
                                <optgroup label="Seus Custos Gerais">
                                    <?php foreach($global_costs as $gc): ?>
                                        <option value="<?= htmlspecialchars($gc['name']) ?>" 
                                                data-value="<?= $gc['value'] ?>" 
                                                data-perc="<?= $gc['is_percentage'] ?>"
                                                data-type="<?= $gc['type'] ?>"
                                                data-incidence="<?= $gc['incidence'] ?>">
                                            <?= $gc['name'] ?> (<?= $gc['is_percentage'] ? $gc['value'].'%' : 'R$ '.$gc['value'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                            <optgroup label="Personalizado">
                                <option value="Outro">+ Definir Novo...</option>
                            </optgroup>
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 0.7rem; font-weight: 700; margin-bottom: 4px; display: block;">Valor</label>
                        <input type="number" name="value" id="skuCostValue" step="0.01" class="form-control" placeholder="0.00" required>
                    </div>
                    <div style="text-align: center;">
                        <label style="font-size: 0.7rem; font-weight: 700; margin-bottom: 4px; display: block;">%?</label>
                        <input type="checkbox" name="is_percentage" id="skuCostIsPerc" value="1" style="width: 20px; height: 20px;" checked>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary" style="height: 42px; width: 42px; padding: 0;"><i data-lucide="plus"></i></button>
                    </div>
                    <input type="hidden" name="type" id="skuCostType" value="Tax">
                    <input type="hidden" name="incidence" id="skuCostIncidence" value="sale">
                </form>
            </div>

            <div style="max-height: 300px; overflow-y: auto;">
                <table class="app-table">
                    <tbody id="skuCostsTableBody"></tbody>
                </table>
            </div>

            <div style="margin-top: 2rem; display: flex; justify-content: flex-end;">
                <button class="btn btn-outline" onclick="closeModal('manageSKUCostsModal')">Fechar</button>
            </div>
        </div>
    </div>

    <!-- Mini Detalhamento Modal -->
    <div id="evalModal" class="modal">
        <div class="modal-content" style="max-width: 450px;">
            <h2 id="evalTitle" style="font-family: 'Outfit'; margin-bottom: 1.5rem;">Detalhamento de Margem</h2>
            <div id="evalContent"></div>
            <div style="margin-top: 2rem; display: flex; justify-content: flex-end;">
                <button class="btn btn-primary" onclick="closeModal('evalModal')">Entendi</button>
            </div>
        </div>
    </div>

    <div id="toast-container"></div>

    <script>
        lucide.createIcons();

        // Real-time search for SKU or Name
        document.getElementById('skuSearch').addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.app-table tbody tr');
            rows.forEach(row => {
                const sku = row.querySelector('td:first-child div:first-child').innerText.toLowerCase();
                const name = row.querySelector('td:first-child div:last-child').innerText.toLowerCase();
                row.style.display = (sku.includes(term) || name.includes(term)) ? '' : 'none';
            });
        });

        function showToast(msg) {
            const t = document.createElement('div');
            t.className = 'toast success';
            t.innerText = msg;
            document.getElementById('toast-container').appendChild(t);
            setTimeout(() => t.remove(), 3000);
        }

        function closeModal(id) { document.getElementById(id).classList.remove('active'); }

        function showHelp(title, html) {
            document.getElementById('evalTitle').innerText = title;
            document.getElementById('evalContent').innerHTML = html;
            document.getElementById('evalModal').classList.add('active');
        }


        async function openSKUCosts(sku) {
            document.getElementById('manageSKUCostsTitle').innerText = 'Custos: ' + sku.sku;
            document.getElementById('skuCostProductId').value = sku.id;
            await refreshSKUList(sku.id);
            document.getElementById('manageSKUCostsModal').classList.add('active');
        }

        async function refreshSKUList(pid) {
            const res = await fetch(`api/actions.php?action=get_costs&product_id=${pid}`);
            const data = await res.json();
            const tbody = document.getElementById('skuCostsTableBody');
            tbody.innerHTML = '';
            if(data.costs && data.costs.length > 0) {
                data.costs.forEach(c => {
                    tbody.innerHTML += `<tr>
                        <td><strong>${c.name}</strong></td>
                        <td>${c.is_percentage == 1 ? c.value + '%' : 'R$ ' + c.value}</td>
                        <td style="text-align: right;">
                            <button class="btn-icon delete" onclick="deleteSKUCost(${c.id}, ${pid})"><i data-lucide="trash-2" style="width: 14px;"></i></button>
                        </td>
                    </tr>`;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:var(--text-dim); padding: 2rem;">Nenhum custo específico.</td></tr>';
            }
            lucide.createIcons();
        }

        function handleTemplateSelect(val) {
            const select = document.getElementById('taxNameSelect');
            const selectedOption = select.options[select.selectedIndex];
            
            if (val === 'Outro') {
                const n = prompt('Nome do custo:');
                if (n) {
                    const opt = document.createElement('option');
                    opt.value = n; opt.text = n;
                    select.add(opt, 1);
                    select.value = n;
                }
                return;
            }

            if (selectedOption && selectedOption.dataset.value) {
                document.getElementById('skuCostValue').value = selectedOption.dataset.value;
                document.getElementById('skuCostIsPerc').checked = selectedOption.dataset.perc == 1;
                document.getElementById('skuCostType').value = selectedOption.dataset.type;
                document.getElementById('skuCostIncidence').value = selectedOption.dataset.incidence || 'sale';
            }
        }

        document.getElementById('addSKUCostForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            data.is_percentage = e.target.is_percentage.checked ? 1 : 0;
            
            const res = await fetch('api/actions.php?action=save_cost', {
                method: 'POST',
                body: JSON.stringify(data),
                headers: {'Content-Type': 'application/json'}
            });
            if((await res.json()).success) {
                showToast('Adicionado!');
                refreshSKUList(data.product_id);
            }
        });

        async function deleteSKUCost(id, pid) {
            if(!confirm('Remover este custo?')) return;
            const res = await fetch('api/actions.php?action=delete_cost', {
                method: 'POST',
                body: JSON.stringify({id, product_id: pid}),
                headers: {'Content-Type': 'application/json'}
            });
            if((await res.json()).success) {
                refreshSKUList(pid);
            }
        }

        function evaluateSKU(sku, costs) {
            const price = parseFloat(sku.current_price);
            const base = parseFloat(sku.cost_price || 0);
            let totalIncident = 0;
            
            let html = `<div style="background: #f8fafc; padding: 1.5rem; border-radius: 16px; border: 1px solid #e2e8f0;">
                <div style="display:flex; justify-content:space-between; margin-bottom: 0.5rem;"><span>Preço:</span><strong>R$ ${price.toFixed(2)}</strong></div>
                <div style="display:flex; justify-content:space-between; margin-bottom: 0.5rem; color:#ef4444;"><span>(-) Custo Base:</span><span>- R$ ${base.toFixed(2)}</span></div>`;
            
            costs.forEach(c => {
                let amt = c.is_percentage == 1 ? (price * (parseFloat(c.value)/100)) : parseFloat(c.value);
                totalIncident += amt;
                html += `<div style="display:flex; justify-content:space-between; font-size: 0.8rem; color:#ef4444; margin-left: 1rem;"><span>(-) ${c.name}:</span><span>- R$ ${amt.toFixed(2)}</span></div>`;
            });
            
            const margin = price - base - totalIncident;
            const perc = price > 0 ? (margin / price) * 100 : 0;
            
            html += `<hr style="margin: 1rem 0; border: 0; border-top: 1px solid #e2e8f0;">
                <div style="display:flex; justify-content:space-between; font-size: 1.1rem;"><strong>Lucro Líquido:</strong><strong style="color:${margin > 0 ? '#10b981' : '#ef4444'}">R$ ${margin.toFixed(2)}</strong></div>
                <div style="display:flex; justify-content:space-between; font-size: 0.9rem; margin-top: 0.5rem;"><span>Margem %:</span><strong>${perc.toFixed(1)}%</strong></div>
            </div>`;
            
            document.getElementById('evalTitle').innerText = sku.sku;
            document.getElementById('evalContent').innerHTML = html;
            document.getElementById('evalModal').classList.add('active');
        }
    </script>
</body>
</html>
