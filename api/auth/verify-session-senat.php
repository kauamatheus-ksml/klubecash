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

// Verificar se é requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

try {
    // Iniciar sessão se não estiver ativa
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Obter dados da requisição
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Dados inválidos');
    }

    $sessionToken = $input['session_id'] ?? '';
    $email = $input['email'] ?? '';
    $requireSenat = $input['require_senat'] ?? false;

    if (empty($sessionToken) || empty($email)) {
        throw new Exception('Token de sessão e email são obrigatórios');
    }

    // Verificar se o token de sessão é válido
    if (!isset($_SESSION['senat_session_token']) || $_SESSION['senat_session_token'] !== $sessionToken) {
        throw new Exception('Token de sessão inválido');
    }

    // Verificar se a sessão não expirou (24 horas)
    $loginTime = $_SESSION['senat_login_time'] ?? 0;
    $currentTime = time();
    $sessionDuration = 24 * 60 * 60; // 24 horas

    if (($currentTime - $loginTime) > $sessionDuration) {
        throw new Exception('Sessão expirada');
    }

    // Verificar se há usuário logado
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Usuário não está logado');
    }

    // Buscar dados do usuário
    $db = Database::getConnection();
    $stmt = $db->prepare("
        SELECT id, nome, email, tipo, senat, status
        FROM usuarios
        WHERE id = ? AND email = ? AND status = 'ativo'
    ");
    $stmt->execute([$_SESSION['user_id'], $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('Usuário não encontrado ou inativo');
    }

    // Verificar se é usuário do Senat (se requerido)
    if ($requireSenat && $user['senat'] !== 'Sim') {
        throw new Exception('Acesso negado: Apenas usuários do Senat podem acessar este sistema');
    }

    // Atualizar tempo da sessão
    $_SESSION['senat_login_time'] = $currentTime;

    // Resposta de sucesso
    echo json_encode([
        'success' => true,
        'message' => 'Sessão válida',
        'user' => [
            'id' => intval($user['id']),
            'nome' => $user['nome'],
            'email' => $user['email'],
            'tipo' => $user['tipo'],
            'senat' => $user['senat'],
            'status' => $user['status']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>