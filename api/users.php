<?php
// api/users.php

// Definir cabeçalhos
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Incluir arquivos necessários
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../controllers/AuthController.php';
require_once '../controllers/UserController.php';
require_once '../utils/Security.php';

// Tratar requisições OPTIONS (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Função para validar token JWT (reutilização do código existente)
function validateToken() {
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? '';
    
    if (empty($token)) {
        // Tentar obter token da sessão como fallback
        session_start();
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['status' => false, 'message' => 'Token não fornecido']);
            exit;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'tipo' => $_SESSION['user_type']
        ];
    }
    
    // Remover "Bearer " se presente
    $token = str_replace('Bearer ', '', $token);
    
    try {
        // Validar token JWT (implementar conforme sua biblioteca JWT)
        // Por enquanto, usar validação de sessão
        session_start();
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['status' => false, 'message' => 'Token inválido']);
            exit;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'tipo' => $_SESSION['user_type']
        ];
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['status' => false, 'message' => 'Token inválido']);
        exit;
    }
}

// Roteamento baseado no método HTTP
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        handleGetRequest();
        break;
    case 'POST':
        handlePostRequest();
        break;
    case 'PUT':
        handlePutRequest();
        break;
    case 'DELETE':
        handleDeleteRequest();
        break;
    default:
        http_response_code(405);
        echo json_encode(['status' => false, 'message' => 'Método não permitido']);
        break;
}

// Função para tratar requisições GET (listar ou buscar usuários)
function handleGetRequest() {
    // Validar token
    $userData = validateToken();
    
    // Verificar se é administrador para listar usuários
    if ($userData['tipo'] !== USER_TYPE_ADMIN) {
        // Usuários não-admin só podem ver seus próprios dados
        $userId = isset($_GET['id']) ? intval($_GET['id']) : null;
        if (!$userId || $userId !== $userData['id']) {
            http_response_code(403);
            echo json_encode(['status' => false, 'message' => 'Acesso negado']);
            exit;
        }
    }
    
    // Verificar se é busca por ID específico
    $userId = isset($_GET['id']) ? intval($_GET['id']) : null;
    
    if ($userId) {
        // Buscar usuário específico
        $result = UserController::getUserDetails($userId);
    } else {
        // Listar usuários (apenas para admins)
        if ($userData['tipo'] !== USER_TYPE_ADMIN) {
            http_response_code(403);
            echo json_encode(['status' => false, 'message' => 'Apenas administradores podem listar usuários']);
            exit;
        }
        
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $filters = [];
        
        // Aplicar filtros se fornecidos
        if (isset($_GET['tipo'])) $filters['tipo'] = $_GET['tipo'];
        if (isset($_GET['status'])) $filters['status'] = $_GET['status'];
        if (isset($_GET['busca'])) $filters['busca'] = $_GET['busca'];
        
        $result = UserController::listUsers($filters, $page);
    }
    
    // Retornar resultado
    echo json_encode($result);
}

// Função para tratar requisições POST (criar novo usuário)
function handlePostRequest() {
    // Validar se é registro público ou criação de admin
    $isPublicRegistration = isset($_GET['public']) && $_GET['public'] === 'true';
    
    if (!$isPublicRegistration) {
        // Validar token para criação de usuários via admin
        $userData = validateToken();
        
        // Verificar se é administrador
        if ($userData['tipo'] !== USER_TYPE_ADMIN) {
            http_response_code(403);
            echo json_encode(['status' => false, 'message' => 'Apenas administradores podem criar usuários']);
            exit;
        }
    }
    
    // Obter dados do corpo da requisição
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validar dados básicos
    if (!$data || !isset($data['nome']) || !isset($data['email'])) {
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'Dados incompletos']);
        exit;
    }
    
    // Definir senha padrão se não fornecida (para criação de admin)
    if (!isset($data['senha']) || empty($data['senha'])) {
        if ($isPublicRegistration) {
            http_response_code(400);
            echo json_encode(['status' => false, 'message' => 'Senha é obrigatória']);
            exit;
        }
        $data['senha'] = 'senha123'; // Senha padrão para usuários criados pelo admin
    }
    
    // Definir tipo padrão
    if (!isset($data['tipo'])) {
        $data['tipo'] = $isPublicRegistration ? USER_TYPE_CLIENT : USER_TYPE_CLIENT;
    }
    
    // Criar usuário
    $result = UserController::createUser($data);
    
    // Retornar resultado
    echo json_encode($result);
}

// Função para tratar requisições PUT (atualizar usuário existente)
function handlePutRequest() {
    // Validar token
    $userData = validateToken();
    
    // Obter dados do corpo da requisição
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validar dados básicos
    if (!$data || !isset($data['userId'])) {
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'ID do usuário não fornecido']);
        exit;
    }
    
    $userId = intval($data['userId']);
    
    // Verificar permissões
    if ($userData['tipo'] !== USER_TYPE_ADMIN && $userId !== $userData['id']) {
        http_response_code(403);
        echo json_encode(['status' => false, 'message' => 'Você só pode editar seu próprio perfil']);
        exit;
    }
    
    // Remover userId dos dados para não tentar atualizar no banco
    unset($data['userId']);
    
    // Atualizar usuário
    $result = UserController::updateUser($userId, $data);
    
    // Retornar resultado
    echo json_encode($result);
}

// Função para tratar requisições DELETE (excluir usuário)
function handleDeleteRequest() {
    // Validar token
    $userData = validateToken();
    
    // Apenas administradores podem excluir usuários
    if ($userData['tipo'] !== USER_TYPE_ADMIN) {
        http_response_code(403);
        echo json_encode(['status' => false, 'message' => 'Apenas administradores podem excluir usuários']);
        exit;
    }
    
    // Obter ID do usuário da URL
    $userId = isset($_GET['id']) ? intval($_GET['id']) : null;
    
    if (!$userId) {
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'ID do usuário não fornecido']);
        exit;
    }
    
    // Não permitir que um administrador exclua a si mesmo
    if ($userId === $userData['id']) {
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'Você não pode excluir seu próprio usuário']);
        exit;
    }
    
    // Excluir usuário
    $result = UserController::deleteUser($userId);
    
    // Retornar resultado
    echo json_encode($result);
}
?>