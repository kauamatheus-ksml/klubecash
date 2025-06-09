<?php
/**
 * Envio de Emails Personalizados - Klube Cash
 * Permite ao administrador enviar emails em HTML para múltiplos destinatários
 */

session_start();

// Verificação de segurança - só admin pode acessar
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ' . LOGIN_URL);
    exit;
}

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../utils/Email.php';

// Processar envio do email
if ($_POST && $_POST['action'] === 'enviar_email') {
    $resultado = processarEnvioEmail($_POST);
}

// Buscar listas de emails disponíveis
$db = Database::getConnection();

// Total de emails de usuários
$totalUsuarios = $db->query("SELECT COUNT(DISTINCT email) FROM usuarios WHERE email IS NOT NULL AND email != ''")->fetchColumn();

// Total de emails da landing page
$totalLanding = 0;
$emailsFile = '../../embreve/emails.json';
if (file_exists($emailsFile)) {
    $emailsData = json_decode(file_get_contents($emailsFile), true);
    if ($emailsData) {
        $emailsUnicos = array_unique(array_column($emailsData, 'email'));
        $totalLanding = count(array_filter($emailsUnicos));
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
        if (isset($dados['incluir_usuarios']) && $dados['incluir_usuarios'] == '1') {
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
            $emailsFile = '../../embreve/emails.json';
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

        // Enviar emails
        $sucessos = 0;
        $falhas = 0;
        $erros = [];

        foreach ($destinatarios as $email) {
            try {
                $enviado = Email::send(
                    $email,
                    $dados['assunto'],
                    $dados['conteudo_html'],
                    'Destinatário'
                );
                
                if ($enviado) {
                    $sucessos++;
                } else {
                    $falhas++;
                    $erros[] = "Falha ao enviar para: $email";
                }
                
                // Pequeno delay para evitar sobrecarga do servidor SMTP
                usleep(100000); // 0.1 segundo
                
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
    <title>Enviar Email - Klube Cash Admin</title>
    <link href="../../assets/css/admin.css" rel="stylesheet">
    <link href="../../assets/css/responsive.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .email-form-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
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
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #FF6B00;
        }
        
        .html-editor {
            min-height: 400px;
            font-family: 'Courier New', monospace;
            resize: vertical;
        }
        
        .preview-container {
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            padding: 20px;
            background: #f8f9fa;
            margin-top: 15px;
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
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #FF6B00, #FF8533);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            display: block;
        }
        
        .stat-label {
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #FF6B00, #FF8533);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(255, 107, 0, 0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            margin-left: 10px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
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
            padding: 12px 24px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .tab.active {
            border-bottom-color: #FF6B00;
            color: #FF6B00;
            font-weight: 600;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <?php include '../components/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../components/navbar.php'; ?>
        
        <div class="content">
            <div class="page-header">
                <h1><i class="fas fa-envelope"></i> Envio de Emails Personalizados</h1>
                <p>Envie emails em HTML para múltiplos destinatários</p>
            </div>

            <!-- Estatísticas de emails disponíveis -->
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

            <!-- Resultado do envio -->
            <?php if (isset($resultado)): ?>
                <div class="alert <?= $resultado['status'] === 'sucesso' ? 'alert-success' : 'alert-error' ?>">
                    <strong><?= htmlspecialchars($resultado['message']) ?></strong>
                    <?php if (isset($resultado['detalhes'])): ?>
                        <div style="margin-top: 10px;">
                            <small>
                                Total: <?= $resultado['detalhes']['total_destinatarios'] ?> | 
                                Sucessos: <?= $resultado['detalhes']['sucessos'] ?> | 
                                Falhas: <?= $resultado['detalhes']['falhas'] ?>
                            </small>
                            <?php if (!empty($resultado['detalhes']['erros'])): ?>
                                <details style="margin-top: 10px;">
                                    <summary>Ver erros (<?= count($resultado['detalhes']['erros']) ?>)</summary>
                                    <ul style="margin-top: 5px;">
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

            <div class="email-form-container">
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
                            rows="3" 
                            placeholder="exemplo@email.com, outro@email.com, terceiro@email.com..."
                        ><?= isset($_POST['emails_manuais']) ? htmlspecialchars($_POST['emails_manuais']) : '' ?></textarea>
                        <small style="color: #666; font-size: 12px;">
                            Digite os emails separados por vírgula ou use as opções abaixo
                        </small>
                    </div>

                    <!-- Opções de listas -->
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
                        <div class="tab active" onclick="switchTab('html')">
                            <i class="fas fa-code"></i> Código HTML
                        </div>
                        <div class="tab" onclick="switchTab('preview')">
                            <i class="fas fa-eye"></i> Visualizar
                        </div>
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
                            <small style="color: #666; font-size: 12px;">
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
                        <button type="submit" class="btn-primary" id="enviarBtn">
                            <i class="fas fa-paper-plane"></i> Enviar Emails
                        </button>
                    </div>
                </form>
            </div>
        </div>
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
        document.getElementById('emailForm').addEventListener('submit', function(e) {
            const emailsManuais = document.getElementById('emails_manuais').value.trim();
            const incluirUsuarios = document.getElementById('incluir_usuarios').checked;
            const incluirLanding = document.getElementById('incluir_landing').checked;
            
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
        document.getElementById('conteudo_html').addEventListener('input', function() {
            if (document.getElementById('preview-tab').classList.contains('active')) {
                atualizarPreview();
            }
        });
    </script>
</body>
</html>