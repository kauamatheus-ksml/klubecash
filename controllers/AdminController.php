<?php

// controllers/AdminController.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/email.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/AuthController.php';



if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se é uma requisição AJAX válida
$isAjaxRequest = (
    isset($_POST['action']) || 
    isset($_GET['action']) ||
    !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
);

// Se for acesso direto ao arquivo (não AJAX), redirecionar
if (!$isAjaxRequest && basename($_SERVER['PHP_SELF']) === 'AdminController.php') {
    // Verificar autenticação
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== USER_TYPE_ADMIN) {
        header('Location: /login?error=' . urlencode('Você precisa fazer login.'));
    } else {
        header('Location: /admin/dashboard');
    }
    exit;
}

// Verificar autenticação para requisições AJAX
if ($isAjaxRequest) {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== USER_TYPE_ADMIN) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['status' => false, 'message' => 'Sessão expirada. Faça login novamente.']);
        exit;
    }
}

// No início do arquivo, após os includes
ini_set('display_errors', 0);
error_reporting(E_ALL);

function sendJsonResponse($data) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data);
    exit;
}

// Adicione um manipulador de erros para registrar erros sem exibi-los
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Erro PHP [$errno]: $errstr em $errfile:$errline");
    return true;
});

/**
 * Controlador de Administração
 * Sistema completo de gerenciamento de usuários com segurança e auditoria
 */
class AdminController {
    private static function sendJsonResponse($data) {
    // Limpar qualquer output anterior
    ob_clean();
    
    // Definir headers corretos
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // Enviar resposta e finalizar
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

    /**
     * Valida se o usuário atual é um administrador
     * 
     * @return bool True se for admin
     */
    private static function validateAdmin() {
        return isset($_SESSION['user_id']) && 
               isset($_SESSION['user_type']) && 
               $_SESSION['user_type'] === USER_TYPE_ADMIN;
    }

    /**
    * Gerencia usuários do sistema com suporte completo para todos os tipos
    * Implementa segurança, paginação eficiente e logs de auditoria
    * 
    * @param array $filters Filtros para a listagem
    * @param int $page Página atual
    * @return array Lista de usuários
    */
    public static function manageUsers($filters = [], $page = 1) {
        try {
            // Verificar se é um administrador
            if (!self::validateAdmin()) {
                return ['status' => false, 'message' => 'Acesso restrito a administradores.'];
            }
            
            $db = Database::getConnection();
            
            // Sanitizar e validar filtros
            $filters = self::sanitizeUserFilters($filters);
            
            // Construir condições WHERE com prepared statements
            $whereConditions = [];
            $params = [];
            
            // Aplicar filtros
            if (!empty($filters['tipo']) && $filters['tipo'] !== 'todos') {
                $whereConditions[] = "u.tipo = ?";
                $params[] = $filters['tipo'];
            }
            
            if (!empty($filters['subtipo_funcionario'])) {
                $whereConditions[] = "u.subtipo_funcionario = ?";
                $params[] = $filters['subtipo_funcionario'];
            }
            
            if (!empty($filters['status']) && $filters['status'] !== 'todos') {
                $whereConditions[] = "u.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['loja_vinculada'])) {
                $whereConditions[] = "u.loja_vinculada_id = ?";
                $params[] = $filters['loja_vinculada'];
            }
            
            if (!empty($filters['busca'])) {
                $whereConditions[] = "(u.nome LIKE ? OR u.email LIKE ? OR u.telefone LIKE ? OR u.cpf LIKE ?)";
                $searchTerm = '%' . $filters['busca'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if (!empty($filters['data_inicio'])) {
                $whereConditions[] = "DATE(u.data_criacao) >= ?";
                $params[] = $filters['data_inicio'];
            }
            
            if (!empty($filters['data_fim'])) {
                $whereConditions[] = "DATE(u.data_criacao) <= ?";
                $params[] = $filters['data_fim'];
            }
            
            $whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);
            
            // Query principal otimizada com índices
            $query = "
                SELECT 
                    u.id, 
                    u.nome, 
                    u.email, 
                    u.telefone,
                    u.cpf,
                    u.tipo, 
                    u.subtipo_funcionario,
                    u.status, 
                    u.data_criacao, 
                    u.ultimo_login,
                    u.loja_vinculada_id,
                    u.mvp,
                    u.email_verified,
                    u.two_factor_enabled,
                    u.provider,
                    l.nome_fantasia as nome_loja_vinculada,
                    (SELECT COUNT(*) FROM usuarios_logs ul WHERE ul.usuario_id = u.id) as total_logs
                FROM usuarios u
                LEFT JOIN lojas l ON u.loja_vinculada_id = l.id
                $whereClause
                ORDER BY u.data_criacao DESC
            ";
            
            // Calcular total de registros para paginação
            $countQuery = "SELECT COUNT(*) as total FROM usuarios u LEFT JOIN lojas l ON u.loja_vinculada_id = l.id $whereClause";
            $countStmt = $db->prepare($countQuery);
            $countStmt->execute($params);
            $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Configuração de paginação
            $perPage = defined('ITEMS_PER_PAGE') ? ITEMS_PER_PAGE : 15;
            $page = max(1, (int)$page);
            $offset = ($page - 1) * $perPage;
            $query .= " LIMIT $offset, $perPage";
            
            // Executar consulta
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Estatísticas completas dos usuários
            $statsQuery = "
                SELECT 
                    COUNT(*) as total_usuarios,
                    SUM(CASE WHEN tipo = 'cliente' THEN 1 ELSE 0 END) as total_clientes,
                    SUM(CASE WHEN tipo = 'admin' THEN 1 ELSE 0 END) as total_admins,
                    SUM(CASE WHEN tipo = 'loja' THEN 1 ELSE 0 END) as total_lojas,
                    SUM(CASE WHEN tipo = 'funcionario' THEN 1 ELSE 0 END) as total_funcionarios,
                    SUM(CASE WHEN tipo = 'funcionario' AND subtipo_funcionario = 'financeiro' THEN 1 ELSE 0 END) as total_funcionarios_financeiro,
                    SUM(CASE WHEN tipo = 'funcionario' AND subtipo_funcionario = 'gerente' THEN 1 ELSE 0 END) as total_funcionarios_gerente,
                    SUM(CASE WHEN tipo = 'funcionario' AND subtipo_funcionario = 'vendedor' THEN 1 ELSE 0 END) as total_funcionarios_vendedor,
                    SUM(CASE WHEN status = 'ativo' THEN 1 ELSE 0 END) as total_ativos,
                    SUM(CASE WHEN status = 'inativo' THEN 1 ELSE 0 END) as total_inativos,
                    SUM(CASE WHEN status = 'bloqueado' THEN 1 ELSE 0 END) as total_bloqueados,
                    SUM(CASE WHEN email_verified = 1 THEN 1 ELSE 0 END) as total_emails_verificados,
                    SUM(CASE WHEN two_factor_enabled = 1 THEN 1 ELSE 0 END) as total_2fa_ativo,
                    SUM(CASE WHEN ultimo_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as total_ativos_30_dias
                FROM usuarios
            ";
            
            $statsStmt = $db->prepare($statsQuery);
            $statsStmt->execute();
            $statistics = $statsStmt->fetch(PDO::FETCH_ASSOC);
            
            // Calcular informações de paginação
            $totalPages = ceil($totalCount / $perPage);
            
            return [
                'status' => true,
                'data' => [
                    'usuarios' => $users,
                    'estatisticas' => $statistics,
                    'paginacao' => [
                        'total' => $totalCount,
                        'por_pagina' => $perPage,
                        'pagina_atual' => $page,
                        'total_paginas' => $totalPages,
                        'primeira_pagina' => 1,
                        'ultima_pagina' => $totalPages,
                        'pagina_anterior' => max(1, $page - 1),
                        'proxima_pagina' => min($totalPages, $page + 1)
                    ]
                ]
            ];
            
        } catch (PDOException $e) {
            error_log('Erro ao gerenciar usuários: ' . $e->getMessage());
            return [
                'status' => false, 
                'message' => 'Erro interno do servidor. Tente novamente.'
            ];
        }
    }
    
    /**
     * Obtém detalhes completos de um usuário específico
     * Inclui histórico, logs e informações de segurança
     * 
     * @param int $userId ID do usuário
     * @return array Dados completos do usuário
     */
    public static function getUserDetails($userId) {
        try {
            // Verificar se é um administrador
            if (!self::validateAdmin()) {
                return ['status' => false, 'message' => 'Acesso restrito a administradores.'];
            }
            
            // Validar e sanitizar ID
            $userId = filter_var($userId, FILTER_VALIDATE_INT);
            if (!$userId) {
                return ['status' => false, 'message' => 'ID de usuário inválido.'];
            }
            
            $db = Database::getConnection();
            
            // Obter dados completos do usuário
            $stmt = $db->prepare("
                SELECT 
                    u.id, u.nome, u.email, u.telefone, u.cpf,
                    u.tipo, u.subtipo_funcionario, u.status, 
                    u.data_criacao, u.ultimo_login, u.mvp,
                    u.loja_vinculada_id, u.email_verified, u.two_factor_enabled,
                    u.provider, u.avatar_url, u.tipo_cliente,
                    l.nome_fantasia as nome_loja_vinculada,
                    l.email as email_loja_vinculada,
                    uc.celular, uc.email_alternativo,
                    ue.cep, ue.logradouro, ue.numero, ue.complemento, 
                    ue.bairro, ue.cidade, ue.estado
                FROM usuarios u
                LEFT JOIN lojas l ON u.loja_vinculada_id = l.id
                LEFT JOIN usuarios_contato uc ON u.id = uc.usuario_id
                LEFT JOIN usuarios_endereco ue ON u.id = ue.usuario_id AND ue.principal = 1
                WHERE u.id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return ['status' => false, 'message' => 'Usuário não encontrado.'];
            }
            
            // Obter histórico de logs do usuário
            $logsStmt = $db->prepare("
                SELECT 
                    ul.acao, ul.data_acao, ul.ip_origem,
                    ua.nome as admin_nome
                FROM usuarios_logs ul
                LEFT JOIN usuarios ua ON ul.admin_id = ua.id
                WHERE ul.usuario_id = ?
                ORDER BY ul.data_acao DESC
                LIMIT 10
            ");
            $logsStmt->execute([$userId]);
            $logs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obter estatísticas do usuário
            $statsStmt = $db->prepare("
                SELECT 
                    (SELECT COUNT(*) FROM cashback_movimentacoes WHERE usuario_id = ?) as total_transacoes,
                    (SELECT SUM(saldo_disponivel) FROM cashback_saldos WHERE usuario_id = ?) as saldo_total,
                    (SELECT COUNT(*) FROM usuarios_sessoes_ativas WHERE usuario_id = ? AND ativo = 1) as sessoes_ativas
            ");
            $statsStmt->execute([$userId, $userId, $userId]);
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'status' => true,
                'data' => [
                    'usuario' => $user,
                    'logs' => $logs,
                    'estatisticas' => $stats
                ]
            ];
            
        } catch (PDOException $e) {
            error_log('Erro ao obter detalhes do usuário: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro interno do servidor.'];
        }
    }

    /**
     * Sanitiza e valida filtros de usuário
     * Implementa proteção contra XSS e SQL Injection
     * 
     * @param array $filters Filtros brutos
     * @return array Filtros sanitizados
     */
    private static function sanitizeUserFilters($filters) {
        $sanitized = [];
        
        // Tipos permitidos
        $allowedTypes = ['todos', 'cliente', 'admin', 'loja', 'funcionario'];
        if (isset($filters['tipo']) && in_array($filters['tipo'], $allowedTypes)) {
            $sanitized['tipo'] = $filters['tipo'];
        }
        
        // Subtipos de funcionário permitidos
        $allowedSubtypes = ['financeiro', 'gerente', 'vendedor'];
        if (isset($filters['subtipo_funcionario']) && in_array($filters['subtipo_funcionario'], $allowedSubtypes)) {
            $sanitized['subtipo_funcionario'] = $filters['subtipo_funcionario'];
        }
        
        // Status permitidos
        $allowedStatus = ['todos', 'ativo', 'inativo', 'bloqueado'];
        if (isset($filters['status']) && in_array($filters['status'], $allowedStatus)) {
            $sanitized['status'] = $filters['status'];
        }
        
        // Sanitizar busca
        if (isset($filters['busca']) && !empty(trim($filters['busca']))) {
            $sanitized['busca'] = htmlspecialchars(strip_tags(trim($filters['busca'])), ENT_QUOTES, 'UTF-8');
        }
        
        // Validar datas
        if (isset($filters['data_inicio']) && !empty($filters['data_inicio'])) {
            $date = DateTime::createFromFormat('Y-m-d', $filters['data_inicio']);
            if ($date && $date->format('Y-m-d') === $filters['data_inicio']) {
                $sanitized['data_inicio'] = $filters['data_inicio'];
            }
        }
        
        if (isset($filters['data_fim']) && !empty($filters['data_fim'])) {
            $date = DateTime::createFromFormat('Y-m-d', $filters['data_fim']);
            if ($date && $date->format('Y-m-d') === $filters['data_fim']) {
                $sanitized['data_fim'] = $filters['data_fim'];
            }
        }
        
        // Validar loja vinculada
        if (isset($filters['loja_vinculada'])) {
            $lojaId = filter_var($filters['loja_vinculada'], FILTER_VALIDATE_INT);
            if ($lojaId) {
                $sanitized['loja_vinculada'] = $lojaId;
            }
        }
        
        return $sanitized;
    }

    /**
     * Cria um novo usuário com validação completa e logs de auditoria
     * 
     * @param array $userData Dados do usuário
     * @return array Resultado da operação
     */
    public static function createUser($userData) {
        try {
            // Verificar se é um administrador
            if (!self::validateAdmin()) {
                return ['status' => false, 'message' => 'Acesso restrito a administradores.'];
            }
            
            $db = Database::getConnection();
            $db->beginTransaction();
            
            // Validar dados obrigatórios
            $validation = self::validateUserData($userData, false);
            if (!$validation['valid']) {
                return ['status' => false, 'message' => $validation['message']];
            }
            
            // Verificar se email já existe
            $emailCheck = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
            $emailCheck->execute([$userData['email']]);
            if ($emailCheck->rowCount() > 0) {
                return ['status' => false, 'message' => 'Este email já está cadastrado.'];
            }
            
            // Hash da senha
            $hashedPassword = password_hash($userData['senha'], PASSWORD_DEFAULT);
            
            // Inserir usuário
            $insertStmt = $db->prepare("
                INSERT INTO usuarios (
                    nome, email, telefone, cpf, senha_hash, tipo, 
                    subtipo_funcionario, status, loja_vinculada_id, 
                    mvp, tipo_cliente, email_verified
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $insertStmt->execute([
                $userData['nome'],
                $userData['email'],
                $userData['telefone'] ?? null,
                $userData['cpf'] ?? null,
                $hashedPassword,
                $userData['tipo'],
                $userData['subtipo_funcionario'] ?? null,
                $userData['status'] ?? 'ativo',
                $userData['loja_vinculada_id'] ?? null,
                $userData['mvp'] ?? 'nao',
                $userData['tipo_cliente'] ?? 'completo',
                0
            ]);
            
            $userId = $db->lastInsertId();
            
            // Log de auditoria
            self::logUserAction($userId, 'criado', null, $userData, $_SESSION['user_id']);
            
            $db->commit();
            
            return [
                'status' => true, 
                'message' => 'Usuário criado com sucesso!',
                'user_id' => $userId
            ];
            
        } catch (PDOException $e) {
            $db->rollBack();
            error_log('Erro ao criar usuário: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro interno do servidor.'];
        }
    }

    /**
     * Atualiza um usuário existente com validação e logs
     * 
     * @param int $userId ID do usuário
     * @param array $userData Novos dados do usuário
     * @return array Resultado da operação
     */
    public static function updateUser($userId, $userData) {
        try {
            // Verificar se é um administrador
            if (!self::validateAdmin()) {
                return ['status' => false, 'message' => 'Acesso restrito a administradores.'];
            }
            
            $userId = filter_var($userId, FILTER_VALIDATE_INT);
            if (!$userId) {
                return ['status' => false, 'message' => 'ID de usuário inválido.'];
            }
            
            $db = Database::getConnection();
            $db->beginTransaction();
            
            // Obter dados atuais para log
            $currentStmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
            $currentStmt->execute([$userId]);
            $currentData = $currentStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$currentData) {
                return ['status' => false, 'message' => 'Usuário não encontrado.'];
            }
            
            // Validar novos dados
            $validation = self::validateUserData($userData, true, $userId);
            if (!$validation['valid']) {
                return ['status' => false, 'message' => $validation['message']];
            }
            
            // Construir query de update
            $fields = [];
            $params = [];
            
            if (isset($userData['nome'])) {
                $fields[] = "nome = ?";
                $params[] = $userData['nome'];
            }
            
            if (isset($userData['email'])) {
                // Verificar se email já existe em outro usuário
                $emailCheck = $db->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
                $emailCheck->execute([$userData['email'], $userId]);
                if ($emailCheck->rowCount() > 0) {
                    return ['status' => false, 'message' => 'Este email já está cadastrado.'];
                }
                $fields[] = "email = ?";
                $params[] = $userData['email'];
            }
            
            if (isset($userData['telefone'])) {
                $fields[] = "telefone = ?";
                $params[] = $userData['telefone'];
            }
            
            if (isset($userData['cpf'])) {
                $fields[] = "cpf = ?";
                $params[] = $userData['cpf'];
            }
            
            if (isset($userData['tipo'])) {
                $fields[] = "tipo = ?";
                $params[] = $userData['tipo'];
            }
            
            if (isset($userData['subtipo_funcionario'])) {
                $fields[] = "subtipo_funcionario = ?";
                $params[] = $userData['subtipo_funcionario'];
            }
            
            if (isset($userData['status'])) {
                $fields[] = "status = ?";
                $params[] = $userData['status'];
            }
            
            if (isset($userData['loja_vinculada_id'])) {
                $fields[] = "loja_vinculada_id = ?";
                $params[] = $userData['loja_vinculada_id'];
            }
            
            if (isset($userData['mvp'])) {
                $fields[] = "mvp = ?";
                $params[] = $userData['mvp'];
            }
            
            // Atualizar senha se fornecida
            if (!empty($userData['senha'])) {
                $fields[] = "senha_hash = ?";
                $params[] = password_hash($userData['senha'], PASSWORD_DEFAULT);
            }
            
            if (empty($fields)) {
                return ['status' => false, 'message' => 'Nenhum dado para atualizar.'];
            }
            
            $params[] = $userId;
            $updateStmt = $db->prepare("UPDATE usuarios SET " . implode(', ', $fields) . " WHERE id = ?");
            $updateStmt->execute($params);
            
            // Log de auditoria
            self::logUserAction($userId, 'atualizado', $currentData, $userData, $_SESSION['user_id']);
            
            $db->commit();
            
            return ['status' => true, 'message' => 'Usuário atualizado com sucesso!'];
            
        } catch (PDOException $e) {
            $db->rollBack();
            error_log('Erro ao atualizar usuário: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro interno do servidor.'];
        }
    }

    /**
     * Atualiza o status de um usuário
     * 
     * @param int $userId ID do usuário
     * @param string $status Novo status
     * @return array Resultado da operação
     */
    public static function updateUserStatus($userId, $status) {
        try {
            // Verificar se é um administrador
            if (!self::validateAdmin()) {
                return ['status' => false, 'message' => 'Acesso restrito a administradores.'];
            }
            
            $userId = filter_var($userId, FILTER_VALIDATE_INT);
            if (!$userId) {
                return ['status' => false, 'message' => 'ID de usuário inválido.'];
            }
            
            $allowedStatus = ['ativo', 'inativo', 'bloqueado'];
            if (!in_array($status, $allowedStatus)) {
                return ['status' => false, 'message' => 'Status inválido.'];
            }
            
            $db = Database::getConnection();
            
            // Obter dados atuais
            $currentStmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
            $currentStmt->execute([$userId]);
            $currentData = $currentStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$currentData) {
                return ['status' => false, 'message' => 'Usuário não encontrado.'];
            }
            
            // Não permitir que admin desative a si próprio
            if ($userId == $_SESSION['user_id'] && $status !== 'ativo') {
                return ['status' => false, 'message' => 'Não é possível alterar o status da própria conta.'];
            }
            
            // Atualizar status
            $updateStmt = $db->prepare("UPDATE usuarios SET status = ? WHERE id = ?");
            $updateStmt->execute([$status, $userId]);
            
            // Log de auditoria
            self::logUserAction($userId, 'status_alterado', $currentData, ['status' => $status], $_SESSION['user_id']);
            
            return ['status' => true, 'message' => 'Status atualizado com sucesso!'];
            
        } catch (PDOException $e) {
            error_log('Erro ao atualizar status: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro interno do servidor.'];
        }
    }

    /**
     * Valida dados de usuário
     * 
     * @param array $userData Dados do usuário
     * @param bool $isUpdate Se é uma atualização
     * @param int $userId ID do usuário (para updates)
     * @return array Resultado da validação
     */
    private static function validateUserData($userData, $isUpdate = false, $userId = null) {
        $errors = [];
        
        // Nome obrigatório
        if (!$isUpdate || isset($userData['nome'])) {
            if (empty(trim($userData['nome']))) {
                $errors[] = 'Nome é obrigatório.';
            } elseif (strlen($userData['nome']) < 2) {
                $errors[] = 'Nome deve ter pelo menos 2 caracteres.';
            } elseif (strlen($userData['nome']) > 100) {
                $errors[] = 'Nome não pode ter mais de 100 caracteres.';
            }
        }
        
        // Email obrigatório
        if (!$isUpdate || isset($userData['email'])) {
            if (empty($userData['email'])) {
                $errors[] = 'Email é obrigatório.';
            } elseif (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Email inválido.';
            } elseif (strlen($userData['email']) > 255) {
                $errors[] = 'Email não pode ter mais de 255 caracteres.';
            }
        }
        
        // Senha obrigatória para criação
        if (!$isUpdate && (empty($userData['senha']) || strlen($userData['senha']) < 8)) {
            $errors[] = 'Senha deve ter pelo menos 8 caracteres.';
        }
        
        // Validar senha se fornecida na atualização
        if ($isUpdate && !empty($userData['senha']) && strlen($userData['senha']) < 8) {
            $errors[] = 'Senha deve ter pelo menos 8 caracteres.';
        }
        
        // Tipo obrigatório
        if (!$isUpdate || isset($userData['tipo'])) {
            $allowedTypes = ['cliente', 'admin', 'loja', 'funcionario'];
            if (empty($userData['tipo']) || !in_array($userData['tipo'], $allowedTypes)) {
                $errors[] = 'Tipo de usuário inválido.';
            }
        }
        
        // Subtipo para funcionários
        if (isset($userData['tipo']) && $userData['tipo'] === 'funcionario') {
            $allowedSubtypes = ['financeiro', 'gerente', 'vendedor'];
            if (empty($userData['subtipo_funcionario']) || !in_array($userData['subtipo_funcionario'], $allowedSubtypes)) {
                $errors[] = 'Subtipo de funcionário é obrigatório e deve ser válido.';
            }
        }
        
        // Validar CPF se fornecido
        if (!empty($userData['cpf'])) {
            $cpf = preg_replace('/[^0-9]/', '', $userData['cpf']);
            if (strlen($cpf) !== 11) {
                $errors[] = 'CPF deve ter 11 dígitos.';
            }
        }
        
        // Validar telefone se fornecido
        if (!empty($userData['telefone'])) {
            $phone = preg_replace('/[^0-9]/', '', $userData['telefone']);
            if (strlen($phone) < 10 || strlen($phone) > 11) {
                $errors[] = 'Telefone deve ter 10 ou 11 dígitos.';
            }
        }
        
        return [
            'valid' => empty($errors),
            'message' => empty($errors) ? 'Dados válidos.' : implode(' ', $errors)
        ];
    }

    /**
     * Registra ação do usuário para auditoria
     * 
     * @param int $userId ID do usuário afetado
     * @param string $action Ação realizada
     * @param array $oldData Dados anteriores
     * @param array $newData Novos dados
     * @param int $adminId ID do admin que realizou a ação
     */
    private static function logUserAction($userId, $action, $oldData, $newData, $adminId) {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO usuarios_logs 
                (usuario_id, admin_id, acao, dados_anteriores, dados_novos, ip_origem, user_agent, data_acao)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $userId,
                $adminId,
                $action,
                $oldData ? json_encode($oldData, JSON_UNESCAPED_UNICODE) : null,
                json_encode($newData, JSON_UNESCAPED_UNICODE),
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log('Erro ao registrar log de usuário: ' . $e->getMessage());
        }
    }

    /**
     * Obtém lista de lojas disponíveis para vinculação
     * 
     * @return array Lista de lojas
     */
    public static function getAvailableStores() {
        try {
            if (!self::validateAdmin()) {
                return ['status' => false, 'message' => 'Acesso restrito a administradores.'];
            }
            
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT id, nome_fantasia, email, telefone, categoria, status
                FROM lojas 
                WHERE status = 'aprovado'
                ORDER BY nome_fantasia ASC
            ");
            $stmt->execute();
            $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['status' => true, 'data' => $stores];
            
        } catch (PDOException $e) {
            error_log('Erro ao obter lojas: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro interno do servidor.'];
        }
    }

    /**
     * Exporta lista de usuários para CSV
     * 
     * @param array $filters Filtros aplicados
     * @return array Resultado da operação
     */
    public static function exportUsers($filters = []) {
        try {
            if (!self::validateAdmin()) {
                return ['status' => false, 'message' => 'Acesso restrito a administradores.'];
            }
            
            // Sanitizar filtros
            $filters = self::sanitizeUserFilters($filters);
            
            $db = Database::getConnection();
            
            // Construir query sem paginação
            $whereConditions = [];
            $params = [];
            
            if (!empty($filters['tipo']) && $filters['tipo'] !== 'todos') {
                $whereConditions[] = "u.tipo = ?";
                $params[] = $filters['tipo'];
            }
            
            if (!empty($filters['status']) && $filters['status'] !== 'todos') {
                $whereConditions[] = "u.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['busca'])) {
                $whereConditions[] = "(u.nome LIKE ? OR u.email LIKE ?)";
                $searchTerm = '%' . $filters['busca'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);
            
            $query = "
                SELECT 
                    u.id, u.nome, u.email, u.telefone, u.cpf,
                    u.tipo, u.subtipo_funcionario, u.status,
                    u.data_criacao, u.ultimo_login, u.mvp,
                    l.nome_fantasia as loja_vinculada
                FROM usuarios u
                LEFT JOIN lojas l ON u.loja_vinculada_id = l.id
                $whereClause
                ORDER BY u.nome ASC
            ";
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Gerar CSV
            $filename = 'usuarios_' . date('Y-m-d_H-i-s') . '.csv';
            $filepath = sys_get_temp_dir() . '/' . $filename;
            
            $file = fopen($filepath, 'w');
            
            // Cabeçalho do CSV
            $headers = [
                'ID', 'Nome', 'Email', 'Telefone', 'CPF', 'Tipo', 
                'Subtipo', 'Status', 'Data Criação', 'Último Login', 
                'MVP', 'Loja Vinculada'
            ];
            fputcsv($file, $headers);
            
            // Dados
            foreach ($users as $user) {
                fputcsv($file, [
                    $user['id'],
                    $user['nome'],
                    $user['email'],
                    $user['telefone'],
                    $user['cpf'],
                    $user['tipo'],
                    $user['subtipo_funcionario'],
                    $user['status'],
                    $user['data_criacao'],
                    $user['ultimo_login'],
                    $user['mvp'],
                    $user['loja_vinculada']
                ]);
            }
            
            fclose($file);
            
            return [
                'status' => true,
                'filepath' => $filepath,
                'filename' => $filename,
                'total' => count($users)
            ];
            
        } catch (Exception $e) {
            error_log('Erro ao exportar usuários: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro interno do servidor.'];
        }
    }
}

// Handler para requisições AJAX
if ($isAjaxRequest && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'getUserDetails':
            $userId = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
            if ($userId) {
                $result = AdminController::getUserDetails($userId);
                AdminController::sendJsonResponse($result);
            } else {
                AdminController::sendJsonResponse(['status' => false, 'message' => 'ID inválido']);
            }
            break;
            
        case 'create_user':
        case 'register':
            // Capturar dados do POST
            $userData = [
                'nome' => $_POST['nome'] ?? '',
                'email' => $_POST['email'] ?? '',
                'telefone' => $_POST['telefone'] ?? '',
                'cpf' => $_POST['cpf'] ?? '',
                'senha' => $_POST['senha'] ?? '',
                'tipo' => $_POST['tipo'] ?? '',
                'subtipo_funcionario' => $_POST['subtipo_funcionario'] ?? null,
                'status' => $_POST['status'] ?? 'ativo',
                'loja_vinculada_id' => $_POST['loja_vinculada_id'] ?? null,
                'mvp' => $_POST['mvp'] ?? 'nao'
            ];
            
            $result = AdminController::createUser($userData);
            AdminController::sendJsonResponse($result);
            break;
            
        case 'update_user':
            $userId = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
            if (!$userId) {
                AdminController::sendJsonResponse(['status' => false, 'message' => 'ID inválido']);
                break;
            }
            
            $userData = [
                'nome' => $_POST['nome'] ?? null,
                'email' => $_POST['email'] ?? null,
                'telefone' => $_POST['telefone'] ?? null,
                'cpf' => $_POST['cpf'] ?? null,
                'tipo' => $_POST['tipo'] ?? null,
                'subtipo_funcionario' => $_POST['subtipo_funcionario'] ?? null,
                'status' => $_POST['status'] ?? null,
                'loja_vinculada_id' => $_POST['loja_vinculada_id'] ?? null,
                'mvp' => $_POST['mvp'] ?? null
            ];
            
            // Incluir senha se fornecida
            if (!empty($_POST['senha'])) {
                $userData['senha'] = $_POST['senha'];
            }
            
            $result = AdminController::updateUser($userId, $userData);
            AdminController::sendJsonResponse($result);
            break;
            
        case 'update_user_status':
            $userId = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
            $status = $_POST['status'] ?? '';
            
            if ($userId && $status) {
                $result = AdminController::updateUserStatus($userId, $status);
                AdminController::sendJsonResponse($result);
            } else {
                AdminController::sendJsonResponse(['status' => false, 'message' => 'Parâmetros inválidos']);
            }
            break;
            
        case 'get_available_stores':
            $result = AdminController::getAvailableStores();
            AdminController::sendJsonResponse($result);
            break;
            
        case 'export_users':
            $filters = $_POST['filters'] ?? [];
            $result = AdminController::exportUsers($filters);
            
            if ($result['status']) {
                // Enviar arquivo para download
                $filepath = $result['filepath'];
                $filename = $result['filename'];
                
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Content-Length: ' . filesize($filepath));
                readfile($filepath);
                unlink($filepath); // Limpar arquivo temporário
                exit;
            } else {
                AdminController::sendJsonResponse($result);
            }
            break;
            
        default:
            AdminController::sendJsonResponse(['status' => false, 'message' => 'Ação não reconhecida']);
            break;
    }
}
?>