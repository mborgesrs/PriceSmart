<?php
// app/billing/checkout.php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/auth_check.php'; // Garante que o usuário está logado
require_once __DIR__ . '/../../vendor/autoload.php';

echo "<div style='font-family: sans-serif; text-align: center; padding: 50px;'>
        <p>Redirecionando para o Stripe segura...</p>
        <div style='margin: 20px auto; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3b82f6; border-radius: 50%; animation: spin 1s linear infinite;'></div>
        <style>@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>
      </div>";
flush(); // Send to browser immediately

// 1. Pegar Configurações do Banco
$stmt = $pdo->query("SELECT conf_key, conf_value FROM system_config");
$config = [];
while ($row = $stmt->fetch()) {
    $config[$row['conf_key']] = $row['conf_value'];
}

$stripe_secret = $config['stripe_secret_key'] ?? '';
$plan = $_GET['plan'] ?? 'sme';

// 2. Definir o Price ID com base no plano escolhido
$price_id = ($plan === 'pro') ? ($config['stripe_price_id_pro'] ?? '') : ($config['stripe_price_id_sme'] ?? '');

if (!$stripe_secret) {
    echo "❌ Erro: Chave Secreta do Stripe não configurada no Admin.";
    exit;
}

if (!$price_id) {
    echo "❌ Erro: O ID do Preço (Price ID) para o plano <strong>" . strtoupper($plan) . "</strong> não foi preenchido no Painel Admin.<br>";
    echo "Crie o produto no Stripe, copie o ID do Preço e cole em Configurações Stripe no Admin.";
    exit;
}

\Stripe\Stripe::setApiKey($stripe_secret);

try {
    // Detect Protocol properly
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $baseUrl = $protocol . $_SERVER['HTTP_HOST'] . '/PriceSmart';

    // 3. Criar a Sessão de Checkout
    $checkout_session = \Stripe\Checkout\Session::create([
        'line_items' => [[
            'price' => $price_id,
            'quantity' => 1,
        ]],
        'mode' => 'subscription', // Para planos recorrentes (mensalidade)
        'success_url' => $baseUrl . '/app/billing_success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => $baseUrl . '/app/index.php',
        'client_reference_id' => $_SESSION['company_id'], // Importante para o Webhook saber quem pagou
    ]);

    // 4. Redirecionar para o Stripe
    header("HTTP/1.1 303 See Other");
    header("Location: " . $checkout_session->url);
} catch (Exception $e) {
    echo "Erro ao iniciar checkout: " . $e->getMessage();
}
?>
