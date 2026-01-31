<?php
require_once __DIR__ . '/../includes/admin_auth_check.php';
require_once __DIR__ . '/layout/header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit;
}

// Update Logic
$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $plan_type = $_POST['plan_type'];
        $plan_status = $_POST['plan_status'];
        $trial_days_add = intval($_POST['trial_days_add'] ?? 0);
        
        $sql = "UPDATE companies SET plan_type = ?, plan_status = ? WHERE id = ?";
        $params = [$plan_type, $plan_status, $id];
        
        if ($trial_days_add > 0) {
            $sql = "UPDATE companies SET plan_type = ?, plan_status = ?, trial_expires_at = DATE_ADD(COALESCE(trial_expires_at, NOW()), INTERVAL ? DAY) WHERE id = ?";
            $params = [$plan_type, $plan_status, $trial_days_add, $id];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $msg = 'Empresa atualizada com sucesso!';
        $msgType = 'success';
    } catch (Exception $e) {
        $msg = 'Erro: ' . $e->getMessage();
        $msgType = 'error';
    }
}

// Fetch Company Data
$stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
$stmt->execute([$id]);
$comp = $stmt->fetch();

if (!$comp) die("Empresa não encontrada");
?>

<div style="max-width: 600px; margin: 0 auto;">
    <div style="margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem;">
        <a href="index.php" style="color: var(--text-dim);"><i data-lucide="arrow-left"></i></a>
        <h1 style="font-family: 'Outfit'; font-size: 1.5rem; margin:0;">Editar Empresa: <?= htmlspecialchars($comp['name']) ?></h1>
    </div>

    <?php if ($msg): ?>
        <div style="padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; 
            background: <?= $msgType === 'success' ? '#dcfce7' : '#fee2e2' ?>; 
            color: <?= $msgType === 'success' ? '#166534' : '#991b1b' ?>;">
            <?= $msg ?>
        </div>
    <?php endif; ?>

    <div class="card" style="background: white; padding: 2rem; border-radius: 16px; border: 1px solid var(--border);">
        <form method="POST">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem;">
                <h2 style="font-family: 'Outfit'; font-size: 1.1rem; margin: 0; color: var(--text-dim);">Configurações do Plano</h2>
                <button type="submit" style="background: var(--primary, #2563eb); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 10px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                    <i data-lucide="check" style="width: 18px;"></i> Salvar
                </button>
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 700; margin-bottom: 0.5rem;">Plano Atual</label>
                <select name="plan_type" class="form-control" style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid #cbd5e1;">
                    <option value="Free" <?= $comp['plan_type'] === 'Free' ? 'selected' : '' ?>>Free</option>
                    <option value="SME" <?= $comp['plan_type'] === 'SME' ? 'selected' : '' ?>>SME (Pequena Empresa)</option>
                    <option value="Pro" <?= $comp['plan_type'] === 'Pro' ? 'selected' : '' ?>>Pro (Ilimitado)</option>
                </select>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 700; margin-bottom: 0.5rem;">Status da Assinatura</label>
                <select name="plan_status" class="form-control" style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid #cbd5e1;">
                    <option value="Active" <?= $comp['plan_status'] === 'Active' ? 'selected' : '' ?>>Ativo (Active)</option>
                    <option value="Past_Due" <?= $comp['plan_status'] === 'Past_Due' ? 'selected' : '' ?>>Atrasado (Block)</option>
                    <option value="Canceled" <?= $comp['plan_status'] === 'Canceled' ? 'selected' : '' ?>>Cancelado</option>
                    <option value="Free" <?= $comp['plan_status'] === 'Free' ? 'selected' : '' ?>>Free / Trial</option>
                </select>
            </div>

            <div style="margin-bottom: 1.5rem; background: #f8fafc; padding: 1rem; border-radius: 8px;">
                <label style="display: block; font-weight: 700; margin-bottom: 0.5rem;">Estender Período de Teste</label>
                <div style="display: flex; gap: 0.5rem;">
                    <input type="number" name="trial_days_add" placeholder="Dias a adicionar (ex: 15)" 
                        class="form-control" style="flex: 1; padding: 0.75rem; border-radius: 8px; border: 1px solid #cbd5e1;">
                </div>
                <small style="color: var(--text-dim);">Expira em: <?= $comp['trial_expires_at'] ? date('d/m/Y', strtotime($comp['trial_expires_at'])) : 'Sem trial ativo' ?></small>
            </div>

            <div style="text-align: right;">
                <button type="submit" style="background: var(--primary, #2563eb); color: white; border: none; padding: 1rem 2rem; border-radius: 12px; font-weight: 700; cursor: pointer;">
                    Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    lucide.createIcons();
</script>
</body>
</html>
