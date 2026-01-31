<?php
require_once __DIR__ . '/../includes/admin_auth_check.php';
require_once __DIR__ . '/layout/header.php';

// Fetch all companies and their subscription status
$stmt = $pdo->query("
    SELECT c.*, 
    (SELECT COUNT(*) FROM users WHERE company_id = c.id) as user_count,
    (SELECT COUNT(*) FROM products WHERE company_id = c.id) as product_count
    FROM companies c 
    ORDER BY c.created_at DESC
");
$companies = $stmt->fetchAll();

// Fetch System Config Check
$stmtC = $pdo->prepare("SELECT conf_value FROM system_config WHERE conf_key = 'stripe_public_key'");
$stmtC->execute();
$has_stripe = !empty($stmtC->fetchColumn());

// Formatter Function
function formatCNPJ($cnpj) {
    $cnpj = preg_replace("/\D/", '', $cnpj);
    if (strlen($cnpj) != 14) return $cnpj;
    return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "$1.$2.$3/$4-$5", $cnpj);
}
?>

<div style="margin-bottom: 2rem;">
    <h1 style="font-family: 'Outfit'; font-size: 2rem; margin-bottom: 0.5rem;">Painel de Controle</h1>
    <p style="color: var(--text-dim);">Gerenciamento global de inquilinos e assinaturas.</p>
</div>

<?php if (!$has_stripe): ?>
<div style="background: #ffffb0; border-left: 4px solid #f59e0b; padding: 1rem; margin-bottom: 2rem; border-radius: 4px; color: #92400e;">
    <strong>Atenção:</strong> As chaves do Stripe ainda não foram configuradas. <a href="stripe_config.php" style="color: inherit; text-decoration: underline;">Configurar agora</a> para habilitar pagamentos.
</div>
<?php endif; ?>

<div class="card" style="background: white; border-radius: 16px; border: 1px solid var(--border); overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f8fafc; text-align: left; border-bottom: 1px solid var(--border);">
                <th style="padding: 1rem; color: var(--text-dim); font-size: 0.85rem;">EMPRESA</th>
                <th style="padding: 1rem; color: var(--text-dim); font-size: 0.85rem;">CNPJ</th>
                <th style="padding: 1rem; color: var(--text-dim); font-size: 0.85rem;">PLANO</th>
                <th style="padding: 1rem; color: var(--text-dim); font-size: 0.85rem;">STATUS</th>
                <th style="padding: 1rem; color: var(--text-dim); font-size: 0.85rem;">USUÁRIOS</th>
                <th style="padding: 1rem; color: var(--text-dim); font-size: 0.85rem;">PRODUTOS</th>
                <th style="padding: 1rem; color: var(--text-dim); font-size: 0.85rem;">PRÓX. COBRANÇA</th>
                <th style="padding: 1rem; color: var(--text-dim); font-size: 0.85rem;">AÇÕES</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($companies as $comp): ?>
            <tr style="border-bottom: 1px solid var(--border);">
                <td style="padding: 1rem;">
                    <strong><?= htmlspecialchars($comp['name']) ?></strong><br>
                    <small style="color: var(--text-dim);">ID: <?= $comp['id'] ?></small>
                </td>
                <td style="padding: 1rem;"><?= htmlspecialchars(formatCNPJ($comp['cnpj'])) ?></td>
                <td style="padding: 1rem;">
                    <span style="background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600;">
                        <?= $comp['plan_type'] ?>
                    </span>
                </td>
                <td style="padding: 1rem;">
                    <?php 
                        $statusClass = 'status-' . strtolower($comp['plan_status']);
                    ?>
                    <span class="badge-status <?= $statusClass ?>">
                        <?= $comp['plan_status'] ?>
                    </span>
                </td>
                <td style="padding: 1rem;"><?= $comp['user_count'] ?></td>
                <td style="padding: 1rem;"><?= $comp['product_count'] ?></td>
                <td style="padding: 1rem;">
                    <?= $comp['next_billing_at'] ? date('d/m/Y', strtotime($comp['next_billing_at'])) : '-' ?>
                </td>
                <td style="padding: 1rem;">
                    <a href="company_edit.php?id=<?= $comp['id'] ?>" class="btn-icon" style="background: none; border: none; cursor: pointer; color: var(--text-dim); text-decoration: none;" title="Editar">
                        <i data-lucide="edit-3" size="16"></i>
                    </a>
                    <?php if ($comp['external_customer_id']): ?>
                        <a href="https://dashboard.stripe.com/customers/<?= $comp['external_customer_id'] ?>" target="_blank" style="color: #6366f1; margin-left: 0.5rem;" title="Ver no Stripe">
                            <i data-lucide="external-link" size="16"></i>
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    lucide.createIcons();
</script>
</body>
</html>
