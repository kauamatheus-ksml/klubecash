<?php

require_once 'models/ApiStore.php';
require_once 'middleware/AuthMiddleware.php';
require_once 'middleware/ValidationMiddleware.php';

class StoreController {
    private $storeModel;
    private $authMiddleware;
    
    public function __construct() {
        $this->storeModel = new ApiStore();
        $this->authMiddleware = new AuthMiddleware();
    }
    
    public function getStore($id) {
        $this->authMiddleware->checkPermission('stores.read');
        
        if (!is_numeric($id) || $id <= 0) {
            Response::error('Invalid store ID', 400);
        }
        
        $store = $this->storeModel->getStoreById($id);
        
        if (!$store) {
            Response::notFound('Store not found');
        }
        
        Response::success($store);
    }
    
    public function getStoreByCNPJ() {
        $this->authMiddleware->checkPermission('stores.read');
        
        $cnpj = $_GET['cnpj'] ?? '';
        
        if (empty($cnpj)) {
            Response::error('CNPJ parameter is required', 400);
        }
        
        ValidationMiddleware::validateCNPJ($cnpj);
        
        $store = $this->storeModel->getStoreByCNPJ($cnpj);
        
        if (!$store) {
            Response::notFound('Store not found');
        }
        
        Response::success($store);
    }
    
    public function createStore() {
        $this->authMiddleware->checkPermission('stores.create');
        
        $data = ValidationMiddleware::validateJsonPayload();
        
        // Validações obrigatórias
        ValidationMiddleware::validateRequired($data, [
            'trade_name', 'legal_name', 'cnpj', 'email', 'phone'
        ]);
        
        ValidationMiddleware::validateEmail($data['email']);
        ValidationMiddleware::validateCNPJ($data['cnpj']);
        ValidationMiddleware::validatePhone($data['phone']);
        
        // Validações opcionais
        if (isset($data['cashback_percentage'])) {
            ValidationMiddleware::validatePercentage($data['cashback_percentage'], 'cashback_percentage');
        }
        
        if (isset($data['status'])) {
            ValidationMiddleware::validateEnum($data['status'], ['pendente', 'aprovado', 'rejeitado'], 'status');
        }
        
        try {
            $store = $this->storeModel->createStore($data);
            
            // Log da criação
            $this->logApiAction('store.created', ['store_id' => $store['id']]);
            
            Response::created($store, 'Store created successfully');
            
        } catch (ApiException $e) {
            throw $e;
        } catch (Exception $e) {
            error_log('Error creating store: ' . $e->getMessage());
            Response::error('Failed to create store', 500);
        }
    }
    
    public function updateStore($id) {
        $this->authMiddleware->checkPermission('stores.update');
        
        if (!is_numeric($id) || $id <= 0) {
            Response::error('Invalid store ID', 400);
        }
        
        $data = ValidationMiddleware::validateJsonPayload();
        
        // Validações opcionais
        if (isset($data['email'])) {
            ValidationMiddleware::validateEmail($data['email']);
        }
        
        if (isset($data['cnpj'])) {
            ValidationMiddleware::validateCNPJ($data['cnpj']);
        }
        
        if (isset($data['phone'])) {
            ValidationMiddleware::validatePhone($data['phone']);
        }
        
        if (isset($data['cashback_percentage'])) {
            ValidationMiddleware::validatePercentage($data['cashback_percentage'], 'cashback_percentage');
        }
        
        if (isset($data['status'])) {
            ValidationMiddleware::validateEnum($data['status'], ['pendente', 'aprovado', 'rejeitado'], 'status');
        }
        
        try {
            $store = $this->storeModel->updateStore($id, $data);
            
            // Log da atualização
            $this->logApiAction('store.updated', [
                'store_id' => $id, 
                'updated_fields' => array_keys($data)
            ]);
            
            Response::updated($store, 'Store updated successfully');
            
        } catch (ApiException $e) {
            throw $e;
        } catch (Exception $e) {
            error_log('Error updating store: ' . $e->getMessage());
            Response::error('Failed to update store', 500);
        }
    }
    
    public function deleteStore($id) {
        $this->authMiddleware->checkPermission('stores.delete');
        
        if (!is_numeric($id) || $id <= 0) {
            Response::error('Invalid store ID', 400);
        }
        
        try {
            $result = $this->storeModel->deleteStore($id);
            
            if ($result) {
                // Log da exclusão
                $this->logApiAction('store.deleted', ['store_id' => $id]);
                
                Response::deleted('Store deleted successfully');
            } else {
                Response::error('Failed to delete store', 500);
            }
            
        } catch (ApiException $e) {
            throw $e;
        } catch (Exception $e) {
            error_log('Error deleting store: ' . $e->getMessage());
            Response::error('Failed to delete store', 500);
        }
    }
    
    public function listStores() {
        $this->authMiddleware->checkPermission('stores.list');
        
        // Parâmetros de paginação
        $page = intval($_GET['page'] ?? 1);
        $pageSize = intval($_GET['page_size'] ?? DEFAULT_PAGE_SIZE);
        
        ValidationMiddleware::validatePagination($page, $pageSize);
        
        // Filtros
        $filters = [];
        if (isset($_GET['status'])) {
            ValidationMiddleware::validateEnum($_GET['status'], ['pendente', 'aprovado', 'rejeitado'], 'status');
            $filters['status'] = $_GET['status'];
        }
        if (isset($_GET['trade_name'])) {
            $filters['trade_name'] = $_GET['trade_name'];
        }
        if (isset($_GET['cnpj'])) {
            $filters['cnpj'] = $_GET['cnpj'];
        }
        if (isset($_GET['min_cashback'])) {
            ValidationMiddleware::validatePercentage($_GET['min_cashback'], 'min_cashback');
            $filters['min_cashback'] = floatval($_GET['min_cashback']);
        }
        if (isset($_GET['max_cashback'])) {
            ValidationMiddleware::validatePercentage($_GET['max_cashback'], 'max_cashback');
            $filters['max_cashback'] = floatval($_GET['max_cashback']);
        }
        
        try {
            $result = $this->storeModel->listStores($page, $pageSize, $filters);
            
            Response::paginated(
                $result['stores'],
                $page,
                $pageSize,
                $result['total'],
                'Stores retrieved successfully'
            );
            
        } catch (Exception $e) {
            error_log('Error listing stores: ' . $e->getMessage());
            Response::error('Failed to list stores', 500);
        }
    }
    
    public function getStoreStats($id) {
        $this->authMiddleware->checkPermission('stores.stats');
        
        if (!is_numeric($id) || $id <= 0) {
            Response::error('Invalid store ID', 400);
        }
        
        $days = intval($_GET['days'] ?? 30);
        
        if ($days < 1 || $days > 365) {
            Response::error('Days must be between 1 and 365', 400);
        }
        
        try {
            $stats = $this->storeModel->getStoreStats($id, $days);
            Response::success($stats);
            
        } catch (Exception $e) {
            error_log('Error getting store stats: ' . $e->getMessage());
            Response::error('Failed to get store statistics', 500);
        }
    }
    
    public function getStoreTransactions($id) {
        $this->authMiddleware->checkPermission('stores.transactions.read');
        
        if (!is_numeric($id) || $id <= 0) {
            Response::error('Invalid store ID', 400);
        }
        
        // Parâmetros de paginação
        $page = intval($_GET['page'] ?? 1);
        $pageSize = intval($_GET['page_size'] ?? DEFAULT_PAGE_SIZE);
        
        ValidationMiddleware::validatePagination($page, $pageSize);
        
        // Filtros
        $filters = [];
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
        if (isset($_GET['user_id'])) {
            $filters['user_id'] = intval($_GET['user_id']);
        }
        
        try {
            $result = $this->storeModel->getStoreTransactions($id, $page, $pageSize, $filters);
            
            Response::paginated(
                $result['transactions'],
                $page,
                $pageSize,
                $result['total'],
                'Store transactions retrieved successfully'
            );
            
        } catch (Exception $e) {
            error_log('Error getting store transactions: ' . $e->getMessage());
            Response::error('Failed to get store transactions', 500);
        }
    }
    
    public function getCashbackRules($id) {
        $this->authMiddleware->checkPermission('stores.cashback.read');
        
        if (!is_numeric($id) || $id <= 0) {
            Response::error('Invalid store ID', 400);
        }
        
        try {
            $rules = $this->storeModel->getCashbackRules($id);
            Response::success($rules);
            
        } catch (ApiException $e) {
            throw $e;
        } catch (Exception $e) {
            error_log('Error getting cashback rules: ' . $e->getMessage());
            Response::error('Failed to get cashback rules', 500);
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