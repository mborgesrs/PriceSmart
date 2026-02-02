<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$company_id = $_SESSION['company_id'];

// Fetch all products for selection
$stmtProds = $pdo->prepare("SELECT id, name, sku, cost_price FROM products WHERE company_id = ? ORDER BY name ASC");
$stmtProds->execute([$company_id]);
$products = $stmtProds->fetchAll(PDO::FETCH_ASSOC);

// Fetch saved simulations
$stmtSims = $pdo->prepare("SELECT * FROM purchase_simulations WHERE company_id = ? ORDER BY created_at DESC");
$stmtSims->execute([$company_id]);
$simulations = $stmtSims->fetchAll(PDO::FETCH_ASSOC);

// Fetch global costs for the tax combobox
$stmtCosts = $pdo->prepare("SELECT name, value, is_percentage FROM costs WHERE company_id = ? AND type = 'Tax' ORDER BY name ASC");
$stmtCosts->execute([$company_id]);
$global_costs = $stmtCosts->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulador de Compra | PriceSmart</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
    
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <link rel="stylesheet" href="assets/css/app.css?v=1.4">
    <style>
        .simulator-header {
            background: white;
            padding: 1rem;
            border-radius: 16px;
            border: 1px solid var(--border);
            margin-bottom: 1.5rem;
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 1rem;
            align-items: flex-end;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .form-control-sm {
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
        }
        .item-card {
            background: white;
            border-radius: 12px;
            border: 1px solid var(--border);
            padding: 1rem;
            margin-bottom: 0.75rem;
            transition: all 0.2s;
        }
        .item-card:hover { border-color: var(--primary); }
        
        .tax-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 0.75rem;
            margin-bottom: 0.4rem;
            align-items: center;
        }
        .real-cost-badge {
            background: var(--primary);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.95rem;
        }
        .btn-remove-item {
            color: #ef4444;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .simulation-list-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s;
        }
        .simulation-list-card:hover {
            border-color: var(--primary);
            background: #f8fafc;
        }
        #simulationForm {
            background: #ffffff;
            padding: 1.25rem;
            border-radius: 16px;
            border: 1px solid var(--border);
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        
        /* Product Search Styles */
        .search-container {
            position: relative;
            flex: 1;
        }
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
            z-index: 100;
            max-height: 300px;
            overflow-y: auto;
            display: none;
        }
        .search-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            transition: all 0.2s;
            border-bottom: 1px solid #f1f5f9;
        }
        .search-item:last-child { border-bottom: none; }
        .search-item:hover { background: #f8fafc; color: var(--primary); }
        .search-item .sku { font-size: 0.75rem; color: var(--text-dim); display: block; }
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
            <li class="nav-item"><a href="purchase_simulator.php" class="nav-link active"><i data-lucide="shopping-cart"></i> <span>Simulador Compra</span></a></li>
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
                <h1 style="font-family: 'Outfit'; margin-bottom: 0.25rem;">Simulador de Compra</h1>
                <p style="color: var(--text-dim);">Calcule o custo real de aquisição adicionando impostos e taxas de compra.</p>
            </div>
            <div class="user-profile">
                <span class="badge badge-primary" style="margin-right: 0.5rem;"><?= $_SESSION['user_plan'] ?? 'SME' ?></span>
                <div class="avatar"><?= substr($_SESSION['user_name'],0,1) ?></div>
            </div>
        </header>

        <!-- Creation/Edit Form -->
        <section id="simulationForm">
            <div class="section-title" style="margin-bottom: 0.75rem;">
                <i data-lucide="plus-circle" id="formIcon" style="width: 18px;"></i>
                <span id="formTitle" style="font-size: 1.1rem;">Nova Simulação</span>
            </div>
            
            <input type="hidden" id="simId" value="">

            <div class="simulator-header">
                <div class="form-group" style="margin-bottom: 0;">
                    <label style="font-size: 0.7rem; margin-bottom: 0.2rem;">Data Base</label>
                    <input type="date" id="simDate" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label style="font-size: 0.7rem; margin-bottom: 0.2rem;">Fornecedor</label>
                    <input type="text" id="simSupplier" class="form-control form-control-sm" placeholder="Ex: Fornecedor ABC Ltda">
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button id="btnCancelEdit" class="btn btn-outline" style="display: none; height: 36px; padding: 0 0.75rem; font-size: 0.8rem;" onclick="resetForm()">
                        <i data-lucide="arrow-left" style="width: 14px;"></i> Sair
                    </button>
                    <button class="btn btn-primary" onclick="saveSimulation()" id="btnSave" style="height: 36px; padding: 0 1.25rem; font-size: 0.85rem;">Salvar</button>
                </div>
            </div>

            <div style="margin-bottom: 1rem; display: flex; gap: 0.75rem; align-items: flex-end;">
                <div class="search-container">
                    <label style="font-size: 0.75rem;">Pesquisar Produto (Nome ou SKU)</label>
                    <input type="text" id="productSearch" class="form-control form-control-sm" placeholder="Digite para buscar..." oninput="filterProducts(this.value)" onfocus="this.value && filterProducts(this.value)">
                    <div id="searchResults" class="search-results"></div>
                </div>
                <button class="btn btn-outline" id="btnAddItemSelected" disabled style="height: 38px; padding: 0 1rem; font-size: 0.85rem;"><i data-lucide="plus" style="width: 16px;"></i> Adicionar</button>
            </div>

            <div id="itemsContainer">
                <!-- Items will be added here -->
            </div>
        </section>

        <!-- List of Saved Simulations -->
        <section id="savedSimulations">
            <div class="section-title">
                <i data-lucide="history"></i>
                Simulações Salvas
            </div>
            <div id="simulationsList">
                <?php if(empty($simulations)): ?>
                    <p style="color: var(--text-dim); text-align: center; padding: 2rem;">Nenhuma simulação salva ainda.</p>
                <?php else: ?>
                    <?php foreach($simulations as $sim): ?>
                        <div class="simulation-list-card">
                            <div style="flex: 1;">
                                <div style="font-weight: 800; color: var(--text-main); font-size: 1.1rem;"><?= htmlspecialchars($sim['supplier_name']) ?: 'Sem fornecedor' ?></div>
                                <div style="font-size: 0.85rem; color: var(--text-dim); margin-top: 0.25rem;">
                                    <i data-lucide="calendar" style="width: 14px; display: inline; vertical-align: middle;"></i> <?= date('d/m/Y', strtotime($sim['base_date'])) ?> 
                                    <span style="margin: 0 0.75rem; color: var(--border);">|</span>
                                    <i data-lucide="dollar-sign" style="width: 14px; display: inline; vertical-align: middle;"></i> Total: <strong>R$ <?= number_format($sim['total_value'], 2, ',', '.') ?></strong>
                                </div>
                            </div>
                            <div style="display: flex; gap: 0.75rem;">
                                <button class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.8rem; display: flex; align-items: center; gap: 0.4rem;" onclick="editSimulation(<?= $sim['id'] ?>)">
                                    <i data-lucide="edit-3" style="width: 14px;"></i> Editar
                                </button>
                                <button class="btn-icon delete" style="border: 1px solid var(--border); border-radius: 8px; padding: 0.5rem;" onclick="deleteSimulation(<?= $sim['id'] ?>)">
                                    <i data-lucide="trash-2" style="width: 16px;"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <div id="toast-container"></div>

    <script>
        lucide.createIcons();

        let items = [];
        const globalCosts = <?= json_encode($global_costs) ?>;
        const allProducts = <?= json_encode($products) ?>;
        let selectedProduct = null;

        function filterProducts(query) {
            const results = document.getElementById('searchResults');
            if (!query.trim()) {
                results.style.display = 'none';
                return;
            }

            const matches = allProducts.filter(p => 
                p.name.toLowerCase().includes(query.toLowerCase()) || 
                p.sku.toLowerCase().includes(query.toLowerCase())
            ).slice(0, 10); // Limit to 10 results

            if (matches.length > 0) {
                results.innerHTML = matches.map(p => `
                    <div class="search-item" onclick="selectProduct(${JSON.stringify(p).replace(/"/g, '&quot;')})">
                        <strong>${p.name}</strong>
                        <span class="sku">SKU: ${p.sku} | Custo: R$ ${parseFloat(p.cost_price).toFixed(2)}</span>
                    </div>
                `).join('');
                results.style.display = 'block';
            } else {
                results.innerHTML = '<div class="search-item" style="cursor: default; color: var(--text-dim);">Nenhum produto encontrado</div>';
                results.style.display = 'block';
            }
        }

        function selectProduct(product) {
            selectedProduct = product;
            document.getElementById('productSearch').value = `${product.name} (SKU: ${product.sku})`;
            document.getElementById('searchResults').style.display = 'none';
            document.getElementById('btnAddItemSelected').disabled = false;
            document.getElementById('btnAddItemSelected').onclick = () => addItem();
        }

        // Close search results when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-container')) {
                document.getElementById('searchResults').style.display = 'none';
            }
        });

        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerText = message;
            container.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }

        function addItem() {
            if (!selectedProduct) return;

            const item = {
                id: Date.now(),
                product_id: parseInt(selectedProduct.id),
                name: selectedProduct.name,
                sku: selectedProduct.sku,
                base_cost: parseFloat(selectedProduct.cost_price) || 0,
                taxes_and_costs: []
            };

            items.push(item);
            renderItems();
            
            // Reset search
            selectedProduct = null;
            document.getElementById('productSearch').value = '';
            document.getElementById('btnAddItemSelected').disabled = true;
        }

        function removeItem(id) {
            items = items.filter(i => i.id !== id);
            renderItems();
        }

        function addTax(itemId) {
            const item = items.find(i => i.id === itemId);
            item.taxes_and_costs.push({ name: '', value: 0, is_percentage: true });
            renderItems();
        }

        function removeTax(itemId, index) {
            const item = items.find(i => i.id === itemId);
            item.taxes_and_costs.splice(index, 1);
            renderItems();
        }

        function updateItemBaseCost(itemId, val) {
            const item = items.find(i => i.id === itemId);
            item.base_cost = parseFloat(val) || 0;
            renderItems(false);
        }

        function updateTax(itemId, index, field, val) {
            const item = items.find(i => i.id === itemId);
            if (field === 'name') {
                const selectedCost = globalCosts.find(c => c.name === val);
                if (selectedCost) {
                    item.taxes_and_costs[index].name = selectedCost.name;
                    item.taxes_and_costs[index].value = parseFloat(selectedCost.value);
                    item.taxes_and_costs[index].is_percentage = selectedCost.is_percentage == 1;
                } else {
                    item.taxes_and_costs[index].name = val;
                }
            } else if (field === 'is_percentage') {
                item.taxes_and_costs[index].is_percentage = val;
            } else {
                item.taxes_and_costs[index][field] = val;
            }
            renderItems(false);
        }

        function calculateRealCost(item) {
            let total = item.base_cost;
            item.taxes_and_costs.forEach(t => {
                const val = parseFloat(t.value) || 0;
                if (t.is_percentage) {
                    total += (item.base_cost * (val / 100));
                } else {
                    total += val;
                }
            });
            return total;
        }

        function renderItems(full = true) {
            const container = document.getElementById('itemsContainer');
            if (full) container.innerHTML = '';

            items.forEach(item => {
                const realCost = calculateRealCost(item);
                let html = `
                    <div class="item-card" id="item-${item.id}">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 1.5rem; align-items: flex-start;">
                            <div>
                                <div style="font-weight: 800; font-size: 1.25rem;">${item.name}</div>
                                <div style="color: var(--text-dim); font-size: 0.9rem;">SKU: ${item.sku}</div>
                            </div>
                            <div style="text-align: right;">
                                <div class="real-cost-badge">Custo Real: R$ ${realCost.toFixed(2)}</div>
                                <div class="btn-remove-item" onclick="removeItem(${item.id})" style="margin-top: 0.5rem; justify-content: flex-end;">
                                    <i data-lucide="trash-2" style="width: 14px;"></i> Remover Item
                                </div>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem;">
                            <div>
                                <label style="font-weight: 700; font-size: 0.75rem; display: block; margin-bottom: 0.4rem;">Custo Base</label>
                                <input type="number" class="form-control form-control-sm" step="0.01" value="${item.base_cost}" onchange="updateItemBaseCost(${item.id}, this.value)">
                            </div>
                            <div>
                                <label style="font-weight: 700; font-size: 0.75rem; display: block; margin-bottom: 0.4rem;">Impostos e Taxas</label>
                                <div id="taxes-${item.id}">
                                    ${item.taxes_and_costs.map((t, idx) => `
                                        <div class="tax-row">
                                            <select class="form-control form-control-sm" onchange="updateTax(${item.id}, ${idx}, 'name', this.value)">
                                                <option value="">Selecione...</option>
                                                ${globalCosts.map(gc => `<option value="${gc.name}" ${t.name === gc.name ? 'selected' : ''}>${gc.name}</option>`).join('')}
                                            </select>
                                            <input type="number" class="form-control form-control-sm" step="0.01" placeholder="Valor" value="${t.value}" onchange="updateTax(${item.id}, ${idx}, 'value', this.value)">
                                            <div style="display: flex; align-items: center; gap: 0.25rem;">
                                                <input type="checkbox" ${t.is_percentage ? 'checked' : ''} onchange="updateTax(${item.id}, ${idx}, 'is_percentage', this.checked)"> <span style="font-size: 0.7rem;">%?</span>
                                            </div>
                                            <i data-lucide="x" style="width: 14px; color: #ef4444; cursor: pointer;" onclick="removeTax(${item.id}, ${idx})"></i>
                                        </div>
                                    `).join('')}
                                </div>
                                <button class="btn btn-outline" style="padding: 0.3rem 0.6rem; font-size: 0.7rem; margin-top: 0.4rem;" onclick="addTax(${item.id})">+ Adicionar Taxa</button>
                            </div>
                        </div>
                    </div>
                `;
                
                if (full) {
                    container.insertAdjacentHTML('beforeend', html);
                } else {
                    document.getElementById(`item-${item.id}`).outerHTML = html;
                }
            });
            lucide.createIcons();
        }

        async function saveSimulation() {
            const id = document.getElementById('simId').value;
            const base_date = document.getElementById('simDate').value;
            const supplier_name = document.getElementById('simSupplier').value;

            if (items.length === 0) {
                showToast('Adicione pelo menos um item.', 'error');
                return;
            }

            const data = {
                id: id || null,
                base_date,
                supplier_name,
                items: items.map(i => ({
                    product_id: i.product_id,
                    base_cost: i.base_cost,
                    taxes_and_costs: i.taxes_and_costs,
                    real_cost: calculateRealCost(i)
                }))
            };

            const response = await fetch('api/actions.php?action=save_purchase_simulation', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            if (result.success) {
                showToast(result.message);
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(result.message, 'error');
            }
        }

        async function deleteSimulation(id) {
            if (!confirm('Deseja excluir esta simulação?')) return;

            const response = await fetch('api/actions.php?action=delete_purchase_simulation', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });

            const result = await response.json();
            if (result.success) {
                showToast(result.message);
                setTimeout(() => location.reload(), 1000);
            }
        }

        async function editSimulation(id) {
            showToast('Carregando dados...', 'loading');
            
            const response = await fetch('api/actions.php?action=get_purchase_simulation_details', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });

            const result = await response.json();
            if (result.success) {
                // Set into Edit mode
                document.getElementById('simId').value = result.simulation.id;
                document.getElementById('simDate').value = result.simulation.base_date;
                document.getElementById('simSupplier').value = result.simulation.supplier_name;
                
                document.getElementById('formTitle').innerText = 'Editar Simulação';
                document.getElementById('formIcon').setAttribute('data-lucide', 'edit-3');
                document.getElementById('btnSave').innerText = 'Atualizar Simulação';
                document.getElementById('btnCancelEdit').style.display = 'inline-block';
                
                // Map items to JS state
                items = result.items.map(i => ({
                    id: i.id, // keep DB id for internal tracking or just use native Date.now()
                    product_id: i.product_id,
                    name: i.name,
                    sku: i.sku,
                    base_cost: parseFloat(i.base_cost),
                    taxes_and_costs: i.taxes_and_costs
                }));
                
                renderItems();
                window.scrollTo({ top: 0, behavior: 'smooth' });
                lucide.createIcons();
            } else {
                showToast(result.message, 'error');
            }
        }

        function resetForm() {
            document.getElementById('simId').value = '';
            document.getElementById('simDate').value = '<?= date('Y-m-d') ?>';
            document.getElementById('simSupplier').value = '';
            
            document.getElementById('formTitle').innerText = 'Nova Simulação';
            document.getElementById('formIcon').setAttribute('data-lucide', 'plus-circle');
            document.getElementById('btnSave').innerText = 'Salvar Simulação';
            document.getElementById('btnCancelEdit').style.display = 'none';
            
            items = [];
            renderItems();
            lucide.createIcons();
        }
    </script>
</body>
</html>
