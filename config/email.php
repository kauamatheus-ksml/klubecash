<?php
//config\email.php
/**
 * Configuração de envio de emails
 * Klube Cash - Sistema de Cashback
 */

// Configurações SMTP
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 465);
define('SMTP_USERNAME', 'klubecash@klubecash.com');
define('SMTP_PASSWORD', 'Aaku_2004@'); // Substitua pela senha real - removi a senha por segurança
define('SMTP_FROM_EMAIL', 'klubecash@klubecash.com');
define('SMTP_FROM_NAME', 'Klube Cash');

// Certifique-se de que as constantes estejam definidas em algum lugar
if (!defined('CLIENT_DASHBOARD_URL')) define('CLIENT_DASHBOARD_URL', '/klube-cash/views/client/dashboard.php');
if (!defined('ADMIN_EMAIL')) define('ADMIN_EMAIL', 'contato@klubecash.com');
if (!defined('SITE_URL')) define('SITE_URL', '/klube-cash');

// Importar PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Carregar PHPMailer
require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';

/**
 * Classe Email - Gerencia o envio de emails
 */
class Email {
    /**
     * Envia um email
     * 
     * @param string $to Email do destinatário
     * @param string $subject Assunto do email
     * @param string $message Mensagem do email (HTML)
     * @param string $toName Nome do destinatário (opcional)
     * @param array $attachments Arquivos anexos (opcional)
     * @return bool Retorna true se enviado com sucesso
     */
    public static function send($to, $subject, $message, $toName = '', $attachments = []) {
        // Para armazenar erros
        $errorInfo = '';
        
        try {
            // Criar nova instância do PHPMailer
            $mail = new PHPMailer(true);
            
            // Configurações de debug (apenas em desenvolvimento)
            if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_ADDR'] == '127.0.0.1' || $_SERVER['SERVER_ADDR'] == '192.168.100.53') {
                $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Output detalhes de debug
                $mail->Debugoutput = function($str, $level) use (&$errorInfo) {
                    $errorInfo .= $str . "\n";
                };
            }
            
            // Configurações do servidor
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // ou TLS
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';
            $mail->Timeout    = 30; // Aumentar timeout para 30 segundos
            
            // Desativar verificação de certificados SSL em desenvolvimento (remover em produção)
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            
            // Remetente
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            
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
            
            // Conteúdo do email
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = self::getEmailTemplate($message);
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $message));
            
            // Enviar
            $success = $mail->send();
            
            // Se estiver em ambiente de desenvolvimento, registrar o log completo
            if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_ADDR'] == '127.0.0.1' || $_SERVER['SERVER_ADDR'] == '192.168.100.53') {
                error_log("Resultado do envio de email para $to: " . ($success ? 'Sucesso' : 'Falha') . "\nDebug: $errorInfo");
            }
            
            return $success;
        } catch (Exception $e) {
            // Registra o erro
            $errorDetails = isset($mail) ? $mail->ErrorInfo : $e->getMessage();
            error_log("Erro ao enviar email para $to: " . $errorDetails . "\nDebug: $errorInfo");
            return false;
        }
    }
    
    /**
     * Aplica o template padrão ao corpo do email
     * 
     * @param string $content Conteúdo do email
     * @return string Email formatado com o template
     */
    private static function getEmailTemplate($content) {
        // Template atualizado com a identidade visual do sistema
        $template = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Klube Cash</title>
            <style>
                body { 
                    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; 
                    line-height: 1.6; 
                    color: #333333; 
                    background-color: #FFF9F2; 
                    margin: 0;
                    padding: 0;
                }
                .container {
                    max-width: 600px; 
                    margin: 0 auto; 
                    background-color: #FFFFFF; 
                    border-radius: 15px; 
                    overflow: hidden;
                    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
                }
                .header { 
                    background: linear-gradient(135deg, #FF7A00, #FF9A40);
                    color: white; 
                    padding: 20px; 
                    text-align: center; 
                }
                .logo {
                    max-width: 150px;
                    height: auto;
                }
                .content { 
                    padding: 30px; 
                    background-color: #FFFFFF;
                }
                .footer { 
                    background-color: #FFF0E6; 
                    padding: 20px; 
                    text-align: center; 
                    font-size: 14px; 
                    color: #666666; 
                    border-top: 1px solid #FFD9B3;
                }
                .btn { 
                    display: inline-block; 
                    background-color: #FF7A00; 
                    color: white; 
                    padding: 12px 25px; 
                    text-decoration: none; 
                    border-radius: 30px; 
                    font-weight: 600;
                    font-size: 14px;
                }
                .btn:hover {
                    background-color: #E06E00;
                }
                h2 {
                    color: #333333;
                    margin-top: 0;
                }
                table {
                    border-collapse: collapse;
                    width: 100%;
                }
                table th, table td {
                    padding: 12px;
                    text-align: left;
                    border: 1px solid #eee;
                }
                table th {
                    background-color: #FFF0E6;
                    color: #333333;
                    font-weight: 600;
                }
                table tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <img src="' . SITE_URL . '/assets/images/logobranco.png" alt="Klube Cash" class="logo">
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
     * Envia email de boas-vindas para novo usuário
     * 
     * @param string $email Email do usuário
     * @param string $name Nome do usuário
     * @return bool Retorna true se enviado com sucesso
     */
    public static function sendWelcome($email, $name) {
        // Desativa temporariamente o envio de email para testes
        // Em ambiente de desenvolvimento, simular sucesso
        if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_ADDR'] == '127.0.0.1') {
            error_log("Email de boas-vindas simulado para: $email");
            return true;
        }
        
        $subject = 'Bem-vindo ao Klube Cash';
        $message = '
        <h2>Bem-vindo ao Klube Cash, ' . htmlspecialchars($name) . '!</h2>
        <p>Estamos felizes em ter você conosco. Seu cadastro foi realizado com sucesso.</p>
        <p>Com o Klube Cash, você pode ganhar cashback em suas compras nas lojas parceiras.</p>
        <p>Acesse agora seu painel e comece a aproveitar:</p>
        <p style="text-align: center; margin: 30px 0;">
            <a href="' . CLIENT_DASHBOARD_URL . '" class="btn">Acessar Meu Painel</a>
        </p>
        <p>Se você tiver qualquer dúvida, entre em contato conosco pelo email: ' . ADMIN_EMAIL . '</p>
        <p>Atenciosamente,<br>Equipe Klube Cash</p>';
        
        return self::send($email, $subject, $message, $name);
    }
    
    /**
     * Envia email de recuperação de senha
     * 
     * @param string $email Email do usuário
     * @param string $name Nome do usuário
     * @param string $token Token de recuperação
     * @return bool Retorna true se enviado com sucesso
     */
    public static function sendPasswordRecovery($email, $name, $token) {
        // Desativa temporariamente o envio de email para testes
        if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_ADDR'] == '127.0.0.1') {
            error_log("Email de recuperação simulado para: $email");
            return true;
        }
        
        $resetLink = SITE_URL . '/views/auth/recover-password.php?token=' . urlencode($token);
        
        $subject = 'Recuperação de Senha - Klube Cash';
        $message = '
        <h2>Olá, ' . htmlspecialchars($name) . '!</h2>
        <p>Recebemos uma solicitação para redefinir sua senha no Klube Cash.</p>
        <p>Para redefinir sua senha, clique no botão abaixo:</p>
        <p style="text-align: center; margin: 30px 0;">
            <a href="' . $resetLink . '" class="btn">Redefinir Minha Senha</a>
        </p>
        <p>Se você não solicitou esta alteração, por favor ignore este email ou entre em contato conosco.</p>
        <p>Este link expirará em 2 horas por motivos de segurança.</p>
        <p>Atenciosamente,<br>Equipe Klube Cash</p>';
        
        return self::send($email, $subject, $message, $name);
    }
    
    /**
     * Envia email de confirmação de transação
     * 
     * @param string $email Email do usuário
     * @param string $name Nome do usuário
     * @param array $transaction Dados da transação
     * @return bool Retorna true se enviado com sucesso
     */
    public static function sendTransactionConfirmation($email, $name, $transaction) {
        // Desativa temporariamente o envio de email para testes
        if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_ADDR'] == '127.0.0.1') {
            error_log("Email de transação simulado para: $email");
            return true;
        }
        
        $subject = 'Cashback Recebido - Klube Cash';
        $message = '
        <h2>Olá, ' . htmlspecialchars($name) . '!</h2>
        <p>Você acabou de receber cashback de uma compra:</p>
        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr>
                <th>Loja</th>
                <td>' . htmlspecialchars($transaction['loja']) . '</td>
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
        <p>Para ver mais detalhes, acesse seu extrato:</p>
        <p style="text-align: center; margin: 30px 0;">
            <a href="' . SITE_URL . '/views/client/statement.php" class="btn">Ver Meu Extrato</a>
        </p>
        <p>Atenciosamente,<br>Equipe Klube Cash</p>';
        
        return self::send($email, $subject, $message, $name);
    }
    
    /**
     * Função para testar a configuração de email
     * Útil para verificar se as configurações de SMTP estão corretas
     * 
     * @return array Resultado do teste com status e mensagem
     */
    public static function testEmailConnection() {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = SMTP_PORT;
            
            // Testar conexão apenas
            $mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
            
            // Capturar output em buffer
            ob_start();
            $mail->SmtpConnect();
            $debug = ob_get_clean();
            
            return [
                'status' => true,
                'message' => 'Conexão com servidor SMTP estabelecida com sucesso!',
                'debug' => $debug
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => 'Erro na conexão com servidor SMTP: ' . $e->getMessage(),
                'debug' => $mail->ErrorInfo ?? ''
            ];
        }
    }
}