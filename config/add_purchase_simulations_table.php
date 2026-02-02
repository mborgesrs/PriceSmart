<?php
// config/add_purchase_simulations_table.php
require_once __DIR__ . '/db.php';

try {
    // Table for purchase simulations (Headers)
    $sql1 = "CREATE TABLE IF NOT EXISTS purchase_simulations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        base_date DATE NOT NULL,
        supplier_name VARCHAR(255),
        total_value DECIMAL(12,2) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
    )";

    // Table for simulation items
    $sql2 = "CREATE TABLE IF NOT EXISTS purchase_simulation_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        simulation_id INT NOT NULL,
        product_id INT NOT NULL,
        base_cost DECIMAL(10,2) NOT NULL,
        taxes_and_costs JSON, -- Stores array of {name, value, is_percentage}
        real_cost DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (simulation_id) REFERENCES purchase_simulations(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )";

    $pdo->exec($sql1);
    $pdo->exec($sql2);
    
    echo "Tabelas de simulação de compra criadas com sucesso!";
} catch (PDOException $e) {
    die("Erro ao criar tabelas: " . $e->getMessage());
}
?>
