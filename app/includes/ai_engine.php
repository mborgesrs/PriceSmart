<?php
// app/includes/ai_engine.php
require_once __DIR__ . '/../../config/db.php';

class AIEngine {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Gera sugestões de preço baseadas em MARKUP REAL (Custos + Impostos + Margem)
     */
    public function generateMockSuggestions($company_id) {
        // Obter Margem Alvo da Empresa (Padrão)
        $stmtC = $this->pdo->prepare("SELECT target_margin FROM companies WHERE id = ?");
        $stmtC->execute([$company_id]);
        $comp = $stmtC->fetch();
        $default_margin = $comp['target_margin'] ?? 20; // 20% fallback

        // Obter Produtos
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE company_id = ?");
        $stmt->execute([$company_id]);
        $products = $stmt->fetchAll();

        // Obter Custos Globais (para fallback ou composição)
        // Nota: Idealmente custos globais (impostos) deveriam aplicar a todos,
        // mas aqui vamos checar se o produto tem custos específicos primeiro.
        $stmtG = $this->pdo->prepare("SELECT * FROM costs WHERE company_id = ?");
        $stmtG->execute([$company_id]);
        $global_costs = $stmtG->fetchAll();

        foreach ($products as $product) {
            $cost_base = floatval($product['cost_price']);
            if ($cost_base <= 0) continue; // Skip invalid products

            // 1. Obter Custos Específicos do Produto
            $stmtPC = $this->pdo->prepare("SELECT * FROM product_costs WHERE product_id = ?");
            $stmtPC->execute([$product['id']]);
            $sku_costs = $stmtPC->fetchAll();

            // Lógica de Composição: Se não tem custos específicos, usa os globais
            $applied_costs = empty($sku_costs) ? $global_costs : $sku_costs;

            $fixed_costs = 0;
            $variable_taxes_perc = 0;

            foreach ($applied_costs as $c) {
                if ($c['is_percentage']) {
                    // Soma taxas percentuais (Impostos, Comissões)
                    $variable_taxes_perc += floatval($c['value']);
                } else {
                    $val = floatval($c['value']);
                    
                    // Safety Logic: Ignora Custos Fixos Globais que sejam maiores que o Preço de Custo do Produto
                    // Isso evita que custos como "Aluguel" (R$ 2000) sejam somados ao custo unitário de um produto pequeno (R$ 20)
                    if (empty($sku_costs) && $val > $cost_base) {
                        continue;
                    }
                    
                    $fixed_costs += $val;
                }
            }

            // 2. Definir Margem
            // Usa a margem mínima do produto. Se não tiver, usa a da empresa.
            $target_margin = floatval($product['min_margin'] > 0 ? $product['min_margin'] : $default_margin);

            // 3. Fórmula de Precificação (Markup Divisor)
            // Preço = (Custo + Fixo) / (1 - (Taxas + Margem))
            
            $divisor = 1 - (($variable_taxes_perc + $target_margin) / 100);
            
            if ($divisor <= 0) {
                // Caso impossível (taxas + margem > 100%), sugerimos apenas custo + 100% como alerta
                $suggested = ($cost_base + $fixed_costs) * 2;
                $reason = "ERRO CRÍTICO: Taxas e Margem somam mais de 100%. Revise seus impostos.";
            } else {
                $suggested = ($cost_base + $fixed_costs) / $divisor;
                
                // Formatar a razão
                $reason = "Cálculo Markup: Custo (R$ " . number_format($cost_base,2) . ") + Impostos/Taxas (" . $variable_taxes_perc . "%) + Margem Lucro (" . $target_margin . "%).";
                
                // Análise Simples Nacional
                if (strpos($reason, 'Simples') !== false || $variable_taxes_perc > 0) {
                    $reason .= " Inclui proteção para cobrir DAS/Comissões.";
                }
            }

            // Arredondamento comercial (sempre termina em .90 ou .00)
            // $suggested = ceil($suggested * 10) / 10; // Ex: 10.32 -> 10.4

            // Se o preço atual já for maior ou igual ao sugerido (com margem de 1%), não sugere nada ou sugere manter
            // Mas para o usuário ver que a IA funciona, vamos sugerir o preço ideal se a diferença for > 1%
            $diff = abs($suggested - floatval($product['current_price']));
            if ($diff < ($suggested * 0.01)) {
                // Preço está correto, não gera spam de sugestão
                // Ou removemos sugestão antiga se existir
                $stmtDel = $this->pdo->prepare("DELETE FROM ai_suggestions WHERE product_id = ?");
                $stmtDel->execute([$product['id']]);
                continue; 
            }

            // Atualizar Sugestão
            $stmtClear = $this->pdo->prepare("DELETE FROM ai_suggestions WHERE product_id = ? AND status = 'Pending'");
            $stmtClear->execute([$product['id']]);

            $stmtIns = $this->pdo->prepare("INSERT INTO ai_suggestions (product_id, suggested_price, reason) VALUES (?, ?, ?)");
            $stmtIns->execute([$product['id'], $suggested, $reason]);
        }
    }

    /**
     * Get pending suggestions for dashboard
     */
    public function getPendingSuggestions($company_id) {
        $stmt = $this->pdo->prepare("
            SELECT s.*, p.name as product_name, p.current_price, p.sku
            FROM ai_suggestions s
            JOIN products p ON s.product_id = p.id
            WHERE p.company_id = ? AND s.status = 'Pending'
            ORDER BY s.created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$company_id]);
        return $stmt->fetchAll();
    }
}
?>
