<?php
// test-email-endpoint.php - ENDPOINT SIMPLIFICADO
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Forçar resposta JSON
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Função para log de erros
function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . "EMAIL_TEST: " . $message);
}

// Função para resposta JSON
function jsonResponse($status, $message, $data = null) {
    $response = [
        'status' => $status,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s'),
        'server_info' => [
            'php_version' => PHP_VERSION,
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN'
        ]
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

try {
    logError("Iniciando teste de endpoint");
    
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, "Método não permitido: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN'));
    }
    
    // Verificar se a sessão pode ser iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Verificar se os arquivos necessários existem
    $requiredFiles = [
        'config/database.php',
        'config/constants.php',
        'utils/Email.php'
    ];
    
    $missingFiles = [];
    foreach ($requiredFiles as $file) {
        if (!file_exists($file)) {
            $missingFiles[] = $file;
        }
    }
    
    if (!empty($missingFiles)) {
        jsonResponse(false, "Arquivos não encontrados: " . implode(', ', $missingFiles));
    }
    
    // Incluir arquivos
    require_once 'config/database.php';
    require_once 'config/constants.php';
    require_once 'utils/Email.php';
    
    logError("Arquivos incluídos com sucesso");
    
    // Verificar autenticação
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        jsonResponse(false, "Usuário não autenticado");
    }
    
    if ($_SESSION['user_type'] !== 'admin') {
        jsonResponse(false, "Acesso restrito a administradores");
    }
    
    // Obter ação
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    if (empty($action)) {
        jsonResponse(false, "Ação não especificada");
    }
    
    logError("Executando ação: " . $action);
    
    // Obter dados do admin
    $adminName = $_SESSION['user_name'] ?? 'Administrador';
    $adminEmail = $_SESSION['user_email'] ?? (defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'admin@klubecash.com');
    
    switch ($action) {
        case 'ping':
            jsonResponse(true, "Endpoint funcionando corretamente", [
                'admin_email' => $adminEmail,
                'admin_name' => $adminName,
                'session_id' => session_id()
            ]);
            break;
            
        case 'test_connection':
            logError("Testando conexão SMTP");
            
            if (!class_exists('Email')) {
                jsonResponse(false, "Classe Email não encontrada");
            }
            
            $result = Email::testEmailConnection();
            
            if ($result['status']) {
                jsonResponse(true, "Conexão SMTP OK: " . $result['message'], [
                    'smtp_host' => defined('SMTP_HOST') ? SMTP_HOST : 'N/A'
                ]);
            } else {
                jsonResponse(false, "Falha SMTP: " . $result['message']);
            }
            break;
            
        case 'send_simple':
            logError("Enviando email simples para: " . $adminEmail);
            
            if (!class_exists('Email')) {
                jsonResponse(false, "Classe Email não encontrada");
            }
            
            $result = Email::sendTestEmail($adminEmail, $adminName);
            
            if ($result['status']) {
                jsonResponse(true, "Email simples enviado para: " . $adminEmail);
            } else {
                jsonResponse(false, "Falha ao enviar: " . $result['message']);
            }
            break;
            
        case 'send_2fa':
            logError("Enviando email 2FA para: " . $adminEmail);
            
            if (!class_exists('Email')) {
                jsonResponse(false, "Classe Email não encontrada");
            }
            
            $codigoTeste = '123456';
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            
            $emailSent = Email::send2FACode($adminEmail, $adminName, $codigoTeste, $ipAddress);
            
            if ($emailSent) {
                jsonResponse(true, "Email 2FA enviado para: " . $adminEmail);
            } else {
                jsonResponse(false, "Falha ao enviar email 2FA");
            }
            break;
            
        case 'check_config':
            $config = [
                'smtp_host' => defined('SMTP_HOST') ? SMTP_HOST : 'NÃO DEFINIDO',
                'smtp_port' => defined('SMTP_PORT') ? SMTP_PORT : 'NÃO DEFINIDO',
                'smtp_username' => defined('SMTP_USERNAME') ? SMTP_USERNAME : 'NÃO DEFINIDO',
                'smtp_from_email' => defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'NÃO DEFINIDO',
                'admin_email' => $adminEmail,
                'email_class_exists' => class_exists('Email'),
                'phpmailer_exists' => class_exists('PHPMailer\\PHPMailer\\PHPMailer')
            ];
            
            jsonResponse(true, "Configurações obtidas", $config);
            break;
            
        default:
            jsonResponse(false, "Ação não reconhecida: " . $action);
    }
    
} catch (Throwable $e) {
    logError("ERRO FATAL: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine());
    jsonResponse(false, "Erro interno: " . $e->getMessage());
}
?>