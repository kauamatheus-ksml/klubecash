<?php

class ApiUser {
    private $db;
    
    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    public function getUserById($id) {
        $stmt = $this->db->prepare("
            SELECT 
                id, nome as name, email, tipo as type, status, 
                data_criacao as created_at, ultimo_login as last_login
            FROM usuarios 
            WHERE id = ?
        ");
        
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return null;
        }
        
        // Buscar saldo do usuário se for cliente
        if ($user['type'] === 'cliente') {
            $user['balance'] = $this->getUserTotalBalance($id);
        }
        
        return $user;
    }
    
    public function getUserByEmail($email) {
        $stmt = $this->db->prepare("
            SELECT 
                id, nome as name, email, tipo as type, status, 
                data_criacao as created_at, ultimo_login as last_login
            FROM usuarios 
            WHERE email = ?
        ");
        
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return null;
        }
        
        if ($user['type'] === 'cliente') {
            $user['balance'] = $this->getUserTotalBalance($user['id']);
        }
        
        return $user;
    }
    
    public function createUser($data) {
        // Verificar se email já existe
        if ($this->getUserByEmail($data['email'])) {
            throw ApiException::validation('User with this email already exists');
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO usuarios (nome, email, senha_hash, tipo, status) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        $type = $data['type'] ?? 'cliente';
        $status = $data['status'] ?? 'ativo';
        
        if ($stmt->execute([
            $data['name'],
            $data['email'],
            $passwordHash,
            $type,
            $status
        ])) {
            $userId = $this->db->lastInsertId();
            return $this->getUserById($userId);
        }
        
        throw ApiException::serverError('Failed to create user');
    }
    
    public function updateUser($id, $data) {
        $user = $this->getUserById($id);
        if (!$user) {
            throw ApiException::notFound('User not found');
        }
        
        $updateFields = [];
        $params = [];
        
        if (isset($data['name'])) {
            $updateFields[] = "nome = ?";
            $params[] = $data['name'];
        }
        
        if (isset($data['email'])) {
            // Verificar se email já existe para outro usuário
            $existingUser = $this->getUserByEmail($data['email']);
            if ($existingUser && $existingUser['id'] != $id) {
                throw ApiException::validation('Email already in use by another user');
            }
            
            $updateFields[] = "email = ?";
            $params[] = $data['email'];
        }
        
        if (isset($data['status'])) {
            $updateFields[] = "status = ?";
            $params[] = $data['status'];
        }
        
        if (isset($data['password'])) {
            $updateFields[] = "senha_hash = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (empty($updateFields)) {
            return $user;
        }
        
        $params[] = $id;
        
        $stmt = $this->db->prepare("
            UPDATE usuarios SET " . implode(', ', $updateFields) . " WHERE id = ?
        ");
        
        if ($stmt->execute($params)) {
            return $this->getUserById($id);
        }
        
        throw ApiException::serverError('Failed to update user');
    }
    
    public function getUserBalance($userId, $storeId = null) {
        if ($storeId) {
            // Saldo específico de uma loja
            $stmt = $this->db->prepare("
                SELECT 
                    COALESCE(SUM(
                        CASE 
                            WHEN status = 'aprovado' THEN valor_cliente 
                            ELSE 0 
                        END
                    ), 0) as available_balance,
                    COALESCE(SUM(
                        CASE 
                            WHEN status = 'pendente' THEN valor_cliente 
                            ELSE 0 
                        END
                    ), 0) as pending_balance
                FROM transacoes_cashback
                WHERE usuario_id = ? AND loja_id = ?
            ");
            
            $stmt->execute([$userId, $storeId]);
            $result = $stmt->fetch();
            
            return [
                'store_id' => $storeId,
                'available_balance' => floatval($result['available_balance']),
                'pending_balance' => floatval($result['pending_balance']),
                'total_balance' => floatval($result['available_balance'] + $result['pending_balance'])
            ];
        } else {
            // Saldo total de todas as lojas
            return $this->getUserTotalBalance($userId);
        }
    }
    
    public function getUserTotalBalance($userId) {
        $stmt = $this->db->prepare("
            SELECT 
                COALESCE(SUM(
                    CASE 
                        WHEN status = 'aprovado' THEN valor_cliente 
                        ELSE 0 
                    END
                ), 0) as available_balance,
                COALESCE(SUM(
                    CASE 
                        WHEN status = 'pendente' THEN valor_cliente 
                        ELSE 0 
                    END
                ), 0) as pending_balance
            FROM transacoes_cashback
            WHERE usuario_id = ?
        ");
        
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        return [
            'available_balance' => floatval($result['available_balance']),
            'pending_balance' => floatval($result['pending_balance']),
            'total_balance' => floatval($result['available_balance'] + $result['pending_balance'])
        ];
    }
    
    public function getUserTransactions($userId, $page = 1, $pageSize = 20, $filters = []) {
        $offset = ($page - 1) * $pageSize;
        
        $whereConditions = ['t.usuario_id = ?'];
        $params = [$userId];
        
        // Filtros opcionais
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
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Query principal
        $stmt = $this->db->prepare("
            SELECT 
                t.id, t.valor_total as total_amount, t.valor_cashback as cashback_amount,
                t.valor_cliente as client_amount, t.data_transacao as transaction_date,
                t.status, l.nome_fantasia as store_name, l.id as store_id
            FROM transacoes_cashback t
            JOIN lojas l ON t.loja_id = l.id
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
    
    public function listUsers($page = 1, $pageSize = 20, $filters = []) {
        $offset = ($page - 1) * $pageSize;
        
        $whereConditions = [];
        $params = [];
        
        if (isset($filters['type'])) {
            $whereConditions[] = 'tipo = ?';
            $params[] = $filters['type'];
        }
        
        if (isset($filters['status'])) {
            $whereConditions[] = 'status = ?';
            $params[] = $filters['status'];
        }
        
        if (isset($filters['email'])) {
            $whereConditions[] = 'email LIKE ?';
            $params[] = '%' . $filters['email'] . '%';
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Query principal
        $stmt = $this->db->prepare("
            SELECT 
                id, nome as name, email, tipo as type, status,
                data_criacao as created_at, ultimo_login as last_login
            FROM usuarios
            {$whereClause}
            ORDER BY data_criacao DESC
            LIMIT ? OFFSET ?
        ");
        
        $params[] = $pageSize;
        $params[] = $offset;
        $stmt->execute($params);
        $users = $stmt->fetchAll();
        
        // Contar total
        $countStmt = $this->db->prepare("
            SELECT COUNT(*) FROM usuarios {$whereClause}
        ");
        
        $countStmt->execute(array_slice($params, 0, -2));
        $total = $countStmt->fetchColumn();
        
        return [
            'users' => $users,
            'total' => intval($total)
        ];
    }
    
    public function deleteUser($id) {
        // Verificar se usuário existe
        $user = $this->getUserById($id);
        if (!$user) {
            throw ApiException::notFound('User not found');
        }
        
        // Verificar se tem transações
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM transacoes_cashback WHERE usuario_id = ?
        ");
        $stmt->execute([$id]);
        
        if ($stmt->fetchColumn() > 0) {
            // Se tem transações, apenas inativar
            return $this->updateUser($id, ['status' => 'inativo']);
        }
        
        // Se não tem transações, deletar definitivamente
        $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
?>