<?php
/**
 * Sistema de Envio de Emails - Klube Cash
 * Versão com SMTP funcional
 */

session_start();

// Senha de proteção
$senha_acesso = 'klube2024@!';

// Verificar acesso
$acesso_liberado = false;
if (isset($_POST['senha_acesso']) || isset($_SESSION['email_access_granted'])) {
    if (isset($_POST['senha_acesso']) && $_POST['senha_acesso'] === $senha_acesso) {
        $_SESSION['email_access_granted'] = true;
        $acesso_liberado = true;
    } elseif (isset($_SESSION['email_access_granted'])) {
        $acesso_liberado = true;
    }
}

// Processar logout
if (isset($_GET['sair'])) {
    unset($_SESSION['email_access_granted']);
    header('Location: envio-email.php');
    exit;
}

// Configurações SMTP do Hostinger
$smtp_config = [
    'host' => 'smtp.hostinger.com',
    'port' => 465,
    'username' => 'klubecash@klubecash.com',
    'password' => 'Aaku_2004@',
    'from_email' => 'klubecash@klubecash.com',
    'from_name' => 'Klube Cash',
    'encryption' => 'ssl'
];

/**
 * Função de envio SMTP usando sockets
 */
function enviarEmailSMTP($para, $assunto, $html, $config) {
    try {
        // Criar contexto SSL
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);
        
        // Conectar ao servidor SMTP
        $socket = stream_socket_client(
            "ssl://{$config['host']}:{$config['port']}", 
            $errno, 
            $errstr, 
            30, 
            STREAM_CLIENT_CONNECT, 
            $context
        );
        
        if (!$socket) {
            throw new Exception("Não foi possível conectar ao servidor SMTP: $errstr ($errno)");
        }
        
        // Ler resposta inicial
        $response = fgets($socket, 1024);
        if (substr($response, 0, 3) != '220') {
            throw new Exception("Erro na conexão SMTP: $response");
        }
        
        // Comando EHLO
        fwrite($socket, "EHLO klubecash.com\r\n");
        $response = fgets($socket, 1024);
        
        // Autenticação
        fwrite($socket, "AUTH LOGIN\r\n");
        $response = fgets($socket, 1024);
        
        fwrite($socket, base64_encode($config['username']) . "\r\n");
        $response = fgets($socket, 1024);
        
        fwrite($socket, base64_encode($config['password']) . "\r\n");
        $response = fgets($socket, 1024);
        if (substr($response, 0, 3) != '235') {
            throw new Exception("Falha na autenticação SMTP: $response");
        }
        
        // Comando MAIL FROM
        fwrite($socket, "MAIL FROM: <{$config['from_email']}>\r\n");
        $response = fgets($socket, 1024);
        
        // Comando RCPT TO
        fwrite($socket, "RCPT TO: <$para>\r\n");
        $response = fgets($socket, 1024);
        if (substr($response, 0, 3) != '250') {
            throw new Exception("Destinatário rejeitado: $response");
        }
        
        // Comando DATA
        fwrite($socket, "DATA\r\n");
        $response = fgets($socket, 1024);
        
        // Cabeçalhos e corpo do email
        $headers = "From: {$config['from_name']} <{$config['from_email']}>\r\n";
        $headers .= "To: $para\r\n";
        $headers .= "Subject: $assunto\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: 8bit\r\n";
        $headers .= "\r\n";
        
        fwrite($socket, $headers . $html . "\r\n.\r\n");
        $response = fgets($socket, 1024);
        
        // Comando QUIT
        fwrite($socket, "QUIT\r\n");
        fclose($socket);
        
        return substr($response, 0, 3) == '250';
        
    } catch (Exception $e) {
        error_log("Erro SMTP: " . $e->getMessage());
        return false;
    }
}

/**
 * Fallback usando PHPMailer se existir
 */
function enviarEmailPHPMailer($para, $assunto, $html, $config) {
    // Verificar se PHPMailer existe
    $phpmailer_paths = [
        'libs/PHPMailer/src/PHPMailer.php',
        'vendor/phpmailer/phpmailer/src/PHPMailer.php'
    ];
    
    $phpmailer_found = false;
    foreach ($phpmailer_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            require_once str_replace('PHPMailer.php', 'SMTP.php', $path);
            require_once str_replace('PHPMailer.php', 'Exception.php', $path);
            $phpmailer_found = true;
            break;
        }
    }
    
    if (!$phpmailer_found) {
        return false;
    }
    
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port = $config['port'];
        $mail->CharSet = 'UTF-8';
        
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($para);
        $mail->Subject = $assunto;
        $mail->Body = $html;
        $mail->isHTML(true);
        
        return $mail->send();
        
    } catch (Exception $e) {
        error_log("Erro PHPMailer: " . $e->getMessage());
        return false;
    }
}

/**
 * Função principal de envio (tenta múltiplos métodos)
 */
function enviarEmail($para, $assunto, $html) {
    global $smtp_config;
    
    // Método 1: SMTP nativo
    if (enviarEmailSMTP($para, $assunto, $html, $smtp_config)) {
        return true;
    }
    
    // Método 2: PHPMailer se disponível
    if (enviarEmailPHPMailer($para, $assunto, $html, $smtp_config)) {
        return true;
    }
    
    // Método 3: mail() nativo (último recurso)
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$smtp_config['from_name']} <{$smtp_config['from_email']}>\r\n";
    
    return mail($para, $assunto, $html, $headers);
}

// Processar envio do formulário
$resultado = null;
if ($acesso_liberado && $_POST && isset($_POST['action']) && $_POST['action'] === 'enviar_email') {
    try {
        if (empty($_POST['assunto']) || empty($_POST['conteudo_html'])) {
            throw new Exception('Assunto e conteúdo HTML são obrigatórios');
        }

        $destinatarios = [];
        
        // Emails manuais
        if (!empty($_POST['emails_manuais'])) {
            $emailsManuais = explode(',', $_POST['emails_manuais']);
            foreach ($emailsManuais as $email) {
                $email = trim($email);
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $destinatarios[] = $email;
                }
            }
        }

        // Buscar emails do banco (com proteção contra timeout)
        if (isset($_POST['incluir_usuarios']) && $_POST['incluir_usuarios'] == '1') {
            try {
                // Tentar incluir arquivos de configuração do banco
                if (file_exists('config/database.php')) {
                    require_once 'config/database.php';
                    
                    if (class_exists('Database')) {
                        $db = Database::getConnection();
                        $stmt = $db->prepare("SELECT DISTINCT email FROM usuarios WHERE email IS NOT NULL AND email != '' LIMIT 100");
                        $stmt->execute();
                        
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            if (!in_array($row['email'], $destinatarios)) {
                                $destinatarios[] = $row['email'];
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                // Ignorar erros de banco e continuar só com emails manuais
                error_log("Erro ao buscar emails do banco: " . $e->getMessage());
            }
        }

        // Buscar emails da landing page
        if (isset($_POST['incluir_landing']) && $_POST['incluir_landing'] == '1') {
            $emailsFile = 'embreve/emails.json';
            if (file_exists($emailsFile)) {
                $emailsData = json_decode(file_get_contents($emailsFile), true);
                if ($emailsData) {
                    foreach ($emailsData as $item) {
                        if (isset($item['email']) && filter_var($item['email'], FILTER_VALIDATE_EMAIL)) {
                            if (!in_array($item['email'], $destinatarios)) {
                                $destinatarios[] = $item['email'];
                            }
                        }
                    }
                }
            }
        }

        if (empty($destinatarios)) {
            throw new Exception('Nenhum destinatário válido encontrado');
        }

        // Log do envio
        error_log("KLUBE_EMAIL_SEND: Iniciando envio para " . count($destinatarios) . " destinatários");

        // Enviar emails com limite de tempo
        set_time_limit(300); // 5 minutos
        $sucessos = 0;
        $falhas = 0;
        $erros = [];

        foreach ($destinatarios as $index => $email) {
            try {
                $enviado = enviarEmail($email, $_POST['assunto'], $_POST['conteudo_html']);
                
                if ($enviado) {
                    $sucessos++;
                    error_log("KLUBE_EMAIL_SUCCESS: $email");
                } else {
                    $falhas++;
                    $erros[] = "Falha ao enviar para: $email";
                    error_log("KLUBE_EMAIL_FAIL: $email");
                }
                
                // Delay progressivo (mais delay conforme mais emails)
                $delay = min(500000, 100000 + ($index * 10000)); // 0.1s a 0.5s
                usleep($delay);
                
            } catch (Exception $e) {
                $falhas++;
                $erros[] = "Erro para $email: " . $e->getMessage();
                error_log("KLUBE_EMAIL_ERROR: $email - " . $e->getMessage());
            }
        }

        $resultado = [
            'status' => 'sucesso',
            'message' => "Envio concluído! Sucessos: $sucessos, Falhas: $falhas",
            'detalhes' => [
                'total_destinatarios' => count($destinatarios),
                'sucessos' => $sucessos,
                'falhas' => $falhas,
                'erros' => $erros
            ]
        ];

    } catch (Exception $e) {
        $resultado = [
            'status' => 'erro',
            'message' => 'Erro no envio: ' . $e->getMessage()
        ];
        error_log("KLUBE_EMAIL_FATAL: " . $e->getMessage());
    }
}

// Buscar totais (com proteção)
$totalUsuarios = 0;
$totalLanding = 0;

if ($acesso_liberado) {
    try {
        // Tentar buscar total de usuários
        if (file_exists('config/database.php')) {
            require_once 'config/database.php';
            if (class_exists('Database')) {
                $db = Database::getConnection();
                $totalUsuarios = $db->query("SELECT COUNT(DISTINCT email) FROM usuarios WHERE email IS NOT NULL AND email != ''")->fetchColumn();
            }
        }
    } catch (Exception $e) {
        // Continuar sem erro se banco não funcionar
    }
    
    // Total de emails da landing page
    $emailsFile = 'embreve/emails.json';
    if (file_exists($emailsFile)) {
        $emailsData = json_decode(file_get_contents($emailsFile), true);
        if ($emailsData) {
            $emailsUnicos = array_unique(array_column($emailsData, 'email'));
            $totalLanding = count(array_filter($emailsUnicos));
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Envio de Emails - Klube Cash</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .login-card,
        .email-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .login-card {
            max-width: 400px;
            margin: 50px auto;
            text-align: center;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #FF6B00, #FF8533);
            color: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            display: block;
        }
        
        .stat-label {
            font-size: 1em;
            opacity: 0.9;
            margin-top: 5px;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 10px;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .checkbox-item input[type="checkbox"] {
            width: auto;
            margin: 0;
            transform: scale(1.2);
        }
        
        .html-editor {
            min-height: 400px;
            font-family: 'Courier New', monospace;
            resize: vertical;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
        }
        
        .btn-logout {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            float: right;
            margin-bottom: 20px;
        }
        
        .btn-template {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        
        .alert {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .preview-container {
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            padding: 20px;
            background: #f8f9fa;
            margin-top: 15px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .smtp-status {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-envelope"></i> Klube Cash</h1>
            <p>Sistema de Envio de Emails com SMTP</p>
        </div>

        <?php if (!$acesso_liberado): ?>
            <!-- Tela de Login -->
            <div class="login-card">
                <h2><i class="fas fa-lock"></i> Acesso Restrito</h2>
                <p style="margin-bottom: 20px; color: #666;">
                    Digite a senha para acessar o sistema de envio de emails.
                </p>
                
                <?php if (isset($_POST['senha_acesso']) && $_POST['senha_acesso'] !== $senha_acesso): ?>
                    <div class="alert alert-error">
                        <strong>Senha incorreta!</strong> Tente novamente.
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Senha de Acesso:</label>
                        <input type="password" name="senha_acesso" required placeholder="Digite a senha...">
                    </div>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Acessar Sistema
                    </button>
                </form>
            </div>
        <?php else: ?>
            <!-- Sistema de Envio -->
            <button onclick="window.location.href='?sair=1'" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i> Sair
            </button>
            
            <!-- Status SMTP -->
            <div class="smtp-status">
                <strong><i class="fas fa-server"></i> Configuração SMTP:</strong> 
                Hostinger (<?= $smtp_config['host'] ?>:<?= $smtp_config['port'] ?>) - 
                Usuário: <?= $smtp_config['username'] ?>
            </div>
            
            <!-- Estatísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-number"><?= number_format($totalUsuarios) ?></span>
                    <span class="stat-label">Usuários Cadastrados</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= number_format($totalLanding) ?></span>
                    <span class="stat-label">Emails da Landing Page</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= number_format($totalUsuarios + $totalLanding) ?></span>
                    <span class="stat-label">Total Disponível</span>
                </div>
            </div>
            
            <!-- Resultado -->
            <?php if ($resultado): ?>
                <div class="alert <?= $resultado['status'] === 'sucesso' ? 'alert-success' : 'alert-error' ?>">
                    <strong><?= htmlspecialchars($resultado['message']) ?></strong>
                    <?php if (isset($resultado['detalhes'])): ?>
                        <div style="margin-top: 15px;">
                            <small>
                                <strong>Detalhes:</strong><br>
                                Total: <?= $resultado['detalhes']['total_destinatarios'] ?> | 
                                Sucessos: <?= $resultado['detalhes']['sucessos'] ?> | 
                                Falhas: <?= $resultado['detalhes']['falhas'] ?>
                            </small>
                            <?php if (!empty($resultado['detalhes']['erros'])): ?>
                                <details style="margin-top: 10px;">
                                    <summary style="cursor: pointer; font-weight: bold;">
                                        Ver erros (<?= count($resultado['detalhes']['erros']) ?>)
                                    </summary>
                                    <ul style="margin-top: 10px; padding-left: 20px;">
                                        <?php foreach (array_slice($resultado['detalhes']['erros'], 0, 10) as $erro): ?>
                                            <li><small><?= htmlspecialchars($erro) ?></small></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </details>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="email-card">
                <button type="button" class="btn-template" onclick="preencherTemplate()">
                    <i class="fas fa-magic"></i> Carregar Template de Exemplo
                </button>
                
                <form method="POST">
                    <input type="hidden" name="action" value="enviar_email">
                    
                    <div class="form-group">
                        <label><i class="fas fa-users"></i> Emails (separados por vírgula)</label>
                        <textarea name="emails_manuais" rows="3" placeholder="email1@exemplo.com, email2@exemplo.com"><?= isset($_POST['emails_manuais']) ? htmlspecialchars($_POST['emails_manuais']) : '' ?></textarea>
                        <small style="color: #666;">Digite os emails ou use as opções abaixo</small>
                    </div>

                    <?php if ($totalUsuarios > 0 || $totalLanding > 0): ?>
                    <div class="form-group">
                        <label>Incluir Listas Automáticas:</label>
                        <div class="checkbox-group">
                            <?php if ($totalUsuarios > 0): ?>
                            <div class="checkbox-item">
                                <input type="checkbox" name="incluir_usuarios" value="1" id="incluir_usuarios">
                                <label for="incluir_usuarios">
                                    Usuários cadastrados (<?= number_format($totalUsuarios) ?>)
                                </label>
                            </div>
                            <?php endif; ?>
                            <?php if ($totalLanding > 0): ?>
                            <div class="checkbox-item">
                                <input type="checkbox" name="incluir_landing" value="1" id="incluir_landing">
                                <label for="incluir_landing">
                                    Landing page (<?= number_format($totalLanding) ?>)
                                </label>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Assunto *</label>
                        <input type="text" name="assunto" required placeholder="Digite o assunto do email" value="<?= isset($_POST['assunto']) ? htmlspecialchars($_POST['assunto']) : '' ?>">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-code"></i> Conteúdo HTML *</label>
                        <textarea name="conteudo_html" class="html-editor" required placeholder="Cole o HTML do seu email aqui..."><?= isset($_POST['conteudo_html']) ? htmlspecialchars($_POST['conteudo_html']) : '' ?></textarea>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-eye"></i> Preview</label>
                        <div class="preview-container" id="preview">
                            <p style="color: #666; font-style: italic;">O preview aparecerá aqui...</p>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary" onclick="return confirmarEnvio()">
                        <i class="fas fa-paper-plane"></i> Enviar Emails via SMTP
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function preencherTemplate() {
            document.querySelector('input[name="assunto"]').value = 'Novidades Klube Cash - Seu cashback te espera!';
            
            document.querySelector('textarea[name="conteudo_html"]').value = `<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Klube Cash</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <div style="max-width: 600px; margin: 0 auto; background-color: white;">
        <div style="background: linear-gradient(135deg, #FF6B00, #FF8533); padding: 30px; text-align: center;">
            <h1 style="color: white; margin: 0; font-size: 28px;">Klube Cash</h1>
            <p style="color: white; margin: 10px 0 0 0; opacity: 0.9;">Transforme suas compras em dinheiro de volta</p>
        </div>
        
        <div style="padding: 30px;">
            <h2 style="color: #333; margin-top: 0;">Olá!</h2>
            
            <p style="color: #666; line-height: 1.6; font-size: 16px;">
                Temos novidades incríveis no Klube Cash! Seu programa de cashback favorito está 
                ainda melhor e com mais oportunidades para você ganhar dinheiro de volta.
            </p>
            
            <p style="color: #666; line-height: 1.6; font-size: 16px;">
                <strong>O que há de novo:</strong>
            </p>
            
            <ul style="color: #666; line-height: 1.6;">
                <li>Novas lojas parceiras com cashback de até 15%</li>
                <li>Sistema mais rápido e fácil de usar</li>
                <li>Promoções exclusivas para membros</li>
                <li>Notificações em tempo real</li>
            </ul>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="https://klubecash.com" 
                   style="background: linear-gradient(135deg, #FF6B00, #FF8533); 
                          color: white; 
                          padding: 15px 30px; 
                          text-decoration: none; 
                          border-radius: 25px; 
                          font-weight: bold;
                          display: inline-block;">
                    Acessar Minha Conta
                </a>
            </div>
        </div>
        
        <div style="background-color: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 14px;">
            <p style="margin: 0;">© 2024 Klube Cash. Todos os direitos reservados.</p>
            <p style="margin: 10px 0 0 0;">Este é um email automático, por favor não responda.</p>
        </div>
    </div>
</body>
</html>`;
            
            atualizarPreview();
        }

        function atualizarPreview() {
            const html = document.querySelector('textarea[name="conteudo_html"]').value;
            document.getElementById('preview').innerHTML = html || '<p style="color: #666; font-style: italic;">Digite o HTML para ver o preview...</p>';
        }

        function confirmarEnvio() {
            const emails = document.querySelector('textarea[name="emails_manuais"]').value.trim();
            const incluirUsuarios = document.querySelector('input[name="incluir_usuarios"]')?.checked || false;
            const incluirLanding = document.querySelector('input[name="incluir_landing"]')?.checked || false;
            
            if (!emails && !incluirUsuarios && !incluirLanding) {
                alert('Digite pelo menos um email ou marque uma das opções de lista!');
                return false;
            }
            
            let totalEstimado = 0;
            if (emails) totalEstimado += emails.split(',').length;
            if (incluirUsuarios) totalEstimado += <?= $totalUsuarios ?>;
            if (incluirLanding) totalEstimado += <?= $totalLanding ?>;
            
            if (!confirm(`Confirma o envio para aproximadamente ${totalEstimado} destinatário(s)?\n\nO processo pode demorar alguns minutos.`)) {
                return false;
            }
            
            // Mostrar loading
            const btn = document.querySelector('button[type="submit"]');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando via SMTP...';
            btn.disabled = true;
            
            return true;
        }

        // Auto-update preview
        document.querySelector('textarea[name="conteudo_html"]')?.addEventListener('input', atualizarPreview);
        
        // Preview inicial se houver conteúdo
        if (document.querySelector('textarea[name="conteudo_html"]').value) {
            atualizarPreview();
        }
    </script>
</body>
</html>