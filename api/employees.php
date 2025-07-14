<?php
// api/employees.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../controllers/AuthController.php';
require_once '../controllers/StoreController.php';

session_start();

// Verificar autenticação
if (!AuthController::hasStoreAccess()) {
    http_response_code(401);
    echo json_encode(['status' => false, 'message' => 'Acesso negado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        if ($action === 'list') {
            handleListEmployees();
        } else {
            handleGetEmployee();
        }
        break;
        
    case 'POST':
        handleCreateEmployee();
        break;
        
    case 'PUT':
        handleUpdateEmployee();
        break;
        
    case 'DELETE':
        handleDeleteEmployee();
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['status' => false, 'message' => 'Método não permitido']);
}

function handleCreateEmployee() {
    if (!AuthController::canManageEmployees()) {
        http_response_code(403);
        echo json_encode(['status' => false, 'message' => 'Permissão negada']);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        $data = $_POST; // Fallback para form-data
    }
    
    $result = StoreController::createEmployee($data);
    
    http_response_code($result['status'] ? 200 : 400);
    echo json_encode($result);
}

function handleListEmployees() {
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 10);
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $subtipo = $_GET['subtipo'] ?? '';
    
    $result = StoreController::listEmployees([
        'page' => $page,
        'limit' => $limit,
        'search' => $search,
        'status' => $status,
        'subtipo' => $subtipo
    ]);
    
    echo json_encode($result);
}

function handleUpdateEmployee() {
    if (!AuthController::canManageEmployees()) {
        http_response_code(403);
        echo json_encode(['status' => false, 'message' => 'Permissão negada']);
        return;
    }
    
    $employeeId = (int)($_GET['id'] ?? 0);
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$employeeId) {
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'ID do funcionário obrigatório']);
        return;
    }
    
    $result = StoreController::updateEmployee($employeeId, $data);
    
    http_response_code($result['status'] ? 200 : 400);
    echo json_encode($result);
}

function handleDeleteEmployee() {
    if (!AuthController::canManageEmployees()) {
        http_response_code(403);
        echo json_encode(['status' => false, 'message' => 'Permissão negada']);
        return;
    }
    
    $employeeId = (int)($_GET['id'] ?? 0);
    
    if (!$employeeId) {
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'ID do funcionário obrigatório']);
        return;
    }
    
    $result = StoreController::deleteEmployee($employeeId);
    
    http_response_code($result['status'] ? 200 : 400);
    echo json_encode($result);
}
?>