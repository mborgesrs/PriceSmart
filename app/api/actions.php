<?php
// app/api/actions.php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../../config/db.php';

$action = $_GET['action'] ?? '';
$response = ['success' => false, 'message' => 'Ação inválida'];

$data = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
}

switch ($action) {
    case 'save_cost':
        try {
            $id = $data['id'] ?? '';
            $product_id = $data['product_id'] ?? null;
            $table = $product_id ? 'product_costs' : 'costs';
            
            if ($id) {
                $stmt = $pdo->prepare("UPDATE $table SET name = ?, type = ?, value = ?, is_percentage = ?, incidence = ? WHERE id = ? AND company_id = ?");
                $stmt->execute([
                    $data['name'],
                    $data['type'],
                    $data['value'],
                    $data['is_percentage'] ?? 0,
                    $data['incidence'] ?? 'sale',
                    $id,
                    $company_id
                ]);
                
                // Recalculate price if it's a product-specific cost
                if ($product_id) {
                    updateProductBreakevenPrice($pdo, $product_id, $company_id);
                }
                
                $response = ['success' => true, 'message' => 'Custo atualizado e preço recalculado!'];
            } else {
                $sql = $product_id 
                    ? "INSERT INTO product_costs (company_id, product_id, name, type, value, is_percentage, incidence) VALUES (?, ?, ?, ?, ?, ?, ?)"
                    : "INSERT INTO costs (company_id, name, type, value, is_percentage, incidence) VALUES (?, ?, ?, ?, ?, ?)";
                
                $stmt = $pdo->prepare($sql);
                if ($product_id) {
                    $stmt->execute([$company_id, $product_id, $data['name'], $data['type'], $data['value'], $data['is_percentage'] ?? 0, $data['incidence'] ?? 'sale']);
                    updateProductBreakevenPrice($pdo, $product_id, $company_id);
                } else {
                    $stmt->execute([$company_id, $data['name'], $data['type'], $data['value'], $data['is_percentage'] ?? 0, $data['incidence'] ?? 'sale']);
                }
                $response = ['success' => true, 'message' => 'Custo adicionado e preço recalculado!'];
            }
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        break;

    case 'update_base_tax':
        try {
            $new_rate = $data['tax_rate'] ?? 0;
            $stmt = $pdo->prepare("UPDATE companies SET base_tax_rate = ? WHERE id = ?");
            $stmt->execute([$new_rate, $company_id]);
            $response = ['success' => true, 'message' => 'Taxa global atualizada!'];
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        break;
    
    case 'get_costs':
        try {
            $product_id = $_GET['product_id'] ?? null;
            if ($product_id) {
                // Return ONLY costs from product_costs for SKUs
                $stmt = $pdo->prepare("SELECT * FROM product_costs WHERE company_id = ? AND product_id = ? ORDER BY created_at DESC");
                $stmt->execute([$company_id, $product_id]);
            } else {
                // Return Global templates from costs
                $stmt = $pdo->prepare("SELECT * FROM costs WHERE company_id = ? ORDER BY created_at DESC");
                $stmt->execute([$company_id]);
            }
            $costs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response = ['success' => true, 'costs' => $costs];
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        break;
    case 'delete_cost':
        try {
            $product_id = $data['product_id'] ?? null;
            $table = $product_id ? 'product_costs' : 'costs';
            $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ? AND company_id = ?");
            $stmt->execute([$data['id'], $company_id]);
            if ($product_id) {
                updateProductBreakevenPrice($pdo, $product_id, $company_id);
            }
            $response = ['success' => true, 'message' => 'Custo removido e preço recalculado!'];
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        break;

    case 'reanalyze_all':
        try {
            require_once __DIR__ . '/../includes/ai_engine.php';
            $ai = new AIEngine($pdo);
            $ai->generateMockSuggestions($company_id);
            $response = ['success' => true, 'message' => 'Catálogo reanalisado com sucesso!'];
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        break;

    case 'apply_suggestion':
        try {
            $pdo->beginTransaction();
            $id = $data['id'];
            $stmtS = $pdo->prepare("SELECT * FROM ai_suggestions WHERE id = ?");
            $stmtS->execute([$id]);
            $suggestion = $stmtS->fetch();

            if ($suggestion) {
                $stmtP = $pdo->prepare("UPDATE products SET current_price = ? WHERE id = ?");
                $stmtP->execute([$suggestion['suggested_price'], $suggestion['product_id']]);
                $stmtU = $pdo->prepare("UPDATE ai_suggestions SET status = 'Applied' WHERE id = ?");
                $stmtU->execute([$id]);
                $pdo->commit();
                $response = ['success' => true, 'message' => 'Preço atualizado com sucesso!'];
            } else {
                $response['message'] = 'Sugestão não encontrada';
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $response['message'] = $e->getMessage();
        }
        break;

    case 'save_product':
        try {
            $id = $data['id'] ?? '';
            if ($id) {
                $stmt = $pdo->prepare("UPDATE products SET name = ?, sku = ?, current_price = ?, cost_price = ?, stock_quantity = ?, category = ?, min_margin = ? WHERE id = ? AND company_id = ?");
                $stmt->execute([
                    $data['name'],
                    $data['sku'],
                    $data['current_price'],
                    $data['cost_price'],
                    $data['stock_quantity'],
                    $data['category'],
                    $data['min_margin'],
                    $id,
                    $company_id
                ]);
                $response = ['success' => true, 'message' => 'Produto atualizado!'];
            } else {
                // Check Plan Limit for FREE
                if ($_SESSION['user_plan'] === 'Free') {
                    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM products WHERE company_id = ?");
                    $stmtCount->execute([$company_id]);
                    $currentProducts = $stmtCount->fetchColumn();
                    
                    if ($currentProducts >= 10) {
                        throw new Exception("Limite do Plano Free atingido (máx. 10 produtos). Faça o upgrade para o plano SME para catálogo ilimitado.");
                    }
                }

                // Insert
                $stmt = $pdo->prepare("INSERT INTO products (company_id, name, sku, current_price, cost_price, stock_quantity, category, min_margin) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $company_id,
                    $data['name'],
                    $data['sku'],
                    $data['current_price'],
                    $data['cost_price'],
                    $data['stock_quantity'],
                    $data['category'],
                    $data['min_margin']
                ]);
            }

            // Trigger AI Reanalysis for the company after saving a product
            require_once __DIR__ . '/../includes/ai_engine.php';
            $ai = new AIEngine($pdo);
            $ai->generateMockSuggestions($company_id);

            $response = ['success' => true, 'message' => 'Produto salvo e analisado pela IA!'];
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        break;

    case 'delete_product':
        try {
            // Debug: log received data
            error_log("Delete product request - Data received: " . json_encode($data));
            
            $product_id = $data['id'] ?? null;
            
            if (!$product_id) {
                error_log("Delete product - No ID provided. Full data: " . print_r($data, true));
                throw new Exception("ID do produto não fornecido. Data: " . json_encode($data));
            }

            // Check if product belongs to company
            $stmtCheck = $pdo->prepare("SELECT id FROM products WHERE id = ? AND company_id = ?");
            $stmtCheck->execute([$product_id, $company_id]);
            if (!$stmtCheck->fetch()) {
                throw new Exception("Produto não encontrado ou não pertence a esta empresa.");
            }

            // Check if product has associated costs (block deletion if it does)
            $stmtCosts = $pdo->prepare("SELECT COUNT(*) as count FROM product_costs WHERE product_id = ?");
            $stmtCosts->execute([$product_id]);
            $costCount = $stmtCosts->fetch();
            
            if ($costCount['count'] > 0) {
                throw new Exception("Não é possível excluir este produto pois ele possui custos/impostos associados. Remova os custos primeiro na aba 'Custos por SKU'.");
            }

            // Check if product has AI suggestions (we should clean those too)
            $stmtAI = $pdo->prepare("DELETE FROM ai_suggestions WHERE product_id = ?");
            $stmtAI->execute([$product_id]);

            // Delete the product
            $stmtDel = $pdo->prepare("DELETE FROM products WHERE id = ? AND company_id = ?");
            $stmtDel->execute([$product_id, $company_id]);
            
            $response = ['success' => true, 'message' => 'Produto excluído com sucesso!'];
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        break;


    case 'save_settings':
        try {
            $stmt = $pdo->prepare("UPDATE companies SET name = ?, cnpj = ?, tax_regime = ?, base_tax_rate = ?, target_margin = ? WHERE id = ?");
            $stmt->execute([
                $data['name'],
                $data['cnpj'],
                $data['tax_regime'],
                $data['base_tax_rate'],
                $data['target_margin'],
                $company_id
                ]);
            $response = ['success' => true, 'message' => 'Configurações salvas!'];
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        break;

    case 'save_integration':
        try {
            $platform = $data['platform'];
            $api_key = $data['api_key'] ?? '';
            $webhook_url = $data['webhook_url'] ?? '';
            
            // Check if already exists
            $stmt = $pdo->prepare("SELECT id FROM integrations WHERE company_id = ? AND platform = ?");
            $stmt->execute([$company_id, $platform]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $stmt = $pdo->prepare("UPDATE integrations SET api_key = ?, webhook_url = ?, is_active = 1 WHERE id = ?");
                $stmt->execute([$api_key, $webhook_url, $existing['id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO integrations (company_id, platform, api_key, webhook_url) VALUES (?, ?, ?, ?)");
                $stmt->execute([$company_id, $platform, $api_key, $webhook_url]);
            }
            
            $response = ['success' => true, 'message' => "Integração $platform salva!"];
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        break;

    case 'test_integration':
        try {
            $platform = $data['platform'];
            $api_key = $data['api_key'];
            
            if ($api_key === 'DEMO_PRICESMART') {
                $response = ['success' => true, 'message' => "Modo Demonstração ativado para $platform!"];
            } else if ($platform === 'Bling') {
                // Test Bling V3
                $url = "https://www.bling.com.br/Api/v3/produtos?pagina=1&limite=1";
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $api_key"]);
                $res = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode === 200) {
                    $response = ['success' => true, 'message' => 'Conexão com Bling validada com sucesso!'];
                } else {
                    $response['message'] = "Erro na validação do Bling (HTTP $httpCode). Verifique seu Token V3.";
                }
            } else if ($platform === 'Tiny') {
                $url = "https://api.tiny.com.br/api2/produtos.pesquisa.php?token=$api_key&formato=json";
                $res = @file_get_contents($url);
                if ($res === false) {
                    throw new Exception("Não foi possível conectar ao Tiny.");
                }
                $resData = json_decode($res, true);
                
                if (isset($resData['retorno']['status']) && $resData['retorno']['status'] === 'OK') {
                    $response = ['success' => true, 'message' => 'Conexão com Tiny validada com sucesso!'];
                } else {
                    $errMsg = $resData['retorno']['erros'][0]['erro'] ?? 'Token inválido ou limite excedido';
                    $response['message'] = "Erro no Tiny: $errMsg";
                }
            } else {
                $response = ['success' => true, 'message' => "Configuração salva para $platform (Validação pendente)"];
            }
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        break;

    case 'save_purchase_simulation':
        try {
            $pdo->beginTransaction();
            $id = $data['id'] ?? null;
            $base_date = $data['base_date'];
            $supplier_name = $data['supplier_name'] ?? '';
            $items = $data['items'] ?? [];
            $total_value = 0;

            foreach ($items as $item) {
                $total_value += floatval($item['real_cost']);
            }

            if ($id) {
                // Update header
                $stmt = $pdo->prepare("UPDATE purchase_simulations SET base_date = ?, supplier_name = ?, total_value = ? WHERE id = ? AND company_id = ?");
                $stmt->execute([$base_date, $supplier_name, $total_value, $id, $company_id]);
                $simulation_id = $id;

                // Delete old items to re-insert (cleaner than updating each)
                $stmtDelete = $pdo->prepare("DELETE FROM purchase_simulation_items WHERE simulation_id = ?");
                $stmtDelete->execute([$simulation_id]);
            } else {
                // Insert header
                $stmt = $pdo->prepare("INSERT INTO purchase_simulations (company_id, base_date, supplier_name, total_value) VALUES (?, ?, ?, ?)");
                $stmt->execute([$company_id, $base_date, $supplier_name, $total_value]);
                $simulation_id = $pdo->lastInsertId();
            }

            $stmtItem = $pdo->prepare("INSERT INTO purchase_simulation_items (simulation_id, product_id, base_cost, taxes_and_costs, real_cost) VALUES (?, ?, ?, ?, ?)");
            foreach ($items as $item) {
                $stmtItem->execute([
                    $simulation_id,
                    $item['product_id'],
                    $item['base_cost'],
                    json_encode($item['taxes_and_costs']),
                    $item['real_cost']
                ]);
            }

            $pdo->commit();
            $response = ['success' => true, 'message' => 'Simulação de compra salva com sucesso!'];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $response['message'] = $e->getMessage();
        }
        break;

    case 'get_purchase_simulation_details':
        try {
            $id = $data['id'];
            $stmt = $pdo->prepare("SELECT * FROM purchase_simulations WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $company_id]);
            $simulation = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$simulation) throw new Exception("Simulação não encontrada.");

            $stmtItems = $pdo->prepare("SELECT si.*, p.name, p.sku FROM purchase_simulation_items si JOIN products p ON si.product_id = p.id WHERE si.simulation_id = ?");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

            // Decode JSON for frontend
            foreach ($items as &$item) {
                $item['taxes_and_costs'] = json_decode($item['taxes_and_costs'], true);
            }

            $response = ['success' => true, 'simulation' => $simulation, 'items' => $items];
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        break;

    case 'get_purchase_simulations':
        try {
            $stmt = $pdo->prepare("SELECT * FROM purchase_simulations WHERE company_id = ? ORDER BY created_at DESC");
            $stmt->execute([$company_id]);
            $sims = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response = ['success' => true, 'simulations' => $sims];
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        break;

    case 'delete_purchase_simulation':
        try {
            $stmt = $pdo->prepare("DELETE FROM purchase_simulations WHERE id = ? AND company_id = ?");
            $stmt->execute([$data['id'], $company_id]);
            $response = ['success' => true, 'message' => 'Simulação excluída!'];
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        break;
}

echo json_encode($response);
    
    // Helper function to recalculate product price based on Cost + Taxes + Margin
    function updateProductBreakevenPrice($pdo, $product_id, $company_id) {
        // 1. Get Product Cost & Margin
        $stmtP = $pdo->prepare("SELECT cost_price, min_margin FROM products WHERE id = ?");
        $stmtP->execute([$product_id]);
        $prod = $stmtP->fetch();
        if (!$prod) return;
        
        $base_cost = floatval($prod['cost_price']);
        
        // Check for specific product margin, otherwise use Global Company Margin
        $margin_val = floatval($prod['min_margin'] ?? 0);
        if ($margin_val <= 0) {
            $stmtGlobal = $pdo->prepare("SELECT target_margin FROM companies WHERE id = ?");
            $stmtGlobal->execute([$company_id]);
            $global = $stmtGlobal->fetch();
            $margin_val = floatval($global['target_margin'] ?? 0);
        }

        $margin_perc = $margin_val / 100;
        
        // 2. Get Taxes/Costs
        $stmtC = $pdo->prepare("SELECT * FROM product_costs WHERE product_id = ?");
        $stmtC->execute([$product_id]);
        $costs = $stmtC->fetchAll(PDO::FETCH_ASSOC);
        
        // 3. Calculate Components
        $fixed_purchase = 0;
        $perc_purchase_sum = 0;
        
        $fixed_sale = 0;
        $perc_sale_sum = 0;
        
        foreach($costs as $c) {
            $val = floatval($c['value']);
            $inc = $c['incidence'] ?? 'sale';
            $is_perc = $c['is_percentage'];
            
            if ($inc === 'purchase') {
                if ($is_perc) $perc_purchase_sum += ($val / 100);
                else $fixed_purchase += $val;
            } else {
                // Sale (Taxas de Venda)
                if ($is_perc) $perc_sale_sum += ($val / 100);
                else $fixed_sale += $val;
            }
        }
        
        // Cost Base = Cost * (1 + PercPurchase) + FixedPurchase
        $cost_base = $base_cost * (1 + $perc_purchase_sum) + $fixed_purchase;
        
        // Price Calculation Formula:
        // Price = (CostBase + FixedSale) / (1 - SaleTaxes - Margin)
        
        $divisor = 1 - $perc_sale_sum - $margin_perc;
        
        if ($divisor > 0.05) { // Safety buffer
            $new_price = ($cost_base + $fixed_sale) / $divisor;
        } else {
            // Fallback if Taxes + Margin >= 100% (Mathematical impossibility for simple formula)
            // Just apply a 50% markup over total costs to avoid crash
            $new_price = ($cost_base + $fixed_sale) * 1.5; 
        }
        
        // 4. Update Product
        $stmtUp = $pdo->prepare("UPDATE products SET current_price = ? WHERE id = ?");
        $stmtUp->execute([round($new_price, 2), $product_id]);
        
        return $new_price;
    }

