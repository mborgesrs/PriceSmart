<?php
// config/test_get_costs.php
require_once __DIR__ . '/db.php';

// Simular uma sessão
session_start();
$_SESSION['company_id'] = 1; // Ajuste conforme necessário
$company_id = $_SESSION['company_id'];

echo "<h2>Teste de Recuperação de Impostos</h2>";

$product_id = 1; // ID do produto (SKU-Gamer-001)

echo "<h3>Buscando impostos para o produto ID: $product_id</h3>";

try {
    // Simular a query da API
    $stmt = $pdo->prepare("SELECT * FROM costs WHERE company_id = ? AND product_id = ? ORDER BY product_id DESC, created_at DESC");
    $stmt->execute([$company_id, $product_id]);
    $costs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Resultado da query:</h3>";
    echo "<p>Total de registros encontrados: " . count($costs) . "</p>";
    
    if (count($costs) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>";
        foreach (array_keys($costs[0]) as $key) {
            echo "<th>" . htmlspecialchars($key) . "</th>";
        }
        echo "</tr>";
        foreach ($costs as $cost) {
            echo "<tr>";
            foreach ($cost as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
        
        // Simular o JSON retornado pela API
        echo "<h3>JSON que seria retornado pela API:</h3>";
        echo "<pre>";
        echo json_encode(['success' => true, 'costs' => $costs], JSON_PRETTY_PRINT);
        echo "</pre>";
    } else {
        echo "<p style='color: orange;'>Nenhum imposto encontrado para este produto.</p>";
    }
    
    // Também buscar impostos globais
    echo "<h3>Impostos Globais (product_id IS NULL):</h3>";
    $stmt = $pdo->prepare("SELECT * FROM costs WHERE company_id = ? AND product_id IS NULL ORDER BY created_at DESC");
    $stmt->execute([$company_id]);
    $globalCosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Total de impostos globais: " . count($globalCosts) . "</p>";
    
    if (count($globalCosts) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>";
        foreach (array_keys($globalCosts[0]) as $key) {
            echo "<th>" . htmlspecialchars($key) . "</th>";
        }
        echo "</tr>";
        foreach ($globalCosts as $cost) {
            echo "<tr>";
            foreach ($cost as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>✗ Erro:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}

echo "<br><p><a href='../app/costs.php'>Voltar para Gerenciamento de Custos</a></p>";
?>
