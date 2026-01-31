<?php
// config/fix_costs_table.php
require_once __DIR__ . '/db.php';

try {
    echo "<h2>Atualizando estrutura da tabela 'costs'...</h2>";
    
    // Adicionar coluna 'incidence' se não existir
    $sql = "ALTER TABLE costs ADD COLUMN IF NOT EXISTS incidence ENUM('sale', 'purchase') DEFAULT 'sale' AFTER is_percentage";
    
    try {
        $pdo->exec($sql);
        echo "✓ Coluna 'incidence' adicionada com sucesso!<br>";
    } catch (Exception $e) {
        echo "⚠ Coluna 'incidence' já existe ou erro: " . $e->getMessage() . "<br>";
    }
    
    // Verificar se a coluna foi criada
    $stmt = $pdo->query("SHOW COLUMNS FROM costs LIKE 'incidence'");
    $column = $stmt->fetch();
    
    if ($column) {
        echo "✓ Coluna 'incidence' confirmada na tabela 'costs'<br>";
        echo "<pre>";
        print_r($column);
        echo "</pre>";
    } else {
        echo "✗ ERRO: Coluna 'incidence' NÃO foi criada!<br>";
    }
    
    // Listar todas as colunas da tabela costs
    echo "<h3>Estrutura atual da tabela 'costs':</h3>";
    $stmt = $pdo->query("DESCRIBE costs");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . $col['Default'] . "</td>";
        echo "<td>" . $col['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar registros existentes
    echo "<h3>Registros na tabela 'costs':</h3>";
    $stmt = $pdo->query("SELECT * FROM costs");
    $costs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Total de registros: " . count($costs) . "</p>";
    
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
    }
    
    echo "<br><h2 style='color: green;'>✓ Atualização concluída!</h2>";
    echo "<p><a href='../app/costs.php'>Voltar para Gerenciamento de Custos</a></p>";
    
} catch (PDOException $e) {
    die("<h2 style='color: red;'>✗ Erro ao atualizar o banco de dados:</h2><p>" . $e->getMessage() . "</p>");
}
?>
