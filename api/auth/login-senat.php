<?php
// api/auth/login-senat.php - API de login específica para SestSenat
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:8082');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['email']) || !isset($input['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email e senha são obrigatórios']);
        exit;
    }

    $email = filter_var($input['email'], FILTER_SANITIZE_EMAIL);
    $password = $input['password'];

    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email e senha são obrigatórios']);
        exit;
    }

    // Attempt login using existing AuthController
    session_start();
    $result = AuthController::login($email, $password);

    if (!$result['status']) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => $result['message']]);
        exit;
    }

    // Check if user has Senat access
    $userSenat = $_SESSION['user_senat'] ?? 'Não';
    if ($userSenat !== 'Sim') {
        // Clear session for non-Senat users
        session_destroy();
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Acesso negado. Apenas usuários do Senat podem acessar este portal.'
        ]);
        exit;
    }

    // Success - return user data
    $userData = [
        'id' => $_SESSION['user_id'],
        'nome' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'tipo' => $_SESSION['user_type'],
        'senat' => $_SESSION['user_senat'],
        'status' => 'ativo'
    ];

    echo json_encode([
        'success' => true,
        'message' => 'Login realizado com sucesso',
        'user' => $userData
    ]);

} catch (Exception $e) {
    error_log("Erro no login SestSenat: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
}
?>