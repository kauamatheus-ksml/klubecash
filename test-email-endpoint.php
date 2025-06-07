<?php
// test-email-endpoint.php - VERSÃO ULTRA ROBUSTA
// Capturar QUALQUER output indesejado
ob_start();

// Configurar tratamento de erros
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Função para limpar output buffer e enviar JSON
function cleanJsonResponse($status, $message, $data = null, $errorDetails = null) {
    // Limpar qualquer output anterior
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Garantir header JSON
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-cache, must-revalidate');
    
    $response = [
        'status' => $status,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    if ($errorDetails !== null) {
        $response['error_details'] = $errorDetails;
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Tratamento global de erros
set_error_handler(function($severity, $message, $file, $line) {
    cleanJsonResponse(false, "Erro PHP: $message", null, [
        'file' => $file,
        'line' => $line,
        'severity' => $severity
    ]);
});

// Tratamento de exceções não capturadas
set_exception_handler(function($exception) {
    cleanJsonResponse(false, "Exceção não tratada: " . $exception->getMessage(), null, [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ]);
});

try {
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        cleanJsonResponse(false, "Método não permitido: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN'));
    }
    
    // Iniciar sessão com tratamento de erro
    if (session_status() === PHP_SESSION_NONE) {
        if (!session_start()) {
            cleanJsonResponse(false, "Não foi possível iniciar a sessão");
        }
    }
    
    // Verificar autenticação
    if (!isset($_SESSION['user_id'])) {
        cleanJsonResponse(false, "Usuário não está logado");
    }
    
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        cleanJsonResponse(false, "Acesso restrito a administradores");
    }
    
    // Obter ação
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    if (empty($action)) {
        cleanJsonResponse(false, "Ação não especificada");
    }
    
    // Verificar arquivos essenciais ANTES de incluí-los
    $requiredFiles = [
        'config/constants.php',
        'config/database.php'
    ];
    
    foreach ($requiredFiles as $file) {
        if (!file_exists($file)) {
            cleanJsonResponse(false, "Arquivo não encontrado: $file");
        }
        if (!is_readable($file)) {
            cleanJsonResponse(false, "Arquivo não pode ser lido: $file");
        }
    }
    
    // Incluir arquivos com tratamento de erro
    require_once 'config/constants.php';
    require_once 'config/database.php';
    
    // Verificar se as constantes SMTP estão definidas
    $requiredConstants = ['SMTP_HOST', 'SMTP_PORT', 'SMTP_USERNAME', 'SMTP_PASSWORD', 'SMTP_FROM_EMAIL'];
    $missingConstants = [];
    
    foreach ($requiredConstants as $constant) {
        if (!defined($constant)) {
            $missingConstants[] = $constant;
        }
    }
    
    if (!empty($missingConstants)) {
        cleanJsonResponse(false, "Constantes SMTP não definidas: " . implode(', ', $missingConstants));
    }
    
    // Obter dados do admin
    $adminName = $_SESSION['user_name'] ?? 'Administrador';
    $adminEmail = $_SESSION['user_email'] ?? (defined('ADMIN_EMAIL') ? ADMIN_EMAIL : SMTP_USERNAME);
    
    // Processar ações
    switch ($action) {
        case 'ping':
            cleanJsonResponse(true, "Endpoint funcionando", [
                'admin_email' => $adminEmail,
                'session_id' => substr(session_id(), 0, 8) . '...'
            ]);
            break;
            
        case 'check_config':
            cleanJsonResponse(true, "Configurações carregadas", [
                'smtp_host' => SMTP_HOST,
                'smtp_port' => SMTP_PORT,
                'smtp_username' => SMTP_USERNAME,
                'smtp_from_email' => SMTP_FROM_EMAIL,
                'smtp_encryption' => defined('SMTP_ENCRYPTION') ? SMTP_ENCRYPTION : 'ssl',
                'admin_email' => $adminEmail,
                'phpmailer_path' => 'libs/PHPMailer/src/PHPMailer.php',
                'phpmailer_exists' => file_exists('libs/PHPMailer/src/PHPMailer.php')
            ]);
            break;
            
        case 'test_phpmailer_load':
            // Testar se conseguimos carregar o PHPMailer
            $phpmailerPath = 'libs/PHPMailer/src/PHPMailer.php';
            
            if (!file_exists($phpmailerPath)) {
                cleanJsonResponse(false, "PHPMailer não encontrado em: $phpmailerPath");
            }
            
            try {
                require_once 'libs/PHPMailer/src/PHPMailer.php';
                require_once 'libs/PHPMailer/src/SMTP.php';
                require_once 'libs/PHPMailer/src/Exception.php';
                
                if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
                    cleanJsonResponse(false, "Classe PHPMailer não pôde ser carregada");
                }
                
                cleanJsonResponse(true, "PHPMailer carregado com sucesso", [
                    'phpmailer_version' => 'Carregado',
                    'classes_loaded' => [
                        'PHPMailer' => class_exists('PHPMailer\\PHPMailer\\PHPMailer'),
                        'SMTP' => class_exists('PHPMailer\\PHPMailer\\SMTP'),
                        'Exception' => class_exists('PHPMailer\\PHPMailer\\Exception')
                    ]
                ]);
                
            } catch (Exception $e) {
                cleanJsonResponse(false, "Erro ao carregar PHPMailer: " . $e->getMessage());
            }
            break;
            
        case 'test_smtp_basic':
            // Teste básico de conexão SMTP
            if (!file_exists('libs/PHPMailer/src/PHPMailer.php')) {
                cleanJsonResponse(false, "PHPMailer não encontrado");
            }
            
            require_once 'libs/PHPMailer/src/PHPMailer.php';
            require_once 'libs/PHPMailer/src/SMTP.php';
            require_once 'libs/PHPMailer/src/Exception.php';
            
            use PHPMailer\PHPMailer\PHPMailer;
            use PHPMailer\PHPMailer\SMTP;
            use PHPMailer\PHPMailer\Exception;
            
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USERNAME;
                $mail->Password = SMTP_PASSWORD;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Forçar SSL
                $mail->Port = SMTP_PORT;
                $mail->Timeout = 10; // Timeout menor para teste
                
                // Opções SSL permissivas para Hostinger
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];
                
                // Testar conexão
                $connected = $mail->smtpConnect();
                
                if ($connected) {
                    $mail->smtpClose();
                    cleanJsonResponse(true, "Conexão SMTP estabelecida com sucesso!", [
                        'host' => SMTP_HOST,
                        'port' => SMTP_PORT,
                        'secure' => 'SSL'
                    ]);
                } else {
                    cleanJsonResponse(false, "Falha na conexão SMTP", [
                        'error_info' => $mail->ErrorInfo
                    ]);
                }
                
            } catch (Exception $e) {
                cleanJsonResponse(false, "Erro SMTP: " . $e->getMessage(), [
                    'error_code' => $e->getCode(),
                    'smtp_config' => [
                        'host' => SMTP_HOST,
                        'port' => SMTP_PORT,
                        'username' => SMTP_USERNAME
                    ]
                ]);
            }
            break;
            
        case 'send_test_basic':
            // Teste básico de envio de email
            if (!file_exists('libs/PHPMailer/src/PHPMailer.php')) {
                cleanJsonResponse(false, "PHPMailer não encontrado");
            }
            
            require_once 'libs/PHPMailer/src/PHPMailer.php';
            require_once 'libs/PHPMailer/src/SMTP.php';
            require_once 'libs/PHPMailer/src/Exception.php';
            
            use PHPMailer\PHPMailer\PHPMailer;
            use PHPMailer\PHPMailer\SMTP;
            use PHPMailer\PHPMailer\Exception;
            
            try {
                $mail = new PHPMailer(true);
                
                // Configuração SMTP
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USERNAME;
                $mail->Password = SMTP_PASSWORD;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = SMTP_PORT;
                $mail->CharSet = 'UTF-8';
                
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];
                
                // Configuração do email
                $mail->setFrom(SMTP_FROM_EMAIL, 'Klube Cash Teste');
                $mail->addAddress($adminEmail, $adminName);
                $mail->isHTML(true);
                $mail->Subject = 'Teste Básico - Klube Cash';
                $mail->Body = '
                    <h2>🎯 Teste Básico de Email</h2>
                    <p>Este é um teste básico do sistema de email.</p>
                    <p><strong>Enviado em:</strong> ' . date('d/m/Y H:i:s') . '</p>
                    <p><strong>Para:</strong> ' . $adminEmail . '</p>
                    <p>Se você recebeu este email, o sistema básico está funcionando! ✅</p>
                ';
                
                $sent = $mail->send();
                
                if ($sent) {
                    cleanJsonResponse(true, "Email de teste enviado com sucesso para: " . $adminEmail);
                } else {
                    cleanJsonResponse(false, "Falha ao enviar email", [
                        'error_info' => $mail->ErrorInfo
                    ]);
                }
                
            } catch (Exception $e) {
                cleanJsonResponse(false, "Erro ao enviar email: " . $e->getMessage(), [
                    'error_code' => $e->getCode()
                ]);
            }
            break;
            
        default:
            cleanJsonResponse(false, "Ação não reconhecida: $action");
    }
    
} catch (Throwable $e) {
    cleanJsonResponse(false, "Erro fatal: " . $e->getMessage(), null, [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'type' => get_class($e)
    ]);
}
?>