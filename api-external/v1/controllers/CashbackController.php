<?php

require_once 'middleware/AuthMiddleware.php';
require_once 'middleware/ValidationMiddleware.php';

class CashbackController {
    private $db;
    private $authMiddleware;
    
    public function __construct() {
        $this->db = Database::getConnection();
        $this->authMiddleware = new AuthMiddleware();
    }
    
    public function calculateCashback() {
        $this->authMiddleware->checkPermission('cashback.calculate');
        
        $data = ValidationMiddleware::validateJsonPayload();
        
        ValidationMiddleware::validateRequired($data, ['store_id', 'amount']);
        ValidationMiddleware::validateAmount($data['amount'], 'amount', 0.01);
        
        if (!is_numeric($data['store_id']) || $data['store_id'] <= 0) {
            Response::validation(['store_id' => 'Invalid store ID']);
        }
        
        try {
            // Buscar configurações da loja
            $storeStmt = $this->db->prepare("
                SELECT porcentagem_cashback FROM lojas WHERE id = ? AND status = 'aprovado'
            ");
            $storeStmt->execute([$data['store_id']]);
            $store = $storeStmt->fetch();
            
            if (!$store) {
                Response::notFound('Store not found or not approved');
            }
            
            // Buscar configurações de distribuição
            $configStmt = $this->db->prepare("
                SELECT porcentagem_cliente, porcentagem_admin, porcentagem_loja 
                FROM configuracoes_cashback LIMIT 1
            ");
            $configStmt->execute();
            $config = $configStmt->fetch();
            
            if (!$config) {
                Response::error('Cashback configuration not found', 500);
            }
            
            // Calcular cashback
            $amount = floatval($data['amount']);
            $cashbackPercentage = floatval($store['porcentagem_cashback']);
            $totalCashback = ($amount * $cashbackPercentage) / 100;
            
            $clientAmount = ($totalCashback * $config['porcentagem_cliente']) / 100;
            $adminAmount = ($totalCashback * $config['porcentagem_admin']) / 100;
            $storeAmount = ($totalCashback * $config['porcentagem_loja']) / 100;
            
            $result = [
                'store_id' => intval($data['store_id']),
                'purchase_amount' => $amount,
                'store_cashback_percentage' => $cashbackPercentage,
                'cashback_calculation' => [
                    'total_cashback' => $totalCashback,
                    'client_receives' => $clientAmount,
                    'admin_receives' => $adminAmount,
                    'store_receives' => $storeAmount
                ],
                'distribution_percentages' => [
                    'client_percentage' => floatval($config['porcentagem_cliente']),
                    'admin_percentage' => floatval($config['porcentagem_admin']),
                    'store_percentage' => floatval($config['porcentagem_loja'])
                ]
            ];
            
            Response::success($result);
            
        } catch (Exception $e) {
            error_log('Error calculating cashback: ' . $e->getMessage());
            Response::error('Failed to calculate cashback', 500);
        }
    }
    
    public function getUserCashback($userId) {
        $this->authMiddleware->checkPermission('cashback.read');
        
        if (!is_numeric($userId) || $userId <= 0) {
            Response::error('Invalid user ID', 400);
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    l.id as store_id,
                    l.nome_fantasia as store_name,
                    COALESCE(SUM(
                        CASE WHEN t.status = 'aprovado' THEN t.valor_cliente ELSE 0 END
                    ), 0) as available_balance,
                    COALESCE(SUM(
                        CASE WHEN t.status = 'pendente' THEN t.valor_cliente ELSE 0 END
                    ), 0) as pending_balance,
                    COUNT(t.id) as total_transactions
                FROM lojas l
                LEFT JOIN transacoes_cashback t ON l.id = t.loja_id AND t.usuario_id = ?
                WHERE l.status = 'aprovado'
                GROUP BY l.id, l.nome_fantasia
                HAVING (available_balance > 0 OR pending_balance > 0 OR total_transactions > 0)
                ORDER BY (available_balance + pending_balance) DESC
            ");
            
            $stmt->execute([$userId]);
            $cashbackByStore = $stmt->fetchAll();
            
            // Calcular totais
            $totalAvailable = 0;
            $totalPending = 0;
            
            foreach ($cashbackByStore as $store) {
                $totalAvailable += $store['available_balance'];
                $totalPending += $store['pending_balance'];
            }
            
            $result = [
                'user_id' => intval($userId),
                'summary' => [
                    'total_available_balance' => $totalAvailable,
                    'total_pending_balance' => $totalPending,
                    'total_balance' => $totalAvailable + $totalPending
                ],
                'by_store' => $cashbackByStore
            ];
            
            Response::success($result);
            
        } catch (Exception $e) {
            error_log('Error getting user cashback: ' . $e->getMessage());
            Response::error('Failed to get user cashback', 500);
        }
    }
}
?>