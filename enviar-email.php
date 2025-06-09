<?php
/**
 * Sistema de Envio de Emails - Klube Cash
 * Versão com AJAX e progresso em tempo real
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
                        $stmt = $db->query("SELECT DISTINCT email FROM usuarios WHERE email IS NOT NULL AND email != '' LIMIT 50");
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
            'port' => 587, // Mudando para porta 587 (menos restritiva)
            'username' => 'klubecash@klubecash.com',
            'password' => 'Aaku_2004@',
            'from_email' => 'klubecash@klubecash.com',
            'from_name' => 'Klube Cash'
        ];

        // Função de envio simplificada
        function enviarEmailSimples($para, $assunto, $html, $config) {
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: {$config['from_name']} <{$config['from_email']}>\r\n";
            $headers .= "Reply-To: {$config['from_email']}\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
            
            // Adicionar parâmetros adicionais para o sendmail
            $parametros = "-f {$config['from_email']}";
            
            return mail($para, $assunto, $html, $headers, $parametros);
        }

        // Enviar emails com feedback em tempo real
        $sucessos = 0;
        $falhas = 0;
        $erros = [];
        $total = count($destinatarios);

        foreach ($destinatarios as $index => $email) {
            try {
                $enviado = enviarEmailSimples($email, $input['assunto'], $input['conteudo_html'], $smtp_config);
                
                if ($enviado) {
                    $sucessos++;
                } else {
                    $falhas++;
                    $erros[] = "Falha: $email";
                }
                
                // Pequeno delay
                usleep(250000); // 0.25 segundos
                
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
            }
        }

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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-envelope"></i> Klube Cash</h1>
            <p>Sistema de Envio de Emails com Progresso</p>
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
                    <i class="fas fa-paper-plane"></i> Enviando Emails
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
                <button type="button" class="btn-template" onclick="preencherTemplate()">
                    <i class="fas fa-magic"></i> Carregar Template de Exemplo
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
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-code"></i> Conteúdo HTML *</label>
                        <textarea name="conteudo_html" id="conteudo_html" class="html-editor" required placeholder="Cole o HTML do seu email aqui..."></textarea>
                    </div>

                    <button type="submit" class="btn-primary" id="btnEnviar">
                        <i class="fas fa-paper-plane"></i> Enviar Emails
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function preencherTemplate() {
            document.getElementById('assunto').value = 'Novidades Klube Cash - Seu cashback te espera!';
            
            document.getElementById('conteudo_html').value = `<!DOCTYPE html>
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
</html>`;
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
                        html += `<div style="margin-top: 20px;">
                            <p><strong>Estatísticas:</strong></p>
                            <ul>
                                <li>Total: ${resultado.total}</li>
                                <li>Sucessos: ${resultado.sucessos}</li>
                                <li>Falhas: ${resultado.falhas}</li>
                            </ul>
                        </div>`;
                        
                        if (resultado.erros && resultado.erros.length > 0) {
                            html += `<details style="margin-top: 15px;">
                                <summary style="cursor: pointer; font-weight: bold;">Ver erros (${resultado.erros.length})</summary>
                                <ul style="margin-top: 10px;">`;
                            resultado.erros.slice(0, 10).forEach(erro => {
                                html += `<li><small>${erro}</small></li>`;
                            });
                            html += `</ul></details>`;
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
                    <p>Ocorreu um erro durante o envio. Tente novamente.</p>
                    <button class="btn-primary" onclick="location.reload()" style="margin-top: 20px;">
                        <i class="fas fa-redo"></i> Tentar Novamente
                    </button>
                `;
            });
        });
    </script>
</body>
</html>