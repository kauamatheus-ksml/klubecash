<?php
/**
 * Hub de Autogestão para Lojas Parceiras - Klube Cash
 * Página reformulada focada em autogestão sem exibição de saldos
 */

session_start();
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/StoreController.php';
require_once '../../utils/Security.php';

// Verificar autenticação
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'loja') {
    header('Location: /login');
    exit;
}

$storeId = $_SESSION['user_id'];
$storeController = new StoreController();

// Inicializar variáveis
$storeData = [];
$recentActivities = [];
$storeStats = [];
$message = '';
$messageType = '';

try {
    // Buscar dados da loja sem duplicatas
    $storeQuery = "SELECT DISTINCT l.*, u.email, u.created_at as data_cadastro 
                   FROM lojas l 
                   INNER JOIN usuarios u ON l.usuario_id = u.id 
                   WHERE l.usuario_id = ? AND l.status = 'aprovado'
                   LIMIT 1";
    
    $stmt = $db->prepare($storeQuery);
    $stmt->execute([$storeId]);
    $storeData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$storeData) {
        throw new Exception('Loja não encontrada ou não aprovada');
    }
    
    // Buscar estatísticas (SEM SALDO - apenas contadores)
    $statsQuery = "SELECT 
                    COUNT(DISTINCT t.id) as total_transacoes,
                    COUNT(DISTINCT CASE WHEN t.status = 'aprovado' THEN t.id END) as transacoes_aprovadas,
                    COUNT(DISTINCT CASE WHEN DATE(t.data_transacao) = CURDATE() THEN t.id END) as transacoes_hoje,
                    COUNT(DISTINCT CASE WHEN WEEK(t.data_transacao) = WEEK(NOW()) THEN t.id END) as transacoes_semana
                   FROM transacoes_cashback t 
                   WHERE t.loja_id = ?";
    
    $stmt = $db->prepare($statsQuery);
    $stmt->execute([$storeId]);
    $storeStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Buscar atividades recentes
    $activitiesQuery = "SELECT 
                        'transacao' as tipo,
                        CONCAT('Transação registrada - R$ ', FORMAT(valor_total, 2, 'de_DE')) as descricao,
                        data_transacao as data_atividade
                       FROM transacoes_cashback 
                       WHERE loja_id = ? 
                       ORDER BY data_transacao DESC 
                       LIMIT 5";
    
    $stmt = $db->prepare($activitiesQuery);
    $stmt->execute([$storeId]);
    $recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $message = 'Erro ao carregar dados: ' . $e->getMessage();
    $messageType = 'error';
}

// Processar ações AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'update_profile':
            try {
                $updates = [];
                $params = [$storeId];
                
                // Campos editáveis
                $editableFields = ['telefone', 'endereco', 'horario_funcionamento', 'descricao'];
                
                foreach ($editableFields as $field) {
                    if (isset($_POST[$field]) && !empty(trim($_POST[$field]))) {
                        $updates[] = "$field = ?";
                        $params[] = trim($_POST[$field]);
                    }
                }
                
                if (!empty($updates)) {
                    $updateQuery = "UPDATE lojas SET " . implode(', ', $updates) . " WHERE usuario_id = ?";
                    $stmt = $db->prepare($updateQuery);
                    
                    // Mover $storeId para o final do array
                    $storeIdValue = array_shift($params);
                    $params[] = $storeIdValue;
                    
                    $stmt->execute($params);
                    
                    echo json_encode(['success' => true, 'message' => 'Perfil atualizado com sucesso!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Nenhum campo válido para atualizar']);
                }
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
            }
            exit;
            
        case 'send_support_message':
            try {
                $subject = 'Mensagem de Suporte - ' . $storeData['nome_fantasia'];
                $message = $_POST['message'] ?? '';
                $priority = $_POST['priority'] ?? 'normal';
                
                if (empty($message)) {
                    throw new Exception('Mensagem não pode estar vazia');
                }
                
                // Aqui você integraria com seu sistema de tickets/suporte
                // Por enquanto, vamos simular o envio
                
                echo json_encode(['success' => true, 'message' => 'Mensagem enviada com sucesso!']);
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
            }
            exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hub de Autogestão - <?php echo htmlspecialchars($storeData['nome_fantasia'] ?? 'Loja'); ?></title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        /* Variáveis CSS */
        :root {
            --primary-color: #FF7A00;
            --primary-light: #FFF0E6;
            --primary-dark: #E06E00;
            --white: #FFFFFF;
            --light-gray: #F8F9FA;
            --medium-gray: #6C757D;
            --dark-gray: #333333;
            --success-color: #28A745;
            --danger-color: #DC3545;
            --warning-color: #FFC107;
            --info-color: #17A2B8;
            --border-radius: 12px;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            color: var(--dark-gray);
            line-height: 1.6;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .store-avatar {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
        }

        .store-info h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .store-info p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        /* Container principal */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        /* Grid de cards */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        /* Cards */
        .card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            background: var(--light-gray);
            padding: 1.5rem;
            border-bottom: 1px solid #E9ECEF;
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark-gray);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Estatísticas */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background: var(--light-gray);
            border-radius: 8px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
            display: block;
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--medium-gray);
            margin-top: 0.5rem;
        }

        /* Formulários */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark-gray);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #E9ECEF;
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 122, 0, 0.1);
        }

        .form-control:disabled {
            background: #F8F9FA;
            color: var(--medium-gray);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        /* Botões */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: var(--medium-gray);
            color: white;
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
        }

        /* Lista de atividades */
        .activity-list {
            list-style: none;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid #E9ECEF;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-light);
            color: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .activity-content {
            flex: 1;
        }

        .activity-description {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .activity-date {
            font-size: 0.9rem;
            color: var(--medium-gray);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-title {
            font-size: 1.4rem;
            font-weight: 600;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--medium-gray);
        }

        /* Alertas */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid;
        }

        .alert-success {
            background: #D4EDDA;
            color: #155724;
            border-color: var(--success-color);
        }

        .alert-error {
            background: #F8D7DA;
            color: #721C24;
            border-color: var(--danger-color);
        }

        .alert-info {
            background: #D1ECF1;
            color: #0C5460;
            border-color: var(--info-color);
        }

        /* Loading */
        .loading {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsivo */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .store-avatar {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }

            .store-info h1 {
                font-size: 1.5rem;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .modal-content {
                margin: 1rem;
                padding: 1.5rem;
            }
        }

        /* Navbar compatibility */
        .page-wrapper {
            margin-top: 80px;
        }
    </style>
</head>
<body>
    <?php include_once '../components/navbar.php'; ?>

    <div class="page-wrapper">
        <!-- Header da loja -->
        <div class="header">
            <div class="header-content">
                <div class="store-avatar">
                    <?php echo strtoupper(substr($storeData['nome_fantasia'] ?? 'L', 0, 1)); ?>
                </div>
                <div class="store-info">
                    <h1><?php echo htmlspecialchars($storeData['nome_fantasia'] ?? 'Sua Loja'); ?></h1>
                    <p>Hub de Autogestão - Gerencie suas informações e acompanhe suas atividades</p>
                </div>
            </div>
        </div>

        <div class="container">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType === 'error' ? 'error' : 'success'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Grid principal do dashboard -->
            <div class="dashboard-grid">
                <!-- Card de Estatísticas -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-chart-bar"></i>
                            Resumo de Atividades
                        </h2>
                    </div>
                    <div class="card-body">
                        <div class="stats-grid">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $storeStats['total_transacoes'] ?? 0; ?></span>
                                <span class="stat-label">Total Transações</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $storeStats['transacoes_aprovadas'] ?? 0; ?></span>
                                <span class="stat-label">Aprovadas</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $storeStats['transacoes_hoje'] ?? 0; ?></span>
                                <span class="stat-label">Hoje</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $storeStats['transacoes_semana'] ?? 0; ?></span>
                                <span class="stat-label">Esta Semana</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card de Informações da Loja -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-store"></i>
                            Informações da Loja
                        </h2>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Nome Fantasia</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($storeData['nome_fantasia'] ?? ''); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">CNPJ</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($storeData['cnpj'] ?? ''); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($storeData['email'] ?? ''); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <input type="text" class="form-control" value="Aprovado ✓" disabled style="color: var(--success-color); font-weight: bold;">
                        </div>
                    </div>
                </div>

                <!-- Card de Ações Rápidas -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-tools"></i>
                            Ações Rápidas
                        </h2>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; gap: 1rem;">
                            <button onclick="openEditProfileModal()" class="btn btn-primary">
                                <i class="fas fa-edit"></i>
                                Editar Perfil
                            </button>
                            <button onclick="openSupportModal()" class="btn btn-outline">
                                <i class="fas fa-headset"></i>
                                Suporte/Contato
                            </button>
                            <a href="/store/dashboard" class="btn btn-secondary">
                                <i class="fas fa-tachometer-alt"></i>
                                Painel Completo
                            </a>
                            <a href="/store/transacoes" class="btn btn-success">
                                <i class="fas fa-list"></i>
                                Ver Transações
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Card de Atividades Recentes -->
                <div class="card" style="grid-column: 1 / -1;">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-history"></i>
                            Atividades Recentes
                        </h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recentActivities)): ?>
                            <ul class="activity-list">
                                <?php foreach ($recentActivities as $activity): ?>
                                    <li class="activity-item">
                                        <div class="activity-icon">
                                            <i class="fas fa-<?php echo $activity['tipo'] === 'transacao' ? 'shopping-cart' : 'bell'; ?>"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-description">
                                                <?php echo htmlspecialchars($activity['descricao']); ?>
                                            </div>
                                            <div class="activity-date">
                                                <?php echo date('d/m/Y H:i', strtotime($activity['data_atividade'])); ?>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div style="text-align: center; color: var(--medium-gray); padding: 2rem;">
                                <i class="fas fa-clock" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                                <p>Nenhuma atividade recente encontrada</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Edição de Perfil -->
    <div id="editProfileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Editar Perfil da Loja</h3>
                <button class="modal-close" onclick="closeEditProfileModal()">&times;</button>
            </div>
            <form id="editProfileForm">
                <div class="form-group">
                    <label class="form-label">Telefone</label>
                    <input type="text" name="telefone" class="form-control" value="<?php echo htmlspecialchars($storeData['telefone'] ?? ''); ?>" placeholder="(11) 99999-9999">
                </div>
                <div class="form-group">
                    <label class="form-label">Endereço</label>
                    <input type="text" name="endereco" class="form-control" value="<?php echo htmlspecialchars($storeData['endereco'] ?? ''); ?>" placeholder="Endereço completo">
                </div>
                <div class="form-group">
                    <label class="form-label">Horário de Funcionamento</label>
                    <input type="text" name="horario_funcionamento" class="form-control" value="<?php echo htmlspecialchars($storeData['horario_funcionamento'] ?? ''); ?>" placeholder="Ex: Seg-Sex 9h-18h, Sáb 9h-12h">
                </div>
                <div class="form-group">
                    <label class="form-label">Descrição</label>
                    <textarea name="descricao" class="form-control" placeholder="Descreva sua loja, produtos e serviços"><?php echo htmlspecialchars($storeData['descricao'] ?? ''); ?></textarea>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="closeEditProfileModal()" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="btn-text">Salvar Alterações</span>
                        <span class="btn-loading" style="display: none;">
                            <span class="spinner"></span> Salvando...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Suporte -->
    <div id="supportModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Contato/Suporte</h3>
                <button class="modal-close" onclick="closeSupportModal()">&times;</button>
            </div>
            <form id="supportForm">
                <div class="form-group">
                    <label class="form-label">Prioridade</label>
                    <select name="priority" class="form-control">
                        <option value="normal">Normal</option>
                        <option value="urgente">Urgente</option>
                        <option value="baixa">Baixa</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Mensagem</label>
                    <textarea name="message" class="form-control" required placeholder="Descreva sua dúvida, problema ou sugestão..." style="min-height: 120px;"></textarea>
                </div>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Nossa equipe responderá em até 24 horas no email cadastrado.
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="closeSupportModal()" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="btn-text">Enviar Mensagem</span>
                        <span class="btn-loading" style="display: none;">
                            <span class="spinner"></span> Enviando...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Funções para os modais
        function openEditProfileModal() {
            document.getElementById('editProfileModal').classList.add('active');
        }

        function closeEditProfileModal() {
            document.getElementById('editProfileModal').classList.remove('active');
        }

        function openSupportModal() {
            document.getElementById('supportModal').classList.add('active');
        }

        function closeSupportModal() {
            document.getElementById('supportModal').classList.remove('active');
        }

        // Fechar modal ao clicar fora
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('active');
            }
        });

        // Manipular formulário de edição de perfil
        document.getElementById('editProfileForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = this.querySelector('button[type="submit"]');
            const btnText = btn.querySelector('.btn-text');
            const btnLoading = btn.querySelector('.btn-loading');
            
            // Mostrar loading
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline-flex';
            btn.disabled = true;
            
            try {
                const formData = new FormData(this);
                formData.append('action', 'update_profile');
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', result.message);
                    closeEditProfileModal();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('error', result.message);
                }
                
            } catch (error) {
                showAlert('error', 'Erro de conexão. Tente novamente.');
            } finally {
                // Esconder loading
                btnText.style.display = 'inline';
                btnLoading.style.display = 'none';
                btn.disabled = false;
            }
        });

        // Manipular formulário de suporte
        document.getElementById('supportForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = this.querySelector('button[type="submit"]');
            const btnText = btn.querySelector('.btn-text');
            const btnLoading = btn.querySelector('.btn-loading');
            
            // Mostrar loading
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline-flex';
            btn.disabled = true;
            
            try {
                const formData = new FormData(this);
                formData.append('action', 'send_support_message');
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', result.message);
                    closeSupportModal();
                    this.reset();
                } else {
                    showAlert('error', result.message);
                }
                
            } catch (error) {
                showAlert('error', 'Erro de conexão. Tente novamente.');
            } finally {
                // Esconder loading
                btnText.style.display = 'inline';
                btnLoading.style.display = 'none';
                btn.disabled = false;
            }
        });

        // Função para mostrar alertas
        function showAlert(type, message) {
            // Remover alertas existentes
            const existingAlerts = document.querySelectorAll('.alert');
            existingAlerts.forEach(alert => alert.remove());
            
            // Criar novo alerta
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                ${message}
            `;
            
            // Inserir no início do container
            const container = document.querySelector('.container');
            container.insertBefore(alert, container.firstChild);
            
            // Remover após 5 segundos
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }

        // Máscaras para campos
        document.querySelector('input[name="telefone"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
                value = value.replace(/^(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
                value = value.replace(/^(\d{2})(\d{0,5})/, '($1) $2');
                value = value.replace(/^(\d{0,2})/, '($1');
                e.target.value = value;
            }
        });
    </script>
</body>
</html>