<?php
// utils/Email.php
require_once __DIR__ . '/../config/constants.php';

// Importar PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// CORREÇÃO: Tentar carregar via Composer primeiro, se falhar usar libs manuais
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    // Fallback para carregamento manual (mantendo código original)
    require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';
    require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
}

/**
 * Classe Email - Utilitário para envio de emails
 * 
 * Esta classe gerencia o envio de emails utilizando o PHPMailer
 * e fornece métodos específicos para diferentes tipos de mensagens.
 */
class Email {
    // Configurações de SMTP (definidas em constants.php)
    private static $host;
    private static $port;
    private static $username;
    private static $password;
    private static $fromEmail;
    private static $fromName;
    private static $encryption;
    
    /**
     * Inicializa as configurações de SMTP
     */
    private static function init() {
        // Verificar se as constantes estão definidas
        if (defined('SMTP_HOST')) {
            self::$host = SMTP_HOST;
            self::$port = defined('SMTP_PORT') ? SMTP_PORT : 587; // CORREÇÃO: Mudei para 587 padrão
            self::$username = defined('SMTP_USERNAME') ? SMTP_USERNAME : 'klubecash@klubecash.com';
            self::$password = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : 'Aaku_2004@';
            self::$fromEmail = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'noreply@klubecash.com';
            self::$fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Klube Cash';
            // CORREÇÃO: Configuração dinâmica baseada na porta
            if (defined('SMTP_ENCRYPTION')) {
                self::$encryption = SMTP_ENCRYPTION;
            } else {
                self::$encryption = (self::$port == 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            }
        } else {
            // Configurações padrão se as constantes não estiverem definidas
            self::$host = 'smtp.hostinger.com';
            self::$port = 587; // CORREÇÃO: Mudei para 587 padrão
            self::$username = 'klubecash@klubecash.com';
            self::$password = 'Aaku_2004@';
            self::$fromEmail = 'noreply@klubecash.com';
            self::$fromName = 'Klube Cash';
            self::$encryption = PHPMailer::ENCRYPTION_STARTTLS; // TLS para porta 587
            
            // Registrar aviso
            error_log('Constantes SMTP não encontradas. Utilizando valores padrão.');
        }
        
        // ADIÇÃO: Log das configurações para debug
        error_log("Email Config - Host: " . self::$host . ", Port: " . self::$port . ", Encryption: " . self::$encryption);
    }
    
    /**
     * Envia um email
     * 
     * @param string $to Email do destinatário
     * @param string $subject Assunto do email
     * @param string $message Corpo do email (HTML)
     * @param string $toName Nome do destinatário (opcional)
     * @param array $attachments Arquivos anexos (opcional)
     * @return bool Verdadeiro se enviado com sucesso
     */
    public static function send($to, $subject, $message, $toName = '', $attachments = []) {
        // Inicializar configurações
        self::init();
        
        try {
            $mail = new PHPMailer(true);
            
            // Configurações de servidor
            $mail->isSMTP();
            $mail->Host       = self::$host;
            $mail->SMTPAuth   = true;
            $mail->Username   = self::$username;
            $mail->Password   = self::$password;
            $mail->SMTPSecure = self::$encryption;
            $mail->Port       = self::$port;
            $mail->CharSet    = 'UTF-8';
            
            // ADIÇÃO: Timeout aumentado para servidores lentos
            $mail->Timeout = 60;
            
            // ADIÇÃO: Configurações SSL mais permissivas para servidores compartilhados
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            
            // Configurações para ambiente de desenvolvimento
            if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;
                $mail->Debugoutput = 'error_log'; // ADIÇÃO: Redirecionar debug para log
            }
            
            // Remetente
            $mail->setFrom(self::$fromEmail, self::$fromName);
            $mail->addReplyTo(self::$fromEmail, self::$fromName);
            
            // Destinatário
            $mail->addAddress($to, $toName);
            
            // Anexos
            if (!empty($attachments) && is_array($attachments)) {
                foreach ($attachments as $attachment) {
                    if (file_exists($attachment)) {
                        $mail->addAttachment($attachment);
                    }
                }
            }
            
            // Conteúdo
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = self::applyTemplate($message);
            $mail->AltBody = self::stripHtml($message);
            
            // Enviar o email
            $result = $mail->send();
            
            // ADIÇÃO: Log detalhado do resultado
            if ($result) {
                error_log("Email enviado com sucesso para: " . $to . " - Assunto: " . $subject);
            } else {
                error_log("Falha ao enviar email para: " . $to . " - Erro: " . $mail->ErrorInfo);
            }
            
            return $result;
        } catch (Exception $e) {
            // CORREÇÃO: Log mais detalhado do erro
            error_log('ERRO CRÍTICO ao enviar email: ' . $e->getMessage());
            error_log('SMTP Debug Info: ' . ($mail->ErrorInfo ?? 'N/A'));
            error_log('Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Aplica um template ao conteúdo do email
     * 
     * @param string $content Conteúdo do email
     * @return string Conteúdo com template aplicado
     */
    private static function applyTemplate($content) {
        // Template HTML para emails
        $template = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Klube Cash</title>
            <style>
                body { 
                    font-family: Arial, Helvetica, sans-serif; 
                    line-height: 1.6; 
                    color: #333333; 
                    margin: 0;
                    padding: 0;
                    background-color: #f5f5f5;
                }
                .container {
                    max-width: 600px; 
                    margin: 0 auto; 
                    background-color: #ffffff; 
                    padding: 20px;
                    border-radius: 5px;
                    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
                }
                .header { 
                    background-color: #FF7A00; 
                    color: white; 
                    padding: 20px; 
                    text-align: center; 
                    border-radius: 5px 5px 0 0;
                }
                .content { 
                    padding: 20px; 
                }
                .footer { 
                    background-color: #f9f9f9; 
                    padding: 15px; 
                    text-align: center; 
                    font-size: 12px; 
                    color: #999999; 
                    border-radius: 0 0 5px 5px;
                }
                .btn { 
                    display: inline-block; 
                    background-color: #FF7A00; 
                    color: white; 
                    padding: 10px 20px; 
                    text-decoration: none; 
                    border-radius: 5px; 
                    margin: 15px 0;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                }
                table th, table td {
                    padding: 10px;
                    border: 1px solid #ddd;
                }
                table th {
                    background-color: #f2f2f2;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Klube Cash</h1>
                </div>
                <div class="content">
                    ' . $content . '
                </div>
                <div class="footer">
                    <p>&copy; ' . date('Y') . ' Klube Cash. Todos os direitos reservados.</p>
                    <p>Este é um email automático, por favor não responda.</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $template;
    }
    
    /**
     * Remove tags HTML para criar versão em texto puro
     * 
     * @param string $html Conteúdo HTML
     * @return string Conteúdo em texto puro
     */
    private static function stripHtml($html) {
        // Substituir tags comuns por quebras de linha
        $text = str_replace(['<br>', '<br/>', '<br />', '<p>', '</p>', '<div>', '</div>'], "\n", $html);
        
        // Remover todas as outras tags
        $text = strip_tags($text);
        
        // Converter entidades HTML
        $text = html_entity_decode($text);
        
        // Remover espaços em excesso
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }
    
    /**
     * Envia um email de boas-vindas
     * 
     * @param string $to Email do destinatário
     * @param string $name Nome do destinatário
     * @return bool Verdadeiro se enviado com sucesso
     */
    public static function sendWelcome($to, $name) {
        $subject = 'Bem-vindo ao Klube Cash';
        
        $message = '
        <h2>Olá, ' . htmlspecialchars($name) . '!</h2>
        <p>Seja bem-vindo ao Klube Cash! Estamos felizes em tê-lo conosco.</p>
        <p>Com o Klube Cash, você pode ganhar cashback em suas compras nas lojas parceiras.</p>
        <p>Para começar, acesse sua conta e explore as lojas parceiras disponíveis.</p>
        <p><a href="' . SITE_URL . '/views/auth/login.php" class="btn">Acessar Minha Conta</a></p>
        <p>Se você tiver alguma dúvida, não hesite em entrar em contato conosco.</p>
        <p>Atenciosamente,<br>Equipe Klube Cash</p>';
        
        return self::send($to, $subject, $message, $name);
    }
    
    /**
     * Envia um email de recuperação de senha
     * 
     * @param string $to Email do destinatário
     * @param string $name Nome do destinatário
     * @param string $token Token de recuperação
     * @return bool Verdadeiro se enviado com sucesso
     */
    public static function sendPasswordRecovery($to, $name, $token) {
        $subject = 'Recuperação de Senha - Klube Cash';
        
        // CORREÇÃO: URL corrigida para recuperação de senha
        $resetLink = SITE_URL . '/recuperar-senha?token=' . urlencode($token);
        
        $message = '
        <h2>Olá, ' . htmlspecialchars($name) . '!</h2>
        <p>Recebemos uma solicitação para redefinir sua senha no Klube Cash.</p>
        <p>Para redefinir sua senha, clique no botão abaixo:</p>
        <p><a href="' . $resetLink . '" class="btn">Redefinir Minha Senha</a></p>
        <p><strong>Este link é válido por 2 horas.</strong></p>
        <p>Se você não solicitou esta alteração, por favor ignore este email.</p>
        <p>Atenciosamente,<br>Equipe Klube Cash</p>';
        
        return self::send($to, $subject, $message, $name);
    }
    
    /**
     * Envia um email de notificação de nova transação
     * 
     * @param string $to Email do destinatário
     * @param string $name Nome do destinatário
     * @param array $transaction Dados da transação
     * @return bool Verdadeiro se enviado com sucesso
     */
    public static function sendTransactionNotification($to, $name, $transaction) {
        $subject = 'Nova Transação - Klube Cash';
        
        $message = '
        <h2>Olá, ' . htmlspecialchars($name) . '!</h2>
        <p>Você acabou de receber cashback de uma compra:</p>
        <table>
            <tr>
                <th>Loja</th>
                <td>' . htmlspecialchars($transaction['nome_loja']) . '</td>
            </tr>
            <tr>
                <th>Valor da Compra</th>
                <td>R$ ' . number_format($transaction['valor_total'], 2, ',', '.') . '</td>
            </tr>
            <tr>
                <th>Cashback</th>
                <td>R$ ' . number_format($transaction['valor_cashback'], 2, ',', '.') . '</td>
            </tr>
            <tr>
                <th>Data</th>
                <td>' . date('d/m/Y H:i', strtotime($transaction['data_transacao'])) . '</td>
            </tr>
        </table>
        <p>Para ver todos os detalhes, acesse seu extrato:</p>
        <p><a href="' . SITE_URL . '/views/client/statement.php" class="btn">Ver Meu Extrato</a></p>
        <p>Atenciosamente,<br>Equipe Klube Cash</p>';
        
        return self::send($to, $subject, $message, $name);
    }
    
    /**
     * Envia um email de notificação de aprovação de loja
     * 
     * @param string $to Email do destinatário
     * @param string $storeName Nome da loja
     * @return bool Verdadeiro se enviado com sucesso
     */
    public static function sendStoreApproval($to, $storeName) {
        $subject = 'Loja Aprovada - Klube Cash';
        
        $message = '
        <h2>Parabéns!</h2>
        <p>Sua loja <strong>' . htmlspecialchars($storeName) . '</strong> foi aprovada no Klube Cash!</p>
        <p>Agora seus clientes podem começar a receber cashback em suas compras.</p>
        <p>Para acessar seu painel e gerenciar suas transações, clique no botão abaixo:</p>
        <p><a href="' . SITE_URL . '/views/auth/login.php" class="btn">Acessar Meu Painel</a></p>
        <p>Atenciosamente,<br>Equipe Klube Cash</p>';
        
        return self::send($to, $subject, $message, $storeName);
    }
    
    /**
     * Envia um email de notificação de rejeição de loja
     * 
     * @param string $to Email do destinatário
     * @param string $storeName Nome da loja
     * @param string $reason Motivo da rejeição
     * @return bool Verdadeiro se enviado com sucesso
     */
    public static function sendStoreRejection($to, $storeName, $reason = '') {
        $subject = 'Loja Não Aprovada - Klube Cash';
        
        $message = '
        <h2>Olá!</h2>
        <p>Infelizmente, sua loja <strong>' . htmlspecialchars($storeName) . '</strong> não foi aprovada no Klube Cash.</p>';
        
        if (!empty($reason)) {
            $message .= '<p><strong>Motivo:</strong> ' . htmlspecialchars($reason) . '</p>';
        }
        
        $message .= '
        <p>Se tiver dúvidas ou quiser mais informações, entre em contato conosco.</p>
        <p>Atenciosamente,<br>Equipe Klube Cash</p>';
        
        return self::send($to, $subject, $message, $storeName);
    }
    
    /**
     * Envia um email para o administrador
     * 
     * @param string $subject Assunto do email
     * @param string $message Corpo do email
     * @return bool Verdadeiro se enviado com sucesso
     */
    public static function sendToAdmin($subject, $message) {
        // Verifica se o email do administrador está definido
        $adminEmail = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'admin@klubecash.com';
        
        return self::send($adminEmail, $subject, $message, 'Administrador');
    }
    
    /**
     * Testa a conexão com o servidor SMTP
     * 
     * @return array Resultado do teste
     */
    public static function testConnection() {
        // Inicializar configurações
        self::init();
        
        try {
            $mail = new PHPMailer(true);
            
            // Configurações do servidor
            $mail->isSMTP();
            $mail->Host       = self::$host;
            $mail->SMTPAuth   = true;
            $mail->Username   = self::$username;
            $mail->Password   = self::$password;
            $mail->SMTPSecure = self::$encryption;
            $mail->Port       = self::$port;
            
            // ADIÇÃO: Configurações SSL para teste
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            
            // ADIÇÃO: Timeout para teste
            $mail->Timeout = 30;
            
            // Teste de conexão apenas
            $mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
            
            // Capturar saída em buffer
            ob_start();
            $result = $mail->smtpConnect();
            $debugInfo = ob_get_clean();
            
            if ($result) {
                $mail->smtpClose();
                return [
                    'status' => true,
                    'message' => 'Conexão com servidor SMTP estabelecida com sucesso!',
                    'debug' => $debugInfo,
                    // ADIÇÃO: Informações de configuração para debug
                    'config' => [
                        'host' => self::$host,
                        'port' => self::$port,
                        'encryption' => self::$encryption,
                        'username' => self::$username
                    ]
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'Não foi possível conectar ao servidor SMTP.',
                    'debug' => $debugInfo,
                    'config' => [
                        'host' => self::$host,
                        'port' => self::$port,
                        'encryption' => self::$encryption,
                        'username' => self::$username
                    ]
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => 'Erro: ' . $e->getMessage(),
                'debug' => $mail->ErrorInfo ?? '',
                'config' => [
                    'host' => self::$host,
                    'port' => self::$port,
                    'encryption' => self::$encryption,
                    'username' => self::$username
                ]
            ];
        }
    }
    
    // ADIÇÃO: Método para testar o envio de email de recuperação
    public static function testPasswordRecoveryEmail($email = null) {
        $testEmail = $email ?: 'teste@klubecash.com';
        $testToken = 'test_token_' . time();
        
        error_log("Testando envio de email de recuperação para: " . $testEmail);
        
        $result = self::sendPasswordRecovery($testEmail, 'Usuário Teste', $testToken);
        
        if ($result) {
            error_log("Teste de email de recuperação: SUCESSO");
        } else {
            error_log("Teste de email de recuperação: FALHOU");
        }
        
        return $result;
    }
}
?>