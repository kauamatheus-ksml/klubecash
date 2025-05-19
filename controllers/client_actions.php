<?php
// controllers/client_actions.php
require_once __DIR__ . '/ClientController.php';

// Verificar se há uma ação solicitada
if (isset($_GET['action']) || isset($_POST['action'])) {
    ClientController::handleAction();
} else {
    echo json_encode(['status' => false, 'message' => 'Nenhuma ação especificada']);
}
?>