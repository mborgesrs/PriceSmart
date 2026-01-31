<?php
// config/test_save_cost.php
require_once __DIR__ . '/db.php';

// Simular uma sessão
session_start();
$_SESSION['company_id'] = 1; // Ajuste conforme necessário
$company_id = $_SESSION['company_id'];

echo "<h2>Teste de Salvamento de Imposto</h2>";

// Dados de teste
$test_data = [
    'product_id' => 1, // ID do produto (SKU-Gamer-001)
    'name' => 'Teste Imposto',
    'type' => 'Tax',
    'value' => 5.00,
    'is_percentage' => 1,
    'incidence' => 'sale'
];

echo "<h3>Dados a serem inseridos:</h3>";
echo "<pre>";
print_r($test_data);
echo "</pre>";

try {
    $stmt = $pdo->prepare("INSERT INTO costs (company_id, product_id, name, type, value, is_percentage, incidence) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $result = $stmt->execute([
        $company_id,
        $test_data['product_id'],
        $test_data['name'],
        $test_data['type'],
        $test_data['value'],
        $test_data['is_percentage'],
        $test_data['incidence']
    ]);
    
    if ($result) {
        $lastId = $pdo->lastInsertId();
        echo "<h3 style='color: green;'>✓ Imposto inserido com sucesso!</h3>";
        echo "<p>ID do registro: $lastId</p>";
        
        // Verificar se foi realmente inserido
        $stmt = $pdo->prepare("SELECT * FROM costs WHERE id = ?");
        $stmt->execute([$lastId]);
        $inserted = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h3>Registro inserido:</h3>";
        echo "<table border='1' cellpadding='5'>";
        foreach ($inserted as $key => $value) {
            echo "<tr><th>$key</th><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
        }
        echo "</table>";
        
        // Verificar todos os impostos do produto
        echo "<h3>Todos os impostos do produto ID {$test_data['product_id']}:</h3>";
        $stmt = $pdo->prepare("SELECT * FROM costs WHERE product_id = ? AND company_id = ?");
        $stmt->execute([$test_data['product_id'], $company_id]);
        $costs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
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
        } else {
            echo "<p>Nenhum imposto encontrado para este produto.</p>";
        }
        
    } else {
        echo "<h3 style='color: red;'>✗ Erro ao inserir imposto</h3>";
    }
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>✗ Erro:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}

echo "<br><p><a href='../app/costs.php'>Voltar para Gerenciamento de Custos</a></p>";
?>
