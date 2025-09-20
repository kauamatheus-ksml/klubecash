<?php

require_once 'models/ApiTransaction.php';
require_once 'middleware/AuthMiddleware.php';
require_once 'middleware/ValidationMiddleware.php';

class TransactionController {
    private $transactionModel;
    private $authMiddleware;
    
    public function __construct() {
        $this->transactionModel = new ApiTransaction();
        $this->authMiddleware = new AuthMiddleware();
    }
    
    public function createTransaction() {
        $this->authMiddleware->checkPermission('transactions.create');
        
        $data = ValidationMiddleware::validateJsonPayload();
        
        // Validações obrigatórias
        ValidationMiddleware::validateRequired($data, ['user_id', 'store_id', 'total_amount']);
        ValidationMiddleware::validateAmount($data['total_amount'], 'total_amount', 0.01);
        
        if (!is_numeric($data['user_id']) || $data['user_id'] <= 0) {
            Response::validation(['user_id' => 'Invalid user ID']);
        }
        
        if (!is_numeric($data['store_id']) || $data['store_id'] <= 0) {
            Response::validation(['store_id' => 'Invalid store ID']);
        }
        
        // Validações opcionais
        if (isset($data['status'])) {
            ValidationMiddleware::validateEnum($data['status'], ['pendente', 'aprovado'], 'status');
        }
        
        try {
            $transaction = $this->transactionModel->createTransaction($data);
            
            // Log da criação
            $this->logApiAction('transaction.created', [
                'transaction_id' => $transaction['id'],
                'user_id' => $data['user_id'],
                'store_id' => $data['store_id'],
                'amount' => $data['total_amount']
            ]);
            
            Response::created($transaction, 'Transaction created successfully');
            
        } catch (ApiException $e) {
            throw $e;
        } catch (Exception $e) {
            error_log('Error creating transaction: ' . $e->getMessage());
            Response::error('Failed to create transaction', 500);
        }
    }
    
    public function getTransaction($id) {
        $this->authMiddleware->checkPermission('transactions.read');
        
        if (!is_numeric($id) || $id <= 0) {
            Response::error('Invalid transaction ID', 400);
        }
        
        $transaction = $this->transactionModel->getTransactionById($id);
        
        if (!$transaction) {
            Response::notFound('Transaction not found');
        }
        
        Response::success($transaction);
    }
    
    public function updateTransactionStatus($id) {
        $this->authMiddleware->checkPermission('transactions.update');
        
        if (!is_numeric($id) || $id <= 0) {
            Response::error('Invalid transaction ID', 400);
        }
        
        $data = ValidationMiddleware::validateJsonPayload();
        
        ValidationMiddleware::validateRequired($data, ['status']);
        ValidationMiddleware::validateEnum($data['status'], ['pendente', 'aprovado', 'cancelado'], 'status');
        
        $reason = $data['reason'] ?? null;
        
        try {
            $transaction = $this->transactionModel->updateTransactionStatus($id, $data['status'], $reason);
            
            // Log da atualização
            $this->logApiAction('transaction.status_updated', [
                'transaction_id' => $id,
                'old_status' => $transaction['status'],
                'new_status' => $data['status'],
                'reason' => $reason
            ]);
            
            Response::updated($transaction, 'Transaction status updated successfully');
            
        } catch (ApiException $e) {
            throw $e;
        } catch (Exception $e) {
            error_log('Error updating transaction status: ' . $e->getMessage());
            Response::error('Failed to update transaction status', 500);
        }
    }
    
    public function listTransactions() {
        $this->authMiddleware->checkPermission('transactions.list');
        
        // Parâmetros de paginação
        $page = intval($_GET['page'] ?? 1);
        $pageSize = intval($_GET['page_size'] ?? DEFAULT_PAGE_SIZE);
        
        ValidationMiddleware::validatePagination($page, $pageSize);
        
        // Filtros
        $filters = [];
        if (isset($_GET['user_id'])) {
            $filters['user_id'] = intval($_GET['user_id']);
        }
        if (isset($_GET['store_id'])) {
            $filters['store_id'] = intval($_GET['store_id']);
        }
        if (isset($_GET['status'])) {
            ValidationMiddleware::validateEnum($_GET['status'], ['pendente', 'aprovado', 'cancelado'], 'status');
            $filters['status'] = $_GET['status'];
        }
        if (isset($_GET['date_from'])) {
            ValidationMiddleware::validateDate($_GET['date_from'], 'date_from');
            $filters['date_from'] = $_GET['date_from'];
        }
        if (isset($_GET['date_to'])) {
            ValidationMiddleware::validateDate($_GET['date_to'], 'date_to');
            $filters['date_to'] = $_GET['date_to'];
        }
        if (isset($_GET['min_amount'])) {
            ValidationMiddleware::validateAmount($_GET['min_amount'], 'min_amount');
            $filters['min_amount'] = floatval($_GET['min_amount']);
        }
        if (isset($_GET['max_amount'])) {
            ValidationMiddleware::validateAmount($_GET['max_amount'], 'max_amount');
            $filters['max_amount'] = floatval($_GET['max_amount']);
        }
        
        try {
            $result = $this->transactionModel->listTransactions($page, $pageSize, $filters);
            
            Response::paginated(
                $result['transactions'],
                $page,
                $pageSize,
                $result['total'],
                'Transactions retrieved successfully'
            );
            
        } catch (Exception $e) {
            error_log('Error listing transactions: ' . $e->getMessage());
            Response::error('Failed to list transactions', 500);
        }
    }
    
    public function getTransactionStats() {
        $this->authMiddleware->checkPermission('transactions.stats');
        
        $days = intval($_GET['days'] ?? 30);
        
        if ($days < 1 || $days > 365) {
            Response::error('Days must be between 1 and 365', 400);
        }
        
        // Filtros opcionais
        $filters = [];
        if (isset($_GET['store_id'])) {
            $filters['store_id'] = intval($_GET['store_id']);
        }
        if (isset($_GET['user_id'])) {
            $filters['user_id'] = intval($_GET['user_id']);
        }
        
        try {
            $stats = $this->transactionModel->getTransactionStats($days, $filters);
            Response::success($stats);
            
        } catch (Exception $e) {
            error_log('Error getting transaction stats: ' . $e->getMessage());
            Response::error('Failed to get transaction statistics', 500);
        }
    }
    
    private function logApiAction($action, $data = []) {
        if (!API_LOG_ENABLED) {
            return;
        }
        
        try {
            $keyData = AuthMiddleware::getCurrentApiKeyData();
            $logData = [
                'action' => $action,
                'api_key_id' => $keyData['id'] ?? null,
                'partner_name' => $keyData['partner_name'] ?? null,
                'data' => $data,
                'timestamp' => date('c'),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ];
            
            error_log('API_ACTION: ' . json_encode($logData));
            
        } catch (Exception $e) {
            error_log('Failed to log API action: ' . $e->getMessage());
        }
    }
}
?>