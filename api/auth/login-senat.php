<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:8080, http://192.168.100.53:8080, https://sestsenat.klubecash.com, https://www.sestsenat.klubecash.com');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Tratar requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';

// Verificar se é requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

try {
    // Obter dados da requisição
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Dados inválidos');
    }

    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    $requireSenat = $input['require_senat'] ?? false;

    if (empty($email) || empty($password)) {
        throw new Exception('Email e senha são obrigatórios');
    }

    // Fazer login usando o AuthController
    $loginResult = AuthController::login($email, $password);

    if (!$loginResult['status']) {
        throw new Exception($loginResult['message']);
    }

    // Verificar se é necessário ser usuário do Senat
    if ($requireSenat && (!isset($_SESSION['user_senat']) || $_SESSION['user_senat'] !== 'Sim')) {
        throw new Exception('Acesso negado: Apenas usuários do Senat podem acessar este sistema');
    }

    // Buscar dados completos do usuário
    $db = Database::getConnection();
    $stmt = $db->prepare("
        SELECT id, nome, email, tipo, senat, status
        FROM usuarios
        WHERE id = ? AND status = 'ativo'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('Usuário não encontrado');
    }

    // Verificar novamente se é usuário do Senat
    if ($requireSenat && $user['senat'] !== 'Sim') {
        throw new Exception('Acesso negado: Apenas usuários do Senat podem acessar este sistema');
    }

    // Gerar token de sessão para o SestSenat
    $sessionToken = bin2hex(random_bytes(32));

    // Salvar token na sessão
    $_SESSION['senat_session_token'] = $sessionToken;
    $_SESSION['senat_login_time'] = time();

    // Resposta de sucesso
    echo json_encode([
        'success' => true,
        'message' => 'Login realizado com sucesso',
        'user' => [
            'id' => intval($user['id']),
            'nome' => $user['nome'],
            'email' => $user['email'],
            'tipo' => $user['tipo'],
            'senat' => $user['senat'],
            'status' => $user['status']
        ],
        'session_token' => $sessionToken,
        'redirect_url' => 'http://localhost:8080?session=' . urlencode($sessionToken) . '&email=' . urlencode($user['email']) . '&senat=' . urlencode($user['senat'])
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>