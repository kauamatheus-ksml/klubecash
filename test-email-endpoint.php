<?php
// test-email-endpoint.php - VERSÃO COM DIAGNÓSTICO SMTP AVANÇADO
error_reporting(E_ALL);
ini_set('display_errors', 0);
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
            
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
    
    // Verificar autenticação
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        jsonResponse(false, "Usuário não autenticado");
    }
    
    if ($_SESSION['user_type'] !== 'admin') {
        jsonResponse(false, "Acesso restrito a administradores");
    }
    
    // Incluir arquivos necessários
    require_once 'config/database.php';
    require_once 'config/constants.php';
    
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
            
        case 'check_config':
            $config = [
                'smtp_host' => defined('SMTP_HOST') ? SMTP_HOST : 'NÃO DEFINIDO',
                'smtp_port' => defined('SMTP_PORT') ? SMTP_PORT : 'NÃO DEFINIDO',
                'smtp_username' => defined('SMTP_USERNAME') ? SMTP_USERNAME : 'NÃO DEFINIDO',
                'smtp_from_email' => defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'NÃO DEFINIDO',
                'smtp_encryption' => defined('SMTP_ENCRYPTION') ? SMTP_ENCRYPTION : 'NÃO DEFINIDO',
                'admin_email' => $adminEmail,
                'phpmailer_exists' => class_exists('PHPMailer\\PHPMailer\\PHPMailer'),
                'email_file_exists' => file_exists('utils/Email.php'),
                'constants_loaded' => defined('SMTP_HOST')
            ];
            
            jsonResponse(true, "Configurações obtidas", $config);
            break;
            
        case 'test_connection_manual':
            // Teste manual da conexão SMTP sem usar a classe Email
            logError("Testando conexão SMTP manualmente");
            
            // Carregar PHPMailer diretamente
            if (!file_exists('libs/PHPMailer/src/PHPMailer.php')) {
                jsonResponse(false, "PHPMailer não encontrado em libs/PHPMailer/src/");
            }
            
            require_once 'libs/PHPMailer/src/PHPMailer.php';
            require_once 'libs/PHPMailer/src/SMTP.php';
            require_once 'libs/PHPMailer/src/Exception.php';
            
        
        
            try {
                $mail = new PHPMailer(true);
                
                // Configurações básicas
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USERNAME;
                $mail->Password = SMTP_PASSWORD;
                $mail->SMTPSecure = SMTP_ENCRYPTION === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = SMTP_PORT;
                $mail->CharSet = 'UTF-8';
                $mail->Timeout = 30;
                
                // Configurações SSL para Hostinger
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];
                
                // Ativar debug
                $mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
                
                // Capturar debug em buffer
                ob_start();
                $result = $mail->smtpConnect();
                $debugInfo = ob_get_clean();
                
                if ($result) {
                    $mail->smtpClose();
                    jsonResponse(true, "Conexão SMTP manual estabelecida com sucesso!", [
                        'debug_info' => $debugInfo,
                        'smtp_config' => [
                            'host' => SMTP_HOST,
                            'port' => SMTP_PORT,
                            'encryption' => SMTP_ENCRYPTION
                        ]
                    ]);
                } else {
                    jsonResponse(false, "Falha na conexão SMTP manual", [
                        'debug_info' => $debugInfo,
                        'error_info' => $mail->ErrorInfo
                    ]);
                }
                
            } catch (Exception $e) {
                jsonResponse(false, "Erro na conexão SMTP: " . $e->getMessage(), [
                    'error_code' => $e->getCode(),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine()
                ]);
            }
            break;
            
        case 'send_simple_manual':
            // Envio manual de email sem usar a classe Email
            logError("Enviando email simples manualmente");
            
            if (!file_exists('libs/PHPMailer/src/PHPMailer.php')) {
                jsonResponse(false, "PHPMailer não encontrado");
            }
            
            require_once 'libs/PHPMailer/src/PHPMailer.php';
            require_once 'libs/PHPMailer/src/SMTP.php';
            require_once 'libs/PHPMailer/src/Exception.php';
            
            
        

            try {
                $mail = new PHPMailer(true);
                
                // Configurações do servidor
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USERNAME;
                $mail->Password = SMTP_PASSWORD;
                $mail->SMTPSecure = SMTP_ENCRYPTION === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = SMTP_PORT;
                $mail->CharSet = 'UTF-8';
                $mail->Timeout = 30;
                
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];
                
                // Configurações do email
                $mail->setFrom(SMTP_FROM_EMAIL, 'Klube Cash - Teste');
                $mail->addAddress($adminEmail, $adminName);
                $mail->isHTML(true);
                $mail->Subject = 'Teste Manual - Klube Cash';
                $mail->Body = '
                    <h2>✅ Teste Manual de Email</h2>
                    <p>Este email foi enviado através de teste manual do endpoint.</p>
                    <p><strong>Data/Hora:</strong> ' . date('d/m/Y H:i:s') . '</p>
                    <p><strong>Servidor:</strong> ' . SMTP_HOST . '</p>
                    <p>Se você recebeu este email, o sistema está funcionando!</p>
                ';
                
                // Capturar debug
                ob_start();
                $result = $mail->send();
                $debugInfo = ob_get_clean();
                
                if ($result) {
                    jsonResponse(true, "Email manual enviado com sucesso para: " . $adminEmail, [
                        'debug_info' => $debugInfo
                    ]);
                } else {
                    jsonResponse(false, "Falha ao enviar email manual", [
                        'error_info' => $mail->ErrorInfo,
                        'debug_info' => $debugInfo
                    ]);
                }
                
            } catch (Exception $e) {
                jsonResponse(false, "Erro ao enviar email: " . $e->getMessage(), [
                    'error_code' => $e->getCode(),
                    'error_details' => $e->getTraceAsString()
                ]);
            }
            break;
            
        case 'test_connection':
            // Teste usando a classe Email
            if (!file_exists('utils/Email.php')) {
                jsonResponse(false, "Arquivo utils/Email.php não encontrado");
            }
            
            require_once 'utils/Email.php';
            
            if (!class_exists('Email')) {
                jsonResponse(false, "Classe Email não pode ser carregada");
            }
            
            $result = Email::testEmailConnection();
            
            if ($result['status']) {
                jsonResponse(true, "Conexão SMTP via classe Email: " . $result['message'], $result);
            } else {
                jsonResponse(false, "Falha SMTP via classe Email: " . $result['message'], $result);
            }
            break;
            
        case 'send_simple':
            // Teste usando a classe Email
            if (!file_exists('utils/Email.php')) {
                jsonResponse(false, "Arquivo utils/Email.php não encontrado");
            }
            
            require_once 'utils/Email.php';
            
            if (!class_exists('Email')) {
                jsonResponse(false, "Classe Email não pode ser carregada");
            }
            
            $result = Email::sendTestEmail($adminEmail, $adminName);
            
            if ($result['status']) {
                jsonResponse(true, "Email via classe Email enviado para: " . $adminEmail);
            } else {
                jsonResponse(false, "Falha ao enviar via classe Email: " . $result['message']);
            }
            break;
            
        case 'send_2fa':
            // Teste 2FA usando a classe Email
            if (!file_exists('utils/Email.php')) {
                jsonResponse(false, "Arquivo utils/Email.php não encontrado");
            }
            
            require_once 'utils/Email.php';
            
            if (!class_exists('Email')) {
                jsonResponse(false, "Classe Email não pode ser carregada");
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
            
        default:
            jsonResponse(false, "Ação não reconhecida: " . $action);
    }
    
} catch (Throwable $e) {
    logError("ERRO FATAL: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine());
    jsonResponse(false, "Erro interno: " . $e->getMessage() . " (Linha " . $e->getLine() . ")");
}
?>