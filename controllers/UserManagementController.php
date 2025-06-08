<?php
// controllers/UserManagementController.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/Logger.php';

class UserManagementController {
    private static $conn;
    
    /**
     * Inicializar conexão
     */
    private static function init() {
        global $conn;
        self::$conn = $conn;
    }
    
    /**
     * Listar usuários com filtros e paginação
     */
    public static function listUsers($filters = [], $page = 1, $limit = 20) {
        self::init();
        
        try {
            // Construir query base
            $sql = "SELECT u.*, 
                    COUNT(DISTINCT t.id) as total_transacoes,
                    COALESCE(SUM(t.valor_cashback_cliente), 0) as total_cashback
                    FROM usuarios u
                    LEFT JOIN transacoes t ON u.id = t.usuario_id
                    WHERE 1=1";
            
            $params = [];
            $types = "";
            
            // Aplicar filtros
            if (!empty($filters['tipo']) && $filters['tipo'] !== 'todos') {
                $sql .= " AND u.tipo = ?";
                $params[] = $filters['tipo'];
                $types .= "s";
            }
            
            if (!empty($filters['status']) && $filters['status'] !== 'todos') {
                $sql .= " AND u.status = ?";
                $params[] = $filters['status'];
                $types .= "s";
            }
            
            if (!empty($filters['busca'])) {
                $sql .= " AND (u.nome LIKE ? OR u.email LIKE ? OR u.telefone LIKE ?)";
                $busca = "%{$filters['busca']}%";
                $params[] = $busca;
                $params[] = $busca;
                $params[] = $busca;
                $types .= "sss";
            }
            
            $sql .= " GROUP BY u.id";
            
            // Contar total de registros
            $countSql = "SELECT COUNT(DISTINCT u.id) as total FROM usuarios u WHERE 1=1";
            if (!empty($filters['tipo']) && $filters['tipo'] !== 'todos') {
                $countSql .= " AND u.tipo = '{$filters['tipo']}'";
            }
            if (!empty($filters['status']) && $filters['status'] !== 'todos') {
                $countSql .= " AND u.status = '{$filters['status']}'";
            }
            if (!empty($filters['busca'])) {
                $countSql .= " AND (u.nome LIKE '%{$filters['busca']}%' OR u.email LIKE '%{$filters['busca']}%')";
            }
            
            $countResult = self::$conn->query($countSql);
            $totalRecords = $countResult->fetch_assoc()['total'];
            
            // Aplicar paginação
            $offset = ($page - 1) * $limit;
            $sql .= " ORDER BY u.data_criacao DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= "ii";
            
            // Executar query
            $stmt = self::$conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            
            // Calcular estatísticas
            $stats = self::getUserStatistics();
            
            return [
                'status' => true,
                'data' => [
                    'usuarios' => $users,
                    'total' => $totalRecords,
                    'pagina_atual' => $page,
                    'total_paginas' => ceil($totalRecords / $limit),
                    'estatisticas' => $stats
                ]
            ];
            
        } catch (Exception $e) {
            Logger::error("Erro ao listar usuários: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Erro ao buscar usuários'
            ];
        }
    }
    
    /**
     * Buscar usuário por ID
     */
    public static function getUserById($id) {
        self::init();
        
        try {
            $sql = "SELECT u.*, 
                    COUNT(DISTINCT t.id) as total_transacoes,
                    COALESCE(SUM(t.valor_cashback_cliente), 0) as total_cashback,
                    COALESCE(SUM(CASE WHEN t.status = 'aprovado' THEN t.valor_cashback_cliente ELSE 0 END), 0) as cashback_disponivel
                    FROM usuarios u
                    LEFT JOIN transacoes t ON u.id = t.usuario_id
                    WHERE u.id = ?
                    GROUP BY u.id";
            
            $stmt = self::$conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($user = $result->fetch_assoc()) {
                return [
                    'status' => true,
                    'data' => $user
                ];
            }
            
            return [
                'status' => false,
                'message' => 'Usuário não encontrado'
            ];
            
        } catch (Exception $e) {
            Logger::error("Erro ao buscar usuário: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Erro ao buscar usuário'
            ];
        }
    }
    
    /**
     * Criar novo usuário
     */
    public static function createUser($data) {
        self::init();
        
        try {
            // Validar dados
            $validation = self::validateUserData($data);
            if (!$validation['status']) {
                return $validation;
            }
            
            // Verificar se email já existe
            $checkSql = "SELECT id FROM usuarios WHERE email = ?";
            $checkStmt = self::$conn->prepare($checkSql);
            $checkStmt->bind_param("s", $data['email']);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                return [
                    'status' => false,
                    'message' => 'Este email já está cadastrado'
                ];
            }
            
            // Hash da senha
            $senha_hash = password_hash($data['senha'], PASSWORD_DEFAULT);
            
            // Inserir usuário
            $sql = "INSERT INTO usuarios (nome, email, telefone, senha_hash, tipo, status, data_criacao) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = self::$conn->prepare($sql);
            $stmt->bind_param("ssssss", 
                $data['nome'], 
                $data['email'], 
                $data['telefone'], 
                $senha_hash, 
                $data['tipo'], 
                $data['status']
            );
            
            if ($stmt->execute()) {
                Logger::info("Novo usuário criado: " . $data['email']);
                return [
                    'status' => true,
                    'message' => 'Usuário criado com sucesso',
                    'data' => ['id' => self::$conn->insert_id]
                ];
            }
            
            throw new Exception("Erro ao executar query");
            
        } catch (Exception $e) {
            Logger::error("Erro ao criar usuário: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Erro ao criar usuário'
            ];
        }
    }
    
    /**
     * Atualizar usuário
     */
    public static function updateUser($id, $data) {
        self::init();
        
        try {
            // Validar dados (exceto senha se não foi fornecida)
            $validation = self::validateUserData($data, !empty($data['senha']));
            if (!$validation['status']) {
                return $validation;
            }
            
            // Verificar se email já existe (exceto para o próprio usuário)
            if (!empty($data['email'])) {
                $checkSql = "SELECT id FROM usuarios WHERE email = ? AND id != ?";
                $checkStmt = self::$conn->prepare($checkSql);
                $checkStmt->bind_param("si", $data['email'], $id);
                $checkStmt->execute();
                if ($checkStmt->get_result()->num_rows > 0) {
                    return [
                        'status' => false,
                        'message' => 'Este email já está cadastrado'
                    ];
                }
            }
            
            // Construir query de atualização
            $updateFields = [];
            $params = [];
            $types = "";
            
            if (!empty($data['nome'])) {
                $updateFields[] = "nome = ?";
                $params[] = $data['nome'];
                $types .= "s";
            }
            
            if (!empty($data['email'])) {
                $updateFields[] = "email = ?";
                $params[] = $data['email'];
                $types .= "s";
            }
            
            if (!empty($data['telefone'])) {
                $updateFields[] = "telefone = ?";
                $params[] = $data['telefone'];
                $types .= "s";
            }
            
            if (!empty($data['senha'])) {
                $updateFields[] = "senha_hash = ?";
                $params[] = password_hash($data['senha'], PASSWORD_DEFAULT);
                $types .= "s";
            }
            
            if (!empty($data['tipo'])) {
                $updateFields[] = "tipo = ?";
                $params[] = $data['tipo'];
                $types .= "s";
            }
            
            if (!empty($data['status'])) {
                $updateFields[] = "status = ?";
                $params[] = $data['status'];
                $types .= "s";
            }
            
            if (empty($updateFields)) {
                return [
                    'status' => false,
                    'message' => 'Nenhum dado para atualizar'
                ];
            }
            
            $sql = "UPDATE usuarios SET " . implode(", ", $updateFields) . " WHERE id = ?";
            $params[] = $id;
            $types .= "i";
            
            $stmt = self::$conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                Logger::info("Usuário atualizado: ID $id");
                return [
                    'status' => true,
                    'message' => 'Usuário atualizado com sucesso'
                ];
            }
            
            throw new Exception("Erro ao executar query");
            
        } catch (Exception $e) {
            Logger::error("Erro ao atualizar usuário: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Erro ao atualizar usuário'
            ];
        }
    }
    
    /**
     * Excluir usuário
     */
    public static function deleteUser($id) {
        self::init();
        
        try {
            // Verificar se usuário tem transações
            $checkSql = "SELECT COUNT(*) as total FROM transacoes WHERE usuario_id = ?";
            $checkStmt = self::$conn->prepare($checkSql);
            $checkStmt->bind_param("i", $id);
            $checkStmt->execute();
            $result = $checkStmt->get_result()->fetch_assoc();
            
            if ($result['total'] > 0) {
                return [
                    'status' => false,
                    'message' => 'Não é possível excluir usuário com transações. Considere desativá-lo.'
                ];
            }
            
            // Excluir usuário
            $sql = "DELETE FROM usuarios WHERE id = ?";
            $stmt = self::$conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                Logger::info("Usuário excluído: ID $id");
                return [
                    'status' => true,
                    'message' => 'Usuário excluído com sucesso'
                ];
            }
            
            throw new Exception("Erro ao executar query");
            
        } catch (Exception $e) {
            Logger::error("Erro ao excluir usuário: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Erro ao excluir usuário'
            ];
        }
    }
    
    /**
     * Obter estatísticas de usuários
     */
    private static function getUserStatistics() {
        try {
            $stats = [
                'total' => 0,
                'ativos' => 0,
                'inativos' => 0,
                'bloqueados' => 0,
                'clientes' => 0,
                'lojas' => 0,
                'admins' => 0
            ];
            
            // Total por status
            $sql = "SELECT status, COUNT(*) as total FROM usuarios GROUP BY status";
            $result = self::$conn->query($sql);
            while ($row = $result->fetch_assoc()) {
                $stats[$row['status'] === 'ativo' ? 'ativos' : 
                       ($row['status'] === 'inativo' ? 'inativos' : 'bloqueados')] = $row['total'];
            }
            
            // Total por tipo
            $sql = "SELECT tipo, COUNT(*) as total FROM usuarios GROUP BY tipo";
            $result = self::$conn->query($sql);
            while ($row = $result->fetch_assoc()) {
                $stats[$row['tipo'] === 'cliente' ? 'clientes' : 
                       ($row['tipo'] === 'loja' ? 'lojas' : 'admins')] = $row['total'];
            }
            
            // Total geral
            $sql = "SELECT COUNT(*) as total FROM usuarios";
            $result = self::$conn->query($sql);
            $stats['total'] = $result->fetch_assoc()['total'];
            
            return $stats;
            
        } catch (Exception $e) {
            Logger::error("Erro ao obter estatísticas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Validar dados do usuário
     */
    private static function validateUserData($data, $requirePassword = true) {
        $errors = [];
        
        if (empty($data['nome'])) {
            $errors[] = 'Nome é obrigatório';
        }
        
        if (empty($data['email'])) {
            $errors[] = 'Email é obrigatório';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email inválido';
        }
        
        if ($requirePassword && empty($data['senha'])) {
            $errors[] = 'Senha é obrigatória';
        } elseif (!empty($data['senha']) && strlen($data['senha']) < 8) {
            $errors[] = 'Senha deve ter no mínimo 8 caracteres';
        }
        
        if (!empty($data['tipo']) && !in_array($data['tipo'], ['cliente', 'loja', 'admin'])) {
            $errors[] = 'Tipo de usuário inválido';
        }
        
        if (!empty($data['status']) && !in_array($data['status'], ['ativo', 'inativo', 'bloqueado'])) {
            $errors[] = 'Status inválido';
        }
        
        if (!empty($errors)) {
            return [
                'status' => false,
                'message' => implode(', ', $errors)
            ];
        }
        
        return ['status' => true];
    }
}

// Processar requisições AJAX
if (basename($_SERVER['PHP_SELF']) === 'UserManagementController.php') {
    session_start();
    
    // Verificar autenticação
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== USER_TYPE_ADMIN) {
        http_response_code(403);
        echo json_encode(['status' => false, 'message' => 'Acesso negado']);
        exit;
    }
    
    header('Content-Type: application/json');
    
    try {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';
        
        switch ($method) {
            case 'GET':
                if (isset($_GET['id'])) {
                    echo json_encode(UserManagementController::getUserById($_GET['id']));
                } else {
                    $filters = [
                        'tipo' => $_GET['tipo'] ?? '',
                        'status' => $_GET['status'] ?? '',
                        'busca' => $_GET['busca'] ?? ''
                    ];
                    $page = $_GET['page'] ?? 1;
                    echo json_encode(UserManagementController::listUsers($filters, $page));
                }
                break;
                
            case 'POST':
                $data = json_decode(file_get_contents('php://input'), true);
                echo json_encode(UserManagementController::createUser($data));
                break;
                
            case 'PUT':
                if (!isset($_GET['id'])) {
                    throw new Exception('ID não fornecido');
                }
                $data = json_decode(file_get_contents('php://input'), true);
                echo json_encode(UserManagementController::updateUser($_GET['id'], $data));
                break;
                
            case 'DELETE':
                if (!isset($_GET['id'])) {
                    throw new Exception('ID não fornecido');
                }
                echo json_encode(UserManagementController::deleteUser($_GET['id']));
                break;
                
            default:
                http_response_code(405);
                echo json_encode(['status' => false, 'message' => 'Método não permitido']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>