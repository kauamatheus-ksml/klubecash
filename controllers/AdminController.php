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

// Configurar tratamento de erros
ini_set('display_errors', 0);
error_reporting(E_ALL);

/**
 * Controlador de Administração
 */
class AdminController {

    /**
     * Envia resposta JSON limpa
     */
    private static function sendJsonResponse($data) {
        ob_clean();
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-cache, must-revalidate');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Gerenciar usuários com filtros e paginação
     */
    public static function manageUsers($filters = [], $page = 1) {
        try {
            $db = Database::getConnection();
            
            $whereConditions = ['1=1'];
            $params = [];
            
            if (!empty($filters['tipo'])) {
                $whereConditions[] = "tipo = :tipo";
                $params[':tipo'] = $filters['tipo'];
            }
            
            if (!empty($filters['status'])) {
                $whereConditions[] = "status = :status";
                $params[':status'] = $filters['status'];
            }
            
            if (!empty($filters['busca'])) {
                $whereConditions[] = "(nome LIKE :busca OR email LIKE :busca)";
                $params[':busca'] = '%' . $filters['busca'] . '%';
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // Estatísticas
            $statsStmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_usuarios,
                    SUM(CASE WHEN tipo = 'cliente' THEN 1 ELSE 0 END) as total_clientes,
                    SUM(CASE WHEN tipo = 'loja' THEN 1 ELSE 0 END) as total_lojas,
                    SUM(CASE WHEN tipo = 'admin' THEN 1 ELSE 0 END) as total_admins
                FROM usuarios 
                WHERE $whereClause
            ");
            $statsStmt->execute($params);
            $estatisticas = $statsStmt->fetch(PDO::FETCH_ASSOC);
            
            // Contar total
            $countStmt = $db->prepare("SELECT COUNT(*) as total FROM usuarios WHERE $whereClause");
            $countStmt->execute($params);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Paginação
            $limit = ITEMS_PER_PAGE;
            $offset = ($page - 1) * $limit;
            $totalPages = ceil($total / $limit);
            
            // Buscar usuários
            $stmt = $db->prepare("
                SELECT id, nome, email, telefone, tipo, status, data_criacao, ultimo_login 
                FROM usuarios 
                WHERE $whereClause 
                ORDER BY data_criacao DESC 
                LIMIT :limit OFFSET :offset
            ");
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'status' => true,
                'data' => [
                    'usuarios' => $usuarios,
                    'estatisticas' => $estatisticas,
                    'paginacao' => [
                        'current_page' => $page,
                        'total_pages' => $totalPages,
                        'total_items' => $total,
                        'items_per_page' => $limit
                    ]
                ]
            ];
            
        } catch (PDOException $e) {
            error_log('Erro ao buscar usuários: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro interno do servidor'];
        }
    }

    /**
     * Obter detalhes de um usuário específico
     */
    public static function getUserDetails($userId) {
        try {
            $db = Database::getConnection();
            
            $stmt = $db->prepare("
                SELECT id, nome, email, telefone, cpf, tipo, status, 
                       data_criacao, ultimo_login, provider, email_verified
                FROM usuarios 
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario) {
                return ['status' => false, 'message' => 'Usuário não encontrado'];
            }
            
            return [
                'status' => true,
                'data' => ['usuario' => $usuario]
            ];
            
        } catch (PDOException $e) {
            error_log('Erro ao buscar detalhes do usuário: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro interno do servidor'];
        }
    }

    /**
     * Atualizar dados de um usuário
     */
    public static function updateUser($userId, $data) {
        try {
            $db = Database::getConnection();
            
            $updateFields = [];
            $params = [':id' => $userId];
            
            $allowedFields = ['nome', 'email', 'telefone', 'tipo', 'status'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "$field = :$field";
                    $params[":$field"] = $data[$field];
                }
            }
            
            if (isset($data['senha']) && !empty($data['senha'])) {
                $updateFields[] = "senha_hash = :senha_hash";
                $params[':senha_hash'] = password_hash($data['senha'], PASSWORD_DEFAULT);
            }
            
            if (empty($updateFields)) {
                return ['status' => false, 'message' => 'Nenhum campo para atualizar'];
            }
            
            $sql = "UPDATE usuarios SET " . implode(', ', $updateFields) . " WHERE id = :id";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                return ['status' => true, 'message' => 'Usuário atualizado com sucesso'];
            } else {
                return ['status' => false, 'message' => 'Erro ao atualizar usuário'];
            }
            
        } catch (PDOException $e) {
            error_log('Erro ao atualizar usuário: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro interno do servidor'];
        }
    }

    /**
     * Atualizar status de um usuário
     */
    public static function updateUserStatus($userId, $status) {
        try {
            $db = Database::getConnection();
            
            // Validar status
            $validStatuses = ['ativo', 'inativo', 'bloqueado'];
            if (!in_array($status, $validStatuses)) {
                return ['status' => false, 'message' => 'Status inválido'];
            }
            
            $stmt = $db->prepare("UPDATE usuarios SET status = :status WHERE id = :id");
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            
            $result = $stmt->execute();
            
            if ($result) {
                $action = $status === 'ativo' ? 'ativado' : 'desativado';
                return ['status' => true, 'message' => "Usuário $action com sucesso"];
            } else {
                return ['status' => false, 'message' => 'Erro ao alterar status do usuário'];
            }
            
        } catch (PDOException $e) {
            error_log('Erro ao atualizar status do usuário: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro interno do servidor'];
        }
    }

    /**
     * Valida se o usuário é admin
     */
    private static function validateAdmin() {
        return AuthController::isAdmin();
    }

    // ... outros métodos existentes permanecem iguais ...
}

// ========== PROCESSAMENTO DE REQUISIÇÕES AJAX ==========

// Verificar se é requisição AJAX
$isAjaxRequest = (
    isset($_POST['action']) || 
    isset($_GET['action']) ||
    !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
);

// Processar apenas requisições AJAX
if ($isAjaxRequest) {
    // Verificar autenticação
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== USER_TYPE_ADMIN) {
        AdminController::sendJsonResponse(['status' => false, 'message' => 'Sessão expirada. Faça login novamente.']);
    }
    
    $action = $_REQUEST['action'] ?? '';
    
    switch ($action) {
        case 'users':
            $filters = [];
            if (isset($_REQUEST['tipo'])) $filters['tipo'] = $_REQUEST['tipo'];
            if (isset($_REQUEST['status'])) $filters['status'] = $_REQUEST['status'];
            if (isset($_REQUEST['busca'])) $filters['busca'] = $_REQUEST['busca'];
            
            $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $result = AdminController::manageUsers($filters, $page);
            AdminController::sendJsonResponse($result);
            break;
            
        case 'getUserDetails':
            $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
            $result = AdminController::getUserDetails($userId);
            AdminController::sendJsonResponse($result);
            break;
            
        case 'update_user_status':
        case 'toggle_status':
            error_log('Ação update_user_status recebida');
            error_log('POST data: ' . print_r($_POST, true));
            
            $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
            $status = $_POST['status'] ?? '';
            
            if (!$userId) {
                AdminController::sendJsonResponse(['status' => false, 'message' => 'ID do usuário não fornecido']);
            }
            
            if (empty($status)) {
                AdminController::sendJsonResponse(['status' => false, 'message' => 'Status não fornecido']);
            }
            
            // Verificar se não está tentando desativar a si mesmo
            if ($userId === $_SESSION['user_id']) {
                AdminController::sendJsonResponse(['status' => false, 'message' => 'Não é possível alterar seu próprio status']);
            }
            
            // Validar status permitidos
            $validStatuses = ['ativo', 'inativo', 'bloqueado'];
            if (!in_array($status, $validStatuses)) {
                AdminController::sendJsonResponse(['status' => false, 'message' => 'Status inválido']);
            }
            
            $result = AdminController::updateUserStatus($userId, $status);
            AdminController::sendJsonResponse($result);
            break;
            
        case 'update_user':
            $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
            $result = AdminController::updateUser($userId, $_POST);
            AdminController::sendJsonResponse($result);
            break;
            
        default:
            AdminController::sendJsonResponse(['status' => false, 'message' => 'Ação não reconhecida: ' . $action]);
    }
}

// Redirecionamento para acesso direto
if (!$isAjaxRequest && basename($_SERVER['PHP_SELF']) === 'AdminController.php') {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== USER_TYPE_ADMIN) {
        header('Location: /login?error=' . urlencode('Você precisa fazer login.'));
    } else {
        header('Location: /admin/dashboard');
    }
    exit;
}
?>