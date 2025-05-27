<?php
// test_action.php
session_start();
require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/ClientController.php';

header('Content-Type: application/json');

echo json_encode([
    'session' => $_SESSION,
    'authenticated' => AuthController::isAuthenticated(),
    'is_client' => AuthController::isClient(),
    'user_id' => AuthController::getCurrentUserId(),
    'get_params' => $_GET,
    'post_params' => $_POST
]);
?>