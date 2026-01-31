<?php
// app/api/erp_import.php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../../config/db.php';

$platform = $_GET['platform'] ?? '';
$company_id = $_SESSION['company_id'];

if (!$platform) {
    echo json_encode(['success' => false, 'message' => 'Plataforma não especificada']);
    exit;
}

// Get API Key
$stmt = $pdo->prepare("SELECT api_key FROM integrations WHERE company_id = ? AND platform = ?");
$stmt->execute([$company_id, $platform]);
$integration = $stmt->fetch();

if (!$integration || !$integration['api_key']) {
    echo json_encode(['success' => false, 'message' => "Integração $platform não configurada"]);
    exit;
}

$apiKey = $integration['api_key'];
$importedCount = 0;
$updatedCount = 0;

try {
    if ($apiKey === 'DEMO_PRICESMART') {
        // Mock Demo Data
        $demoProducts = [
            ['codigo' => 'SKU-DEMO-001', 'nome' => 'Produto Demo ERP 1', 'preco' => 150.00, 'preco_custo' => 80.00],
            ['codigo' => 'SKU-DEMO-002', 'nome' => 'Produto Demo ERP 2', 'preco' => 290.00, 'preco_custo' => 145.00],
            ['codigo' => 'SKU-DEMO-003', 'nome' => 'Produto Demo ERP 3', 'preco' => 45.00, 'preco_custo' => 12.00],
        ];

        foreach ($demoProducts as $p) {
            $sku = $p['codigo'];
            $name = $p['nome'];
            $price = $p['preco'];
            $cost = $p['preco_custo'];

            $stmtCheck = $pdo->prepare("SELECT id FROM products WHERE sku = ? AND company_id = ?");
            $stmtCheck->execute([$sku, $company_id]);
            $exists = $stmtCheck->fetch();

            if ($exists) {
                $stmtUp = $pdo->prepare("UPDATE products SET name = ?, current_price = ?, cost_price = ? WHERE id = ?");
                $stmtUp->execute([$name, $price, $cost, $exists['id']]);
                $updatedCount++;
            } else {
                $stmtIns = $pdo->prepare("INSERT INTO products (company_id, sku, name, current_price, cost_price) VALUES (?, ?, ?, ?, ?)");
                $stmtIns->execute([$company_id, $sku, $name, $price, $cost]);
                $importedCount++;
            }
        }
    } else if ($platform === 'Bling') {
        // Bling V3 API - Import Products
        $url = "https://www.bling.com.br/Api/v3/produtos?pagina=1&limite=50";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $apiKey"]);
        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Erro ao conectar com Bling (HTTP $httpCode)");
        }

        $data = json_decode($res, true);
        $products = $data['data'] ?? [];

        foreach ($products as $p) {
            $sku = $p['codigo'] ?? '';
            $name = $p['nome'] ?? '';
            $price = (float)($p['preco'] ?? 0);
            $cost = (float)($p['precoCusto'] ?? 0);
            // $stock = ... Bling V3 stock might be in another endpoint or nested
            
            if (!$sku || !$name) continue;

            $stmtCheck = $pdo->prepare("SELECT id FROM products WHERE sku = ? AND company_id = ?");
            $stmtCheck->execute([$sku, $company_id]);
            $exists = $stmtCheck->fetch();

            if ($exists) {
                $stmtUp = $pdo->prepare("UPDATE products SET name = ?, current_price = ?, cost_price = ? WHERE id = ?");
                $stmtUp->execute([$name, $price, $cost, $exists['id']]);
                $updatedCount++;
            } else {
                $stmtIns = $pdo->prepare("INSERT INTO products (company_id, sku, name, current_price, cost_price) VALUES (?, ?, ?, ?, ?)");
                $stmtIns->execute([$company_id, $sku, $name, $price, $cost]);
                $importedCount++;
            }
        }

    } else if ($platform === 'Tiny') {
        // Tiny API V2 - Import Products
        $url = "https://api.tiny.com.br/api2/produtos.pesquisa.php?token=$apiKey&formato=json";
        $res = @file_get_contents($url);
        if ($res === false) throw new Exception("Não foi possível conectar ao Tiny.");
        
        $data = json_decode($res, true);
        if (($data['retorno']['status'] ?? '') !== 'OK') {
            throw new Exception($data['retorno']['erros'][0]['erro'] ?? 'Erro desconhecido no Tiny');
        }

        $products = $data['retorno']['produtos'] ?? [];

        foreach ($products as $item) {
            $p = $item['produto'];
            $sku = $p['codigo'] ?? '';
            $name = $p['nome'] ?? '';
            $price = (float)($p['preco'] ?? 0);
            $cost = (float)($p['preco_custo'] ?? 0);

            if (!$sku || !$name) continue;

            $stmtCheck = $pdo->prepare("SELECT id FROM products WHERE sku = ? AND company_id = ?");
            $stmtCheck->execute([$sku, $company_id]);
            $exists = $stmtCheck->fetch();

            if ($exists) {
                $stmtUp = $pdo->prepare("UPDATE products SET name = ?, current_price = ?, cost_price = ? WHERE id = ?");
                $stmtUp->execute([$name, $price, $cost, $exists['id']]);
                $updatedCount++;
            } else {
                $stmtIns = $pdo->prepare("INSERT INTO products (company_id, sku, name, current_price, cost_price) VALUES (?, ?, ?, ?, ?)");
                $stmtIns->execute([$company_id, $sku, $name, $price, $cost]);
                $importedCount++;
            }
        }
    }

    echo json_encode([
        'success' => true, 
        'message' => "Sincronização concluída!",
        'imported' => $importedCount,
        'updated' => $updatedCount
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
