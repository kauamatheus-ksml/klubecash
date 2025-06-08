<?php
// controllers/UserController.php

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../utils/Validator.php';
require_once '../utils/Security.php';

/**
 * Controller específico para gerenciamento de usuários
 * Separado do AdminController para melhor organização e manutenibilidade
 */
class UserController {
    
    /**
     * Lista todos os usuários com filtros e paginação
     */
    public static function listUsers($filters = [], $page = 1, $limit = 20) {
        try {
            $pdo = getConnection();
            
            // Query base
            $query = "SELECT u.id, u.nome, u.email, u.telefone, u.cpf, u.data_criacao, 
                            u.ultimo_login, u.status, u.tipo, u.email_verified, u.two_factor_enabled 
                     FROM usuarios u WHERE 1=1";
            
            $params = [];
            
            // Aplicar filtros
            if (!empty($filters['tipo'])) {
                $query .= " AND u.tipo = :tipo";
                $params[':tipo'] = $filters['tipo'];
            }
            
            if (!empty($filters['status'])) {
                $query .= " AND u.status = :status";
                $params[':status'] = $filters['status'];
            }
            
            if (!empty($filters['busca'])) {
                $query .= " AND (u.nome LIKE :busca OR u.email LIKE :busca OR u.cpf LIKE :busca)";
                $params[':busca'] = '%' . $filters['busca'] . '%';
            }
            
            // Contar total de registros para paginação
            $countQuery = str_replace("SELECT u.id, u.nome, u.email, u.telefone, u.cpf, u.data_criacao, u.ultimo_login, u.status, u.tipo, u.email_verified, u.two_factor_enabled", "SELECT COUNT(*)", $query);
            $countStmt = $pdo->prepare($countQuery);
            $countStmt->execute($params);
            $totalRecords = $countStmt->fetchColumn();
            
            // Adicionar ordenação e paginação
            $query .= " ORDER BY u.data_criacao DESC";
            $offset = ($page - 1) * $limit;
            $query .= " LIMIT :limit OFFSET :offset";
            
            $stmt = $pdo->prepare($query);
            
            // Bind dos parâmetros
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular informações de paginação
            $totalPages = ceil($totalRecords / $limit);
            
            return [
                'status' => true,
                'data' => $users,
                'pagination' => [
                    'currentPage' => $page,
                    'totalPages' => $totalPages,
                    'totalRecords' => $totalRecords,
                    'recordsPerPage' => $limit
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao listar usuários: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Erro interno do servidor ao carregar usuários'
            ];
        }
    }
    
    /**
     * Busca detalhes de um usuário específico
     */
    public static function getUserDetails($userId) {
        try {
            $pdo = getConnection();
            
            $query = "SELECT u.*, 
                            (SELECT COUNT(*) FROM transacoes_cashback t WHERE t.usuario_id = u.id) as total_transacoes,
                            (SELECT COALESCE(SUM(t.valor_cashback), 0) FROM transacoes_cashback t WHERE t.usuario_id = u.id) as total_cashback_recebido
                     FROM usuarios u 
                     WHERE u.id = :id";
            
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return [
                    'status' => false,
                    'message' => 'Usuário não encontrado'
                ];
            }
            
            // Buscar histórico de transações recentes (últimas 10)
            $transactionsQuery = "SELECT t.*, l.nome_fantasia as loja_nome 
                                 FROM transacoes_cashback t 
                                 LEFT JOIN lojas l ON t.loja_id = l.id 
                                 WHERE t.usuario_id = :userId 
                                 ORDER BY t.data_transacao DESC 
                                 LIMIT 10";
            
            $transStmt = $pdo->prepare($transactionsQuery);
            $transStmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $transStmt->execute();
            $recentTransactions = $transStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $user['recent_transactions'] = $recentTransactions;
            
            return [
                'status' => true,
                'data' => $user
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao buscar detalhes do usuário: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Erro interno do servidor'
            ];
        }
    }
    
    /**
     * Cria um novo usuário
     */
    public static function createUser($data) {
        try {
            // Validar dados de entrada
            $validation = self::validateUserData($data);
            if (!$validation['valid']) {
                return [
                    'status' => false,
                    'message' => $validation['message']
                ];
            }
            
            $pdo = getConnection();
            
            // Verificar se email já existe
            $checkEmailQuery = "SELECT id FROM usuarios WHERE email = :email";
            $checkStmt = $pdo->prepare($checkEmailQuery);
            $checkStmt->bindParam(':email', $data['email']);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                return [
                    'status' => false,
                    'message' => 'Este email já está cadastrado no sistema'
                ];
            }
            
            // Hash da senha
            $hashedPassword = password_hash($data['senha'], PASSWORD_DEFAULT);
            
            // Inserir usuário
            $query = "INSERT INTO usuarios (nome, email, telefone, cpf, senha_hash, tipo, status, data_criacao) 
                     VALUES (:nome, :email, :telefone, :cpf, :senha_hash, :tipo, :status, NOW())";
            
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':nome', $data['nome']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':telefone', $data['telefone']);
            $stmt->bindParam(':cpf', $data['cpf']);
            $stmt->bindParam(':senha_hash', $hashedPassword);
            $stmt->bindParam(':tipo', $data['tipo']);
            $stmt->bindValue(':status', 'ativo');
            
            if ($stmt->execute()) {
                $userId = $pdo->lastInsertId();
                
                return [
                    'status' => true,
                    'message' => 'Usuário criado com sucesso',
                    'user_id' => $userId
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'Erro ao criar usuário'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Erro ao criar usuário: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Erro interno do servidor'
            ];
        }
    }
    
    /**
     * Atualiza dados de um usuário
     */
    public static function updateUser($userId, $data) {
        try {
            $pdo = getConnection();
            
            // Verificar se usuário existe
            $checkQuery = "SELECT id, email FROM usuarios WHERE id = :id";
            $checkStmt = $pdo->prepare($checkQuery);
            $checkStmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $checkStmt->execute();
            
            $currentUser = $checkStmt->fetch(PDO::FETCH_ASSOC);
            if (!$currentUser) {
                return [
                    'status' => false,
                    'message' => 'Usuário não encontrado'
                ];
            }
            
            // Preparar campos para atualização
            $updateFields = [];
            $params = [':id' => $userId];
            
            // Campos que podem ser atualizados
            $allowedFields = ['nome', 'telefone', 'cpf', 'tipo', 'status'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "$field = :$field";
                    $params[":$field"] = $data[$field];
                }
            }
            
            // Verificar se email foi alterado e se já existe
            if (isset($data['email']) && $data['email'] !== $currentUser['email']) {
                $checkEmailQuery = "SELECT id FROM usuarios WHERE email = :email AND id != :userId";
                $checkEmailStmt = $pdo->prepare($checkEmailQuery);
                $checkEmailStmt->bindParam(':email', $data['email']);
                $checkEmailStmt->bindParam(':userId', $userId, PDO::PARAM_INT);
                $checkEmailStmt->execute();
                
                if ($checkEmailStmt->rowCount() > 0) {
                    return [
                        'status' => false,
                        'message' => 'Este email já está em uso por outro usuário'
                    ];
                }
                
                $updateFields[] = "email = :email";
                $params[':email'] = $data['email'];
            }
            
            // Atualizar senha se fornecida
            if (isset($data['senha']) && !empty($data['senha'])) {
                $hashedPassword = password_hash($data['senha'], PASSWORD_DEFAULT);
                $updateFields[] = "senha_hash = :senha_hash";
                $params[':senha_hash'] = $hashedPassword;
            }
            
            if (empty($updateFields)) {
                return [
                    'status' => false,
                    'message' => 'Nenhum campo válido para atualização'
                ];
            }
            
            // Executar atualização
            $query = "UPDATE usuarios SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $pdo->prepare($query);
            
            if ($stmt->execute($params)) {
                return [
                    'status' => true,
                    'message' => 'Usuário atualizado com sucesso'
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'Erro ao atualizar usuário'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Erro ao atualizar usuário: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Erro interno do servidor'
            ];
        }
    }
    
    /**
     * Altera status de um usuário (ativar/desativar/bloquear)
     */
    public static function updateUserStatus($userId, $status) {
        try {
            $pdo = getConnection();
            
            // Validar status
            $allowedStatuses = ['ativo', 'inativo', 'bloqueado'];
            if (!in_array($status, $allowedStatuses)) {
                return [
                    'status' => false,
                    'message' => 'Status inválido'
                ];
            }
            
            $query = "UPDATE usuarios SET status = :status WHERE id = :id";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $statusText = [
                    'ativo' => 'ativado',
                    'inativo' => 'desativado',
                    'bloqueado' => 'bloqueado'
                ];
                
                return [
                    'status' => true,
                    'message' => "Usuário {$statusText[$status]} com sucesso"
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'Erro ao alterar status do usuário'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Erro ao alterar status do usuário: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Erro interno do servidor'
            ];
        }
    }
    
    /**
     * Exclui um usuário (soft delete - marca como inativo)
     */
    public static function deleteUser($userId) {
        try {
            $pdo = getConnection();
            
            // Verificar se usuário tem transações associadas
            $checkTransQuery = "SELECT COUNT(*) FROM transacoes_cashback WHERE usuario_id = :userId";
            $checkStmt = $pdo->prepare($checkTransQuery);
            $checkStmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $checkStmt->execute();
            
            $hasTransactions = $checkStmt->fetchColumn() > 0;
            
            if ($hasTransactions) {
                // Soft delete - apenas desativar usuário
                return self::updateUserStatus($userId, 'inativo');
            } else {
                // Hard delete - remover completamente
                $query = "DELETE FROM usuarios WHERE id = :id";
                $stmt = $pdo->prepare($query);
                $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
                
                if ($stmt->execute()) {
                    return [
                        'status' => true,
                        'message' => 'Usuário removido com sucesso'
                    ];
                } else {
                    return [
                        'status' => false,
                        'message' => 'Erro ao remover usuário'
                    ];
                }
            }
            
        } catch (Exception $e) {
            error_log("Erro ao excluir usuário: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Erro interno do servidor'
            ];
        }
    }
    
    /**
     * Valida dados de usuário
     */
    private static function validateUserData($data) {
        // Validar campos obrigatórios
        $requiredFields = ['nome', 'email', 'tipo'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return [
                    'valid' => false,
                    'message' => "O campo {$field} é obrigatório"
                ];
            }
        }
        
        // Validar email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'valid' => false,
                'message' => 'Email inválido'
            ];
        }
        
        // Validar tipo de usuário
        $allowedTypes = [USER_TYPE_CLIENT, USER_TYPE_ADMIN, USER_TYPE_STORE];
        if (!in_array($data['tipo'], $allowedTypes)) {
            return [
                'valid' => false,
                'message' => 'Tipo de usuário inválido'
            ];
        }
        
        // Validar CPF se fornecido
        if (!empty($data['cpf'])) {
            if (!Validator::validateCPF($data['cpf'])) {
                return [
                    'valid' => false,
                    'message' => 'CPF inválido'
                ];
            }
        }
        
        // Validar senha (mínimo 6 caracteres)
        if (isset($data['senha']) && strlen($data['senha']) < 6) {
            return [
                'valid' => false,
                'message' => 'A senha deve ter pelo menos 6 caracteres'
            ];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Obtém estatísticas gerais dos usuários
     */
    public static function getUserStats() {
        try {
            $pdo = getConnection();
            
            $query = "SELECT 
                        COUNT(*) as total_usuarios,
                        SUM(CASE WHEN tipo = 'cliente' THEN 1 ELSE 0 END) as total_clientes,
                        SUM(CASE WHEN tipo = 'loja' THEN 1 ELSE 0 END) as total_lojas,
                        SUM(CASE WHEN tipo = 'admin' THEN 1 ELSE 0 END) as total_admins,
                        SUM(CASE WHEN status = 'ativo' THEN 1 ELSE 0 END) as usuarios_ativos,
                        SUM(CASE WHEN status = 'inativo' THEN 1 ELSE 0 END) as usuarios_inativos,
                        SUM(CASE WHEN status = 'bloqueado' THEN 1 ELSE 0 END) as usuarios_bloqueados,
                        SUM(CASE WHEN DATE(data_criacao) = CURDATE() THEN 1 ELSE 0 END) as novos_hoje,
                        SUM(CASE WHEN DATE(data_criacao) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as novos_semana
                      FROM usuarios";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'status' => true,
                'data' => $stats
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao obter estatísticas dos usuários: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Erro interno do servidor'
            ];
        }
    }
}
?>