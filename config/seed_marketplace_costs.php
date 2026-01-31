<?php
require_once __DIR__ . '/../config/db.php';
session_start();

$company_id = $_SESSION['company_id'] ?? 1;

$defaults = [
    ['name' => 'DAS (Simples Nacional)', 'type' => 'Tax', 'value' => 6.00, 'is_percentage' => 1, 'incidence' => 'sale'],
    ['name' => 'Comissão Mercado Livre', 'type' => 'Tax', 'value' => 17.00, 'is_percentage' => 1, 'incidence' => 'sale'],
    ['name' => 'Taxa Fixa ML (Vendas < R$79)', 'type' => 'Tax', 'value' => 6.00, 'is_percentage' => 0, 'incidence' => 'sale'],
    ['name' => 'Comissão Amazon', 'type' => 'Tax', 'value' => 15.00, 'is_percentage' => 1, 'incidence' => 'sale'],
    ['name' => 'Comissão Shopify', 'type' => 'Tax', 'value' => 2.00, 'is_percentage' => 1, 'incidence' => 'sale'],
    ['name' => 'Frete Fixo Médio', 'type' => 'Variable', 'value' => 20.00, 'is_percentage' => 0, 'incidence' => 'sale']
];

echo "<h2>Semeando custos de marketplace para empresa ID: $company_id</h2>";

foreach ($defaults as $d) {
    // Check if exists by name
    $stmt = $pdo->prepare("SELECT id FROM costs WHERE company_id = ? AND name = ?");
    $stmt->execute([$company_id, $d['name']]);
    if (!$stmt->fetch()) {
        $stmtIns = $pdo->prepare("INSERT INTO costs (company_id, name, type, value, is_percentage, incidence) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtIns->execute([$company_id, $d['name'], $d['type'], $d['value'], $d['is_percentage'], $d['incidence']]);
        echo "Adicionado: {$d['name']}<br>";
    } else {
        echo "Já existe: {$d['name']}<br>";
    }
}

echo "<br><a href='../app/costs.php'>Voltar para Custos</a>";
?>
