<?php
// admin/email-marketing.php
session_start();

// Verificação básica de admin (adapte conforme seu sistema de autenticação)
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: /login');
    exit;
}

require_once '../config/database.php';

// Processar ações do formulário
if ($_POST) {
    if ($_POST['action'] === 'criar_campanha') {
        criarCampanha($_POST);
    } elseif ($_POST['action'] === 'agendar_campanha') {
        agendarCampanha($_POST['campaign_id'], $_POST['data_agendamento']);
    }
}

// Buscar campanhas existentes
$db = Database::getConnection();
$campanhas = $db->query("
    SELECT *, 
    (SELECT COUNT(*) FROM email_envios WHERE campaign_id = email_campaigns.id AND status = 'enviado') as enviados
    FROM email_campaigns 
    ORDER BY data_criacao DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Contar emails cadastrados na lista
$totalEmails = $db->query("SELECT COUNT(*) FROM (SELECT email FROM users WHERE email IS NOT NULL UNION SELECT email FROM embreve/emails.json) as emails")->fetchColumn();

function criarCampanha($dados) {
    $db = Database::getConnection();
    
    // Criar a campanha
    $stmt = $db->prepare("
        INSERT INTO email_campaigns (titulo, assunto, conteudo_html, conteudo_texto, data_agendamento, status) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $dataAgendamento = !empty($dados['data_agendamento']) ? $dados['data_agendamento'] : null;
    $status = $dataAgendamento ? 'agendado' : 'rascunho';
    
    $stmt->execute([
        $dados['titulo'],
        $dados['assunto'], 
        $dados['conteudo_html'],
        strip_tags($dados['conteudo_html']), // Versão texto
        $dataAgendamento,
        $status
    ]);
    
    $campaignId = $db->lastInsertId();
    
    // Se foi agendada, preparar lista de emails
    if ($dataAgendamento) {
        prepararListaEmails($campaignId);
    }
    
    $_SESSION['sucesso'] = 'Campanha criada com sucesso!';
}

function prepararListaEmails($campaignId) {
    $db = Database::getConnection();
    
    // Buscar todos os emails únicos (do sistema + da landing page)
    $emails = [];
    
    // Emails dos usuários do sistema
    $usuariosEmails = $db->query("SELECT DISTINCT email FROM usuarios WHERE email IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
    $emails = array_merge($emails, $usuariosEmails);
    
    // Emails da landing page "em breve" 
    $emailsFile = '../embreve/emails.json';
    if (file_exists($emailsFile)) {
        $emailsData = json_decode(file_get_contents($emailsFile), true);
        foreach ($emailsData as $entry) {
            if (!empty($entry['email'])) {
                $emails[] = $entry['email'];
            }
        }
    }
    
    // Remover duplicatas
    $emails = array_unique($emails);
    
    // Inserir na tabela de envios
    $stmt = $db->prepare("INSERT IGNORE INTO email_envios (campaign_id, email) VALUES (?, ?)");
    foreach ($emails as $email) {
        $stmt->execute([$campaignId, $email]);
    }
    
    // Atualizar total na campanha
    $total = count($emails);
    $db->prepare("UPDATE email_campaigns SET total_emails = ? WHERE id = ?")->execute([$total, $campaignId]);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Marketing - Klube Cash Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: #1a202c;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .header {
            background: linear-gradient(135deg, #FF7A00, #FF9A40);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #FF7A00;
        }
        
        .btn {
            background: #FF7A00;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s;
        }
        
        .btn:hover {
            background: #E86E00;
        }
        
        .campaigns-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .campaigns-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .campaigns-table th,
        .campaigns-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .campaigns-table th {
            background: #f7fafc;
            font-weight: 600;
            color: #374151;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .status-rascunho { background: #fed7d7; color: #c53030; }
        .status-agendado { background: #bee3f8; color: #2b6cb0; }
        .status-enviado { background: #c6f6d5; color: #276749; }
        
        .template-preview {
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            background: #f9f9f9;
        }
        
        .success-message {
            background: #c6f6d5;
            color: #276749;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>📧 Email Marketing - Klube Cash</h1>
            <p>Gerencie campanhas de email para manter seus futuros clientes engajados</p>
        </div>

        <?php if (isset($_SESSION['sucesso'])): ?>
            <div class="success-message">
                <?php echo $_SESSION['sucesso']; unset($_SESSION['sucesso']); ?>
            </div>
        <?php endif; ?>

        <!-- Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>📬 Total de Emails</h3>
                <p style="font-size: 2rem; font-weight: bold; color: #FF7A00;"><?php echo number_format($totalEmails); ?></p>
                <small>Cadastrados na lista</small>
            </div>
            <div class="stat-card">
                <h3>📨 Campanhas Ativas</h3>
                <p style="font-size: 2rem; font-weight: bold; color: #2563EB;">
                    <?php echo count(array_filter($campanhas, fn($c) => in_array($c['status'], ['agendado', 'enviando']))); ?>
                </p>
                <small>Agendadas ou enviando</small>
            </div>
            <div class="stat-card">
                <h3>✅ Taxa de Entrega</h3>
                <p style="font-size: 2rem; font-weight: bold; color: #10B981;">95%</p>
                <small>Média de entregas bem-sucedidas</small>
            </div>
        </div>

        <!-- Formulário para Nova Campanha -->
        <div class="form-container">
            <h2>🎯 Criar Nova Campanha</h2>
            <form method="POST">
                <input type="hidden" name="action" value="criar_campanha">
                
                <div class="form-group">
                    <label for="titulo">Título da Campanha (interno)</label>
                    <input type="text" id="titulo" name="titulo" required placeholder="Ex: Newsletter Semanal - Semana 1">
                </div>
                
                <div class="form-group">
                    <label for="assunto">Assunto do Email</label>
                    <input type="text" id="assunto" name="assunto" required placeholder="Ex: 🚀 Novidades da Klube Cash - Última semana antes do lançamento!">
                </div>
                
                <div class="form-group">
                    <label for="conteudo_html">Conteúdo do Email (HTML)</label>
                    <textarea id="conteudo_html" name="conteudo_html" rows="15" required placeholder="Digite o conteúdo HTML do email..."><?php echo getTemplateDefault(); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="data_agendamento">Agendar Envio (opcional)</label>
                    <input type="datetime-local" id="data_agendamento" name="data_agendamento">
                    <small>Deixe em branco para salvar como rascunho</small>
                </div>
                
                <button type="submit" class="btn">💌 Criar Campanha</button>
            </form>
        </div>

        <!-- Lista de Campanhas -->
        <div class="campaigns-table">
            <h2 style="padding: 1.5rem 1.5rem 0;">📋 Campanhas Criadas</h2>
            <table>
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Assunto</th>
                        <th>Status</th>
                        <th>Agendamento</th>
                        <th>Progresso</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($campanhas as $campanha): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($campanha['titulo']); ?></td>
                        <td><?php echo htmlspecialchars($campanha['assunto']); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $campanha['status']; ?>">
                                <?php echo ucfirst($campanha['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            if ($campanha['data_agendamento']) {
                                echo date('d/m/Y H:i', strtotime($campanha['data_agendamento']));
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td>
                            <?php 
                            if ($campanha['total_emails'] > 0) {
                                $progresso = ($campanha['emails_enviados'] / $campanha['total_emails']) * 100;
                                echo $campanha['emails_enviados'] . '/' . $campanha['total_emails'] . ' (' . round($progresso) . '%)';
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if ($campanha['status'] === 'rascunho'): ?>
                                <button class="btn" onclick="agendarCampanha(<?php echo $campanha['id']; ?>)">📅 Agendar</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function agendarCampanha(id) {
            const dataAgendamento = prompt('Digite a data e hora para envio (formato: YYYY-MM-DD HH:MM):');
            if (dataAgendamento) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="agendar_campanha">
                    <input type="hidden" name="campaign_id" value="${id}">
                    <input type="hidden" name="data_agendamento" value="${dataAgendamento}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>

<?php
function getTemplateDefault() {
    return '
<div style="max-width: 600px; margin: 0 auto; font-family: Inter, sans-serif;">
    <!-- Header -->
    <div style="background: linear-gradient(135deg, #FF7A00, #FF9A40); color: white; padding: 2rem; text-align: center; border-radius: 12px 12px 0 0;">
        <img src="https://klubecash.com/assets/images/logobranco.png" alt="Klube Cash" style="height: 50px;">
        <h1 style="margin: 1rem 0 0; font-size: 1.5rem;">🎉 Novidades da Klube Cash!</h1>
    </div>
    
    <!-- Conteúdo -->
    <div style="background: white; padding: 2rem; border-radius: 0 0 12px 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h2 style="color: #333; margin-bottom: 1rem;">Olá, futuro membro! 👋</h2>
        
        <p style="color: #666; line-height: 1.6; margin-bottom: 1.5rem;">
            Esperamos que você esteja ansioso pelo lançamento da Klube Cash! Temos novidades incríveis para compartilhar com você.
        </p>
        
        <!-- Contagem Regressiva Visual -->
        <div style="background: #FFF5E6; border: 2px solid #FFD700; border-radius: 8px; padding: 1.5rem; text-align: center; margin: 1.5rem 0;">
            <h3 style="color: #FF7A00; margin-bottom: 0.5rem;">⏰ Faltam poucos dias!</h3>
            <p style="font-size: 1.2rem; font-weight: bold; color: #333;">Lançamento em 9 de junho às 18h</p>
        </div>
        
        <h3 style="color: #FF7A00; margin: 1.5rem 0 1rem;">✨ O que você pode esperar:</h3>
        <ul style="color: #666; line-height: 1.8; margin-left: 1rem;">
            <li>💰 Cashback real em centenas de lojas</li>
            <li>📱 Plataforma super fácil de usar</li>
            <li>🎁 Bônus especial para os primeiros cadastrados</li>
            <li>🔒 Segurança total para suas informações</li>
        </ul>
        
        <div style="text-align: center; margin: 2rem 0;">
            <a href="https://klubecash.com" style="background: #FF7A00; color: white; padding: 1rem 2rem; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block;">
                🚀 Visite nosso site
            </a>
        </div>
        
        <p style="color: #666; font-size: 0.9rem; text-align: center; margin-top: 2rem;">
            Siga-nos nas redes sociais para não perder nenhuma novidade!<br>
            <a href="https://instagram.com/klubecash" style="color: #FF7A00;">Instagram</a> | 
            <a href="https://tiktok.com/@klube.cash" style="color: #FF7A00;">TikTok</a>
        </p>
    </div>
    
    <!-- Footer -->
    <div style="text-align: center; padding: 1rem; color: #999; font-size: 0.8rem;">
        <p>&copy; 2025 Klube Cash. Todos os direitos reservados.</p>
        <p>Você está recebendo este email porque se cadastrou em nossa lista de lançamento.</p>
    </div>
</div>';
}
?>