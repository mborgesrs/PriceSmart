<?php
// config/add_integrations_table.php
require_once __DIR__ . '/db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS integrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        platform ENUM('Bling', 'Tiny', 'MercadoLivre', 'Shopify', 'Amazon') NOT NULL,
        api_key VARCHAR(255),
        api_token TEXT,
        api_url VARCHAR(255),
        webhook_url VARCHAR(255),
        is_active BOOLEAN DEFAULT TRUE,
        last_sync TIMESTAMP NULL,
        settings JSON NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
    )";

    $pdo->exec($sql);
    echo "Tabela 'integrations' criada com sucesso!";
} catch (PDOException $e) {
    die("Erro ao criar tabela: " . $e->getMessage());
}
?>
