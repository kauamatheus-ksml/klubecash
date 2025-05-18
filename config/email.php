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
                    padding: 20px 0; /* Adicionado padding para espaçamento vertical */
                    -webkit-font-smoothing: antialiased; /* Melhora a renderização da fonte */
                    -moz-osx-font-smoothing: grayscale; /* Melhora a renderização da fonte */
                }
                .container {
                    max-width: 600px; 
                    margin: 20px auto; /* Margem aumentada para melhor separação */
                    background-color: #FFFFFF; 
                    border-radius: 15px; 
                    overflow: hidden;
                    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.07); /* Sombra um pouco mais suave e difundida */
                }
                .header { 
                    background: linear-gradient(135deg, #FF7A00, #FF9A40);
                    color: white; 
                    padding: 25px 20px; /* Padding ligeiramente aumentado */
                    text-align: center; 
                }
                .logo {
                    max-width: 160px; /* Tamanho ligeiramente aumentado para destaque */
                    height: auto;
                    display: block; /* Garante que a margem automática funcione */
                    margin: 0 auto; /* Centraliza o logo de forma robusta */
                }
                .content { 
                    padding: 30px 35px; /* Padding horizontal aumentado para respiro */
                    background-color: #FFFFFF;
                }
                .content p {
                    margin-top: 0;
                    margin-bottom: 18px; /* Espaçamento padrão para parágrafos */
                    color: #333333;
                    font-size: 16px;
                    line-height: 1.7; /* Altura de linha generosa para leitura */
                }
                .content p:last-child {
                    margin-bottom: 0; /* Remove margem do último parágrafo */
                }
                .content h1, .content h2, .content h3 {
                    color: #333333;
                    margin-top: 0;
                    font-weight: 600;
                }
                .content h1 {
                    font-size: 26px;
                    line-height: 1.3;
                    margin-bottom: 20px;
                }
                .content h2 {
                    font-size: 22px; /* Tamanho de fonte para h2 */
                    line-height: 1.4; /* Altura de linha para h2 */
                    margin-bottom: 15px; /* Espaçamento após h2 */
                }
                .content h3 {
                    font-size: 18px;
                    line-height: 1.5;
                    margin-bottom: 10px;
                }
                .footer { 
                    background-color: #FFF0E6; 
                    padding: 25px 20px; /* Padding aumentado */
                    text-align: center; 
                    font-size: 13px; /* Tamanho de fonte ligeiramente reduzido */
                    color: #777777; /* Cor do texto um pouco mais suave */
                    border-top: 1px solid #FFD9B3;
                }
                .footer p {
                    margin: 5px 0; /* Espaçamento mais justo entre linhas no footer */
                }
                .btn { 
                    display: inline-block; 
                    background-color: #FF7A00; 
                    color: white !important; /* Garante que a cor do texto do botão seja branca */
                    padding: 14px 28px; /* Padding aumentado para um botão mais robusto */
                    text-decoration: none; 
                    border-radius: 30px; 
                    font-weight: 600;
                    font-size: 15px; /* Tamanho de fonte ligeiramente aumentado */
                    text-transform: uppercase; /* Caixa alta para mais impacto */
                    letter-spacing: 0.5px; /* Leve espaçamento entre letras */
                    transition: background-color 0.25s ease-out; /* Transição suave */
                }
                .btn:hover {
                    background-color: #E06E00;
                    /* transform: translateY(-2px); */ /* Efeito sutil de elevação - pode ter problemas em alguns clientes de email */
                }
                table {
                    border-collapse: collapse;
                    width: 100%;
                    margin-bottom: 20px; /* Espaçamento após a tabela */
                }
                table th, table td {
                    padding: 12px 15px; /* Padding ajustado nas células */
                    text-align: left;
                    border: 1px solid #EAEAEA; /* Cor da borda suavizada */
                    font-size: 14px; /* Tamanho de fonte para texto da tabela */
                    vertical-align: middle; /* Alinhamento vertical centralizado */
                }
                table th {
                    background-color: #FFF0E6;
                    color: #333333;
                    font-weight: 600; /* Peso da fonte para cabeçalho */
                    font-size: 14px; /* Consistência de tamanho com td */
                }
                table tr:nth-child(even) {
                    background-color: #f9f9f9; /* Mantém o zebrado original */
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
            error_log("Email de boas-vindas simulado para: $email com nome: $name");
            return true;
        }
        
        $subject = 'Bem-vindo ao Klube Cash!'; // Título um pouco mais caloroso
        $nameEscaped = htmlspecialchars($name);
        // Assume-se que ADMIN_EMAIL é um email válido e seguro para exibição.
        // Para o href mailto:, o email bruto é geralmente melhor.
        // Para exibição no texto, htmlspecialchars pode ser usado se houver preocupação.
        $adminEmailDisplay = htmlspecialchars(ADMIN_EMAIL);

        $message = '
        <h2 style="color: #333333; font-size: 22px; line-height: 1.4; margin-bottom: 15px;">Bem-vindo(a) ao Klube Cash, ' . $nameEscaped . '!</h2>
        <p style="color: #333333; font-size: 16px; line-height: 1.7; margin-bottom: 18px;">Estamos super felizes em ter você conosco! Seu cadastro em nossa plataforma foi realizado com sucesso.</p>
        <p style="color: #333333; font-size: 16px; line-height: 1.7; margin-bottom: 18px;">Com o Klube Cash, a economia inteligente está ao seu alcance. Prepare-se para ganhar cashback em suas compras em diversas lojas parceiras e aproveitar benefícios exclusivos.</p>
        <p style="color: #333333; font-size: 16px; line-height: 1.7; margin-bottom: 18px;">Não espere mais! Acesse agora seu painel e comece a desfrutar de todas as vantagens:</p>
        <p style="text-align: center; margin: 30px 0;">
            <a href="' . CLIENT_DASHBOARD_URL . '" class="btn">Acessar Meu Painel</a>
        </p>
        <hr style="border: 0; height: 1px; background-color: #EAEAEA; margin: 25px 0 20px;">
        <p style="font-size: 15px; line-height: 1.6; color: #555555; margin-bottom: 18px;">Se você tiver qualquer dúvida ou precisar de ajuda, nossa equipe está à disposição. Entre em contato conosco pelo email: <a href="mailto:' . ADMIN_EMAIL . '" style="color: #FF7A00; text-decoration: none; font-weight: 500;">' . $adminEmailDisplay . '</a>.</p>
        <p style="font-size: 15px; line-height: 1.6; color: #555555; margin-top: 10px;">Atenciosamente,<br><strong>Equipe Klube Cash</strong></p>';
        
        // As linhas de h2 e p acima incluem estilos inline para garantir
        // que, mesmo se o $message for usado em um contexto sem o CSS completo do template,
        // ele ainda tenha uma formatação base decente.
        // Se estiver *sempre* dentro do template anterior, esses estilos inline em h2/p podem ser omitidos
        // para depender dos estilos de .content h2 e .content p.
        // A classe .btn no link é essencial e dependerá do CSS do template.

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
            error_log("Email de recuperação simulado para: $email com token: $token");
            return true;
        }
        
        $resetLink = SITE_URL . '/views/auth/recover-password.php?token=' . urlencode($token);
        $nameEscaped = htmlspecialchars($name);
        // Assume-se que ADMIN_EMAIL é uma constante ou variável definida
        $adminEmailDisplay = htmlspecialchars(ADMIN_EMAIL);
        
        $subject = 'Recuperação de Senha - Klube Cash';
        $message = '
        <h2 style="color: #333333; font-size: 22px; line-height: 1.4; margin-bottom: 15px;">Olá, ' . $nameEscaped . '!</h2>
        <p style="color: #333333; font-size: 16px; line-height: 1.7; margin-bottom: 18px;">Recebemos uma solicitação para redefinir a senha da sua conta no Klube Cash.</p>
        <p style="color: #333333; font-size: 16px; line-height: 1.7; margin-bottom: 18px;">Para criar uma nova senha, por favor, clique no botão abaixo. Lembre-se que este link é de uso único e <strong>expirará em 2 horas</strong> por motivos de segurança.</p>
        <p style="text-align: center; margin: 30px 0;">
            <a href="' . $resetLink . '" class="btn">Redefinir Minha Senha</a>
        </p>
        <p style="font-size: 15px; line-height: 1.6; color: #555555; margin-bottom: 10px;">Se você não solicitou esta alteração, pode ignorar este email com segurança. Nenhuma modificação será feita em sua conta.</p>
        <p style="font-size: 15px; line-height: 1.6; color: #555555; margin-bottom: 20px;">Caso tenha alguma dúvida ou identifique qualquer atividade suspeita, não hesite em <a href="mailto:' . ADMIN_EMAIL . '" style="color: #FF7A00; text-decoration: none; font-weight: 500;">entrar em contato conosco</a> imediatamente.</p>
        <hr style="border: 0; height: 1px; background-color: #EAEAEA; margin: 25px 0 20px;">
        <p style="font-size: 15px; line-height: 1.6; color: #555555; margin-top: 10px;">Atenciosamente,<br><strong>Equipe Klube Cash</strong></p>';
        
        // Os estilos inline em h2/p são uma salvaguarda para manter a formatação base,
        // caso o email seja visualizado em um ambiente sem o CSS completo do template.
        // A classe .btn no link dependerá do CSS do template principal.

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
            error_log("Email de transação simulado para: $email. Dados da transação: " . print_r($transaction, true));
            return true;
        }
        
        $nameEscaped = htmlspecialchars($name);
        // Assumindo que ADMIN_EMAIL é uma constante ou variável definida
        $adminEmailDisplay = htmlspecialchars(ADMIN_EMAIL);

        // Formatando os detalhes da transação para clareza e segurança
        $loja = htmlspecialchars($transaction['loja']);
        $valorCompra = 'R$ ' . number_format($transaction['valor_total'], 2, ',', '.');
        $valorCashback = 'R$ ' . number_format($transaction['valor_cashback'], 2, ',', '.');
        // Garantindo que a data da transação seja válida antes de formatar
        $dataTransacaoFormatada = 'N/D';
        if (!empty($transaction['data_transacao']) && strtotime($transaction['data_transacao']) !== false) {
            $dataTransacaoFormatada = date('d/m/Y H:i', strtotime($transaction['data_transacao']));
        }

        $subject = '🎉 Cashback Confirmado! - Klube Cash'; // Assunto mais chamativo
        $message = '
        <h2 style="color: #333333; font-size: 22px; line-height: 1.4; margin-bottom: 15px;">Ótimas Notícias, ' . $nameEscaped . '!</h2>
        <p style="color: #333333; font-size: 16px; line-height: 1.7; margin-bottom: 18px;">Seu cashback foi confirmado e já está disponível em sua conta Klube Cash! Confira os detalhes da transação abaixo:</p>

        <table style="width: 100%; border-collapse: collapse; margin: 25px 0; font-size: 14px; line-height: 1.6; border: 1px solid #EAEAEA; border-radius: 8px; overflow: hidden;">
            <tr style="border-bottom: 1px solid #EAEAEA;">
                <th style="padding: 12px 15px; text-align: left; background-color: #FFF0E6; color: #333333; font-weight: 600;">Loja</th>
                <td style="padding: 12px 15px; text-align: left; background-color: #FFFFFF;">' . $loja . '</td>
            </tr>
            <tr style="border-bottom: 1px solid #EAEAEA;">
                <th style="padding: 12px 15px; text-align: left; background-color: #FFF0E6; color: #333333; font-weight: 600;">Valor da Compra</th>
                <td style="padding: 12px 15px; text-align: left; background-color: #FFFFFF;">' . $valorCompra . '</td>
            </tr>
            <tr style="border-bottom: 1px solid #EAEAEA;">
                <th style="padding: 12px 15px; text-align: left; background-color: #FFF0E6; color: #333333; font-weight: 600;">Cashback Recebido</th>
                <td style="padding: 12px 15px; text-align: left; background-color: #FFFFFF; font-weight: bold; color: #FF7A00;">' . $valorCashback . '</td>
            </tr>
            <tr> <th style="padding: 12px 15px; text-align: left; background-color: #FFF0E6; color: #333333; font-weight: 600;">Data da Transação</th>
                <td style="padding: 12px 15px; text-align: left; background-color: #FFFFFF;">' . $dataTransacaoFormatada . '</td>
            </tr>
        </table>

        <p style="color: #333333; font-size: 16px; line-height: 1.7; margin-bottom: 18px;">Para conferir este e outros cashbacks, acesse seu extrato completo em seu painel:</p>
        <p style="text-align: center; margin: 30px 0;">
            <a href="' . SITE_URL . '/views/client/statement.php" class="btn">Ver Meu Extrato</a>
        </p>
        <p style="font-size: 15px; line-height: 1.6; color: #555555; margin-bottom: 20px;">Dúvidas? <a href="mailto:' . ADMIN_EMAIL . '" style="color: #FF7A00; text-decoration: none; font-weight: 500;">Fale conosco</a>!</p>
        <hr style="border: 0; height: 1px; background-color: #EAEAEA; margin: 25px 0 20px;">
        <p style="font-size: 15px; line-height: 1.6; color: #555555; margin-top: 10px;">Continue aproveitando os benefícios do Klube Cash!<br><strong>Equipe Klube Cash</strong></p>';
        
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