<?php

class ApiStore {
    private $db;
    
    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    public function getStoreById($id) {
        $stmt = $this->db->prepare("
            SELECT 
                id, nome_fantasia as trade_name, razao_social as legal_name,
                cnpj, email, telefone as phone, porcentagem_cashback as cashback_percentage,
                status, data_cadastro as created_at
            FROM lojas 
            WHERE id = ?
        ");
        
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getStoreByCNPJ($cnpj) {
        $cleanCnpj = preg_replace('/[^0-9]/', '', $cnpj);
        
        $stmt = $this->db->prepare("
            SELECT 
                id, nome_fantasia as trade_name, razao_social as legal_name,
                cnpj, email, telefone as phone, porcentagem_cashback as cashback_percentage,
                status, data_cadastro as created_at
            FROM lojas 
            WHERE cnpj = ?
        ");
        
        $stmt->execute([$cleanCnpj]);
        return $stmt->fetch();
    }
    
    public function createStore($data) {
        // Verificar se CNPJ já existe
        if ($this->getStoreByCNPJ($data['cnpj'])) {
            throw ApiException::validation('Store with this CNPJ already exists');
        }
        
        $cleanCnpj = preg_replace('/[^0-9]/', '', $data['cnpj']);
        
        $stmt = $this->db->prepare("
            INSERT INTO lojas (
                nome_fantasia, razao_social, cnpj, email, telefone, 
                porcentagem_cashback, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $status = $data['status'] ?? 'pendente';
        $cashbackPercentage = $data['cashback_percentage'] ?? 5.0;
        
        if ($stmt->execute([
            $data['trade_name'],
            $data['legal_name'],
            $cleanCnpj,
            $data['email'],
            $data['phone'],
            $cashbackPercentage,
            $status
        ])) {
            $storeId = $this->db->lastInsertId();
            return $this->getStoreById($storeId);
        }
        
        throw ApiException::serverError('Failed to create store');
    }
    
    public function updateStore($id, $data) {
        $store = $this->getStoreById($id);
        if (!$store) {
            throw ApiException::notFound('Store not found');
        }
        
        $updateFields = [];
        $params = [];
        
        if (isset($data['trade_name'])) {
            $updateFields[] = "nome_fantasia = ?";
            $params[] = $data['trade_name'];
        }
        
        if (isset($data['legal_name'])) {
            $updateFields[] = "razao_social = ?";
            $params[] = $data['legal_name'];
        }
        
        if (isset($data['cnpj'])) {
            $cleanCnpj = preg_replace('/[^0-9]/', '', $data['cnpj']);
            
            // Verificar se CNPJ já existe para outra loja
            $existingStore = $this->getStoreByCNPJ($cleanCnpj);
            if ($existingStore && $existingStore['id'] != $id) {
                throw ApiException::validation('CNPJ already in use by another store');
            }
            
            $updateFields[] = "cnpj = ?";
            $params[] = $cleanCnpj;
        }
        
        if (isset($data['email'])) {
            $updateFields[] = "email = ?";
            $params[] = $data['email'];
        }
        
        if (isset($data['phone'])) {
            $updateFields[] = "telefone = ?";
            $params[] = $data['phone'];
        }
        
        if (isset($data['cashback_percentage'])) {
            $updateFields[] = "porcentagem_cashback = ?";
            $params[] = $data['cashback_percentage'];
        }
        
        if (isset($data['status'])) {
            $updateFields[] = "status = ?";
            $params[] = $data['status'];
        }
        
        if (empty($updateFields)) {
            return $store;
        }
        
        $params[] = $id;
        
        $stmt = $this->db->prepare("
            UPDATE lojas SET " . implode(', ', $updateFields) . " WHERE id = ?
        ");
        
        if ($stmt->execute($params)) {
            return $this->getStoreById($id);
        }
        
        throw ApiException::serverError('Failed to update store');
    }
    
    public function listStores($page = 1, $pageSize = 20, $filters = []) {
        $offset = ($page - 1) * $pageSize;
        
        $whereConditions = [];
        $params = [];
        
        if (isset($filters['status'])) {
            $whereConditions[] = 'status = ?';
            $params[] = $filters['status'];
        }
        
        if (isset($filters['trade_name'])) {
            $whereConditions[] = 'nome_fantasia LIKE ?';
            $params[] = '%' . $filters['trade_name'] . '%';
        }
        
        if (isset($filters['cnpj'])) {
            $cleanCnpj = preg_replace('/[^0-9]/', '', $filters['cnpj']);
            $whereConditions[] = 'cnpj LIKE ?';
            $params[] = '%' . $cleanCnpj . '%';
        }
        
        if (isset($filters['min_cashback'])) {
            $whereConditions[] = 'porcentagem_cashback >= ?';
            $params[] = $filters['min_cashback'];
        }
        
        if (isset($filters['max_cashback'])) {
            $whereConditions[] = 'porcentagem_cashback <= ?';
            $params[] = $filters['max_cashback'];
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Query principal
        $stmt = $this->db->prepare("
            SELECT 
                id, nome_fantasia as trade_name, razao_social as legal_name,
                cnpj, email, telefone as phone, porcentagem_cashback as cashback_percentage,
                status, data_cadastro as created_at
            FROM lojas
            {$whereClause}
            ORDER BY data_cadastro DESC
            LIMIT ? OFFSET ?
        ");
        
        $params[] = $pageSize;
        $params[] = $offset;
        $stmt->execute($params);
        $stores = $stmt->fetchAll();
        
        // Contar total
        $countStmt = $this->db->prepare("
            SELECT COUNT(*) FROM lojas {$whereClause}
        ");
        
        $countStmt->execute(array_slice($params, 0, -2));
        $total = $countStmt->fetchColumn();
        
        return [
            'stores' => $stores,
            'total' => intval($total)
        ];
    }
    
    public function getStoreStats($storeId, $days = 30) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_transactions,
                COALESCE(SUM(valor_total), 0) as total_sales,
                COALESCE(SUM(valor_cashback), 0) as total_cashback,
                COALESCE(SUM(valor_loja), 0) as store_commission,
                COUNT(DISTINCT usuario_id) as unique_customers,
                AVG(valor_total) as average_sale
            FROM transacoes_cashback
            WHERE loja_id = ? AND data_transacao >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        
        $stmt->execute([$storeId, $days]);
        $stats = $stmt->fetch();
        
        // Estatísticas por status
        $statusStmt = $this->db->prepare("
            SELECT 
                status,
                COUNT(*) as count,
                COALESCE(SUM(valor_total), 0) as total_amount
            FROM transacoes_cashback
            WHERE loja_id = ? AND data_transacao >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY status
        ");
        
        $statusStmt->execute([$storeId, $days]);
        $statusStats = $statusStmt->fetchAll();
        
        return [
            'period_days' => $days,
            'summary' => [
                'total_transactions' => intval($stats['total_transactions']),
                'total_sales' => floatval($stats['total_sales']),
                'total_cashback' => floatval($stats['total_cashback']),
                'store_commission' => floatval($stats['store_commission']),
                'unique_customers' => intval($stats['unique_customers']),
                'average_sale' => floatval($stats['average_sale'])
            ],
            'by_status' => $statusStats
        ];
    }
    
    public function getStoreTransactions($storeId, $page = 1, $pageSize = 20, $filters = []) {
        $offset = ($page - 1) * $pageSize;
        
        $whereConditions = ['t.loja_id = ?'];
        $params = [$storeId];
        
        if (isset($filters['status'])) {
            $whereConditions[] = 't.status = ?';
            $params[] = $filters['status'];
        }
        
        if (isset($filters['date_from'])) {
            $whereConditions[] = 'DATE(t.data_transacao) >= ?';
            $params[] = $filters['date_from'];
        }
        
        if (isset($filters['date_to'])) {
            $whereConditions[] = 'DATE(t.data_transacao) <= ?';
            $params[] = $filters['date_to'];
        }
        
        if (isset($filters['user_id'])) {
            $whereConditions[] = 't.usuario_id = ?';
            $params[] = $filters['user_id'];
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Query principal
        $stmt = $this->db->prepare("
            SELECT 
                t.id, t.valor_total as total_amount, t.valor_cashback as cashback_amount,
                t.valor_cliente as client_amount, t.valor_loja as store_commission,
                t.data_transacao as transaction_date, t.status,
                u.nome as client_name, u.email as client_email, u.id as client_id
            FROM transacoes_cashback t
            JOIN usuarios u ON t.usuario_id = u.id
            WHERE {$whereClause}
            ORDER BY t.data_transacao DESC
            LIMIT ? OFFSET ?
        ");
        
        $params[] = $pageSize;
        $params[] = $offset;
        $stmt->execute($params);
        $transactions = $stmt->fetchAll();
        
        // Contar total
        $countStmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM transacoes_cashback t 
            WHERE {$whereClause}
        ");
        
        $countStmt->execute(array_slice($params, 0, -2));
        $total = $countStmt->fetchColumn();
        
        return [
            'transactions' => $transactions,
            'total' => intval($total)
        ];
    }
    
    public function getCashbackRules($storeId) {
        $store = $this->getStoreById($storeId);
        
        if (!$store) {
            throw ApiException::notFound('Store not found');
        }
        
        // Buscar configurações globais de cashback
        $configStmt = $this->db->prepare("
            SELECT 
                porcentagem_cliente as client_percentage,
                porcentagem_admin as admin_percentage,
                porcentagem_loja as store_percentage
            FROM configuracoes_cashback
            LIMIT 1
        ");
        
        $configStmt->execute();
        $config = $configStmt->fetch();
        
        return [
            'store_id' => $storeId,
            'store_cashback_percentage' => floatval($store['cashback_percentage']),
            'distribution' => [
                'client_percentage' => floatval($config['client_percentage']),
                'admin_percentage' => floatval($config['admin_percentage']),
                'store_percentage' => floatval($config['store_percentage'])
            ],
            'calculation_example' => $this->calculateCashbackExample($store['cashback_percentage'], $config)
        ];
    }
    
    public function deleteStore($id) {
        $store = $this->getStoreById($id);
        if (!$store) {
            throw ApiException::notFound('Store not found');
        }
        
        // Verificar se tem transações
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM transacoes_cashback WHERE loja_id = ?
        ");
        $stmt->execute([$id]);
        
        if ($stmt->fetchColumn() > 0) {
            // Se tem transações, apenas inativar
            return $this->updateStore($id, ['status' => 'rejeitado']);
        }
        
        // Se não tem transações, deletar definitivamente
        $stmt = $this->db->prepare("DELETE FROM lojas WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    private function calculateCashbackExample($storeCashback, $config) {
        $saleAmount = 100.00; // Valor exemplo
        $totalCashback = ($saleAmount * $storeCashback) / 100;
        
        return [
            'sale_amount' => $saleAmount,
            'total_cashback' => $totalCashback,
            'client_receives' => ($totalCashback * $config['client_percentage']) / 100,
            'admin_receives' => ($totalCashback * $config['admin_percentage']) / 100,
            'store_receives' => ($totalCashback * $config['store_percentage']) / 100
        ];
    }
}
?>