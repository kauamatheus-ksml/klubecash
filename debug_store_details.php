<?php
// debug_store_details.php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once 'config/database.php';
    require_once 'config/constants.php';
    require_once 'controllers/AuthController.php';
    require_once 'controllers/ClientController.php';

    echo json_encode([
        'step' => 1,
        'message' => 'Arquivos carregados com sucesso',
        'session_exists' => isset($_SESSION['user_id']),
        'user_id' => $_SESSION['user_id'] ?? null,
        'user_type' => $_SESSION['user_type'] ?? null,
        'get_params' => $_GET,
        'constants_ok' => defined('STORE_APPROVED')
    ]);

} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>