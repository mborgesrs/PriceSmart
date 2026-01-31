<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    $company_id = $_SESSION['company_id'];

    if (($handle = fopen($file, "r")) !== FALSE) {
        $row = 0;
        $pdo->beginTransaction();
        
        try {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Skip header
                if ($row === 0) { $row++; continue; }
                
                $sku = trim($data[0]);
                $name = trim($data[1]);
                $price_sale = (float)str_replace(',', '.', $data[2]);
                $price_cost = (float)str_replace(',', '.', $data[3]);
                $stock = (int)$data[4];
                $category = trim($data[5]);

                if (!$sku || !$name) continue;

                // Insert or Update
                $stmt = $pdo->prepare("
                    SELECT id FROM products WHERE sku = ? AND company_id = ?
                ");
                $stmt->execute([$sku, $company_id]);
                $exists = $stmt->fetch();

                if ($exists) {
                    $stmtUp = $pdo->prepare("
                        UPDATE products SET 
                        name = ?, current_price = ?, cost_price = ?, stock_quantity = ?, category = ?
                        WHERE id = ?
                    ");
                    $stmtUp->execute([$name, $price_sale, $price_cost, $stock, $category, $exists['id']]);
                } else {
                    $stmtIns = $pdo->prepare("
                        INSERT INTO products (company_id, sku, name, current_price, cost_price, stock_quantity, category)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmtIns->execute([$company_id, $sku, $name, $price_sale, $price_cost, $stock, $category]);
                }
                $row++;
            }
            $pdo->commit();
            fclose($handle);
            header("Location: ../products.php?success=imported&count=" . ($row - 1));
        } catch (Exception $e) {
            $pdo->rollBack();
            fclose($handle);
            die("Erro na importação: " . $e->getMessage());
        }
    }
} else {
    header("Location: ../products.php");
}
