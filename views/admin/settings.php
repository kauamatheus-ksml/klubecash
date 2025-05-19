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

// Inicializar variáveis
$message = '';
$messageType = '';

// Processar formulário se enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'update_cashback':
                // Converter valores para float para garantir o formato correto
                $data = [
                    'porcentagem_total' => floatval($_POST['porcentagem_total']),
                    'porcentagem_cliente' => floatval($_POST['porcentagem_cliente']),
                    'porcentagem_admin' => floatval($_POST['porcentagem_admin']),
                    'porcentagem_loja' => floatval($_POST['porcentagem_loja'])
                ];
                
                // Verificar se a soma está correta
                $soma = $data['porcentagem_cliente'] + $data['porcentagem_admin'] + $data['porcentagem_loja'];
                if (abs($soma - $data['porcentagem_total']) > 0.01) {
                    $message = 'Erro: A soma das porcentagens (' . number_format($soma, 2) . '%) não é igual à porcentagem total (' . number_format($data['porcentagem_total'], 2) . '%).';
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
                $db = Database::getConnection();
                
                // Verificar se a tabela de configurações de saldo existe
                $checkTable = $db->query("SHOW TABLES LIKE 'configuracoes_saldo'");
                if ($checkTable->rowCount() == 0) {
                    // Criar tabela se não existir
                    $createTable = "
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
                    $db->exec($createTable);
                    
                    // Inserir configurações padrão
                    $db->exec("INSERT INTO configuracoes_saldo (permitir_uso_saldo) VALUES (1)");
                }
                
                // Atualizar configurações
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
                $db = Database::getConnection();
                
                // Verificar se a tabela existe
                $checkTable = $db->query("SHOW TABLES LIKE 'configuracoes_notificacao'");
                if ($checkTable->rowCount() == 0) {
                    // Criar tabela se não existir
                    $createTable = "
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
                    $db->exec($createTable);
                    
                    // Inserir configurações padrão
                    $db->exec("INSERT INTO configuracoes_notificacao (email_nova_transacao) VALUES (1)");
                }
                
                // Atualizar configurações
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
    $db = Database::getConnection();
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
</head>
<body>
    <?php include_once '../components/sidebar.php'; ?>
    
    <div class="main-content" id="mainContent">
        <div class="page-wrapper">
            <h1 class="page-title">Configurações</h1>
            
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
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="porcentagemTotal">Porcentagem Total de Cashback</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control" id="porcentagemTotal" name="porcentagem_total" value="<?php echo $settings['porcentagem_total']; ?>" required>
                                <small class="form-text">Porcentagem total aplicada sobre o valor da compra</small>
                            </div>
                        </div>
                        
                        <div class="form-divider"></div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="porcentagemCliente">Porcentagem para o Cliente</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control" id="porcentagemCliente" name="porcentagem_cliente" value="<?php echo $settings['porcentagem_cliente']; ?>" required>
                                <small class="form-text">Parte do cashback que vai para o cliente</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="porcentagemAdmin">Porcentagem para o Admin</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control" id="porcentagemAdmin" name="porcentagem_admin" value="<?php echo $settings['porcentagem_admin']; ?>" required>
                                <small class="form-text">Parte do cashback que vai para a plataforma (comissão)</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="porcentagemLoja">Porcentagem para a Loja</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control" id="porcentagemLoja" name="porcentagem_loja" value="<?php echo $settings['porcentagem_loja']; ?>" required>
                                <small class="form-text">Parte do cashback que vai para a loja parceira</small>
                            </div>
                        </div>
                        
                        <p class="form-text" id="somaInfo">
                            A soma das porcentagens deve ser igual à porcentagem total.
                            <strong>Soma atual: <span id="somaAtual">0.00</span>%</strong>
                        </p>
                        
                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary">Salvar Configurações de Cashback</button>
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
                    <h2 class="card-title">Configurações do Sistema</h2>
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
        // Atualizar soma das porcentagens em tempo real
        function updateSoma() {
            const porcentagemCliente = parseFloat(document.getElementById('porcentagemCliente').value) || 0;
            const porcentagemAdmin = parseFloat(document.getElementById('porcentagemAdmin').value) || 0;
            const porcentagemLoja = parseFloat(document.getElementById('porcentagemLoja').value) || 0;
            
            const soma = porcentagemCliente + porcentagemAdmin + porcentagemLoja;
            document.getElementById('somaAtual').textContent = soma.toFixed(2);
            
            // Verificar se soma é igual à porcentagem total
            const porcentagemTotal = parseFloat(document.getElementById('porcentagemTotal').value) || 0;
            const somaInfo = document.getElementById('somaInfo');
            
            if (Math.abs(soma - porcentagemTotal) > 0.01) {
                somaInfo.style.color = 'var(--danger-color)';
            } else {
                somaInfo.style.color = 'var(--success-color)';
            }
        }
        
        // Adicionar eventos para campos de porcentagem
        document.getElementById('porcentagemTotal').addEventListener('input', updateSoma);
        document.getElementById('porcentagemCliente').addEventListener('input', updateSoma);
        document.getElementById('porcentagemAdmin').addEventListener('input', updateSoma);
        document.getElementById('porcentagemLoja').addEventListener('input', updateSoma);
        
        // Inicializar soma
        document.addEventListener('DOMContentLoaded', updateSoma);
        
        // Validar formulário de cashback antes de enviar
        document.getElementById('cashbackForm').addEventListener('submit', function(event) {
            const porcentagemTotal = parseFloat(document.getElementById('porcentagemTotal').value);
            const porcentagemCliente = parseFloat(document.getElementById('porcentagemCliente').value);
            const porcentagemAdmin = parseFloat(document.getElementById('porcentagemAdmin').value);
            const porcentagemLoja = parseFloat(document.getElementById('porcentagemLoja').value);
            
            // Verificar se valores são válidos
            if (isNaN(porcentagemTotal) || isNaN(porcentagemCliente) || isNaN(porcentagemAdmin) || isNaN(porcentagemLoja)) {
                alert('Por favor, preencha todos os campos com valores numéricos válidos.');
                event.preventDefault();
                return false;
            }
            
            // Verificar se porcentagens estão entre 0 e 100
            if (porcentagemTotal < 0 || porcentagemTotal > 100 || 
                porcentagemCliente < 0 || porcentagemCliente > 100 || 
                porcentagemAdmin < 0 || porcentagemAdmin > 100 || 
                porcentagemLoja < 0 || porcentagemLoja > 100) {
                alert('As porcentagens devem estar entre 0 e 100.');
                event.preventDefault();
                return false;
            }
            
            // Verificar se a soma das porcentagens é igual à porcentagem total
            const soma = porcentagemCliente + porcentagemAdmin + porcentagemLoja;
            if (Math.abs(soma - porcentagemTotal) > 0.01) {
                alert('A soma das porcentagens (cliente, admin e loja) deve ser igual à porcentagem total.');
                event.preventDefault();
                return false;
            }
        });
        
        // Controlar visibilidade de campos dependentes
        document.addEventListener('DOMContentLoaded', function() {
            const permitirUsoSaldo = document.querySelector('input[name="permitir_uso_saldo"]');
            const notificarSaldoBaixo = document.querySelector('input[name="notificar_saldo_baixo"]');
            const permitirTransferencia = document.querySelector('input[name="permitir_transferencia"]');
            
            // Função para controlar campos dependentes
            function toggleDependentFields() {
                const balanceFields = document.querySelectorAll('#valorMinimoUso, #percentualMaximoUso');
                const lowBalanceField = document.querySelector('#limiteSaldoBaixo');
                const transferFields = document.querySelectorAll('#taxaTransferencia');
                
                // Campos relacionados ao uso de saldo
                balanceFields.forEach(field => {
                    field.disabled = !permitirUsoSaldo.checked;
                    field.style.opacity = permitirUsoSaldo.checked ? '1' : '0.5';
                });
                
                // Campo relacionado a notificação de saldo baixo
                lowBalanceField.disabled = !notificarSaldoBaixo.checked;
                lowBalanceField.style.opacity = notificarSaldoBaixo.checked ? '1' : '0.5';
                
                // Campos relacionados à transferência
                transferFields.forEach(field => {
                    field.disabled = !permitirTransferencia.checked;
                    field.style.opacity = permitirTransferencia.checked ? '1' : '0.5';
                });
            }
            
            // Adicionar eventos
            permitirUsoSaldo.addEventListener('change', toggleDependentFields);
            notificarSaldoBaixo.addEventListener('change', toggleDependentFields);
            permitirTransferencia.addEventListener('change', toggleDependentFields);
            
            // Executar inicialmente
            toggleDependentFields();
        });
    </script>
</body>
</html>