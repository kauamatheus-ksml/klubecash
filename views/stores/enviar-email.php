<?php
/**
 * Envio de Emails Personalizados - Acesso Público
 * Arquivo na raiz para acesso direto
 */

session_start();

// Verificar se os arquivos de configuração existem antes de incluir
$config_files = [
    'config/database.php',
    'config/constants.php', 
    'utils/Email.php'
];

foreach($config_files as $file) {
    if(file_exists($file)) {
        require_once $file;
    }
}

// Senha de proteção para acesso
$senha_acesso = 'klube2024@!';

// Verificar se a senha foi fornecida
$acesso_liberado = false;
if (isset($_POST['senha_acesso']) || isset($_SESSION['email_access_granted'])) {
    if (isset($_POST['senha_acesso']) && $_POST['senha_acesso'] === $senha_acesso) {
        $_SESSION['email_access_granted'] = true;
        $acesso_liberado = true;
    } elseif (isset($_SESSION['email_access_granted'])) {
        $acesso_liberado = true;
    }
}

// Processar logout/sair
if (isset($_GET['sair'])) {
    unset($_SESSION['email_access_granted']);
    header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// Processar envio do email
if ($acesso_liberado && $_POST && $_POST['action'] === 'enviar_email') {
    $resultado = processarEnvioEmail($_POST);
}

// Buscar listas de emails disponíveis (somente se o acesso foi liberado)
$totalUsuarios = 0;
$totalLanding = 0;

if ($acesso_liberado) {
    try {
        if(class_exists('Database')) {
            $db = Database::getConnection();
            
            // Total de emails de usuários
            $totalUsuarios = $db->query("SELECT COUNT(DISTINCT email) FROM usuarios WHERE email IS NOT NULL AND email != ''")->fetchColumn();
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
    } catch (Exception $e) {
        $erro_bd = "Erro ao conectar com o banco de dados: " . $e->getMessage();
    }
}

/**
 * Função para processar o envio de emails
 */
function processarEnvioEmail($dados) {
    try {
        // Validar dados básicos
        if (empty($dados['assunto']) || empty($dados['conteudo_html'])) {
            throw new Exception('Assunto e conteúdo HTML são obrigatórios');
        }

        // Preparar lista de destinatários
        $destinatarios = [];
        
        // Emails manuais (digitados no campo)
        if (!empty($dados['emails_manuais'])) {
            $emailsManuais = explode(',', $dados['emails_manuais']);
            foreach ($emailsManuais as $email) {
                $email = trim($email);
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $destinatarios[] = $email;
                }
            }
        }

        // Incluir usuários cadastrados
        if (isset($dados['incluir_usuarios']) && $dados['incluir_usuarios'] == '1' && class_exists('Database')) {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT DISTINCT email FROM usuarios WHERE email IS NOT NULL AND email != ''");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (!in_array($row['email'], $destinatarios)) {
                    $destinatarios[] = $row['email'];
                }
            }
        }

        // Incluir emails da landing page
        if (isset($dados['incluir_landing']) && $dados['incluir_landing'] == '1') {
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
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'assunto' => $dados['assunto'],
            'total_destinatarios' => count($destinatarios)
        ];
        error_log("EMAIL_SEND_LOG: " . json_encode($logData));

        // Enviar emails
        $sucessos = 0;
        $falhas = 0;
        $erros = [];

        foreach ($destinatarios as $email) {
            try {
                $enviado = false;
                
                // Tentar usar a classe Email se existir
                if(class_exists('Email')) {
                    $enviado = Email::send(
                        $email,
                        $dados['assunto'],
                        $dados['conteudo_html'],
                        'Destinatário'
                    );
                } else {
                    // Fallback usando mail() básico
                    $headers = "MIME-Version: 1.0" . "\r\n";
                    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                    $headers .= 'From: Klube Cash <noreply@klubecash.com>' . "\r\n";
                    
                    $enviado = mail($email, $dados['assunto'], $dados['conteudo_html'], $headers);
                }
                
                if ($enviado) {
                    $sucessos++;
                } else {
                    $falhas++;
                    $erros[] = "Falha ao enviar para: $email";
                }
                
                // Delay para evitar sobrecarga do servidor SMTP
                usleep(200000); // 0.2 segundo
                
            } catch (Exception $e) {
                $falhas++;
                $erros[] = "Erro para $email: " . $e->getMessage();
            }
        }

        return [
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
        return [
            'status' => 'erro',
            'message' => 'Erro no envio: ' . $e->getMessage()
        ];
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
            max-width: 1200px;
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
        
        .header p {
            font-size: 1.1em;
            opacity: 0.9;
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
        
        .login-card h2 {
            color: #333;
            margin-bottom: 20px;
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
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .html-editor {
            min-height: 400px;
            font-family: 'Courier New', monospace;
            resize: vertical;
        }
        
        .preview-container {
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            padding: 20px;
            background: #f8f9fa;
            margin-top: 15px;
            max-height: 500px;
            overflow-y: auto;
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
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            margin-right: 10px;
            font-size: 14px;
        }
        
        .btn-logout {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            float: right;
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
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .tabs {
            display: flex;
            border-bottom: 2px solid #e1e5e9;
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 15px 30px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            background: none;
            border-top: none;
            border-left: none;
            border-right: none;
            font-size: 16px;
        }
        
        .tab.active {
            border-bottom-color: #667eea;
            color: #667eea;
            font-weight: 600;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .access-info {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            color: #004085;
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
            <p>Sistema de Envio de Emails</p>
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
                        <label for="senha_acesso">Senha de Acesso:</label>
                        <input type="password" name="senha_acesso" id="senha_acesso" required 
                               placeholder="Digite a senha..." autocomplete="off">
                    </div>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Acessar Sistema
                    </button>
                </form>
                
                <div class="access-info">
                    <small>
                        <i class="fas fa-info-circle"></i>
                        Esta é uma área restrita para envio de emails em massa. 
                        Se você não possui a senha, entre em contato com o administrador.
                    </small>
                </div>
            </div>
        <?php else: ?>
            <!-- Sistema de Envio de Emails -->
            <button onclick="window.location.href='?sair=1'" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i> Sair
            </button>
            
            <!-- Estatísticas de emails disponíveis -->
            <?php if (isset($erro_bd)): ?>
                <div class="alert alert-error">
                    <strong>Erro de Conexão:</strong> <?= htmlspecialchars($erro_bd) ?>
                </div>
            <?php else: ?>
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
                        <span class="stat-label">Total de Emails</span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Resultado do envio -->
            <?php if (isset($resultado)): ?>
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
                <form method="POST" id="emailForm">
                    <input type="hidden" name="action" value="enviar_email">
                    
                    <!-- Destinatários -->
                    <div class="form-group">
                        <label for="emails_manuais">
                            <i class="fas fa-users"></i> Destinatários (separados por vírgula)
                        </label>
                        <textarea 
                            name="emails_manuais" 
                            id="emails_manuais" 
                            rows="4" 
                            placeholder="exemplo@email.com, outro@email.com, terceiro@email.com..."
                        ><?= isset($_POST['emails_manuais']) ? htmlspecialchars($_POST['emails_manuais']) : '' ?></textarea>
                        <small style="color: #666; font-size: 14px;">
                            Digite os emails separados por vírgula ou use as opções abaixo
                        </small>
                    </div>

                    <!-- Opções de listas -->
                    <?php if (!isset($erro_bd)): ?>
                    <div class="form-group">
                        <label>Incluir Listas de Emails:</label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" name="incluir_usuarios" value="1" id="incluir_usuarios">
                                <label for="incluir_usuarios">
                                    Todos os usuários cadastrados (<?= number_format($totalUsuarios) ?>)
                                </label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="incluir_landing" value="1" id="incluir_landing">
                                <label for="incluir_landing">
                                    Emails da landing page (<?= number_format($totalLanding) ?>)
                                </label>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Assunto -->
                    <div class="form-group">
                        <label for="assunto">
                            <i class="fas fa-tag"></i> Assunto do Email *
                        </label>
                        <input 
                            type="text" 
                            name="assunto" 
                            id="assunto" 
                            required 
                            placeholder="Digite o assunto do email..."
                            value="<?= isset($_POST['assunto']) ? htmlspecialchars($_POST['assunto']) : '' ?>"
                        >
                    </div>

                    <!-- Tabs para HTML e Preview -->
                    <div class="tabs">
                        <button type="button" class="tab active" onclick="switchTab('html')">
                            <i class="fas fa-code"></i> Código HTML
                        </button>
                        <button type="button" class="tab" onclick="switchTab('preview')">
                            <i class="fas fa-eye"></i> Visualizar
                        </button>
                    </div>

                    <!-- Conteúdo HTML -->
                    <div class="tab-content active" id="html-tab">
                        <div class="form-group">
                            <label for="conteudo_html">
                                <i class="fas fa-code"></i> Conteúdo HTML *
                            </label>
                            <textarea 
                                name="conteudo_html" 
                                id="conteudo_html" 
                                class="html-editor" 
                                required 
                                placeholder="Digite o HTML do seu email aqui..."
                            ><?= isset($_POST['conteudo_html']) ? htmlspecialchars($_POST['conteudo_html']) : '' ?></textarea>
                            <small style="color: #666; font-size: 14px;">
                                Cole aqui o código HTML do seu email. Use a aba "Visualizar" para ver como ficará.
                            </small>
                        </div>
                    </div>

                    <!-- Preview -->
                    <div class="tab-content" id="preview-tab">
                        <div class="preview-container">
                            <div id="email-preview">
                                <p style="color: #666; font-style: italic;">
                                    O preview aparecerá aqui quando você digitar o HTML na aba anterior
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Botões -->
                    <div style="text-align: right; margin-top: 30px;">
                        <button type="button" class="btn-secondary" onclick="preencherTemplate()">
                            <i class="fas fa-magic"></i> Template Exemplo
                        </button>
                        <button type="submit" class="btn-primary" id="enviarBtn" style="width: auto; margin-left: 10px;">
                            <i class="fas fa-paper-plane"></i> Enviar Emails
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Alternar entre tabs
        function switchTab(tab) {
            // Remover active de todas as tabs
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            // Ativar tab selecionada
            event.target.classList.add('active');
            document.getElementById(tab + '-tab').classList.add('active');
            
            // Se for tab de preview, atualizar o conteúdo
            if (tab === 'preview') {
                atualizarPreview();
            }
        }

        // Atualizar preview do email
        function atualizarPreview() {
            const htmlContent = document.getElementById('conteudo_html').value;
            const previewContainer = document.getElementById('email-preview');
            
            if (htmlContent.trim()) {
                previewContainer.innerHTML = htmlContent;
            } else {
                previewContainer.innerHTML = '<p style="color: #666; font-style: italic;">Digite o HTML na aba anterior para ver o preview</p>';
            }
        }

        // Preencher com template de exemplo
        function preencherTemplate() {
            const template = `<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Klube Cash</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <div style="max-width: 600px; margin: 0 auto; background-color: white;">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #FF6B00, #FF8533); padding: 30px; text-align: center;">
            <h1 style="color: white; margin: 0; font-size: 28px;">Klube Cash</h1>
            <p style="color: white; margin: 10px 0 0 0; opacity: 0.9;">Transforme suas compras em dinheiro de volta</p>
        </div>
        
        <!-- Conteúdo -->
        <div style="padding: 30px;">
            <h2 style="color: #333; margin-top: 0;">Olá!</h2>
            
            <p style="color: #666; line-height: 1.6; font-size: 16px;">
                Este é um email de exemplo do sistema Klube Cash. Você pode personalizar 
                completamente este conteúdo com seu próprio HTML.
            </p>
            
            <p style="color: #666; line-height: 1.6; font-size: 16px;">
                Algumas possibilidades:
            </p>
            
            <ul style="color: #666; line-height: 1.6;">
                <li>Promoções especiais</li>
                <li>Novidades do programa</li>
                <li>Relatórios de cashback</li>
                <li>Convites para eventos</li>
            </ul>
            
            <!-- Botão -->
            <div style="text-align: center; margin: 30px 0;">
                <a href="https://klubecash.com" 
                   style="background: linear-gradient(135deg, #FF6B00, #FF8533); 
                          color: white; 
                          padding: 15px 30px; 
                          text-decoration: none; 
                          border-radius: 25px; 
                          font-weight: bold;
                          display: inline-block;">
                    Acessar Klube Cash
                </a>
            </div>
        </div>
        
        <!-- Footer -->
        <div style="background-color: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 14px;">
            <p style="margin: 0;">© 2024 Klube Cash. Todos os direitos reservados.</p>
            <p style="margin: 10px 0 0 0;">Este é um email automático, por favor não responda.</p>
        </div>
    </div>
</body>
</html>`;

            document.getElementById('conteudo_html').value = template;
            if (document.querySelector('.tab.active').textContent.includes('Visualizar')) {
                atualizarPreview();
            }
        }

        // Validação do formulário
        document.getElementById('emailForm')?.addEventListener('submit', function(e) {
            const emailsManuais = document.getElementById('emails_manuais').value.trim();
            const incluirUsuarios = document.getElementById('incluir_usuarios')?.checked || false;
            const incluirLanding = document.getElementById('incluir_landing')?.checked || false;
            
            if (!emailsManuais && !incluirUsuarios && !incluirLanding) {
                e.preventDefault();
                alert('Você deve especificar pelo menos um destinatário:\n- Digite emails manualmente\n- Marque "Incluir usuários cadastrados"\n- Marque "Incluir emails da landing page"');
                return false;
            }
            
            // Confirmar envio
            const totalEstimado = emailsManuais.split(',').filter(e => e.trim()).length + 
                                 (incluirUsuarios ? <?= $totalUsuarios ?> : 0) + 
                                 (incluirLanding ? <?= $totalLanding ?> : 0);
            
            if (totalEstimado > 0) {
                const confirmar = confirm(`Confirma o envio para aproximadamente ${totalEstimado} destinatários?`);
                if (!confirmar) {
                    e.preventDefault();
                    return false;
                }
                
                // Mostrar indicador de carregamento
                document.getElementById('enviarBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
                document.getElementById('enviarBtn').disabled = true;
            }
        });

        // Auto-update do preview quando digitar no HTML
        document.getElementById('conteudo_html')?.addEventListener('input', function() {
            if (document.getElementById('preview-tab').classList.contains('active')) {
                atualizarPreview();
            }
        });
    </script>
</body>
</html>