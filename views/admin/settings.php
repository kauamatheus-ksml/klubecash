<?php
// views/admin/settings.php
// Definir o menu ativo na sidebar
$activeMenu = 'configuracoes';

// Incluir conexão com o banco de dados e arquivos necessários
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/AdminController.php';

// Iniciar sessão
session_start();

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== USER_TYPE_ADMIN) {
    // Redirecionar para a página de login com mensagem de erro
    header("Location: " . LOGIN_URL . "?error=acesso_restrito");
    exit;
}

// Função para garantir que as tabelas de configuração existam
function ensureConfigurationTables($db) {
    try {
        // Verificar e criar tabela de configurações de saldo
        $checkSaldoTable = $db->query("SHOW TABLES LIKE 'configuracoes_saldo'");
        if ($checkSaldoTable->rowCount() == 0) {
            $createSaldoTable = "
                CREATE TABLE configuracoes_saldo (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    permitir_uso_saldo TINYINT(1) DEFAULT 1,
                    valor_minimo_uso DECIMAL(10,2) DEFAULT 1.00,
                    percentual_maximo_uso DECIMAL(5,2) DEFAULT 100.00,
                    tempo_expiracao_dias INT DEFAULT 0,
                    notificar_saldo_baixo TINYINT(1) DEFAULT 1,
                    limite_saldo_baixo DECIMAL(10,2) DEFAULT 10.00,
                    permitir_transferencia TINYINT(1) DEFAULT 0,
                    taxa_transferencia DECIMAL(5,2) DEFAULT 0.00,
                    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ";
            $db->exec($createSaldoTable);
            
            // Inserir configurações padrão
            $db->exec("INSERT INTO configuracoes_saldo (permitir_uso_saldo) VALUES (1)");
        }
        
        // Verificar e criar tabela de configurações de notificação
        $checkNotificationTable = $db->query("SHOW TABLES LIKE 'configuracoes_notificacao'");
        if ($checkNotificationTable->rowCount() == 0) {
            $createNotificationTable = "
                CREATE TABLE configuracoes_notificacao (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email_nova_transacao TINYINT(1) DEFAULT 1,
                    email_pagamento_aprovado TINYINT(1) DEFAULT 1,
                    email_saldo_disponivel TINYINT(1) DEFAULT 1,
                    email_saldo_baixo TINYINT(1) DEFAULT 1,
                    email_saldo_expirado TINYINT(1) DEFAULT 1,
                    push_nova_transacao TINYINT(1) DEFAULT 1,
                    push_saldo_disponivel TINYINT(1) DEFAULT 1,
                    push_promocoes TINYINT(1) DEFAULT 1,
                    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ";
            $db->exec($createNotificationTable);
            
            // Inserir configurações padrão
            $db->exec("INSERT INTO configuracoes_notificacao (email_nova_transacao) VALUES (1)");
        }
        
        return true;
    } catch (Exception $e) {
        error_log('Erro ao criar tabelas de configuração: ' . $e->getMessage());
        return false;
    }
}

// Inicializar variáveis
$message = '';
$messageType = '';

// Processar formulário se enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        $db = Database::getConnection();
        
        // Garantir que as tabelas existam antes de processar
        ensureConfigurationTables($db);
        
        switch ($action) {
            case 'update_2fa_settings':
                $config2FA = [
                    'habilitado' => isset($_POST['2fa_habilitado']) ? 1 : 0,
                    'tempo_expiracao_minutos' => intval($_POST['tempo_expiracao_minutos'] ?? 5),
                    'max_tentativas' => intval($_POST['max_tentativas'] ?? 3)
                ];
                
                $result = AuthController::update2FASettings($config2FA);
                
                if ($result['status']) {
                    $message = 'Configurações de 2FA atualizadas com sucesso!';
                    $messageType = 'success';
                } else {
                    $message = $result['message'];
                    $messageType = 'danger';
                }
                break;
                
            case 'update_cashback':
                // Converter valores para float para garantir o formato correto
                $data = [
                    'porcentagem_total' => 10.00, // Sempre 10%
                    'porcentagem_cliente' => floatval($_POST['porcentagem_cliente']),
                    'porcentagem_admin' => floatval($_POST['porcentagem_admin']),
                    'porcentagem_loja' => 0.00 // Loja não recebe cashback
                ];
                
                // Verificar se a soma está correta
                $soma = $data['porcentagem_cliente'] + $data['porcentagem_admin'];
                if (abs($soma - 10.00) > 0.01) {
                    $message = 'Erro: A soma das porcentagens (' . number_format($soma, 2) . '%) não é igual a 10%.';
                    $messageType = 'danger';
                } else {
                    $result = AdminController::updateSettings($data);
                    
                    if ($result['status']) {
                        $message = 'Configurações de cashback atualizadas com sucesso!';
                        $messageType = 'success';
                    } else {
                        $message = $result['message'];
                        $messageType = 'danger';
                    }
                }
                break;
                
            case 'update_balance_settings':
                // Atualizar configurações de saldo
                $updateQuery = "
                    UPDATE configuracoes_saldo SET
                        permitir_uso_saldo = :permitir_uso_saldo,
                        valor_minimo_uso = :valor_minimo_uso,
                        percentual_maximo_uso = :percentual_maximo_uso,
                        tempo_expiracao_dias = :tempo_expiracao_dias,
                        notificar_saldo_baixo = :notificar_saldo_baixo,
                        limite_saldo_baixo = :limite_saldo_baixo,
                        permitir_transferencia = :permitir_transferencia,
                        taxa_transferencia = :taxa_transferencia
                    WHERE id = 1
                ";
                
                $stmt = $db->prepare($updateQuery);
                $stmt->execute([
                    ':permitir_uso_saldo' => isset($_POST['permitir_uso_saldo']) ? 1 : 0,
                    ':valor_minimo_uso' => floatval($_POST['valor_minimo_uso']),
                    ':percentual_maximo_uso' => floatval($_POST['percentual_maximo_uso']),
                    ':tempo_expiracao_dias' => intval($_POST['tempo_expiracao_dias']),
                    ':notificar_saldo_baixo' => isset($_POST['notificar_saldo_baixo']) ? 1 : 0,
                    ':limite_saldo_baixo' => floatval($_POST['limite_saldo_baixo']),
                    ':permitir_transferencia' => isset($_POST['permitir_transferencia']) ? 1 : 0,
                    ':taxa_transferencia' => floatval($_POST['taxa_transferencia'])
                ]);
                
                $message = 'Configurações de saldo atualizadas com sucesso!';
                $messageType = 'success';
                break;
                
            case 'update_notification_settings':
                // Atualizar configurações de notificação
                $updateQuery = "
                    UPDATE configuracoes_notificacao SET
                        email_nova_transacao = :email_nova_transacao,
                        email_pagamento_aprovado = :email_pagamento_aprovado,
                        email_saldo_disponivel = :email_saldo_disponivel,
                        email_saldo_baixo = :email_saldo_baixo,
                        email_saldo_expirado = :email_saldo_expirado,
                        push_nova_transacao = :push_nova_transacao,
                        push_saldo_disponivel = :push_saldo_disponivel,
                        push_promocoes = :push_promocoes
                    WHERE id = 1
                ";
                
                $stmt = $db->prepare($updateQuery);
                $stmt->execute([
                    ':email_nova_transacao' => isset($_POST['email_nova_transacao']) ? 1 : 0,
                    ':email_pagamento_aprovado' => isset($_POST['email_pagamento_aprovado']) ? 1 : 0,
                    ':email_saldo_disponivel' => isset($_POST['email_saldo_disponivel']) ? 1 : 0,
                    ':email_saldo_baixo' => isset($_POST['email_saldo_baixo']) ? 1 : 0,
                    ':email_saldo_expirado' => isset($_POST['email_saldo_expirado']) ? 1 : 0,
                    ':push_nova_transacao' => isset($_POST['push_nova_transacao']) ? 1 : 0,
                    ':push_saldo_disponivel' => isset($_POST['push_saldo_disponivel']) ? 1 : 0,
                    ':push_promocoes' => isset($_POST['push_promocoes']) ? 1 : 0
                ]);
                
                $message = 'Configurações de notificação atualizadas com sucesso!';
                $messageType = 'success';
                break;
        }
    } catch (Exception $e) {
        error_log('Erro ao atualizar configurações: ' . $e->getMessage());
        $message = 'Erro ao atualizar configurações: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Obter configurações atuais
try {
    $db = Database::getConnection();
    
    // Garantir que as tabelas existam antes de consultar
    ensureConfigurationTables($db);
    
    // Obter configurações de 2FA
    $config2FA = AuthController::get2FASettings();
    
    // Configurações de cashback
    $settingsResult = AdminController::getSettings();
    
    if ($settingsResult['status']) {
        $settings = $settingsResult['data'];
    } else {
        $settings = [
            'porcentagem_total' => DEFAULT_CASHBACK_TOTAL,
            'porcentagem_cliente' => DEFAULT_CASHBACK_CLIENT,
            'porcentagem_admin' => DEFAULT_CASHBACK_ADMIN,
            'porcentagem_loja' => DEFAULT_CASHBACK_STORE
        ];
    }
    
    // Configurações de saldo
    $balanceSettingsQuery = $db->query("SELECT * FROM configuracoes_saldo ORDER BY id DESC LIMIT 1");
    $balanceSettings = $balanceSettingsQuery->fetch(PDO::FETCH_ASSOC);
    
    if (!$balanceSettings) {
        $balanceSettings = [
            'permitir_uso_saldo' => 1,
            'valor_minimo_uso' => 1.00,
            'percentual_maximo_uso' => 100.00,
            'tempo_expiracao_dias' => 0,
            'notificar_saldo_baixo' => 1,
            'limite_saldo_baixo' => 10.00,
            'permitir_transferencia' => 0,
            'taxa_transferencia' => 0.00
        ];
    }
    
    // Configurações de notificação
    $notificationSettingsQuery = $db->query("SELECT * FROM configuracoes_notificacao ORDER BY id DESC LIMIT 1");
    $notificationSettings = $notificationSettingsQuery->fetch(PDO::FETCH_ASSOC);
    
    if (!$notificationSettings) {
        $notificationSettings = [
            'email_nova_transacao' => 1,
            'email_pagamento_aprovado' => 1,
            'email_saldo_disponivel' => 1,
            'email_saldo_baixo' => 1,
            'email_saldo_expirado' => 1,
            'push_nova_transacao' => 1,
            'push_saldo_disponivel' => 1,
            'push_promocoes' => 1
        ];
    }
    
} catch (Exception $e) {
    error_log('Erro ao carregar configurações: ' . $e->getMessage());
    $message = 'Erro ao carregar configurações: ' . $e->getMessage();
    $messageType = 'danger';
    
    // Definir valores padrão em caso de erro
    $config2FA = [
        'habilitado' => false,
        'tempo_expiracao_minutos' => 5,
        'max_tentativas' => 3
    ];
    
    $settings = [
        'porcentagem_total' => DEFAULT_CASHBACK_TOTAL,
        'porcentagem_cliente' => DEFAULT_CASHBACK_CLIENT,
        'porcentagem_admin' => DEFAULT_CASHBACK_ADMIN,
        'porcentagem_loja' => DEFAULT_CASHBACK_STORE
    ];
    
    $balanceSettings = [
        'permitir_uso_saldo' => 1,
        'valor_minimo_uso' => 1.00,
        'percentual_maximo_uso' => 100.00,
        'tempo_expiracao_dias' => 0,
        'notificar_saldo_baixo' => 1,
        'limite_saldo_baixo' => 10.00,
        'permitir_transferencia' => 0,
        'taxa_transferencia' => 0.00
    ];
    
    $notificationSettings = [
        'email_nova_transacao' => 1,
        'email_pagamento_aprovado' => 1,
        'email_saldo_disponivel' => 1,
        'email_saldo_baixo' => 1,
        'email_saldo_expirado' => 1,
        'push_nova_transacao' => 1,
        'push_saldo_disponivel' => 1,
        'push_promocoes' => 1
    ];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <link rel="stylesheet" href="../../assets/css/views/admin/settings.css">
    <link rel="stylesheet" href="../../assets/css/layout-fix.css">
    <style>
        /* Estilos específicos para 2FA */
        .status-info {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            flex-direction: column;
        }

        .status-info strong {
            margin-left: 20px;
        }

        .status-info .form-text {
            margin-left: 20px;
            margin-top: 5px;
        }

        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .status-indicator.active {
            background-color: #28a745;
            box-shadow: 0 0 8px rgba(40, 167, 69, 0.4);
        }

        .status-indicator.inactive {
            background-color: #6c757d;
        }

        .info-box {
            background-color: #e7f3ff;
            border: 1px solid #b3d7ff;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .info-icon {
            font-size: 1.2rem;
            margin-top: 2px;
        }

        .info-content {
            flex: 1;
            color: #2c5aa0;
            line-height: 1.5;
        }

        .info-content strong {
            color: #1a4480;
        }

        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-group .btn {
            margin: 0;
        }

        .form-divider {
            border-top: 1px solid #e9ecef;
            margin: 25px 0;
        }

        .subsection-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 20px 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #f8f9fa;
        }
    </style>
</head>
<body>
    <?php include_once '../components/sidebar.php'; ?>
    
    <div class="main-content" id="mainContent">
        <div class="page-wrapper">
            <h1 class="page-title">Configurações do Sistema</h1>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Configurações de Cashback -->
            <form method="post" action="" id="cashbackForm">
                <input type="hidden" name="action" value="update_cashback">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Configurações de Cashback</h2>
                    </div>
                    <div class="card-body">
                        <div class="info-box">
                            <div class="info-icon">ℹ️</div>
                            <div class="info-content">
                                <strong>Informação importante:</strong> A comissão total é sempre 10% sobre cada venda. 
                                Esta porcentagem é distribuída entre cliente e plataforma. A loja não recebe cashback.
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="porcentagemCliente">Porcentagem para o Cliente (%)</label>
                                <input type="number" step="0.01" min="0" max="10" class="form-control" id="porcentagemCliente" name="porcentagem_cliente" value="<?php echo $settings['porcentagem_cliente']; ?>" required>
                                <small class="form-text">Parte do cashback que vai para o cliente</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="porcentagemAdmin">Porcentagem para a Plataforma (%)</label>
                                <input type="number" step="0.01" min="0" max="10" class="form-control" id="porcentagemAdmin" name="porcentagem_admin" value="<?php echo $settings['porcentagem_admin']; ?>" required>
                                <small class="form-text">Parte da comissão que fica para a plataforma</small>
                            </div>
                        </div>
                        
                        <p class="form-text" id="somaInfo">
                            A soma deve ser exatamente <strong>10%</strong> (cliente + plataforma).
                            <strong>Soma atual: <span id="somaAtual">0.00</span>%</strong>
                        </p>
                        
                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary">Salvar Configurações de Cashback</button>
                        </div>
                    </div>
                </div>
            </form>
            
            <!-- Configurações de Autenticação de Dois Fatores -->
            <form method="post" action="" id="twoFactorForm">
                <input type="hidden" name="action" value="update_2fa_settings">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Autenticação de Dois Fatores (2FA)</h2>
                    </div>
                    <div class="card-body">
                        <div class="info-box">
                            <div class="info-icon">🔐</div>
                            <div class="info-content">
                                <strong>Sobre o 2FA:</strong> A autenticação de dois fatores adiciona uma camada extra de segurança, 
                                enviando um código por email para verificar a identidade do usuário durante o login.
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <input type="checkbox" name="2fa_habilitado" value="1" <?php echo $config2FA['habilitado'] ? 'checked' : ''; ?>>
                                    Habilitar verificação em duas etapas
                                </label>
                                <small class="form-text">Quando habilitado, todos os usuários precisarão verificar um código enviado por email</small>
                            </div>
                        </div>
                        
                        <div class="form-divider"></div>
                        
                        <h3 class="subsection-title">Configurações Avançadas</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="tempoExpiracaoMinutos">Tempo de Expiração do Código (minutos)</label>
                                <input type="number" min="1" max="60" class="form-control" id="tempoExpiracaoMinutos" 
                                       name="tempo_expiracao_minutos" value="<?php echo $config2FA['tempo_expiracao_minutos']; ?>">
                                <small class="form-text">Por quanto tempo o código de verificação permanece válido</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="maxTentativas">Máximo de Tentativas</label>
                                <input type="number" min="1" max="10" class="form-control" id="maxTentativas" 
                                       name="max_tentativas" value="<?php echo $config2FA['max_tentativas']; ?>">
                                <small class="form-text">Número máximo de tentativas antes de bloquear temporariamente</small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Status do Sistema</label>
                                <div class="status-info">
                                    <?php if ($config2FA['habilitado']): ?>
                                        <span class="status-indicator active"></span>
                                        <strong style="color: var(--success-color);">2FA Ativo</strong>
                                        <p class="form-text">Todos os novos logins requerem verificação por email</p>
                                    <?php else: ?>
                                        <span class="status-indicator inactive"></span>
                                        <strong style="color: var(--medium-gray);">2FA Inativo</strong>
                                        <p class="form-text">Login tradicional (apenas email e senha)</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary">Salvar Configurações de 2FA</button>
                        </div>

                        <div class="btn-group" style="margin-top: 15px;">
                            <h4>🔧 Diagnósticos Básicos</h4>
                            <button type="button" class="btn btn-outline-info" onclick="pingEndpoint()">🏓 Ping</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="checkEmailConfig()">⚙️ Config</button>
                        </div>

                        <div class="btn-group" style="margin-top: 10px;">
                            <h4>🔍 Testes Manuais (PHPMailer direto)</h4>
                            <button type="button" class="btn btn-warning" onclick="testSMTPManual()">🔗 SMTP Manual</button>
                            <button type="button" class="btn btn-warning" onclick="sendEmailManual()">📧 Email Manual</button>
                        </div>

                        <div class="btn-group" style="margin-top: 10px;">
                            <h4>📨 Testes via Classe Email</h4>
                            <button type="button" class="btn btn-secondary" onclick="testEmailConnection()">🔗 SMTP Classe</button>
                            <button type="button" class="btn btn-info" onclick="sendTestEmail()">📧 Email Classe</button>
                            <button type="button" class="btn btn-secondary" onclick="test2FAEmail()">🔐 2FA Classe</button>
                        </div>
                    </div>
                </div>
            </form>
            
            <!-- Configurações de Saldo -->
            <form method="post" action="" id="balanceForm">
                <input type="hidden" name="action" value="update_balance_settings">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Configurações de Saldo</h2>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <input type="checkbox" name="permitir_uso_saldo" value="1" <?php echo $balanceSettings['permitir_uso_saldo'] ? 'checked' : ''; ?>>
                                    Permitir uso do saldo
                                </label>
                                <small class="form-text">Permitir que clientes usem seu saldo de cashback em compras</small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="valorMinimoUso">Valor Mínimo para Uso de Saldo</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="valorMinimoUso" name="valor_minimo_uso" value="<?php echo $balanceSettings['valor_minimo_uso']; ?>">
                                <small class="form-text">Valor mínimo de saldo que pode ser usado em uma compra</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="percentualMaximoUso">Percentual Máximo de Uso</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control" id="percentualMaximoUso" name="percentual_maximo_uso" value="<?php echo $balanceSettings['percentual_maximo_uso']; ?>">
                                <small class="form-text">Percentual máximo do valor da compra que pode ser pago com saldo (%)</small>
                            </div>
                        </div>
                        
                        <div class="form-divider"></div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="tempoExpiracaoDias">Tempo de Expiração do Saldo (dias)</label>
                                <input type="number" min="0" class="form-control" id="tempoExpiracaoDias" name="tempo_expiracao_dias" value="<?php echo $balanceSettings['tempo_expiracao_dias']; ?>">
                                <small class="form-text">Tempo em dias para o saldo expirar (0 = nunca expira)</small>
                            </div>
                        </div>
                        
                        <div class="form-divider"></div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <input type="checkbox" name="notificar_saldo_baixo" value="1" <?php echo $balanceSettings['notificar_saldo_baixo'] ? 'checked' : ''; ?>>
                                    Notificar saldo baixo
                                </label>
                                <small class="form-text">Enviar notificação quando o saldo do cliente estiver baixo</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="limiteSaldoBaixo">Limite para Saldo Baixo</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="limiteSaldoBaixo" name="limite_saldo_baixo" value="<?php echo $balanceSettings['limite_saldo_baixo']; ?>">
                                <small class="form-text">Valor limite para considerar o saldo como "baixo"</small>
                            </div>
                        </div>
                        
                        <div class="form-divider"></div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <input type="checkbox" name="permitir_transferencia" value="1" <?php echo $balanceSettings['permitir_transferencia'] ? 'checked' : ''; ?>>
                                    Permitir transferência de saldo entre clientes
                                </label>
                                <small class="form-text">Permitir que clientes transfiram saldo entre si</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="taxaTransferencia">Taxa de Transferência (%)</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control" id="taxaTransferencia" name="taxa_transferencia" value="<?php echo $balanceSettings['taxa_transferencia']; ?>">
                                <small class="form-text">Taxa cobrada sobre transferências de saldo entre clientes</small>
                            </div>
                        </div>
                        
                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary">Salvar Configurações de Saldo</button>
                        </div>
                    </div>
                </div>
            </form>
            
            <!-- Configurações de Notificação -->
            <form method="post" action="" id="notificationForm">
                <input type="hidden" name="action" value="update_notification_settings">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Configurações de Notificação</h2>
                    </div>
                    <div class="card-body">
                        <h3 class="subsection-title">Notificações por Email</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <input type="checkbox" name="email_nova_transacao" value="1" <?php echo $notificationSettings['email_nova_transacao'] ? 'checked' : ''; ?>>
                                    Nova transação registrada
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <input type="checkbox" name="email_pagamento_aprovado" value="1" <?php echo $notificationSettings['email_pagamento_aprovado'] ? 'checked' : ''; ?>>
                                    Pagamento aprovado (cashback liberado)
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <input type="checkbox" name="email_saldo_disponivel" value="1" <?php echo $notificationSettings['email_saldo_disponivel'] ? 'checked' : ''; ?>>
                                    Saldo disponível para uso
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <input type="checkbox" name="email_saldo_baixo" value="1" <?php echo $notificationSettings['email_saldo_baixo'] ? 'checked' : ''; ?>>
                                    Alerta de saldo baixo
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <input type="checkbox" name="email_saldo_expirado" value="1" <?php echo $notificationSettings['email_saldo_expirado'] ? 'checked' : ''; ?>>
                                    Saldo próximo da expiração
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-divider"></div>
                        
                        <h3 class="subsection-title">Notificações Push (App)</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <input type="checkbox" name="push_nova_transacao" value="1" <?php echo $notificationSettings['push_nova_transacao'] ? 'checked' : ''; ?>>
                                    Nova transação registrada
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <input type="checkbox" name="push_saldo_disponivel" value="1" <?php echo $notificationSettings['push_saldo_disponivel'] ? 'checked' : ''; ?>>
                                    Saldo disponível para uso
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <input type="checkbox" name="push_promocoes" value="1" <?php echo $notificationSettings['push_promocoes'] ? 'checked' : ''; ?>>
                                    Promoções e ofertas especiais
                                </label>
                            </div>
                        </div>
                        
                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary">Salvar Configurações de Notificação</button>
                        </div>
                    </div>
                </div>
            </form>
            
            <!-- Limites e Valores Mínimos -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Limites e Valores Mínimos</h2>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="minTransactionValue">Valor Mínimo de Transação</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="minTransactionValue" value="<?php echo MIN_TRANSACTION_VALUE; ?>" readonly>
                            <small class="form-text">Valor mínimo para uma transação ser processada</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="minWithdrawalValue">Valor Mínimo para Saque</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="minWithdrawalValue" value="<?php echo MIN_WITHDRAWAL_VALUE; ?>" readonly>
                            <small class="form-text">Valor mínimo para solicitação de saque</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Configurações do Sistema -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Informações do Sistema</h2>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="systemName">Nome do Sistema</label>
                            <input type="text" class="form-control" id="systemName" value="<?php echo SYSTEM_NAME; ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="systemVersion">Versão</label>
                            <input type="text" class="form-control" id="systemVersion" value="<?php echo SYSTEM_VERSION; ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="adminEmail">Email do Administrador</label>
                            <input type="email" class="form-control" id="adminEmail" value="<?php echo ADMIN_EMAIL; ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="itemsPerPage">Itens por Página</label>
                            <input type="number" class="form-control" id="itemsPerPage" value="<?php echo ITEMS_PER_PAGE; ?>" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Função para teste SMTP manual
        function testSMTPManual() {
            if (!confirm('Testar conexão SMTP manualmente (sem classe Email)?')) {
                return;
            }
            makeEmailTestRequest('test_connection_manual', event.target);
        }

        // Função para envio de email manual
        function sendEmailManual() {
            if (!confirm('Enviar email de teste manualmente (sem classe Email)?')) {
                return;
            }
            makeEmailTestRequest('send_simple_manual', event.target);
        }
// URL do endpoint de teste
const EMAIL_TEST_URL = '<?php echo SITE_URL; ?>/test-email-endpoint.php';

// Função para log de debug
function debugLog(message, data = null) {
    console.log(`[EMAIL_TEST] ${message}`, data || '');
}

// Função para fazer requisições com debug completo
async function makeEmailTestRequest(action, buttonElement) {
    const originalText = buttonElement.textContent;
    
    try {
        debugLog(`Iniciando teste: ${action}`);
        
        // Desabilitar botão
        buttonElement.disabled = true;
        buttonElement.textContent = 'Processando...';
        
        // Fazer requisição
        debugLog(`Fazendo requisição para: ${EMAIL_TEST_URL}`);
        const response = await fetch(EMAIL_TEST_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=${encodeURIComponent(action)}`
        });
        
        debugLog(`Resposta recebida. Status: ${response.status}`);
        
        // Verificar status HTTP
        if (!response.ok) {
            throw new Error(`Erro HTTP ${response.status}: ${response.statusText}`);
        }
        
        // Obter texto da resposta primeiro
        const responseText = await response.text();
        debugLog('Texto da resposta:', responseText);
        
        // Verificar se começa com JSON
        if (!responseText.trim().startsWith('{')) {
            console.error('Resposta HTML recebida:', responseText);
            throw new Error('Servidor retornou HTML em vez de JSON. Verifique os logs.');
        }
        
        // Tentar parsear JSON
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('Erro ao parsear JSON:', parseError);
            console.error('Texto que não pôde ser parseado:', responseText);
            throw new Error('Resposta não é um JSON válido');
        }
        
        debugLog('Dados parseados:', data);
        
        // Exibir resultado
        if (data.status) {
            alert(`✅ Sucesso: ${data.message}`);
            if (data.data) {
                console.log('Dados adicionais:', data.data);
            }
        } else {
            alert(`❌ Erro: ${data.message}`);
        }
        
    } catch (error) {
        debugLog('Erro na requisição:', error);
        console.error('Erro completo:', error);
        
        // Mensagem de erro mais específica
        let errorMessage = error.message;
        if (error.message.includes('Failed to fetch')) {
            errorMessage = 'Erro de conexão. Verifique se o servidor está respondendo.';
        } else if (error.message.includes('HTML')) {
            errorMessage = 'Servidor retornou página de erro. Verifique se o endpoint existe.';
        }
        
        alert(`❌ Erro: ${errorMessage}`);
    } finally {
        // Restaurar botão
        buttonElement.disabled = false;
        buttonElement.textContent = originalText;
    }
}

// Função específica para ping (testar se endpoint funciona)
function pingEndpoint() {
    debugLog('Fazendo ping no endpoint');
    makeEmailTestRequest('ping', event.target);
}

// Função para verificar configurações
function checkEmailConfig() {
    debugLog('Verificando configurações');
    makeEmailTestRequest('check_config', event.target);
}

// Função para testar conexão SMTP
function testEmailConnection() {
    if (!confirm('Deseja testar a conexão com o servidor SMTP?')) {
        return;
    }
    makeEmailTestRequest('test_connection', event.target);
}

// Função para enviar email simples
function sendTestEmail() {
    if (!confirm('Deseja enviar um email de teste simples?')) {
        return;
    }
    makeEmailTestRequest('send_simple', event.target);
}

// Função para testar email 2FA
function test2FAEmail() {
    if (!confirm('Deseja enviar um email de teste 2FA?')) {
        return;
    }
    makeEmailTestRequest('send_2fa', event.target);
}

// Função para atualizar soma das porcentagens em tempo real
function updateSoma() {
    const porcentagemCliente = parseFloat(document.getElementById('porcentagemCliente').value) || 0;
    const porcentagemAdmin = parseFloat(document.getElementById('porcentagemAdmin').value) || 0;
    
    const soma = porcentagemCliente + porcentagemAdmin;
    document.getElementById('somaAtual').textContent = soma.toFixed(2);
    
    // Verificar se soma é exatamente 10%
    const somaInfo = document.getElementById('somaInfo');
    
    if (Math.abs(soma - 10.00) > 0.01) {
        somaInfo.style.color = 'var(--danger-color)';
    } else {
        somaInfo.style.color = 'var(--success-color)';
    }
}

// Inicialização do DOM
document.addEventListener('DOMContentLoaded', function() {
    debugLog('DOM carregado, inicializando...');
    
    // Atualizar soma das porcentagens
    const porcentagemCliente = document.getElementById('porcentagemCliente');
    const porcentagemAdmin = document.getElementById('porcentagemAdmin');
    
    if (porcentagemCliente && porcentagemAdmin) {
        porcentagemCliente.addEventListener('input', updateSoma);
        porcentagemAdmin.addEventListener('input', updateSoma);
        updateSoma(); // Executar inicialmente
    }
    
    // Controlar campos dependentes do 2FA
    const habilitado2FA = document.querySelector('input[name="2fa_habilitado"]');
    const tempoExpiracao = document.querySelector('#tempoExpiracaoMinutos');
    const maxTentativas = document.querySelector('#maxTentativas');
    
    function toggle2FAFields() {
        const isEnabled = habilitado2FA && habilitado2FA.checked;
        
        [tempoExpiracao, maxTentativas].forEach(field => {
            if (field) {
                field.disabled = !isEnabled;
                field.style.opacity = isEnabled ? '1' : '0.5';
            }
        });
    }
    
    if (habilitado2FA) {
        habilitado2FA.addEventListener('change', toggle2FAFields);
        toggle2FAFields(); // Executar inicialmente
    }
    
    // Controlar campos dependentes de configurações de saldo
    const permitirUsoSaldo = document.querySelector('input[name="permitir_uso_saldo"]');
    const notificarSaldoBaixo = document.querySelector('input[name="notificar_saldo_baixo"]');
    const permitirTransferencia = document.querySelector('input[name="permitir_transferencia"]');
    
    function toggleDependentFields() {
        // Campos relacionados ao uso de saldo
        const balanceFields = document.querySelectorAll('#valorMinimoUso, #percentualMaximoUso');
        const lowBalanceField = document.querySelector('#limiteSaldoBaixo');
        const transferFields = document.querySelectorAll('#taxaTransferencia');
        
        if (permitirUsoSaldo) {
            balanceFields.forEach(field => {
                field.disabled = !permitirUsoSaldo.checked;
                field.style.opacity = permitirUsoSaldo.checked ? '1' : '0.5';
            });
        }
        
        if (notificarSaldoBaixo && lowBalanceField) {
            lowBalanceField.disabled = !notificarSaldoBaixo.checked;
            lowBalanceField.style.opacity = notificarSaldoBaixo.checked ? '1' : '0.5';
        }
        
        if (permitirTransferencia) {
            transferFields.forEach(field => {
                field.disabled = !permitirTransferencia.checked;
                field.style.opacity = permitirTransferencia.checked ? '1' : '0.5';
            });
        }
    }
    
    // Adicionar eventos
    if (permitirUsoSaldo) permitirUsoSaldo.addEventListener('change', toggleDependentFields);
    if (notificarSaldoBaixo) notificarSaldoBaixo.addEventListener('change', toggleDependentFields);
    if (permitirTransferencia) permitirTransferencia.addEventListener('change', toggleDependentFields);
    
    // Executar inicialmente
    toggleDependentFields();
});

// Validar formulário de cashback antes de enviar
document.getElementById('cashbackForm').addEventListener('submit', function(event) {
    const porcentagemCliente = parseFloat(document.getElementById('porcentagemCliente').value);
    const porcentagemAdmin = parseFloat(document.getElementById('porcentagemAdmin').value);
    
    if (isNaN(porcentagemCliente) || isNaN(porcentagemAdmin)) {
        alert('Por favor, preencha todos os campos com valores numéricos válidos.');
        event.preventDefault();
        return false;
    }
    
    if (porcentagemCliente < 0 || porcentagemCliente > 10 || 
        porcentagemAdmin < 0 || porcentagemAdmin > 10) {
        alert('As porcentagens devem estar entre 0 e 10.');
        event.preventDefault();
        return false;
    }
    
    const soma = porcentagemCliente + porcentagemAdmin;
    if (Math.abs(soma - 10.00) > 0.01) {
        alert('A soma das porcentagens deve ser exatamente 10%.');
        event.preventDefault();
        return false;
    }
});
</script>
</body>
</html>