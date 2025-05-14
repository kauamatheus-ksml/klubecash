<?php
// api/payments.php
// API para gerenciar pagamentos de comissões do sistema Klube Cash

// Configurações iniciais
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Se for requisição OPTIONS (preflight), encerra a execução
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Incluir arquivos necessários
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/TransactionController.php';
require_once __DIR__ . '/../controllers/AdminController.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../utils/Security.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/FileUpload.php';

// Função para validar token JWT
function validateToken() {
    // Obter token do cabeçalho Authorization
    $headers = getallheaders();
    $auth = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (empty($auth) || !preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
        http_response_code(401);
        echo json_encode(['status' => false, 'message' => 'Token de autenticação não fornecido']);
        exit;
    }
    
    $token = $matches[1];
    
    // Validar token
    $decoded = Security::validateJWT($token);
    
    if (!$decoded) {
        http_response_code(401);
        echo json_encode(['status' => false, 'message' => 'Token de autenticação inválido ou expirado']);
        exit;
    }
    
    return $decoded;
}

// Processar a requisição com base no método HTTP
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
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

// Função para tratar requisições GET (consulta de pagamentos)
function handleGetRequest() {
    // Validar token
    $userData = validateToken();
    
    // Obter parâmetros da URL
    $paymentId = isset($_GET['id']) ? intval($_GET['id']) : null;
    $storeId = isset($_GET['loja_id']) ? intval($_GET['loja_id']) : null;
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $filters = [];
    
    // Aplicar filtros se fornecidos
    if (isset($_GET['status'])) $filters['status'] = $_GET['status'];
    if (isset($_GET['metodo_pagamento'])) $filters['metodo_pagamento'] = $_GET['metodo_pagamento'];
    if (isset($_GET['data_inicio'])) $filters['data_inicio'] = $_GET['data_inicio'];
    if (isset($_GET['data_fim'])) $filters['data_fim'] = $_GET['data_fim'];
    if (isset($_GET['valor_min'])) $filters['valor_min'] = floatval($_GET['valor_min']);
    if (isset($_GET['valor_max'])) $filters['valor_max'] = floatval($_GET['valor_max']);
    
    // Verificar tipo de usuário e redirecionar para o processamento apropriado
    if ($userData['tipo'] === USER_TYPE_ADMIN) {
        // Administrador pode ver todos os pagamentos
        if ($paymentId) {
            // Detalhes de um pagamento específico
            $result = TransactionController::getPaymentDetails($paymentId);
            echo json_encode($result);
        } else if ($storeId) {
            // Pagamentos de uma loja específica
            $result = TransactionController::getPaymentHistory($storeId, $filters, $page);
            echo json_encode($result);
        } else {
            // Busca geral de pagamentos com paginação
            $result = Payment::find($filters, $page);
            
            // Calcular estatísticas
            $stats = Payment::calculateStats($filters);
            
            echo json_encode([
                'status' => true, 
                'data' => [
                    'pagamentos' => array_map(function($payment) {
                        return $payment->toArray();
                    }, $result['payments']),
                    'estatisticas' => $stats,
                    'paginacao' => $result['pagination']
                ]
            ]);
        }
    } else if ($userData['tipo'] === USER_TYPE_STORE) {
        // Loja só pode ver seus próprios pagamentos
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id FROM lojas WHERE usuario_id = ?");
        $stmt->execute([$userData['id']]);
        $store = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$store) {
            http_response_code(404);
            echo json_encode(['status' => false, 'message' => 'Loja não encontrada para este usuário']);
            exit;
        }
        
        $storeId = $store['id'];
        
        if ($paymentId) {
            // Verificar se o pagamento pertence à loja
            $payment = new Payment();
            if (!$payment->loadById($paymentId)) {
                http_response_code(404);
                echo json_encode(['status' => false, 'message' => 'Pagamento não encontrado']);
                exit;
            }
            
            if ($payment->getLojaId() != $storeId) {
                http_response_code(403);
                echo json_encode(['status' => false, 'message' => 'Acesso não autorizado a este pagamento']);
                exit;
            }
            
            // Carregar transações associadas
            $payment->loadTransactions();
            echo json_encode(['status' => true, 'data' => $payment->toArray(true)]);
        } else {
            // Listar histórico de pagamentos da loja
            $result = TransactionController::getPaymentHistory($storeId, $filters, $page);
            echo json_encode($result);
        }
    } else {
        // Clientes não têm acesso a pagamentos
        http_response_code(403);
        echo json_encode(['status' => false, 'message' => 'Acesso não autorizado para o tipo de usuário']);
    }
}

// Função para tratar requisições POST (registrar pagamento)
function handlePostRequest() {
    // Validar token
    $userData = validateToken();
    
    // Verificar ação solicitada
    $action = isset($_GET['action']) ? $_GET['action'] : 'register';
    
    switch ($action) {
        case 'register':
            // Registrar novo pagamento
            if ($userData['tipo'] !== USER_TYPE_STORE && $userData['tipo'] !== USER_TYPE_ADMIN) {
                http_response_code(403);
                echo json_encode(['status' => false, 'message' => 'Apenas lojas e administradores podem registrar pagamentos']);
                exit;
            }
            
            // Obter dados do corpo da requisição
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validar dados básicos
            if (!$data || !isset($data['loja_id']) || !isset($data['transacoes']) || 
                !isset($data['valor_total']) || !isset($data['metodo_pagamento'])) {
                http_response_code(400);
                echo json_encode(['status' => false, 'message' => 'Dados incompletos para registrar pagamento']);
                exit;
            }
            
            // Se for loja, verificar se está pagando suas próprias transações
            if ($userData['tipo'] === USER_TYPE_STORE) {
                $db = Database::getConnection();
                $stmt = $db->prepare("SELECT id FROM lojas WHERE usuario_id = ?");
                $stmt->execute([$userData['id']]);
                $store = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$store || $store['id'] != $data['loja_id']) {
                    http_response_code(403);
                    echo json_encode(['status' => false, 'message' => 'Você só pode registrar pagamentos para sua própria loja']);
                    exit;
                }
            }
            
            // Registrar pagamento
            $result = TransactionController::registerPayment($data);
            echo json_encode($result);
            break;
            
        case 'upload_receipt':
            // Upload de comprovante de pagamento
            if ($userData['tipo'] !== USER_TYPE_STORE && $userData['tipo'] !== USER_TYPE_ADMIN) {
                http_response_code(403);
                echo json_encode(['status' => false, 'message' => 'Apenas lojas e administradores podem enviar comprovantes']);
                exit;
            }
            
            // Verificar se foi enviado um arquivo
            if (!isset($_FILES['comprovante']) || $_FILES['comprovante']['error'] !== UPLOAD_ERR_OK) {
                http_response_code(400);
                echo json_encode(['status' => false, 'message' => 'Nenhum arquivo enviado ou erro no upload']);
                exit;
            }
            
            // Verificar ID do pagamento
            $paymentId = isset($_POST['payment_id']) ? intval($_POST['payment_id']) : null;
            if (!$paymentId) {
                http_response_code(400);
                echo json_encode(['status' => false, 'message' => 'ID do pagamento não fornecido']);
                exit;
            }
            
            // Carregar pagamento
            $payment = new Payment();
            if (!$payment->loadById($paymentId)) {
                http_response_code(404);
                echo json_encode(['status' => false, 'message' => 'Pagamento não encontrado']);
                exit;
            }
            
            // Se for loja, verificar se o pagamento pertence a ela
            if ($userData['tipo'] === USER_TYPE_STORE) {
                $db = Database::getConnection();
                $stmt = $db->prepare("SELECT id FROM lojas WHERE usuario_id = ?");
                $stmt->execute([$userData['id']]);
                $store = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$store || $store['id'] != $payment->getLojaId()) {
                    http_response_code(403);
                    echo json_encode(['status' => false, 'message' => 'Você só pode enviar comprovantes para seus próprios pagamentos']);
                    exit;
                }
            }
            
            // Fazer upload do comprovante
            $fileName = $payment->uploadComprovante($_FILES['comprovante']);
            
            if (!$fileName) {
                http_response_code(500);
                echo json_encode(['status' => false, 'message' => 'Erro ao fazer upload do comprovante']);
                exit;
            }
            
            // Salvar referência no banco
            $payment->save();
            
            echo json_encode([
                'status' => true, 
                'message' => 'Comprovante enviado com sucesso', 
                'data' => ['comprovante' => $fileName]
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['status' => false, 'message' => 'Ação não especificada ou inválida']);
            break;
    }
}

// Função para tratar requisições PUT (aprovar/rejeitar pagamento)
function handlePutRequest() {
    // Validar token
    $userData = validateToken();
    
    // Apenas administradores podem aprovar/rejeitar pagamentos
    if ($userData['tipo'] !== USER_TYPE_ADMIN) {
        http_response_code(403);
        echo json_encode(['status' => false, 'message' => 'Apenas administradores podem aprovar ou rejeitar pagamentos']);
        exit;
    }
    
    // Obter dados do corpo da requisição
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validar dados básicos
    if (!$data || !isset($data['id']) || !isset($data['action'])) {
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'Dados incompletos para processar pagamento']);
        exit;
    }
    
    $paymentId = intval($data['id']);
    $action = $data['action'];
    
    // Processar a ação solicitada
    if ($action === 'approve') {
        // Aprovar pagamento
        $observacao = isset($data['observacao']) ? $data['observacao'] : '';
        $result = TransactionController::approvePayment($paymentId, $observacao);
    } else if ($action === 'reject') {
        // Rejeitar pagamento
        if (!isset($data['motivo']) || empty($data['motivo'])) {
            http_response_code(400);
            echo json_encode(['status' => false, 'message' => 'É necessário informar o motivo da rejeição']);
            exit;
        }
        
        $result = TransactionController::rejectPayment($paymentId, $data['motivo']);
    } else {
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'Ação inválida. Use "approve" ou "reject"']);
        exit;
    }
    
    // Retornar resultado
    echo json_encode($result);
}

// Função para tratar requisições DELETE (cancelar pagamento)
function handleDeleteRequest() {
    // Validar token
    $userData = validateToken();
    
    // Apenas administradores e lojas podem cancelar pagamentos pendentes
    if ($userData['tipo'] !== USER_TYPE_ADMIN && $userData['tipo'] !== USER_TYPE_STORE) {
        http_response_code(403);
        echo json_encode(['status' => false, 'message' => 'Acesso não autorizado']);
        exit;
    }
    
    // Obter ID do pagamento da URL
    $paymentId = isset($_GET['id']) ? intval($_GET['id']) : null;
    
    if (!$paymentId) {
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'ID do pagamento não fornecido']);
        exit;
    }
    
    // Carregar pagamento
    $payment = new Payment();
    if (!$payment->loadById($paymentId)) {
        http_response_code(404);
        echo json_encode(['status' => false, 'message' => 'Pagamento não encontrado']);
        exit;
    }
    
    // Verificar status do pagamento
    if ($payment->getStatus() !== 'pendente') {
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'Apenas pagamentos pendentes podem ser cancelados']);
        exit;
    }
    
    // Se for loja, verificar se o pagamento pertence a ela
    if ($userData['tipo'] === USER_TYPE_STORE) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id FROM lojas WHERE usuario_id = ?");
        $stmt->execute([$userData['id']]);
        $store = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$store || $store['id'] != $payment->getLojaId()) {
            http_response_code(403);
            echo json_encode(['status' => false, 'message' => 'Você só pode cancelar seus próprios pagamentos']);
            exit;
        }
    }
    
    // Cancelar pagamento (usando rejeição com motivo específico)
    $motivo = 'Pagamento cancelado ' . 
              ($userData['tipo'] === USER_TYPE_ADMIN ? 'pelo administrador' : 'pela loja');
    
    $result = TransactionController::rejectPayment($paymentId, $motivo);
    
    // Retornar resultado
    echo json_encode($result);
}

// Para qualquer erro não tratado anteriormente
function handleError($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'status' => false, 
        'message' => 'Erro interno do servidor',
        'error' => $errstr,
        'file' => $errfile,
        'line' => $errline
    ]);
    exit;
}

// Registrar manipulador de erros
set_error_handler('handleError');