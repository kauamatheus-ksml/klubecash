<?php
// controllers/client_actions.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/ClientController.php';
require_once __DIR__ . '/AuthController.php';

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Definir header JSON
header('Content-Type: application/json');

// Verificar se o usuário está autenticado e é cliente
if (!AuthController::isAuthenticated() || !AuthController::isClient()) {
    http_response_code(403);
    echo json_encode(['status' => false, 'message' => 'Acesso negado.']);
    exit;
}

// Verificar se há uma ação solicitada
if (isset($_GET['action']) || isset($_POST['action'])) {
    ClientController::handleAction();
} else {
    echo json_encode(['status' => false, 'message' => 'Nenhuma ação especificada']);
}
?>