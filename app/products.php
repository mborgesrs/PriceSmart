<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$company_id = $_SESSION['company_id'];

// Filter, Search or Sort
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

$query = "SELECT * FROM products WHERE company_id = ? ";
$params = [$company_id];

if ($search) {
    $query .= " AND (name LIKE ? OR sku LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Sorting logic
switch ($sort) {
    case 'sku':
        $query .= " ORDER BY sku ASC";
        break;
    case 'name':
        $query .= " ORDER BY name ASC";
        break;
    default:
        $query .= " ORDER BY created_at DESC";
        break;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Produtos | PriceSmart</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
    
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="assets/css/app.css?v=1.4">
    <style>
        .page-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            gap: 1rem;
        }
        .search-box {
            position: relative;
            flex-grow: 1;
            max-width: 400px;
        }
        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-dim);
        }
        .search-box input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.8rem;
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text-main);
            outline: none;
            font-family: inherit;
        }
        .import-btn {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .import-btn:hover {
            background: rgba(16, 185, 129, 0.2);
        }
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.active { display: flex; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-logo">
            <i data-lucide="zap"></i>
            <span>PriceSmart</span>
        </div>
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="index.php" class="nav-link">
                    <i data-lucide="layout-dashboard"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="products.php" class="nav-link active">
                    <i data-lucide="package"></i>
                    <span>Catálogo</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="pricing.php" class="nav-link">
                    <i data-lucide="tags"></i>
                    <span>Precificação IA</span>
                </a>
            </li>
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
            <li class="nav-item">
                <a href="simulator.php" class="nav-link">
                    <i data-lucide="flask-conical"></i>
                    <span>Simulador Lucro</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="purchase_simulator.php" class="nav-link">
                    <i data-lucide="shopping-cart"></i>
                    <span>Simulador Compra</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="integrations.php" class="nav-link">
                    <i data-lucide="plug-2"></i>
                    <span>Integrações</span>
                </a>
            </li>
        </ul>
        <div class="nav-footer">
            <a href="settings.php" class="nav-link">
                <i data-lucide="settings"></i>
                <span>Configurações</span>
            </a>
            <a href="auth/auth_handler.php?action=logout" class="nav-link" style="color: #ef4444;">
                <i data-lucide="log-out"></i>
                <span>Sair</span>
            </a>
        </div>
    </aside>

    <main class="main-content">
        <?php include __DIR__ . '/includes/trial_banner.php'; ?>
        <header class="app-header">
            <div>
                <h1 style="font-family: 'Outfit'; margin-bottom: 0.25rem;">
                    Catálogo de Produtos
                    <div class="btn-help" onclick="showProductsHelp()" title="Ajuda">
                        <i data-lucide="help-circle" style="width: 16px; height: 16px;"></i>
                    </div>
                </h1>
                <p style="color: var(--text-dim);">Gerencie seus SKUs e sincronize com canais de venda.</p>
            </div>
            <div class="user-profile">
                <span class="badge badge-primary" style="margin-right: 0.5rem; background: rgba(59, 130, 246, 0.1); color: var(--primary); border: 1px solid rgba(59, 130, 246, 0.2);"><?= $_SESSION['user_plan'] ?? 'SME' ?></span>
                <span><?= $_SESSION['user_name'] ?></span>
                <div class="avatar"><?= substr($_SESSION['user_name'],0,1) ?></div>
            </div>
        </header>

        <div class="page-actions">
            <form action="" method="GET" class="search-box">
                <i data-lucide="search" style="width: 18px;"></i>
                <input type="text" name="search" placeholder="Buscar por Nome ou SKU..." value="<?= htmlspecialchars($search) ?>">
                <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
            </form>
            
            <div style="display: flex; gap: 0.75rem;">
                <button class="btn btn-outline import-btn" onclick="openModal('importModal')">
                    <i data-lucide="upload" style="width: 18px;"></i> Importar Planilha
                </button>
                <button class="btn btn-primary" onclick="openModal('productModal')">
                    <i data-lucide="plus" style="width: 18px; display: inline; vertical-align: middle;"></i> Novo Produto
                </button>
            </div>
        </div>

        <div class="card-section">
            <table class="app-table">
                <thead>
                    <tr>
                        <th style="cursor: pointer;" onclick="window.location.href='?search=<?= urlencode($search) ?>&sort=sku'">
                            <div style="display: flex; align-items: center; gap: 0.4rem;">
                                SKU <i data-lucide="<?= $sort == 'sku' ? 'chevron-down' : 'chevrons-up-down' ?>" style="width: 14px; opacity: <?= $sort == 'sku' ? '1' : '0.3' ?>;"></i>
                            </div>
                        </th>
                        <th style="cursor: pointer;" onclick="window.location.href='?search=<?= urlencode($search) ?>&sort=name'">
                            <div style="display: flex; align-items: center; gap: 0.4rem;">
                                Produto <i data-lucide="<?= $sort == 'name' ? 'chevron-down' : 'chevrons-up-down' ?>" style="width: 14px; opacity: <?= $sort == 'name' ? '1' : '0.3' ?>;"></i>
                            </div>
                        </th>
                        <th>Custo</th>
<th style="cursor: pointer;" onclick="showHelp('Preço de Venda Dinâmico', '<p>Este preço é calculado automaticamente para preservar a sua <strong>Margem Mínima</strong>.</p><div style=\'background:#f1f5f9; padding:1rem; border-radius:8px; margin:1rem 0; font-family:monospace; font-weight:bold; font-size:0.85rem;\'>(Custo Total + Taxas Fixas) <br>---------------------------<br> (1 - %Impostos - %Margem)</div><p>A <strong>%Margem</strong> utilizada no cálculo acima é a que foi definida no cadastro deste produto.</p>')">
                            <div style="display: flex; align-items: center; gap: 4px;">
                                Preço Venda <i data-lucide="help-circle" style="width: 14px; color: var(--text-dim);"></i>
                            </div>
                        </th>
                        <th style="cursor: pointer;" onclick="showHelp('Cálculo da Margem Bruta', '<p>A margem bruta mostrada nesta tela é simplificada, considerando apenas o preço de venda e o custo de aquisição.</p><div style=\'background:#f1f5f9; padding:1rem; border-radius:8px; margin:1rem 0; font-family:monospace; font-weight:bold;\'>((Preço Venda - Preço Custo) / Preço Venda) * 100</div><p><strong>Legenda de Cores:</strong></p><ul style=\'list-style:none; padding:0; margin-top:0.5rem;\'><li style=\'margin-bottom:0.25rem; display:flex; align-items:center; gap:0.5rem;\'><span style=\'width:10px; height:10px; background:#10b981; border-radius:50%; display:inline-block;\'></span> <span style=\'color:#10b981; font-weight:bold;\'>Acima de 20%</span> - Margem Saudável</li><li style=\'margin-bottom:0.25rem; display:flex; align-items:center; gap:0.5rem;\'><span style=\'width:10px; height:10px; background:#f59e0b; border-radius:50%; display:inline-block;\'></span> <span style=\'color:#f59e0b; font-weight:bold;\'>Abaixo de 20%</span> - Atenção Necessária</li></ul><p style=\'margin-top:1rem; font-size:0.8rem; color:var(--text-dim);\'>Para uma visão com impostos e taxas, consulte a aba Custos por SKU.</p>')">
                            <div style="display: flex; align-items: center; gap: 4px;">
                                Margem Bruta <i data-lucide="help-circle" style="width: 14px; color: var(--text-dim);"></i>
                            </div>
                        </th>
                        <th>Estoque</th>
                        <th style="cursor: pointer;" onclick="showHelp('Status de Sincronia', '<p>Indica a origem e o estado de conexão do seu produto:</p><ul style=\'list-style:none; padding:0; margin-top:1rem;\'><li style=\'margin-bottom:0.75rem; display:flex; align-items:center; gap:0.75rem;\'><i data-lucide=\'refresh-cw\' style=\'width:16px; color:#10b981;\'></i> <strong>Sincronizado:</strong> Produto conectado via integração (ERP/Marketplace). Preços e estoque podem ser atualizados automaticamente pela IA.</li><li style=\'display:flex; align-items:center; gap:0.75rem;\'><i data-lucide=\'user\' style=\'width:16px; color:var(--text-dim);\'></i> <strong>Manual:</strong> Produto cadastrado localmente ou via CSV, sem vínculo externo ativo.</li></ul>')">
                            <div style="display: flex; align-items: center; gap: 4px;">
                                Sincronia <i data-lucide="help-circle" style="width: 14px; color: var(--text-dim);"></i>
                            </div>
                        </th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 3rem; color: var(--text-dim);">Nenhum produto encontrado.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($products as $p): 
                        $cp = $p['cost_price'] ?? 0;
                        $margin = $p['current_price'] > 0 ? (($p['current_price'] - $cp) / $p['current_price']) * 100 : 0;
                    ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 700; color: var(--primary);"><?= $p['sku'] ?></td>
                        <td><?= $p['name'] ?></td>
                        <td>R$ <?= number_format($cp, 2, ',', '.') ?></td>
                        <td><strong>R$ <?= number_format($p['current_price'], 2, ',', '.') ?></strong></td>
                        <td>
                            <span style="color: <?= ($margin > 20 ? '#10b981' : '#f59e0b') ?>; font-weight: 800;">
                                <?= number_format($margin, 1) ?>%
                            </span>
                        </td>
                        <td><?= $p['stock_quantity'] ?> un.</td>
                        <td>
                            <?php if ($p['external_id']): ?>
                                <i data-lucide="refresh-cw" style="width: 14px; color: #10b981;" title="Integrado"></i>
                            <?php else: ?>
                                <i data-lucide="user" style="width: 14px; color: var(--text-dim);" title="Manual"></i>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn-icon" style="color: var(--primary); margin-right: 0.5rem;" onclick='openProductModal(<?= json_encode($p) ?>)'>
                                <i data-lucide="edit-3" style="width: 16px;"></i>
                            </button>
                            <button class="btn-icon" style="color: #ef4444;" onclick="deleteProduct(<?= $p['id'] ?>)">
                                <i data-lucide="trash-2" style="width: 16px;"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Modal Import -->
    <div id="importModal" class="modal">
        <div class="modal-content">
            <div class="card-title">Importar CSV</div>
            <p style="color: var(--text-dim); font-size: 0.85rem; margin-bottom: 1.5rem;">
                Sua planilha deve conter as colunas: <br>
                <strong>SKU, Nome, Preço Venda, Preço Custo, Estoque, Categoria</strong>
            </p>
            <form action="api/import_csv.php" method="POST" enctype="multipart/form-data">
                <input type="file" name="csv_file" class="form-control" accept=".csv" required style="margin-bottom: 1.5rem; padding: 1.5rem; border: 2px dashed var(--border);">
                <div style="display: flex; gap: 1rem;">
                    <button type="button" class="btn btn-outline" style="flex: 1;" onclick="closeModal('importModal')">Cancelar</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Subir Arquivo</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Product Modal (Add/Edit) -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="card-title" id="modalTitle">Novo Produto</div>
            <form id="productForm" style="display: flex; flex-wrap: wrap; gap: 1rem;">
                <input type="hidden" name="id" id="prodId">
                <div class="form-group" style="width: 100%;">
                    <label>Nome do Produto</label>
                    <input type="text" name="name" id="prodName" class="form-control" placeholder="Ex: Cadeira Gamer RGB" required>
                </div>
                <div class="form-group" style="flex: 1; min-width: 200px;">
                    <label>SKU (Código único)</label>
                    <input type="text" name="sku" id="prodSku" class="form-control" placeholder="SKU-001" required>
                </div>
                <div class="form-group" style="flex: 1; min-width: 200px;">
                    <label>Categoria</label>
                    <input type="text" name="category" id="prodCategory" class="form-control" placeholder="Geral">
                </div>
                <div class="form-group" style="flex: 1; min-width: 200px;">
                    <label>Preço de Venda (R$)</label>
                    <input type="number" name="current_price" id="prodPrice" step="0.01" class="form-control" placeholder="0,00 (Calculado via Impostos)">
                </div>
                <div class="form-group" style="flex: 1; min-width: 200px;">
                    <label>Preço de Custo (R$)</label>
                    <input type="number" name="cost_price" id="prodCost" step="0.01" class="form-control" placeholder="0,00" required>
                </div>
                <div class="form-group" style="flex: 1; min-width: 200px;">
                    <label>Estoque Atual (un.)</label>
                    <input type="number" name="stock_quantity" id="prodStock" class="form-control" placeholder="0" required>
                </div>
                <div class="form-group" style="flex: 1; min-width: 200px;">
                    <label>Margem Mínima (%)</label>
                    <input type="number" name="min_margin" id="prodMargin" class="form-control" value="15.00" step="0.01">
                </div>
                
                <div style="width: 100%; display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-outline" style="flex: 1;" onclick="closeModal('productModal')">Cancelar</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Salvar Produto</button>
                </div>
            </form>
        </div>
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

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-content" style="max-width: 450px;">
            <div style="text-align: center;">
                <div style="width: 64px; height: 64px; background: #fee2e2; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
                    <i data-lucide="alert-triangle" style="width: 32px; height: 32px; color: #ef4444;"></i>
                </div>
                <h3 style="font-family: 'Outfit'; margin-bottom: 0.75rem; color: var(--text-main);">Excluir Produto?</h3>
                <p style="color: var(--text-dim); line-height: 1.6; margin-bottom: 2rem;">
                    Esta ação não pode ser desfeita. O produto será removido permanentemente do catálogo.
                    <br><br>
                    <strong style="color: #ef4444;">Atenção:</strong> A exclusão só será permitida se não houver custos ou impostos associados.
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
        function showHelp(title, html) {
            document.getElementById('helpContent').innerHTML = `<h3>${title}</h3>${html}`;
            document.getElementById('helpModal').classList.add('active');
            lucide.createIcons();
        }

        function closeHelp() {
            document.getElementById('helpModal').classList.remove('active');
        }

        function showProductsHelp() {
            showHelp('Gestão de Catálogo', `
                <p>Gerencie seus SKUs e entenda a lógica de precificação do PriceSmart:</p>
                <ul style="display: flex; flex-direction: column; gap: 0.75rem; margin-top: 1rem;">
                    <li><strong>Preço de Venda Sugerido (Zerado):</strong> Ao cadastrar um produto, o preço de venda pode iniciar zerado ou como sugestão.</li>
                    <li><strong>Cálculo Automático (CDD):</strong> O sistema utiliza os impostos e custos que você informou (Custos Gerais e por SKU) para calcular automaticamente o preço final necessário para cobrir suas despesas (CDD - Custo Direto de Distribuição) e atingir sua margem.</li>
                    <li><strong>Proteção de Margem:</strong> A IA respeitará a "Margem Mínima" definida, garantindo que o preço sugerido nunca gere prejuízo.</li>
                    <li><strong>Importação/Busca:</strong> Use a busca rápida ou importe planilhas CSV para atualizações em massa.</li>
                </ul>
                <p style="margin-top: 1rem; font-size: 0.85rem; color: var(--text-dim); border-top: 1px solid var(--border); padding-top: 0.75rem;">
                    <strong>Dica:</strong> Mantenha os custos e impostos atualizados para que o cálculo do preço final seja preciso.
                </p>
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

        function openModal(id) { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }

        function openProductModal(data = null) {
            const form = document.getElementById('productForm');
            const title = document.getElementById('modalTitle');
            form.reset();
            
            if (data) {
                title.innerText = 'Editar Produto';
                document.getElementById('prodId').value = data.id;
                document.getElementById('prodName').value = data.name;
                document.getElementById('prodSku').value = data.sku;
                document.getElementById('prodCategory').value = data.category;
                document.getElementById('prodPrice').value = data.current_price;
                document.getElementById('prodCost').value = data.cost_price || 0;
                document.getElementById('prodStock').value = data.stock_quantity;
                document.getElementById('prodMargin').value = data.min_margin || 15;
            } else {
                title.innerText = 'Novo Produto';
                document.getElementById('prodId').value = '';
            }
            openModal('productModal');
        }

        document.getElementById('productForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            
            try {
                const response = await fetch('api/actions.php?action=save_product', {
                    method: 'POST',
                    body: JSON.stringify(data),
                    headers: { 'Content-Type': 'application/json' }
                });
                const result = await response.json();
                if (result.success) {
                    showToast('Produto salvo com sucesso!', 'success');
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    showToast('Erro: ' + result.message, 'error');
                }
            } catch (err) {
                console.error(err);
            }
        });

        let pendingDeleteId = null;

        function deleteProduct(id) {
            pendingDeleteId = id;
            document.getElementById('confirmModal').classList.add('active');
            lucide.createIcons();
        }

        function closeConfirmModal() {
            document.getElementById('confirmModal').classList.remove('active');
            pendingDeleteId = null;
        }

        async function confirmDelete() {
            if (!pendingDeleteId) {
                console.error('No pending delete ID');
                return;
            }

            // Save ID BEFORE closing modal (which resets pendingDeleteId to null)
            const productId = parseInt(pendingDeleteId);
            console.log('Deleting product ID:', productId);

            closeConfirmModal();
            showToast('Processando exclusão...', 'loading');

            try {
                const payload = { id: productId };
                console.log('Payload:', payload);
                
                const response = await fetch('api/actions.php?action=delete_product', {
                    method: 'POST',
                    body: JSON.stringify(payload),
                    headers: { 'Content-Type': 'application/json' }
                });
                
                const result = await response.json();
                console.log('Result:', result);
                
                if (result.success) {
                    showToast('Produto excluído com sucesso!', 'success');
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    showToast('Erro: ' + result.message, 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Erro ao excluir produto', 'error');
            }
        }
    </script>
</body>
</html>
