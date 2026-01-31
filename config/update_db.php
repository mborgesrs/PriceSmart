<?php
// config/update_db.php
require_once __DIR__ . '/db.php';

try {
    $columns = [
        "ALTER TABLE products ADD COLUMN IF NOT EXISTS cost_price DECIMAL(10,2) DEFAULT 0.00 AFTER current_price",
        "ALTER TABLE products ADD COLUMN IF NOT EXISTS min_margin DECIMAL(5,2) DEFAULT 15.00 AFTER stock_quantity",
        "ALTER TABLE products ADD COLUMN IF NOT EXISTS external_id VARCHAR(100) NULL AFTER company_id",
        "ALTER TABLE products ADD COLUMN IF NOT EXISTS last_sync_at TIMESTAMP NULL AFTER created_at"
    ];

    foreach ($columns as $sql) {
        try {
            $pdo->exec($sql);
            echo "Executado: $sql <br>";
        } catch (Exception $e) {
            echo "Ignorado (provavelmente já existe): " . $e->getMessage() . "<br>";
        }
    }

    echo "Banco de dados atualizado com sucesso!";
} catch (PDOException $e) {
    die("Erro ao atualizar o banco de dados: " . $e->getMessage());
}
?>
