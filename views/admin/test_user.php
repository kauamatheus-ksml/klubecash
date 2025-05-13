<?php
require_once '../../controllers/AuthController.php';
require_once '../../controllers/AdminController.php';

// Forçar saída em JSON
header('Content-Type: application/json');

try {
    $userId = 14; // O ID que está tentando editar
    $result = AdminController::getUserDetails($userId);
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => 'Exceção: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>