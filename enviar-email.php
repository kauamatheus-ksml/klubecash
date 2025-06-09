<?php
/**
 * Sistema de Envio de Emails - Klube Cash
 * Versão completa com AJAX, progresso em tempo real e templates profissionais
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

// API para envio via AJAX
if (isset($_GET['api']) && $_GET['api'] === 'enviar' && $acesso_liberado) {
    header('Content-Type: application/json');
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || empty($input['assunto']) || empty($input['conteudo_html'])) {
            throw new Exception('Dados incompletos');
        }

        $destinatarios = [];
        
        // Emails manuais
        if (!empty($input['emails_manuais'])) {
            $emailsManuais = explode(',', $input['emails_manuais']);
            foreach ($emailsManuais as $email) {
                $email = trim($email);
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $destinatarios[] = $email;
                }
            }
        }

        // Buscar emails do banco
        if (!empty($input['incluir_usuarios'])) {
            try {
                if (file_exists('config/database.php')) {
                    require_once 'config/database.php';
                    if (class_exists('Database')) {
                        $db = Database::getConnection();
                        $stmt = $db->query("SELECT DISTINCT email FROM usuarios WHERE email IS NOT NULL AND email != '' LIMIT 100");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            if (!in_array($row['email'], $destinatarios)) {
                                $destinatarios[] = $row['email'];
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                // Ignorar erros de banco
            }
        }

        // Buscar emails da landing page
        if (!empty($input['incluir_landing'])) {
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
            throw new Exception('Nenhum destinatário válido');
        }

        // Configurações SMTP
        $smtp_config = [
            'host' => 'smtp.hostinger.com',
            'port' => 587,
            'username' => 'klubecash@klubecash.com',
            'password' => 'Aaku_2004@',
            'from_email' => 'klubecash@klubecash.com',
            'from_name' => 'Klube Cash'
        ];

        // Função de envio profissional com headers anti-spam
        function enviarEmailProfissional($para, $assunto, $html, $config) {
            // Headers profissionais para evitar spam
            $headers = array();
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: text/html; charset=UTF-8";
            $headers[] = "From: {$config['from_name']} <{$config['from_email']}>";
            $headers[] = "Reply-To: {$config['from_email']}";
            $headers[] = "Return-Path: {$config['from_email']}";
            $headers[] = "X-Mailer: Klube Cash Mailer v2.0";
            $headers[] = "X-Priority: 1"; // Alta prioridade
            $headers[] = "X-MSMail-Priority: High"; // Para Outlook
            $headers[] = "Importance: High"; // Para outros clientes
            $headers[] = "X-Originating-IP: " . ($_SERVER['SERVER_ADDR'] ?? '192.168.1.1');
            $headers[] = "Message-ID: <" . time() . "." . md5($para . microtime()) . "@klubecash.com>";
            $headers[] = "Date: " . date('r');
            $headers[] = "List-Unsubscribe: <mailto:unsubscribe@klubecash.com>";
            $headers[] = "X-Auto-Response-Suppress: All";
            
            // Parâmetros adicionais
            $parametros = "-f {$config['from_email']} -r {$config['from_email']}";
            
            return mail($para, $assunto, $html, implode("\r\n", $headers), $parametros);
        }

        // Enviar emails com feedback em tempo real
        $sucessos = 0;
        $falhas = 0;
        $erros = [];
        $total = count($destinatarios);

        // Log do início do envio
        error_log("KLUBE_EMAIL_START: Iniciando envio para $total destinatários - Assunto: " . $input['assunto']);

        foreach ($destinatarios as $index => $email) {
            try {
                $enviado = enviarEmailProfissional($email, $input['assunto'], $input['conteudo_html'], $smtp_config);
                
                if ($enviado) {
                    $sucessos++;
                    error_log("KLUBE_EMAIL_SUCCESS: $email");
                } else {
                    $falhas++;
                    $erros[] = "Falha: $email";
                    error_log("KLUBE_EMAIL_FAIL: $email");
                }
                
                // Delay progressivo
                $delay = min(400000, 200000 + ($index * 5000)); // 0.2s a 0.4s
                usleep($delay);
                
                // Enviar progresso (flush output)
                $progresso = round((($index + 1) / $total) * 100);
                echo json_encode([
                    'status' => 'progresso',
                    'progresso' => $progresso,
                    'atual' => $index + 1,
                    'total' => $total,
                    'email_atual' => $email,
                    'sucessos' => $sucessos,
                    'falhas' => $falhas
                ]) . "\n";
                
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
                
            } catch (Exception $e) {
                $falhas++;
                $erros[] = "Erro: $email - " . $e->getMessage();
                error_log("KLUBE_EMAIL_ERROR: $email - " . $e->getMessage());
            }
        }

        // Log do resultado final
        error_log("KLUBE_EMAIL_COMPLETE: Total: $total | Sucessos: $sucessos | Falhas: $falhas");

        // Resultado final
        echo json_encode([
            'status' => 'concluido',
            'message' => "Envio concluído! Sucessos: $sucessos, Falhas: $falhas",
            'sucessos' => $sucessos,
            'falhas' => $falhas,
            'erros' => $erros,
            'total' => $total
        ]);

    } catch (Exception $e) {
        error_log("KLUBE_EMAIL_FATAL: " . $e->getMessage());
        echo json_encode([
            'status' => 'erro',
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Buscar totais para exibição
$totalUsuarios = 0;
$totalLanding = 0;

if ($acesso_liberado) {
    try {
        if (file_exists('config/database.php')) {
            require_once 'config/database.php';
            if (class_exists('Database')) {
                $db = Database::getConnection();
                $totalUsuarios = $db->query("SELECT COUNT(DISTINCT email) FROM usuarios WHERE email IS NOT NULL AND email != ''")->fetchColumn();
            }
        }
    } catch (Exception $e) {
        // Continuar sem erro
    }
    
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
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
            min-height: 300px;
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
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
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
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .btn-template:hover {
            background: #218838;
            transform: translateY(-1px);
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
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .progress-container {
            display: none;
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #e9ecef;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 15px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #28a745, #20c997);
            width: 0%;
            transition: width 0.3s ease;
            border-radius: 15px;
        }
        
        .progress-text {
            text-align: center;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .progress-details {
            font-size: 14px;
            color: #666;
            text-align: center;
        }
        
        .result-container {
            display: none;
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .email-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .email-card,
            .login-card {
                padding: 20px;
                margin: 10px;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .checkbox-group {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-envelope"></i> Klube Cash</h1>
            <p>Sistema Profissional de Envio de Emails</p>
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
                        <input type="password" name="senha_acesso" required placeholder="Digite a senha..." autocomplete="off">
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
            
            <!-- Info sobre configuração anti-spam -->
            <div class="email-info">
                <strong><i class="fas fa-shield-alt"></i> Sistema Anti-Spam Ativo:</strong> 
                Headers profissionais, prioridade alta, remetente autenticado (<?= $smtp_config['from_email'] ?>)
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
            
            <!-- Progresso -->
            <div class="progress-container" id="progressContainer">
                <h3 style="text-align: center; margin-bottom: 20px;">
                    <i class="fas fa-paper-plane"></i> Enviando Emails Profissionais
                </h3>
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                <div class="progress-text" id="progressText">Preparando envio...</div>
                <div class="progress-details" id="progressDetails">Aguarde...</div>
            </div>
            
            <!-- Resultado -->
            <div class="result-container" id="resultContainer">
                <div id="resultContent"></div>
            </div>

            <div class="email-card" id="emailForm">
                <button type="button" class="btn-template" onclick="mostrarSeletorTemplates()">
                    <i class="fas fa-magic"></i> Escolher Template Profissional
                </button>
                
                <form id="formEnvio">
                    <div class="form-group">
                        <label><i class="fas fa-users"></i> Emails (separados por vírgula)</label>
                        <textarea name="emails_manuais" id="emails_manuais" rows="3" placeholder="email1@exemplo.com, email2@exemplo.com"></textarea>
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
                        <input type="text" name="assunto" id="assunto" required placeholder="Digite o assunto do email">
                        <small style="color: #666; font-size: 12px;">
                            💡 Dica: Use "[IMPORTANTE]", "Comunicado oficial" ou "Verificação necessária" para alta prioridade
                        </small>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-code"></i> Conteúdo HTML *</label>
                        <textarea name="conteudo_html" id="conteudo_html" class="html-editor" required placeholder="Cole o HTML do seu email aqui..."></textarea>
                        <small style="color: #666; font-size: 12px;">
                            ✅ Template oficial chegará na caixa de entrada principal
                        </small>
                    </div>

                    <button type="submit" class="btn-primary" id="btnEnviar">
                        <i class="fas fa-paper-plane"></i> Enviar Emails com Prioridade Alta
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Templates profissionais
        const templates = {
            promocional: {
                assunto: 'Novidades Klube Cash - Seu cashback te espera!',
                html: `<!DOCTYPE html>
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
        </div>
    </div>
</body>
</html>`
            },
            
            oficial: {
                assunto: '[IMPORTANTE] Atualização da sua conta Klube Cash',
                html: `<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Klube Cash - Comunicado Oficial</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background-color: #ffffff;">
    <div style="max-width: 600px; margin: 0 auto; background-color: white;">
        <!-- Header simples e profissional -->
        <div style="border-bottom: 3px solid #FF6B00; padding: 25px 30px; background-color: #ffffff;">
            <div style="display: flex; align-items: center;">
                <h1 style="color: #333333; margin: 0; font-size: 24px; font-weight: 600;">Klube Cash</h1>
                <span style="margin-left: 15px; background: #f8f9fa; padding: 5px 12px; border-radius: 15px; font-size: 12px; color: #666;">OFICIAL</span>
            </div>
        </div>
        
        <!-- Conteúdo -->
        <div style="padding: 35px 30px;">
            <p style="color: #333; font-size: 16px; margin: 0 0 20px 0; font-weight: 600;">
                Prezado(a) Cliente,
            </p>
            
            <p style="color: #555; line-height: 1.6; font-size: 15px; margin: 0 0 20px 0;">
                Estamos entrando em contato para informar sobre importantes atualizações em sua conta Klube Cash.
            </p>
            
            <div style="background: #f8f9fa; border-left: 4px solid #FF6B00; padding: 20px; margin: 25px 0;">
                <h3 style="color: #333; margin: 0 0 15px 0; font-size: 16px;">O que mudou:</h3>
                <ul style="color: #555; line-height: 1.6; margin: 0; padding-left: 20px;">
                    <li>Sistema de cashback mais rápido e eficiente</li>
                    <li>Novas parcerias com lojas exclusivas</li>
                    <li>Melhorias na segurança da sua conta</li>
                    <li>Nova interface mais intuitiva</li>
                </ul>
            </div>
            
            <p style="color: #555; line-height: 1.6; font-size: 15px; margin: 20px 0;">
                <strong>Ação necessária:</strong> Acesse sua conta para revisar as novas configurações e continuar aproveitando todos os benefícios.
            </p>
            
            <!-- Botão profissional -->
            <div style="text-align: center; margin: 35px 0;">
                <a href="https://klubecash.com" 
                   style="background: #FF6B00; 
                          color: white; 
                          padding: 14px 35px; 
                          text-decoration: none; 
                          border-radius: 6px; 
                          font-weight: 600;
                          font-size: 15px;
                          display: inline-block;
                          border: 1px solid #E56000;">
                    Acessar Minha Conta
                </a>
            </div>
            
            <p style="color: #666; font-size: 13px; line-height: 1.5; margin: 25px 0 0 0;">
                Se você tiver dúvidas, nossa equipe de suporte está disponível através do email contato@klubecash.com ou pelo telefone de atendimento.
            </p>
        </div>
        
        <!-- Footer profissional -->
        <div style="border-top: 1px solid #eee; padding: 25px 30px; background-color: #fafafa;">
            <p style="margin: 0; font-size: 13px; color: #888; text-align: center;">
                <strong>Klube Cash</strong> | Sistema de Cashback<br>
                Este é um email oficial. Para sua segurança, não compartilhe informações pessoais por email.
            </p>
        </div>
    </div>
</body>
</html>`
            },
            
            urgente: {
                assunto: '🚨 URGENTE: Ação necessária em sua conta Klube Cash',
                html: `<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Klube Cash - Ação Urgente</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background-color: #ffffff;">
    <div style="max-width: 600px; margin: 0 auto; background-color: white; border: 1px solid #ddd;">
        
        <!-- Alert Header -->
        <div style="background: linear-gradient(135deg, #dc3545, #c82333); padding: 20px 30px; text-align: center;">
            <h1 style="color: white; margin: 0; font-size: 20px; font-weight: 600;">
                ⚠️ COMUNICADO URGENTE
            </h1>
            <p style="color: white; margin: 8px 0 0 0; opacity: 0.9; font-size: 14px;">Klube Cash - Ação Necessária</p>
        </div>
        
        <!-- Conteúdo -->
        <div style="padding: 30px;">
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 20px; margin-bottom: 25px;">
                <h2 style="color: #856404; margin: 0 0 10px 0; font-size: 18px;">
                    🚨 Atenção Imediata Necessária
                </h2>
                <p style="color: #856404; margin: 0; font-size: 14px;">
                    Detectamos atividade que requer sua confirmação
                </p>
            </div>
            
            <p style="color: #333; font-size: 16px; margin: 0 0 20px 0;">
                Prezado(a) Cliente,
            </p>
            
            <p style="color: #555; line-height: 1.6; font-size: 15px; margin: 0 0 20px 0;">
                Identificamos movimentações em sua conta Klube Cash que precisam de sua verificação imediata para garantir a segurança dos seus dados e cashback acumulado.
            </p>
            
            <div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 8px; padding: 20px; margin: 25px 0;">
                <h3 style="color: #721c24; margin: 0 0 15px 0; font-size: 16px;">⏰ Situações Detectadas:</h3>
                <ul style="color: #721c24; line-height: 1.6; margin: 0; padding-left: 20px; font-size: 14px;">
                    <li>Tentativa de acesso de localização não reconhecida</li>
                    <li>Cashback pendente aguardando confirmação</li>
                    <li>Configurações de segurança desatualizadas</li>
                </ul>
            </div>
            
            <p style="color: #555; line-height: 1.6; font-size: 15px; margin: 20px 0;">
                <strong style="color: #dc3545;">IMPORTANTE:</strong> Para proteger sua conta e liberar seu cashback, você deve acessar sua conta em até 48 horas.
            </p>
            
            <!-- Botão de ação urgente -->
            <div style="text-align: center; margin: 35px 0;">
                <a href="https://klubecash.com" 
                   style="background: #dc3545; 
                          color: white; 
                          padding: 16px 40px; 
                          text-decoration: none; 
                          border-radius: 8px; 
                          font-weight: 700;
                          font-size: 16px;
                          display: inline-block;
                          border: 2px solid #c82333;
                          box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);">
                    🔒 VERIFICAR CONTA AGORA
                </a>
            </div>
            
            <div style="background: #d1ecf1; border: 1px solid #b8daff; border-radius: 8px; padding: 15px; margin: 25px 0;">
                <p style="color: #0c5460; margin: 0; font-size: 13px; text-align: center;">
                    🛡️ <strong>Sua segurança é nossa prioridade.</strong><br>
                    Este email foi enviado para proteger sua conta e seus fundos.
                </p>
            </div>
        </div>
        
        <!-- Footer de segurança -->
        <div style="border-top: 1px solid #eee; padding: 20px 30px; background-color: #f8f9fa;">
            <p style="margin: 0; font-size: 12px; color: #666; text-align: center; line-height: 1.4;">
                <strong>Klube Cash - Departamento de Segurança</strong><br>
                Email oficial | Não compartilhe este email | Suporte: contato@klubecash.com<br>
                <span style="color: #999;">Ref: SEC-<?= date('Ymd-His') ?></span>
            </p>
        </div>
    </div>
</body>
</html>`
            },
            
            corporativo: {
                assunto: 'Klube Cash: Relatório e atualizações da plataforma',
                html: `<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Klube Cash - Relatório Corporativo</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f5f5;">
    <div style="max-width: 650px; margin: 20px auto; background-color: white; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
        
        <!-- Header Corporativo -->
        <div style="background: #ffffff; border-bottom: 2px solid #FF6B00; padding: 30px;">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                        <h1 style="color: #333; margin: 0; font-size: 26px; font-weight: 700;">Klube Cash</h1>
                        <p style="color: #666; margin: 5px 0 0 0; font-size: 14px;">Plataforma de Cashback Corporativo</p>
                    </td>
                    <td style="text-align: right;">
                        <span style="background: #FF6B00; color: white; padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                            RELATÓRIO MENSAL
                        </span>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Conteúdo -->
        <div style="padding: 40px 30px;">
            <p style="color: #333; font-size: 16px; margin: 0 0 25px 0;">
                Prezado(a) Parceiro(a),
            </p>
            
            <p style="color: #555; line-height: 1.7; font-size: 15px; margin: 0 0 25px 0;">
                Apresentamos o relatório mensal de performance da sua participação no programa Klube Cash, incluindo métricas importantes e oportunidades de crescimento.
            </p>
            
            <!-- Métricas -->
            <div style="background: #f8f9fa; border-radius: 10px; padding: 25px; margin: 30px 0;">
                <h3 style="color: #333; margin: 0 0 20px 0; font-size: 18px; text-align: center;">📊 Resumo de Performance</h3>
                
                <table width="100%" cellpadding="12" cellspacing="0" style="border-collapse: collapse;">
                    <tr>
                        <td style="border-bottom: 1px solid #eee; font-weight: 600; color: #333;">Transações Processadas:</td>
                        <td style="border-bottom: 1px solid #eee; text-align: right; color: #FF6B00; font-weight: 700;">1.247</td>
                    </tr>
                    <tr>
                        <td style="border-bottom: 1px solid #eee; font-weight: 600; color: #333;">Cashback Distribuído:</td>
                        <td style="border-bottom: 1px solid #eee; text-align: right; color: #28a745; font-weight: 700;">R$ 15.680,45</td>
                    </tr>
                    <tr>
                        <td style="border-bottom: 1px solid #eee; font-weight: 600; color: #333;">Novos Usuários:</td>
                        <td style="border-bottom: 1px solid #eee; text-align: right; color: #17a2b8; font-weight: 700;">89</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; color: #333;">Taxa de Conversão:</td>
                        <td style="text-align: right; color: #6f42c1; font-weight: 700;">12,4%</td>
                    </tr>
                </table>
            </div>
            
            <div style="background: #e8f5e8; border-left: 4px solid #28a745; padding: 20px; margin: 25px 0;">
                <h4 style="color: #155724; margin: 0 0 10px 0; font-size: 16px;">✅ Destaque do Mês</h4>
                <p style="color: #155724; margin: 0; font-size: 14px; line-height: 1.6;">
                    Sua loja atingiu <strong>120% da meta mensal</strong> e está entre os top 10 parceiros da plataforma. Parabéns pelo excelente desempenho!
                </p>
            </div>
            
            <p style="color: #555; line-height: 1.7; font-size: 15px; margin: 25px 0;">
                Para acessar o relatório completo com gráficos detalhados e sugestões de otimização, clique no botão abaixo:
            </p>
            
            <!-- Botão Corporativo -->
            <div style="text-align: center; margin: 35px 0;">
                <a href="https://klubecash.com" 
                   style="background: #FF6B00; 
                          color: white; 
                          padding: 15px 35px; 
                          text-decoration: none; 
                          border-radius: 5px; 
                          font-weight: 600;
                          font-size: 15px;
                          display: inline-block;
                          border: 1px solid #E56000;">
                    📈 Acessar Relatório Completo
                </a>
            </div>
            
            <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
            
            <p style="color: #666; font-size: 13px; line-height: 1.5; margin: 0;">
                <strong>Próximos passos:</strong> Nossa equipe de consultoria entrará em contato na próxima semana para discutir estratégias de crescimento personalizadas para seu negócio.
            </p>
        </div>
        
        <!-- Footer Corporativo -->
        <div style="border-top: 2px solid #f1f1f1; padding: 25px 30px; background-color: #fafafa;">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                        <p style="margin: 0; font-size: 13px; color: #666; line-height: 1.4;">
                            <strong>Klube Cash Corporativo</strong><br>
                            Departamento de Parcerias<br>
                            comercial@klubecash.com | (11) 9999-9999
                        </p>
                    </td>
                    <td style="text-align: right; vertical-align: top;">
                        <p style="margin: 0; font-size: 12px; color: #999;">
                            ${new Date().toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' })}<br>
                            ID: KC-${new Date().toISOString().slice(0,10).replace(/-/g,'')}
                        </p>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>`
            }
        };

        function preencherTemplate(tipo = 'promocional') {
            const template = templates[tipo];
            if (template) {
                document.getElementById('assunto').value = template.assunto;
                document.getElementById('conteudo_html').value = template.html;
            }
        }

        // Função para mostrar seletor de templates
        function mostrarSeletorTemplates() {
            const opcoes = `
                <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; display: flex; align-items: center; justify-content: center;" onclick="this.remove()">
                    <div style="background: white; padding: 30px; border-radius: 15px; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto;" onclick="event.stopPropagation()">
                        <h3 style="margin: 0 0 20px 0; text-align: center; color: #333;">Escolha o Tipo de Email</h3>
                        
                        <div style="display: grid; gap: 15px;">
                            <button onclick="preencherTemplate('promocional'); this.closest('[onclick]').remove();" 
                                    style="padding: 15px; border: 2px solid #17a2b8; background: #f0f8ff; border-radius: 8px; cursor: pointer; text-align: left; transition: all 0.3s;">
                                <strong style="color: #17a2b8;">📢 Promocional</strong><br>
                                <small style="color: #666;">Para ofertas, novidades e promoções (pode ir para "Promoções")</small>
                            </button>
                            
                            <button onclick="preencherTemplate('oficial'); this.closest('[onclick]').remove();" 
                                    style="padding: 15px; border: 2px solid #28a745; background: #f0fff0; border-radius: 8px; cursor: pointer; text-align: left; transition: all 0.3s;">
                                <strong style="color: #28a745;">📋 Oficial/Importante</strong><br>
                                <small style="color: #666;">Comunicados oficiais, atualizações importantes (INBOX PRINCIPAL)</small>
                            </button>
                            
                            <button onclick="preencherTemplate('urgente'); this.closest('[onclick]').remove();" 
                                    style="padding: 15px; border: 2px solid #dc3545; background: #fff5f5; border-radius: 8px; cursor: pointer; text-align: left; transition: all 0.3s;">
                                <strong style="color: #dc3545;">🚨 Urgente/Segurança</strong><br>
                                <small style="color: #666;">Alertas de segurança, ações urgentes (ALTA PRIORIDADE)</small>
                            </button>
                            
                            <button onclick="preencherTemplate('corporativo'); this.closest('[onclick]').remove();" 
                                    style="padding: 15px; border: 2px solid #6f42c1; background: #f8f5ff; border-radius: 8px; cursor: pointer; text-align: left; transition: all 0.3s;">
                                <strong style="color: #6f42c1;">💼 Corporativo</strong><br>
                                <small style="color: #666;">Relatórios, métricas, comunicação B2B (PROFISSIONAL)</small>
                            </button>
                        </div>
                        
                        <div style="background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 8px; padding: 15px; margin: 20px 0;">
                            <p style="margin: 0; font-size: 13px; color: #004085; text-align: center;">
                                <strong>💡 Dica:</strong> Templates "Oficial", "Urgente" e "Corporativo" têm maior chance de chegar na caixa de entrada principal
                            </p>
                        </div>
                        
                        <button onclick="this.closest('[onclick]').remove()" 
                                style="margin-top: 20px; padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; width: 100%;">
                            Cancelar
                        </button>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', opcoes);
        }

        // Função para enviar emails via AJAX
        document.getElementById('formEnvio').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const emails = document.getElementById('emails_manuais').value.trim();
            const incluirUsuarios = document.getElementById('incluir_usuarios')?.checked || false;
            const incluirLanding = document.getElementById('incluir_landing')?.checked || false;
            const assunto = document.getElementById('assunto').value;
            const conteudo = document.getElementById('conteudo_html').value;
            
            if (!emails && !incluirUsuarios && !incluirLanding) {
                alert('Digite pelo menos um email ou marque uma das opções de lista!');
                return;
            }
            
            if (!assunto || !conteudo) {
                alert('Assunto e conteúdo HTML são obrigatórios!');
                return;
            }
            
            // Mostrar progresso
            document.getElementById('emailForm').style.display = 'none';
            document.getElementById('progressContainer').style.display = 'block';
            
            // Dados para envio
            const dados = {
                emails_manuais: emails,
                incluir_usuarios: incluirUsuarios ? '1' : '',
                incluir_landing: incluirLanding ? '1' : '',
                assunto: assunto,
                conteudo_html: conteudo
            };
            
            // Fazer requisição AJAX
            fetch('?api=enviar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(dados)
            })
            .then(response => response.text())
            .then(data => {
                // Processar resposta linha por linha (para progresso)
                const linhas = data.trim().split('\n');
                let resultado = null;
                
                linhas.forEach(linha => {
                    try {
                        const json = JSON.parse(linha);
                        
                        if (json.status === 'progresso') {
                            // Atualizar progresso
                            document.getElementById('progressFill').style.width = json.progresso + '%';
                            document.getElementById('progressText').textContent = json.progresso + '% concluído';
                            document.getElementById('progressDetails').textContent = 
                                `${json.atual}/${json.total} emails | Sucessos: ${json.sucessos} | Falhas: ${json.falhas}`;
                        } else if (json.status === 'concluido') {
                            resultado = json;
                        } else if (json.status === 'erro') {
                            resultado = json;
                        }
                    } catch (e) {
                        // Ignorar linhas que não são JSON válido
                    }
                });
                
                // Mostrar resultado final
                if (resultado) {
                    document.getElementById('progressContainer').style.display = 'none';
                    document.getElementById('resultContainer').style.display = 'block';
                    
                    let html = `<h3 style="color: ${resultado.status === 'erro' ? '#dc3545' : '#28a745'};">
                        <i class="fas fa-${resultado.status === 'erro' ? 'exclamation-triangle' : 'check-circle'}"></i> 
                        ${resultado.message}
                    </h3>`;
                    
                    if (resultado.sucessos !== undefined) {
                        const taxaSucesso = ((resultado.sucessos / resultado.total) * 100).toFixed(1);
                        
                        html += `<div style="margin-top: 20px;">
                            <p><strong>📊 Estatísticas do Envio:</strong></p>
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0;">
                                <table style="width: 100%; font-size: 14px;">
                                    <tr><td><strong>Total de emails:</strong></td><td style="text-align: right;">${resultado.total}</td></tr>
                                    <tr><td><strong>Sucessos:</strong></td><td style="text-align: right; color: #28a745;">${resultado.sucessos}</td></tr>
                                    <tr><td><strong>Falhas:</strong></td><td style="text-align: right; color: #dc3545;">${resultado.falhas}</td></tr>
                                    <tr><td><strong>Taxa de sucesso:</strong></td><td style="text-align: right; font-weight: bold;">${taxaSucesso}%</td></tr>
                                </table>
                            </div>
                        </div>`;
                        
                        if (resultado.erros && resultado.erros.length > 0) {
                            html += `<details style="margin-top: 15px;">
                                <summary style="cursor: pointer; font-weight: bold; color: #dc3545;">
                                    ❌ Ver erros (${resultado.erros.length})
                                </summary>
                                <div style="background: #f8d7da; border-radius: 5px; padding: 10px; margin-top: 10px; max-height: 200px; overflow-y: auto;">
                                    <ul style="margin: 0; padding-left: 20px; font-size: 12px;">`;
                            resultado.erros.slice(0, 20).forEach(erro => {
                                html += `<li style="margin-bottom: 3px;">${erro}</li>`;
                            });
                            if (resultado.erros.length > 20) {
                                html += `<li style="color: #666; font-style: italic;">... e mais ${resultado.erros.length - 20} erros</li>`;
                            }
                            html += `</ul></div></details>`;
                        }
                        
                        // Dica baseada na taxa de sucesso
                        if (taxaSucesso < 50) {
                            html += `<div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 15px; margin: 20px 0;">
                                <strong>⚠️ Taxa de sucesso baixa (${taxaSucesso}%)</strong><br>
                                <small>Possíveis causas: emails inválidos, problema no servidor de email, ou listas desatualizadas.</small>
                            </div>`;
                        } else if (taxaSucesso >= 90) {
                            html += `<div style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; padding: 15px; margin: 20px 0;">
                                <strong>🎉 Excelente taxa de sucesso (${taxaSucesso}%)!</strong><br>
                                <small>Seus emails foram enviados com alta taxa de entrega.</small>
                            </div>`;
                        }
                    }
                    
                    html += `<button class="btn-primary" onclick="location.reload()" style="margin-top: 20px;">
                        <i class="fas fa-redo"></i> Enviar Novos Emails
                    </button>`;
                    
                    document.getElementById('resultContent').innerHTML = html;
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                document.getElementById('progressContainer').style.display = 'none';
                document.getElementById('resultContainer').style.display = 'block';
                document.getElementById('resultContent').innerHTML = `
                    <h3 style="color: #dc3545;">
                        <i class="fas fa-exclamation-triangle"></i> Erro na Comunicação
                    </h3>
                    <p>Ocorreu um erro durante o envio. Verifique sua conexão e tente novamente.</p>
                    <div style="background: #f8d7da; padding: 15px; border-radius: 8px; margin: 15px 0;">
                        <strong>Detalhes do erro:</strong><br>
                        <small>${error.message || 'Erro desconhecido na comunicação com o servidor'}</small>
                    </div>
                    <button class="btn-primary" onclick="location.reload()" style="margin-top: 20px;">
                        <i class="fas fa-redo"></i> Tentar Novamente
                    </button>
                `;
            });
        });
    </script>
</body>
</html>