<?php require_once __DIR__ . '/includes/auth_check.php'; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulador de Cenários | PriceSmart</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
    
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
<?php
// Fetch all products
$stmt = $pdo->prepare("SELECT * FROM products WHERE company_id = ?");
$stmt->execute([$_SESSION['company_id']]);
$products = $stmt->fetchAll();

// Fetch all product specific costs
$stmtCosts = $pdo->prepare("SELECT * FROM product_costs WHERE company_id = ?");
$stmtCosts->execute([$_SESSION['company_id']]);
$all_costs = $stmtCosts->fetchAll();

$total_potential_revenue = 0;
$total_potential_cost = 0; // Base cost + Purchase Taxes/Costs
$total_potential_expenses = 0; // Sale Taxes/Costs

foreach ($products as $p) {
    $qty = $p['stock_quantity'];
    if ($qty <= 0) continue;

    $price = $p['current_price'];
    $base = $p['cost_price'];

    $total_potential_revenue += ($price * $qty);

    // Calculate unit costs
    $unit_add_cost = 0; // Purchase side
    $unit_expenses = 0; // Sale side

    foreach ($all_costs as $c) {
        if ($c['product_id'] == $p['id']) {
            if ($c['is_percentage']) {
                $val_purchase = $base * ($c['value'] / 100);
                $val_sale = $price * ($c['value'] / 100);
            } else {
                $val_purchase = $c['value'];
                $val_sale = $c['value'];
            }

            if (($c['incidence'] ?? 'sale') === 'purchase') {
                $unit_add_cost += $c['is_percentage'] ? $val_purchase : $c['value'];
            } else {
                $unit_expenses += $c['is_percentage'] ? $val_sale : $c['value'];
            }
        }
    }


    // Total Cost for this unit = Base + unit_add_cost
    $total_potential_cost += ($base + $unit_add_cost) * $qty;
    
    // Total Expenses for this unit
    $total_potential_expenses += ($unit_expenses * $qty);
}

// Fetch Fixed Costs (Monthly)
$stmtFixed = $pdo->prepare("SELECT SUM(value) as total FROM costs WHERE company_id = ? AND type = 'Fixed'");
$stmtFixed->execute([$_SESSION['company_id']]);
$fixed_costs = floatval($stmtFixed->fetch()['total']);

$gross_profit = $total_potential_revenue - $total_potential_cost - $total_potential_expenses;
// Net Profit considering we sell the whole stock in a month (Simulation Baseline)
// If turnover is lower, this is just a "Potential" capacity.
$projected_net_profit = $gross_profit - $fixed_costs;
$current_margin = $total_potential_revenue > 0 ? ($gross_profit / $total_potential_revenue) * 100 : 0;
?>
    <link rel="stylesheet" href="assets/css/app.css?v=1.3">
    <style>
        .simulator-controls {
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 20px;
            border: 1px solid var(--border);
        }
        .slider-group {
            margin-bottom: 2rem;
        }
        .slider-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        .slider-label { color: var(--text-dim); font-size: 0.9rem; }
        .slider-value { color: var(--primary); font-weight: 700; }
        
        input[type="range"] {
            width: 100%;
            height: 6px;
            background: #cbd5e1;
            border-radius: 5px;
            outline: none;
            -webkit-appearance: none;
        }
        input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 18px;
            height: 18px;
            background: var(--primary);
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 0 10px var(--primary-glow);
        }

        .result-box {
            background: var(--accent-glow);
            border: 1px solid var(--accent);
            padding: 1.5rem;
            border-radius: 20px;
            text-align: center;
        }
        .impact-card {
            background: rgba(255,255,255,0.05);
            padding: 1.5rem;
            border-radius: 16px;
            margin-bottom: 1rem;
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
            <li class="nav-item"><a href="simulator.php" class="nav-link active"><i data-lucide="flask-conical"></i> <span>Simulador</span></a></li>
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
                    Simulador de Lucro
                    <div class="btn-help" onclick="showSimulatorHelp()" title="Ajuda">
                        <i data-lucide="help-circle" style="width: 16px; height: 16px;"></i>
                    </div>
                </h1>
                <p style="color: var(--text-dim);">Simule variação de preços e entenda o impacto na margem.</p>
            </div>
            <div class="user-profile">
                <span class="badge badge-primary" style="margin-right: 0.5rem; background: rgba(59, 130, 246, 0.1); color: var(--primary); border: 1px solid rgba(59, 130, 246, 0.2);"><?= $_SESSION['user_plan'] ?? 'SME' ?></span>
                <span><?= $_SESSION['user_name'] ?></span>
                <div class="avatar"><?= substr($_SESSION['user_name'],0,1) ?></div>
            </div>
        </header>

        <div class="dashboard-grid">
            <!-- Controls -->
            <div class="simulator-controls">
                <div class="card-title">Ajuste as Variáveis</div>
                
                <div class="slider-group">
                    <div class="slider-header">
                        <span class="slider-label">Variação de Preço de Venda</span>
                        <span class="slider-value" id="priceVal">0%</span>
                    </div>
                    <input type="range" min="-30" max="30" value="0" id="priceSlider">
                </div>

                <div class="slider-group">
                    <div class="slider-header">
                        <span class="slider-label">Custo de Matéria-Prima</span>
                        <span class="slider-value" id="costVal">0%</span>
                    </div>
                    <input type="range" min="-20" max="100" value="0" id="costSlider">
                </div>

                <div class="slider-group">
                    <div class="slider-header">
                        <span class="slider-label">Variação na Demanda (Volume)</span>
                        <span class="slider-value" id="demandVal">0%</span>
                    </div>
                    <input type="range" min="-50" max="50" value="0" id="demandSlider">
                </div>

                <div class="slider-group">
                    <div class="slider-header">
                        <span class="slider-label">Impostos & Taxas (Adicional)</span>
                        <span class="slider-value" id="taxVal">0%</span>
                    </div>
                    <input type="range" min="0" max="20" value="0" id="taxSlider">
                </div>
            </div>

            <!-- Global Result -->
            <div class="results-panel">
                <div class="result-box" style="margin-bottom: 1.5rem;">
                    <small style="color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px;">POTENCIAL DE LUCRO (ESTOQUE)</small>
                    <div class="stat-value" style="font-size: 3rem; margin: 0.5rem 0;" id="profitImpact">R$ 0,00</div>
                    <p id="profitBadge" style="color: var(--text-dim); font-size: 0.85rem;">Considerando custos fixos cadastrados</p>
                </div>

                <div class="impact-card">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div class="slider-label">Nova Margem Média</div>
                            <div class="stat-value" style="font-size: 1.5rem;" id="newMargin">0.0%</div>
                        </div>
                        <i data-lucide="trending-up" id="marginIcon" style="color: #10b981;"></i>
                    </div>
                </div>

                <div class="impact-card">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div class="slider-label">Ponto de Equilíbrio (Receita)</div>
                            <div class="stat-value" style="font-size: 1.5rem;" id="breakEven">R$ 0,00</div>
                        </div>
                        <i data-lucide="activity" style="color: #3b82f6;"></i>
                    </div>
                </div>
            </div>

            <!-- Comparison Chart -->
            <div class="card-section" style="grid-column: span 2;">
                <div class="card-title">Projeção: Lucro Líquido vs Cenário Atual</div>
                <div class="chart-container" style="height: 300px;">
                    <canvas id="projectionChart"></canvas>
                </div>
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

    <div id="toast-container"></div>

    <script>
        function showHelp(title, html) {
            document.getElementById('helpContent').innerHTML = `<h3>${title}</h3>${html}`;
            document.getElementById('helpModal').classList.add('active');
        }

        function closeHelp() {
            document.getElementById('helpModal').classList.remove('active');
        }

        function showSimulatorHelp() {
            showHelp('Simulador de Lucro', `
                <p>O simulador permite prever cenários sem alterar seus dados reais:</p>
                <ul>
                    <li><strong>Ajuste de Preço:</strong> Use o slider para ver como o aumento de preço melhora a margem ou o desconto a reduz.</li>
                    <li><strong>CMV Dinâmico:</strong> Altere o custo de mercadoria para prever impacto de reajustes de fornecedores.</li>
                    <li><strong>Taxas Variáveis:</strong> Veja como novas taxas de marketplace afetam seu lucro líquido por venda.</li>
                </ul>
            `);
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
            }, 5000); // 5 segundos conforme solicitado
        }

        lucide.createIcons();


        // Initial Data from PHP
        const baseNetProfit = <?= $projected_net_profit ?>; 
        const baseMargin = <?= $current_margin ?>;
        const totalRevenue = <?= $total_potential_revenue ?>;
        const totalCost = <?= $total_potential_cost ?>;
        const fixedCosts = <?= $fixed_costs ?>;
        
        const sliders = {
            price: document.getElementById('priceSlider'),
            cost: document.getElementById('costSlider'),
            demand: document.getElementById('demandSlider'),
            tax: document.getElementById('taxSlider')
        };

        const displays = {
            price: document.getElementById('priceVal'),
            cost: document.getElementById('costVal'),
            demand: document.getElementById('demandVal'),
            tax: document.getElementById('taxVal'),
            profit: document.getElementById('profitImpact'),
            margin: document.getElementById('newMargin'),
            breakEven: document.getElementById('breakEven')
        };

        function updateSimulation() {
            displays.price.innerText = (sliders.price.value > 0 ? '+' : '') + sliders.price.value + '%';
            displays.cost.innerText = (sliders.cost.value > 0 ? '+' : '') + sliders.cost.value + '%';
            displays.demand.innerText = (sliders.demand.value > 0 ? '+' : '') + sliders.demand.value + '%';
            displays.tax.innerText = '+' + sliders.tax.value + '%';

            // Simulation Logic
            let p_var = parseFloat(sliders.price.value) / 100;
            let c_var = parseFloat(sliders.cost.value) / 100;
            let d_var = parseFloat(sliders.demand.value) / 100;
            let t_add = parseFloat(sliders.tax.value) / 100;

            // New Revenue
            let newRevenue = totalRevenue * (1 + p_var) * (1 + d_var);
            
            // New Variable Cost
            let newCost = totalCost * (1 + c_var) * (1 + d_var);

            // New Variable Expenses (Taxes + Commissions)
            // Using Margin to estimate Tax proportion if not granular
            // Ideally: Tax = Revenue - GrossProfit - Cost. 
            // Let's assume proportional tax growth with revenue.
            let grossProfitBase = totalRevenue - totalCost; // Simplified pre-tax
            // We need a better tax proxy. 
            // In PHP we had total_potential_expenses (Sale Taxes).
            // Let's assume expenses are a fixed % of revenue roughly.
            let taxRate = <?= ($total_potential_expenses / ($total_potential_revenue ?: 1)) ?>; 
            let newVariableExpenses = (newRevenue * taxRate) + (newRevenue * t_add);

            let newGrossProfit = newRevenue - newCost - newVariableExpenses;
            let newNetProfit = newGrossProfit - fixedCosts;
            
            let difference = newNetProfit - baseNetProfit;

            displays.profit.innerText = 'R$ ' + difference.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
            displays.profit.style.color = difference >= 0 ? '#10b981' : '#ef4444';
            
            let newMargin = newRevenue > 0 ? (newNetProfit / newRevenue) * 100 : 0;
            // Show Gross Margin or Net Margin? Usually Simulators show Contribution Margin, but here user sees "Profit". 
            // Let's show Net Margin since we included fixed costs.
            displays.margin.innerText = newMargin.toFixed(1) + '%';
            
            // Break Even (Ponto de Equilibrio Contábil = Custos Fixos / Margem Contribuição Unitária)
            // Here: BreakEven Revenue = Fixed Costs / (GrossMargin %)
            let grossMarginPerc = newRevenue > 0 ? (newGrossProfit / newRevenue) : 0;
            let breakEvenRev = grossMarginPerc > 0 ? (fixedCosts / grossMarginPerc) : 0;

            displays.breakEven.innerText = breakEvenRev > 0 
                ? 'R$ ' + breakEvenRev.toLocaleString('pt-BR', {maximumFractionDigits:0}) 
                : 'N/A';
            
            updateChart(baseNetProfit, newNetProfit);
        }

        Object.values(sliders).forEach(s => s.addEventListener('input', updateSimulation));

        // Use baseProfit for the chart baseline
        const monthlyData = Array(6).fill(baseNetProfit);

        const ctx = document.getElementById('projectionChart').getContext('2d');
        let chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Mês 1', 'Mês 2', 'Mês 3', 'Mês 4', 'Mês 5', 'Mês 6'],
                datasets: [{
                    label: 'Lucro Atual (Potencial Estoque)',
                    data: monthlyData,
                    backgroundColor: 'rgba(148, 163, 184, 0.2)',
                    borderColor: '#94a3b8',
                    borderWidth: 1
                }, {
                    label: 'Lucro Simulado',
                    data: monthlyData, // Init with same
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    borderColor: '#3b82f6',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8' } },
                    x: { ticks: { color: '#94a3b8' } }
                }
            }
        });

        function updateChart(base, projected) {
            // Update the simulated dataset
            chart.data.datasets[0].data = Array(6).fill(base);
            chart.data.datasets[1].data = Array(6).fill(projected);
            chart.update();
        }

        updateSimulation();

        updateSimulation();
    </script>
</body>
</html>
