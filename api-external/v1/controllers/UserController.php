<?php

require_once 'models/ApiUser.php';
require_once 'middleware/AuthMiddleware.php';
require_once 'middleware/ValidationMiddleware.php';

class UserController {
    private $userModel;
    private $authMiddleware;
    
    public function __construct() {
        $this->userModel = new ApiUser();
        $this->authMiddleware = new AuthMiddleware();
    }
    
    public function getUser($id) {
        $this->authMiddleware->checkPermission('users.read');
        
        if (!is_numeric($id) || $id <= 0) {
            Response::error('Invalid user ID', 400);
        }
        
        $user = $this->userModel->getUserById($id);
        
        if (!$user) {
            Response::notFound('User not found');
        }
        
        Response::success($user);
    }
    
    public function getUserByEmail() {
        $this->authMiddleware->checkPermission('users.read');
        
        $email = $_GET['email'] ?? '';
        
        if (empty($email)) {
            Response::error('Email parameter is required', 400);
        }
        
        ValidationMiddleware::validateEmail($email);
        
        $user = $this->userModel->getUserByEmail($email);
        
        if (!$user) {
            Response::notFound('User not found');
        }
        
        Response::success($user);
    }
    
    public function createUser() {
        $this->authMiddleware->checkPermission('users.create');
        
        $data = ValidationMiddleware::validateJsonPayload();
        
        // Validações obrigatórias
        ValidationMiddleware::validateRequired($data, ['name', 'email', 'password']);
        ValidationMiddleware::validateEmail($data['email']);
        
        // Validações opcionais
        if (isset($data['type'])) {
            ValidationMiddleware::validateEnum($data['type'], ['cliente', 'admin', 'loja'], 'type');
        }
        
        if (isset($data['status'])) {
            ValidationMiddleware::validateEnum($data['status'], ['ativo', 'inativo', 'bloqueado'], 'status');
        }
        
        // Validar senha
        if (strlen($data['password']) < 6) {
            Response::validation(['password' => 'Password must be at least 6 characters']);
        }
        
        try {
            $user = $this->userModel->createUser($data);
            
            // Log da criação
            $this->logApiAction('user.created', ['user_id' => $user['id']]);
            
            Response::created($user, 'User created successfully');
            
        } catch (ApiException $e) {
            throw $e;
        } catch (Exception $e) {
            error_log('Error creating user: ' . $e->getMessage());
            Response::error('Failed to create user', 500);
        }
    }
    
    public function updateUser($id) {
        $this->authMiddleware->checkPermission('users.update');
        
        if (!is_numeric($id) || $id <= 0) {
            Response::error('Invalid user ID', 400);
        }
        
        $data = ValidationMiddleware::validateJsonPayload();
        
        // Validações opcionais
        if (isset($data['email'])) {
            ValidationMiddleware::validateEmail($data['email']);
        }
        
        if (isset($data['type'])) {
            ValidationMiddleware::validateEnum($data['type'], ['cliente', 'admin', 'loja'], 'type');
        }
        
        if (isset($data['status'])) {
            ValidationMiddleware::validateEnum($data['status'], ['ativo', 'inativo', 'bloqueado'], 'status');
        }
        
        if (isset($data['password']) && strlen($data['password']) < 6) {
            Response::validation(['password' => 'Password must be at least 6 characters']);
        }
        
        try {
            $user = $this->userModel->updateUser($id, $data);
            
            // Log da atualização
            $this->logApiAction('user.updated', ['user_id' => $id, 'updated_fields' => array_keys($data)]);
            
            Response::updated($user, 'User updated successfully');
            
        } catch (ApiException $e) {
            throw $e;
        } catch (Exception $e) {
            error_log('Error updating user: ' . $e->getMessage());
            Response::error('Failed to update user', 500);
        }
    }
    
    public function deleteUser($id) {
        $this->authMiddleware->checkPermission('users.delete');
        
        if (!is_numeric($id) || $id <= 0) {
            Response::error('Invalid user ID', 400);
        }
        
        try {
            $result = $this->userModel->deleteUser($id);
            
            if ($result) {
                // Log da exclusão
                $this->logApiAction('user.deleted', ['user_id' => $id]);
                
                Response::deleted('User deleted successfully');
            } else {
                Response::error('Failed to delete user', 500);
            }
            
        } catch (ApiException $e) {
            throw $e;
        } catch (Exception $e) {
            error_log('Error deleting user: ' . $e->getMessage());
            Response::error('Failed to delete user', 500);
        }
    }
    
    public function getUserBalance($id) {
        $this->authMiddleware->checkPermission('users.balance.read');
        
        if (!is_numeric($id) || $id <= 0) {
            Response::error('Invalid user ID', 400);
        }
        
        $storeId = $_GET['store_id'] ?? null;
        
        if ($storeId && (!is_numeric($storeId) || $storeId <= 0)) {
            Response::error('Invalid store ID', 400);
        }
        
        try {
            $balance = $this->userModel->getUserBalance($id, $storeId);
            Response::success($balance);
            
        } catch (Exception $e) {
            error_log('Error getting user balance: ' . $e->getMessage());
            Response::error('Failed to get user balance', 500);
        }
    }
    
    public function getUserTransactions($id) {
        $this->authMiddleware->checkPermission('users.transactions.read');
        
        if (!is_numeric($id) || $id <= 0) {
            Response::error('Invalid user ID', 400);
        }
        
        // Parâmetros de paginação
        $page = intval($_GET['page'] ?? 1);
        $pageSize = intval($_GET['page_size'] ?? DEFAULT_PAGE_SIZE);
        
        ValidationMiddleware::validatePagination($page, $pageSize);
        
        // Filtros
        $filters = [];
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
        
        try {
            $result = $this->userModel->getUserTransactions($id, $page, $pageSize, $filters);
            
            Response::paginated(
                $result['transactions'],
                $page,
                $pageSize,
                $result['total'],
                'Transactions retrieved successfully'
            );
            
        } catch (Exception $e) {
            error_log('Error getting user transactions: ' . $e->getMessage());
            Response::error('Failed to get user transactions', 500);
        }
    }
    
    public function listUsers() {
        $this->authMiddleware->checkPermission('users.list');
        
        // Parâmetros de paginação
        $page = intval($_GET['page'] ?? 1);
        $pageSize = intval($_GET['page_size'] ?? DEFAULT_PAGE_SIZE);
        
        ValidationMiddleware::validatePagination($page, $pageSize);
        
        // Filtros
        $filters = [];
        if (isset($_GET['type'])) {
            ValidationMiddleware::validateEnum($_GET['type'], ['cliente', 'admin', 'loja'], 'type');
            $filters['type'] = $_GET['type'];
        }
        if (isset($_GET['status'])) {
            ValidationMiddleware::validateEnum($_GET['status'], ['ativo', 'inativo', 'bloqueado'], 'status');
            $filters['status'] = $_GET['status'];
        }
        if (isset($_GET['email'])) {
            $filters['email'] = $_GET['email'];
        }
        
        try {
            $result = $this->userModel->listUsers($page, $pageSize, $filters);
            
            Response::paginated(
                $result['users'],
                $page,
                $pageSize,
                $result['total'],
                'Users retrieved successfully'
            );
            
        } catch (Exception $e) {
            error_log('Error listing users: ' . $e->getMessage());
            Response::error('Failed to list users', 500);
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
            // Se falhar o log, apenas continua
            error_log('Failed to log API action: ' . $e->getMessage());
        }
    }
}
?>