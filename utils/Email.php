<?php
// utils/Email.php
require_once __DIR__ . '/../config/constants.php';

// Importar PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Caminho para as classes do PHPMailer
require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';

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
            self::$port = defined('SMTP_PORT') ? SMTP_PORT : 465;
            self::$username = defined('SMTP_USERNAME') ? SMTP_USERNAME : 'klubecash@klubecash.com';
            self::$password = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : 'Aaku_2004@';
            self::$fromEmail = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'noreply@klubecash.com';
            self::$fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Klube Cash';
            self::$encryption = defined('SMTP_ENCRYPTION') ? SMTP_ENCRYPTION : PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            // Configurações padrão se as constantes não estiverem definidas
            self::$host = 'smtp.hostinger.com';
            self::$port = 465;
            self::$username = 'klubecash@klubecash.com';
            self::$password = 'Aaku_2004@';
            self::$fromEmail = 'noreply@klubecash.com';
            self::$fromName = 'Klube Cash';
            self::$encryption = PHPMailer::ENCRYPTION_STARTTLS;
            
            // Registrar aviso
            error_log('Constantes SMTP não encontradas. Utilizando valores padrão.');
        }
    }
    /**
     * Envia código de verificação 2FA por email
     * 
     * @param string $to Email do destinatário
     * @param string $name Nome do destinatário
     * @param string $code Código de verificação
     * @param string $ipAddress IP do usuário
     * @return bool Verdadeiro se enviado com sucesso
     */
    public static function send2FACode($to, $name, $code, $ipAddress = '') {
        $subject = 'Código de Verificação - Klube Cash';
        $nameEscaped = htmlspecialchars($name);
        $location = self::getLocationFromIP($ipAddress);
        $deviceInfo = self::getDeviceInfo();
        
        $message = '
        <h2 style="color: #333333; font-size: 22px; margin-bottom: 20px;">Código de Verificação</h2>
        <p style="color: #333333; font-size: 16px; line-height: 1.7; margin-bottom: 18px;">Olá, ' . $nameEscaped . '!</p>
        <p style="color: #333333; font-size: 16px; line-height: 1.7; margin-bottom: 18px;">Foi solicitado um código de verificação para acessar sua conta no Klube Cash.</p>
        
        <div style="background-color: #FFF0E6; border: 2px solid #FF7A00; border-radius: 8px; padding: 20px; text-align: center; margin: 25px 0;">
            <p style="margin: 0; color: #333; font-size: 14px; margin-bottom: 10px;">Seu código de verificação é:</p>
            <h1 style="margin: 0; color: #FF7A00; font-size: 32px; font-weight: bold; letter-spacing: 8px; font-family: monospace;">' . $code . '</h1>
            <p style="margin: 0; color: #666; font-size: 12px; margin-top: 10px;">Este código expira em 5 minutos</p>
        </div>
        
        <p style="color: #333333; font-size: 14px; line-height: 1.6; margin-bottom: 15px;"><strong>Detalhes do acesso:</strong></p>
        <ul style="color: #666; font-size: 14px; line-height: 1.6; margin-bottom: 20px;">
            <li><strong>Data/Hora:</strong> ' . date('d/m/Y H:i:s') . '</li>
            <li><strong>Dispositivo:</strong> ' . $deviceInfo . '</li>
            <li><strong>Localização:</strong> ' . $location . '</li>
        </ul>
        
        <div style="background-color: #FEF2F2; border-left: 4px solid #EF4444; padding: 15px; margin: 20px 0;">
            <p style="margin: 0; color: #991B1B; font-size: 14px;"><strong>⚠️ Importante:</strong> Se você não solicitou este código, alguém pode estar tentando acessar sua conta. Entre em contato conosco imediatamente.</p>
        </div>
        
        <p style="font-size: 13px; color: #777; margin-top: 25px;">
            Por segurança, não compartilhe este código com ninguém.<br>
            Atenciosamente,<br><strong>Equipe Klube Cash</strong>
        </p>';
        
        return self::send($to, $subject, $message, $name);
    }


/**
     * Obtém informação do dispositivo
     * 
     * @return string Informação do dispositivo
     */
    private static function getDeviceInfo() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconhecido';
        
        // Detectar navegador
        if (strpos($userAgent, 'Chrome') !== false) {
            $browser = 'Chrome';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            $browser = 'Firefox';
        } elseif (strpos($userAgent, 'Safari') !== false) {
            $browser = 'Safari';
        } elseif (strpos($userAgent, 'Edge') !== false) {
            $browser = 'Edge';
        } else {
            $browser = 'Navegador não identificado';
        }
        
        // Detectar sistema operacional
        if (strpos($userAgent, 'Windows') !== false) {
            $os = 'Windows';
        } elseif (strpos($userAgent, 'Mac') !== false) {
            $os = 'Mac OS';
        } elseif (strpos($userAgent, 'Linux') !== false) {
            $os = 'Linux';
        } elseif (strpos($userAgent, 'Android') !== false) {
            $os = 'Android';
        } elseif (strpos($userAgent, 'iOS') !== false) {
            $os = 'iOS';
        } else {
            $os = 'Sistema não identificado';
        }
        
        return $browser . ' em ' . $os;
    }
    
    /**
     * Obtém localização aproximada do IP
     * 
     * @param string $ip Endereço IP
     * @return string Localização aproximada
     */
    private static function getLocationFromIP($ip) {
        if (empty($ip) || $ip === '127.0.0.1' || $ip === '::1') {
            return 'Localhost';
        }
        
        // Para implementação completa, você pode usar serviços como:
        // - ipapi.co
        // - ip-api.com
        // - geoip-db.com
        
        // Por enquanto, retorna uma localização genérica
        return 'Brasil'; // Você pode implementar uma API de geolocalização aqui
    }
    
    /**
     * Envia alerta de novo acesso por email
     * 
     * @param string $to Email do destinatário
     * @param string $name Nome do destinatário
     * @param string $ipAddress IP do usuário
     * @return bool Verdadeiro se enviado com sucesso
     */
    public static function sendLoginAlert($to, $name, $ipAddress = '') {
        $subject = 'Novo Acesso à sua Conta - Klube Cash';
        $nameEscaped = htmlspecialchars($name);
        $location = self::getLocationFromIP($ipAddress);
        $deviceInfo = self::getDeviceInfo();
        
        $message = '
        <h2 style="color: #333333; font-size: 22px; margin-bottom: 20px;">Novo Acesso Detectado</h2>
        <p style="color: #333333; font-size: 16px; line-height: 1.7; margin-bottom: 18px;">Olá, ' . $nameEscaped . '!</p>
        <p style="color: #333333; font-size: 16px; line-height: 1.7; margin-bottom: 18px;">Detectamos um novo acesso à sua conta no Klube Cash.</p>
        
        <div style="background-color: #F0F9FF; border: 1px solid #0EA5E9; border-radius: 8px; padding: 20px; margin: 25px 0;">
            <p style="margin: 0; color: #333; font-size: 14px; margin-bottom: 15px;"><strong>Detalhes do acesso:</strong></p>
            <ul style="color: #666; font-size: 14px; line-height: 1.6; margin: 0;">
                <li><strong>Data/Hora:</strong> ' . date('d/m/Y H:i:s') . '</li>
                <li><strong>Dispositivo:</strong> ' . $deviceInfo . '</li>
                <li><strong>Localização:</strong> ' . $location . '</li>
            </ul>
        </div>
        
        <p style="color: #333333; font-size: 16px; line-height: 1.7; margin-bottom: 18px;">Se foi você quem fez este acesso, pode ignorar este email.</p>
        
        <div style="background-color: #FEF2F2; border-left: 4px solid #EF4444; padding: 15px; margin: 20px 0;">
            <p style="margin: 0; color: #991B1B; font-size: 14px;"><strong>⚠️ Se não foi você:</strong></p>
            <p style="margin: 5px 0 0 0; color: #991B1B; font-size: 14px;">Recomendamos que altere sua senha imediatamente e entre em contato conosco.</p>
        </div>
        
        <p style="text-align: center; margin: 30px 0;">
            <a href="' . SITE_URL . '/recuperar-senha" class="btn">Alterar Minha Senha</a>
        </p>
        
        <p style="font-size: 13px; color: #777; margin-top: 25px;">
            Atenciosamente,<br><strong>Equipe Klube Cash</strong>
        </p>';
        
        return self::send($to, $subject, $message, $name);
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
            
            // Configurações para ambiente de desenvolvimento
            if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];
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
            return $mail->send();
        } catch (Exception $e) {
            error_log('Erro ao enviar email: ' . $e->getMessage());
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
        
        $resetLink = SITE_URL . '/views/auth/recover-password.php?token=' . urlencode($token);
        
        $message = '
        <h2>Olá, ' . htmlspecialchars($name) . '!</h2>
        <p>Recebemos uma solicitação para redefinir sua senha no Klube Cash.</p>
        <p>Para redefinir sua senha, clique no botão abaixo:</p>
        <p><a href="' . $resetLink . '" class="btn">Redefinir Minha Senha</a></p>
        <p>Se você não solicitou esta alteração, por favor ignore este email.</p>
        <p>Este link é válido por ' . (TOKEN_EXPIRATION / 3600) . ' horas.</p>
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
                    'debug' => $debugInfo
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'Não foi possível conectar ao servidor SMTP.',
                    'debug' => $debugInfo
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => 'Erro: ' . $e->getMessage(),
                'debug' => $mail->ErrorInfo ?? ''
            ];
        }
    }
}
?>