<?php
require_once __DIR__ . '/../includes/admin_auth_check.php';
require_once __DIR__ . '/layout/header.php';

$msg = '';
$msgType = '';

// Handle Messages (Flash Session)
if (isset($_SESSION['flash_msg'])) {
    $msg = $_SESSION['flash_msg']['text'];
    $msgType = $_SESSION['flash_msg']['type'];
    unset($_SESSION['flash_msg']); // Clear immediately so it doesn't show again on refresh
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $keys = [
            'stripe_public_key', 
            'stripe_secret_key', 
            'stripe_webhook_secret',
            'stripe_price_id_sme',
            'stripe_price_id_pro'
        ];
        
        foreach ($keys as $key) {
            $val = $_POST[$key] ?? '';
            // Upsert Logic
            $stmt = $pdo->prepare("INSERT INTO system_config (conf_key, conf_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE conf_value = ?");
            $stmt->execute([$key, $val, $val]);
        }
        
        // Set Flash Message
        $_SESSION['flash_msg'] = [
            'text' => 'Configurações salvas com sucesso!',
            'type' => 'success'
        ];
        
        header("Location: stripe_config.php");
        exit;

    } catch (Exception $e) {
        $msg = 'Erro ao salvar: ' . $e->getMessage();
        $msgType = 'error';
    }
}

// Fetch current values
$config = [];
$stmt = $pdo->query("SELECT * FROM system_config");
while ($row = $stmt->fetch()) {
    $config[$row['conf_key']] = $row['conf_value'];
}
?>

<style>
    /* Styling for the Input Group to prevent overlap */
    .input-group-secure {
        display: flex;
        align-items: center;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: white;
        overflow: hidden;
        transition: border-color 0.2s;
    }
    .input-group-secure:focus-within {
        border-color: var(--primary, #2563eb);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }
    .input-group-secure input {
        border: none;
        box-shadow: none;
        padding: 0.75rem;
        flex-grow: 1;
        outline: none;
        width: 100%; /* Ensure it takes available space */
        min-width: 0; /* Fix flex child sizing */
    }
    .input-group-secure .toggle-btn {
        background: #f1f5f9;
        border: none;
        border-left: 1px solid #cbd5e1;
        padding: 0 1rem;
        cursor: pointer;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 48px; /* Match input height roughly */
        transition: all 0.2s;
    }
    .input-group-secure .toggle-btn:hover {
        background: #e2e8f0;
        color: #334155;
    }

    .btn-save {
        background: var(--primary, #2563eb);
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
        font-size: 1rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    .btn-save:hover {
        background: var(--primary-hover, #1d4ed8);
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3);
    }
    
    .flash-message {
        transition: opacity 0.5s ease-out;
    }
</style>

<div style="max-width: 600px; margin: 0 auto;">

    <div style="margin-bottom: 2rem; text-align: center;">
        <h1 style="font-family: 'Outfit'; font-size: 2rem; margin-bottom: 0.5rem;">Configuração Stripe</h1>
        <p style="color: var(--text-dim);">Insira suas chaves de API para habilitar os pagamentos.</p>
    </div>

    <?php if ($msg): ?>
        <div id="flashMsg" class="flash-message" style="padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: center; 
            background: <?= $msgType === 'success' ? '#dcfce7' : '#fee2e2' ?>; 
            color: <?= $msgType === 'success' ? '#166534' : '#991b1b' ?>;">
            <?= $msg ?>
        </div>
    <?php endif; ?>

    <div class="card" style="background: white; padding: 2rem; border-radius: 24px; box-shadow: var(--card-shadow); border: 1px solid var(--border);">
        <form method="POST">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2 style="font-family: 'Outfit'; font-size: 1.25rem; margin: 0;">Parâmetros da API</h2>
                <button type="submit" class="btn-save" style="padding: 0.75rem 1.5rem;">
                    <i data-lucide="save" style="width: 18px;"></i> Salvar
                </button>
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 700; margin-bottom: 0.5rem;">Chave Pública (Public Key)</label>
                <!-- Public key usually doesn't need hiding, keeping as standard input -->
                <input type="text" name="stripe_public_key" value="<?= htmlspecialchars($config['stripe_public_key'] ?? '') ?>" 
                    class="form-control" style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid #cbd5e1;" 
                    placeholder="pk_test_..." required>
                <small style="color: var(--text-dim);">Chave identificadora usada no frontend.</small>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 700; margin-bottom: 0.5rem;">Chave Secreta (Secret Key)</label>
                <div class="input-group-secure">
                    <input type="password" id="secret_key" name="stripe_secret_key" value="<?= htmlspecialchars($config['stripe_secret_key'] ?? '') ?>" 
                        placeholder="sk_test_..." required>
                    <button type="button" class="toggle-btn" onclick="toggleVisibility('secret_key', this)" title="Mostrar/Ocultar">
                        <i data-lucide="eye"></i>
                    </button>
                </div>
                <small style="color: var(--text-dim);">Mantenha esta chave segura. Nunca compartilhe.</small>
            </div>

            <div style="margin-bottom: 2rem;">
                <label style="display: block; font-weight: 700; margin-bottom: 0.5rem;">Segredo do Webhook (Webhook Secret)</label>
                <div class="input-group-secure">
                    <input type="password" id="webhook_key" name="stripe_webhook_secret" value="<?= htmlspecialchars($config['stripe_webhook_secret'] ?? '') ?>" 
                        placeholder="whsec_...">
                    <button type="button" class="toggle-btn" onclick="toggleVisibility('webhook_key', this)" title="Mostrar/Ocultar">
                        <i data-lucide="eye"></i>
                    </button>
                </div>
                <small style="color: var(--text-dim);">Usado para verificar assinaturas e eventos automáticos.</small>
            </div>

            <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 2rem 0;">
            <h3 style="font-family: 'Outfit'; font-size: 1.25rem; margin-bottom: 1.5rem;">IDs dos Planos (Stripe Price IDs)</h3>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 700; margin-bottom: 0.5rem;">ID do Plano SME (Mensal)</label>
                <input type="text" name="stripe_price_id_sme" value="<?= htmlspecialchars($config['stripe_price_id_sme'] ?? '') ?>" 
                    class="form-control" style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid #cbd5e1;" 
                    placeholder="price_...">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 700; margin-bottom: 0.5rem;">ID do Plano PRO (Mensal)</label>
                <input type="text" name="stripe_price_id_pro" value="<?= htmlspecialchars($config['stripe_price_id_pro'] ?? '') ?>" 
                    class="form-control" style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid #cbd5e1;" 
                    placeholder="price_...">
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 2rem;">
                <button type="submit" class="btn-save">
                    <i data-lucide="save" style="width: 20px;"></i>
                    Salvar Configurações
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    lucide.createIcons();

    // Auto dismiss flash message
    const flashMsg = document.getElementById('flashMsg');
    if (flashMsg) {
        setTimeout(() => {
            flashMsg.style.opacity = '0';
            setTimeout(() => flashMsg.remove(), 500);
        }, 3000);
    }

    function toggleVisibility(inputId, btn) {
        const input = document.getElementById(inputId);
        // Toggle logic
        if (input.type === "password") {
            input.type = "text";
            btn.innerHTML = '<i data-lucide="eye-off"></i>';
        } else {
            input.type = "password";
            btn.innerHTML = '<i data-lucide="eye"></i>';
        }
        lucide.createIcons();
    }
</script>
</body>
</html>
