<?php

class ApiTransaction {
    private $db;
    
    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    public function createTransaction($data) {
        // Verificar se usuário e loja existem
        $userStmt = $this->db->prepare("SELECT id, tipo FROM usuarios WHERE id = ? AND status = 'ativo'");
        $userStmt->execute([$data['user_id']]);
        $user = $userStmt->fetch();
        
        if (!$user) {
            throw ApiException::validation('User not found or inactive');
        }
        
        if ($user['tipo'] !== 'cliente') {
            throw ApiException::validation('Only clients can have cashback transactions');
        }
        
        $storeStmt = $this->db->prepare("SELECT id, porcentagem_cashback FROM lojas WHERE id = ? AND status = 'aprovado'");
        $storeStmt->execute([$data['store_id']]);
        $store = $storeStmt->fetch();
        
        if (!$store) {
            throw ApiException::validation('Store not found or not approved');
        }
        
        // Calcular cashback
        $totalAmount = $data['total_amount'];
        $cashbackPercentage = $store['porcentagem_cashback'];
        $totalCashback = ($totalAmount * $cashbackPercentage) / 100;
        
        // Buscar configurações de distribuição
        $configStmt = $this->db->prepare("
            SELECT porcentagem_cliente, porcentagem_admin, porcentagem_loja 
            FROM configuracoes_cashback LIMIT 1
        ");
        $configStmt->execute();
        $config = $configStmt->fetch();
        
        if (!$config) {
            throw ApiException::serverError('Cashback configuration not found');
        }
        
        // Calcular distribuição
        $clientAmount = ($totalCashback * $config['porcentagem_cliente']) / 100;
        $adminAmount = ($totalCashback * $config['porcentagem_admin']) / 100;
        $storeAmount = ($totalCashback * $config['porcentagem_loja']) / 100;
        
        // Criar transação
        $stmt = $this->db->prepare("
            INSERT INTO transacoes_cashback (
                usuario_id, loja_id, valor_total, valor_cashback,
                valor_cliente, valor_admin, valor_loja, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $status = $data['status'] ?? 'pendente';
        
        if ($stmt->execute([
            $data['user_id'],
            $data['store_id'],
            $totalAmount,
            $totalCashback,
            $clientAmount,
            $adminAmount,
            $storeAmount,
            $status
        ])) {
            $transactionId = $this->db->lastInsertId();

            // === INTEGRAÇÃO WHATSAPP: Notificação automática de nova transação ===
            try {
                $triggerPath = __DIR__ . '/../../../utils/NotificationTrigger.php';
                if (file_exists($triggerPath)) {
                    require_once $triggerPath;

                    if (class_exists('NotificationTrigger')) {
                        $notificationResult = NotificationTrigger::send($transactionId);

                        // Log do resultado para debug
                        error_log("[API-EXTERNAL] Notificação enviada para transação {$transactionId}: " .
                                 ($notificationResult['success'] ? 'SUCESSO' : 'FALHA'));
                    }
                }
            } catch (Exception $e) {
                // Não afetar a criação da transação se notificação falhar
                error_log("[API-EXTERNAL] Erro ao enviar notificação para transação {$transactionId}: " . $e->getMessage());
            }

            return $this->getTransactionById($transactionId);
        }
        
        throw ApiException::serverError('Failed to create transaction');
    }
    
    public function getTransactionById($id) {
        $stmt = $this->db->prepare("
            SELECT 
                t.id, t.usuario_id as user_id, t.loja_id as store_id,
                t.valor_total as total_amount, t.valor_cashback as cashback_amount,
                t.valor_cliente as client_amount, t.valor_admin as admin_amount,
                t.valor_loja as store_amount, t.data_transacao as transaction_date,
                t.status,
                u.nome as client_name, u.email as client_email,
                l.nome_fantasia as store_name, l.porcentagem_cashback as store_cashback_percentage
            FROM transacoes_cashback t
            JOIN usuarios u ON t.usuario_id = u.id
            JOIN lojas l ON t.loja_id = l.id
            WHERE t.id = ?
        ");
        
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function updateTransactionStatus($id, $status, $reason = null) {
        $transaction = $this->getTransactionById($id);
        
        if (!$transaction) {
            throw ApiException::notFound('Transaction not found');
        }
        
        // Validar transição de status
        $allowedTransitions = [
            'pendente' => ['aprovado', 'cancelado'],
            'aprovado' => ['cancelado'],
            'cancelado' => []
        ];
        
        $currentStatus = $transaction['status'];
        
        if (!in_array($status, $allowedTransitions[$currentStatus])) {
            throw ApiException::validation("Cannot change status from {$currentStatus} to {$status}");
        }
        
        $updateFields = ['status = ?'];
        $params = [$status];
        
        if ($reason) {
            // Adicionar campo de observação se necessário (seria preciso criar na tabela)
            // Por enquanto vamos apenas logar
            error_log("Transaction {$id} status changed to {$status}. Reason: {$reason}");
        }
        
        $params[] = $id;
        
        $stmt = $this->db->prepare("
            UPDATE transacoes_cashback SET " . implode(', ', $updateFields) . " WHERE id = ?
        ");
        
        if ($stmt->execute($params)) {
            return $this->getTransactionById($id);
        }
        
        throw ApiException::serverError('Failed to update transaction status');
    }
    
    public function listTransactions($page = 1, $pageSize = 20, $filters = []) {
        $offset = ($page - 1) * $pageSize;
        
        $whereConditions = [];
        $params = [];
        
        if (isset($filters['user_id'])) {
            $whereConditions[] = 't.usuario_id = ?';
            $params[] = $filters['user_id'];
        }
        
        if (isset($filters['store_id'])) {
            $whereConditions[] = 't.loja_id = ?';
            $params[] = $filters['store_id'];
        }
        
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
        
        if (isset($filters['min_amount'])) {
            $whereConditions[] = 't.valor_total >= ?';
            $params[] = $filters['min_amount'];
        }
        
        if (isset($filters['max_amount'])) {
            $whereConditions[] = 't.valor_total <= ?';
            $params[] = $filters['max_amount'];
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Query principal
        $stmt = $this->db->prepare("
            SELECT 
                t.id, t.usuario_id as user_id, t.loja_id as store_id,
                t.valor_total as total_amount, t.valor_cashback as cashback_amount,
                t.valor_cliente as client_amount, t.valor_admin as admin_amount,
                t.valor_loja as store_amount, t.data_transacao as transaction_date,
                t.status,
                u.nome as client_name, u.email as client_email,
                l.nome_fantasia as store_name
            FROM transacoes_cashback t
            JOIN usuarios u ON t.usuario_id = u.id
            JOIN lojas l ON t.loja_id = l.id
            {$whereClause}
            ORDER BY t.data_transacao DESC
            LIMIT ? OFFSET ?
        ");
        
        $params[] = $pageSize;
        $params[] = $offset;
        $stmt->execute($params);
        $transactions = $stmt->fetchAll();
        
        // Contar total
        $countStmt = $this->db->prepare("
            SELECT COUNT(*) FROM transacoes_cashback t {$whereClause}
        ");
        
        $countStmt->execute(array_slice($params, 0, -2));
        $total = $countStmt->fetchColumn();
        
        return [
            'transactions' => $transactions,
            'total' => intval($total)
        ];
    }
    
    public function getTransactionStats($days = 30, $filters = []) {
        $whereConditions = ['t.data_transacao >= DATE_SUB(NOW(), INTERVAL ? DAY)'];
        $params = [$days];
        
        if (isset($filters['store_id'])) {
            $whereConditions[] = 't.loja_id = ?';
            $params[] = $filters['store_id'];
        }
        
        if (isset($filters['user_id'])) {
            $whereConditions[] = 't.usuario_id = ?';
            $params[] = $filters['user_id'];
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Estatísticas gerais
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_transactions,
                COALESCE(SUM(valor_total), 0) as total_sales,
                COALESCE(SUM(valor_cashback), 0) as total_cashback,
                COALESCE(AVG(valor_total), 0) as average_sale,
                COUNT(DISTINCT usuario_id) as unique_customers,
                COUNT(DISTINCT loja_id) as unique_stores
            FROM transacoes_cashback t
            WHERE {$whereClause}
        ");
        
        $stmt->execute($params);
        $generalStats = $stmt->fetch();
        
        // Estatísticas por status
        $statusStmt = $this->db->prepare("
            SELECT 
                status,
                COUNT(*) as count,
                COALESCE(SUM(valor_total), 0) as total_amount,
                COALESCE(SUM(valor_cashback), 0) as total_cashback
            FROM transacoes_cashback t
            WHERE {$whereClause}
            GROUP BY status
        ");
        
        $statusStmt->execute($params);
        $statusStats = $statusStmt->fetchAll();
        
        return [
            'period_days' => $days,
            'summary' => [
                'total_transactions' => intval($generalStats['total_transactions']),
                'total_sales' => floatval($generalStats['total_sales']),
                'total_cashback' => floatval($generalStats['total_cashback']),
                'average_sale' => floatval($generalStats['average_sale']),
                'unique_customers' => intval($generalStats['unique_customers']),
                'unique_stores' => intval($generalStats['unique_stores'])
            ],
            'by_status' => $statusStats
        ];
    }
}
?>