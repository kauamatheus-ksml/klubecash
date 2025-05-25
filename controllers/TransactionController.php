<?php
// controllers/TransactionController.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/email.php';
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/StoreController.php';
require_once __DIR__ . '/../utils/Validator.php';

/**
 * Controlador de Transações
 * Gerencia operações relacionadas a transações, comissões e cashback
 */
class TransactionController {
    // Adicionar este método no TransactionController.php
   

    /**
    * Obtém histórico de pagamentos com informações de saldo usado
    * 
    * @param int $storeId ID da loja
    * @param array $filters Filtros adicionais
    * @param int $page Página atual para paginação
    * @return array Resultado da operação
    */
    public static function getPaymentHistoryWithBalance($storeId, $filters = [], $page = 1) {
        try {
            if (!AuthController::isAuthenticated()) {
                return ['status' => false, 'message' => 'Usuário não autenticado.'];
            }
            
            $db = Database::getConnection();
            $limit = ITEMS_PER_PAGE;
            $offset = ($page - 1) * $limit;
            
            // Construir condições WHERE
            $whereConditions = ["pc.loja_id = :loja_id"];
            $params = [':loja_id' => $storeId];
            
            // Aplicar filtros
            if (!empty($filters['status'])) {
                $whereConditions[] = "pc.status = :status";
                $params[':status'] = $filters['status'];
            }
            
            if (!empty($filters['data_inicio'])) {
                $whereConditions[] = "DATE(pc.data_registro) >= :data_inicio";
                $params[':data_inicio'] = $filters['data_inicio'];
            }
            
            if (!empty($filters['data_fim'])) {
                $whereConditions[] = "DATE(pc.data_registro) <= :data_fim";
                $params[':data_fim'] = $filters['data_fim'];
            }
            
            if (!empty($filters['metodo_pagamento'])) {
                $whereConditions[] = "pc.metodo_pagamento = :metodo_pagamento";
                $params[':metodo_pagamento'] = $filters['metodo_pagamento'];
            }
            
            $whereClause = "WHERE " . implode(" AND ", $whereConditions);
            
            // Query para obter pagamentos com informações agregadas de saldo
            $paymentsQuery = "
                SELECT 
                    pc.*,
                    COUNT(pt.transacao_id) as qtd_transacoes,
                    SUM(t.valor_total) as valor_vendas_originais,
                    COALESCE(SUM(
                        (SELECT SUM(cm.valor) 
                        FROM cashback_movimentacoes cm 
                        WHERE cm.usuario_id = t.usuario_id 
                        AND cm.loja_id = t.loja_id 
                        AND cm.tipo_operacao = 'uso'
                        AND cm.transacao_uso_id = t.id)
                    ), 0) as total_saldo_usado,
                    SUM(CASE WHEN EXISTS(
                        SELECT 1 FROM cashback_movimentacoes cm2 
                        WHERE cm2.usuario_id = t.usuario_id 
                        AND cm2.loja_id = t.loja_id 
                        AND cm2.tipo_operacao = 'uso'
                        AND cm2.transacao_uso_id = t.id
                    ) THEN 1 ELSE 0 END) as qtd_com_saldo
                FROM pagamentos_comissao pc
                LEFT JOIN pagamentos_transacoes pt ON pc.id = pt.pagamento_id
                LEFT JOIN transacoes_cashback t ON pt.transacao_id = t.id
                $whereClause
                GROUP BY pc.id
                ORDER BY pc.data_registro DESC
                LIMIT :limit OFFSET :offset
            ";
            
            $stmt = $db->prepare($paymentsQuery);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Query para contar total de pagamentos
            $countQuery = "
                SELECT COUNT(DISTINCT pc.id) as total
                FROM pagamentos_comissao pc
                LEFT JOIN pagamentos_transacoes pt ON pc.id = pt.pagamento_id
                LEFT JOIN transacoes_cashback t ON pt.transacao_id = t.id
                $whereClause
            ";
            
            $countStmt = $db->prepare($countQuery);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Calcular paginação
            $totalPages = ceil($totalCount / $limit);
            
            return [
                'status' => true,
                'data' => [
                    'pagamentos' => $payments,
                    'paginacao' => [
                        'pagina_atual' => $page,
                        'total_paginas' => $totalPages,
                        'total_itens' => $totalCount,
                        'itens_por_pagina' => $limit
                    ]
                ]
            ];
            
        } catch (PDOException $e) {
            error_log('Erro ao buscar histórico de pagamentos com saldo: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao buscar histórico de pagamentos.'];
        }
    }

    /**
    * Obtém detalhes de um pagamento específico com informações de saldo
    * 
    * @param int $paymentId ID do pagamento
    * @return array Resultado da operação
     */
    public static function getPaymentDetailsWithBalance($paymentId) {
        try {
            if (!AuthController::isAuthenticated()) {
                return ['status' => false, 'message' => 'Usuário não autenticado.'];
            }
            
            $db = Database::getConnection();
            
            // Buscar dados do pagamento
            $paymentQuery = "
                SELECT 
                    pc.*,
                    SUM(t.valor_total) as valor_vendas_originais,
                    COALESCE(SUM(
                        (SELECT SUM(cm.valor) 
                        FROM cashback_movimentacoes cm 
                        WHERE cm.usuario_id = t.usuario_id 
                        AND cm.loja_id = t.loja_id 
                        AND cm.tipo_operacao = 'uso'
                        AND cm.transacao_uso_id = t.id)
                    ), 0) as total_saldo_usado
                FROM pagamentos_comissao pc
                LEFT JOIN pagamentos_transacoes pt ON pc.id = pt.pagamento_id
                LEFT JOIN transacoes_cashback t ON pt.transacao_id = t.id
                WHERE pc.id = :payment_id
                GROUP BY pc.id
            ";
            
            $paymentStmt = $db->prepare($paymentQuery);
            $paymentStmt->bindParam(':payment_id', $paymentId);
            $paymentStmt->execute();
            
            $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$payment) {
                return ['status' => false, 'message' => 'Pagamento não encontrado.'];
            }
            
            // Buscar transações do pagamento com informações de saldo
            $transactionsQuery = "
                SELECT 
                    t.*,
                    u.nome as cliente_nome,
                    u.email as cliente_email,
                    COALESCE(
                        (SELECT SUM(cm.valor) 
                        FROM cashback_movimentacoes cm 
                        WHERE cm.usuario_id = t.usuario_id 
                        AND cm.loja_id = t.loja_id 
                        AND cm.tipo_operacao = 'uso'
                        AND cm.transacao_uso_id = t.id), 0
                    ) as saldo_usado
                FROM transacoes_cashback t
                JOIN usuarios u ON t.usuario_id = u.id
                JOIN pagamentos_transacoes pt ON t.id = pt.transacao_id
                WHERE pt.pagamento_id = :payment_id
                ORDER BY t.data_transacao DESC
            ";
            
            $transactionsStmt = $db->prepare($transactionsQuery);
            $transactionsStmt->bindParam(':payment_id', $paymentId);
            $transactionsStmt->execute();
            
            $transactions = $transactionsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'status' => true,
                'data' => [
                    'pagamento' => $payment,
                    'transacoes' => $transactions
                ]
            ];
            
        } catch (PDOException $e) {
            error_log('Erro ao buscar detalhes do pagamento: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao buscar detalhes do pagamento.'];
        }
    }
    /**
    * Obtém transações pendentes com informações de saldo usado
    * 
    * @param int $storeId ID da loja
    * @param array $filters Filtros adicionais
    * @param int $page Página atual para paginação
    * @return array Resultado da operação
    */
    public static function getPendingTransactionsWithBalance($storeId, $filters = [], $page = 1) {
        try {
            if (!AuthController::isAuthenticated()) {
                return ['status' => false, 'message' => 'Usuário não autenticado.'];
            }
            
            $db = Database::getConnection();
            $limit = ITEMS_PER_PAGE;
            $offset = ($page - 1) * $limit;
            
            // Construir condições WHERE
            $whereConditions = ["t.loja_id = :loja_id", "t.status = :status"];
            $params = [
                ':loja_id' => $storeId,
                ':status' => TRANSACTION_PENDING
            ];
            
            // Aplicar filtros
            if (!empty($filters['data_inicio'])) {
                $whereConditions[] = "DATE(t.data_transacao) >= :data_inicio";
                $params[':data_inicio'] = $filters['data_inicio'];
            }
            
            if (!empty($filters['data_fim'])) {
                $whereConditions[] = "DATE(t.data_transacao) <= :data_fim";
                $params[':data_fim'] = $filters['data_fim'];
            }
            
            if (!empty($filters['valor_min'])) {
                $whereConditions[] = "t.valor_total >= :valor_min";
                $params[':valor_min'] = $filters['valor_min'];
            }
            
            if (!empty($filters['valor_max'])) {
                $whereConditions[] = "t.valor_total <= :valor_max";
                $params[':valor_max'] = $filters['valor_max'];
            }
            
            $whereClause = "WHERE " . implode(" AND ", $whereConditions);
            
            // Query para obter transações com informações de saldo usado
            $transactionsQuery = "
                SELECT 
                    t.*,
                    u.nome as cliente_nome,
                    u.email as cliente_email,
                    COALESCE(
                        (SELECT SUM(cm.valor) 
                        FROM cashback_movimentacoes cm 
                        WHERE cm.usuario_id = t.usuario_id 
                        AND cm.loja_id = t.loja_id 
                        AND cm.tipo_operacao = 'uso'
                        AND cm.transacao_uso_id = t.id), 0
                    ) as saldo_usado
                FROM transacoes_cashback t
                JOIN usuarios u ON t.usuario_id = u.id
                $whereClause
                ORDER BY t.data_transacao DESC
                LIMIT :limit OFFSET :offset
            ";
            
            $stmt = $db->prepare($transactionsQuery);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Query para contar total de transações
            $countQuery = "
                SELECT COUNT(*) as total
                FROM transacoes_cashback t
                JOIN usuarios u ON t.usuario_id = u.id
                $whereClause
            ";
            
            $countStmt = $db->prepare($countQuery);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Query para totais - COMPLETAMENTE CORRIGIDA
            $totalsQuery = "
                SELECT 
                    COUNT(*) as total_transacoes,
                    SUM(t.valor_total) as total_valor_vendas_originais,
                    COALESCE(SUM(
                        (SELECT SUM(cm.valor) 
                        FROM cashback_movimentacoes cm 
                        WHERE cm.usuario_id = t.usuario_id 
                        AND cm.loja_id = t.loja_id 
                        AND cm.tipo_operacao = 'uso'
                        AND cm.transacao_uso_id = t.id)
                    ), 0) as total_saldo_usado,
                    -- CORREÇÃO: Calcular comissão total como 10% do valor efetivamente cobrado
                    SUM(
                        (t.valor_total - COALESCE(
                            (SELECT SUM(cm.valor) 
                            FROM cashback_movimentacoes cm 
                            WHERE cm.usuario_id = t.usuario_id 
                            AND cm.loja_id = t.loja_id 
                            AND cm.tipo_operacao = 'uso'
                            AND cm.transacao_uso_id = t.id), 0
                        )) * 0.10
                    ) as total_valor_comissoes
                FROM transacoes_cashback t
                JOIN usuarios u ON t.usuario_id = u.id
                $whereClause
            ";
            
            $totalsStmt = $db->prepare($totalsQuery);
            foreach ($params as $key => $value) {
                $totalsStmt->bindValue($key, $value);
            }
            $totalsStmt->execute();
            $totals = $totalsStmt->fetch(PDO::FETCH_ASSOC);
            
            // Calcular paginação
            $totalPages = ceil($totalCount / $limit);
            
            return [
                'status' => true,
                'data' => [
                    'transacoes' => $transactions,
                    'totais' => $totals,
                    'paginacao' => [
                        'pagina_atual' => $page,
                        'total_paginas' => $totalPages,
                        'total_itens' => $totalCount,
                        'itens_por_pagina' => $limit
                    ]
                ]
            ];
            
        } catch (PDOException $e) {
            error_log('Erro ao buscar transações pendentes com saldo: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao buscar transações pendentes.'];
        }
    }
    /**
    * Registra uma nova transação de cashback
    * 
    * @param array $data Dados da transação
    * @return array Resultado da operação
    */
    public static function registerTransaction($data) {
        try {
            // Validar dados obrigatórios
            $requiredFields = ['loja_id', 'usuario_id', 'valor_total', 'codigo_transacao'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    return ['status' => false, 'message' => 'Dados da transação incompletos. Campo faltante: ' . $field];
                }
            }
            
            // Verificar se o usuário está autenticado e é loja ou admin
            if (!AuthController::isAuthenticated()) {
                return ['status' => false, 'message' => 'Usuário não autenticado.'];
            }
            
            if (!AuthController::isStore() && !AuthController::isAdmin()) {
                return ['status' => false, 'message' => 'Apenas lojas e administradores podem registrar transações.'];
            }
            
            $db = Database::getConnection();
            
            // Verificar se o cliente existe
            $userStmt = $db->prepare("SELECT id, nome, email FROM usuarios WHERE id = :usuario_id AND tipo = :tipo AND status = :status");
            $userStmt->bindParam(':usuario_id', $data['usuario_id']);
            $tipoCliente = USER_TYPE_CLIENT;
            $userStmt->bindParam(':tipo', $tipoCliente);
            $statusAtivo = USER_ACTIVE;
            $userStmt->bindParam(':status', $statusAtivo);
            $userStmt->execute();
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return ['status' => false, 'message' => 'Cliente não encontrado ou inativo.'];
            }
            
            // Verificar se a loja existe e está aprovada
            $storeStmt = $db->prepare("SELECT * FROM lojas WHERE id = :loja_id AND status = :status");
            $storeStmt->bindParam(':loja_id', $data['loja_id']);
            $statusAprovado = STORE_APPROVED;
            $storeStmt->bindParam(':status', $statusAprovado);
            $storeStmt->execute();
            $store = $storeStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$store) {
                return ['status' => false, 'message' => 'Loja não encontrada ou não aprovada.'];
            }
            
            // Verificar se o valor da transação é válido
            if (!is_numeric($data['valor_total']) || $data['valor_total'] <= 0) {
                return ['status' => false, 'message' => 'Valor da transação inválido.'];
            }
            
            // CORREÇÃO 1: Verificar se vai usar saldo do cliente (aceita tanto string 'sim' quanto boolean true)
            $usarSaldo = (isset($data['usar_saldo']) && ($data['usar_saldo'] === 'sim' || $data['usar_saldo'] === true));
            $valorSaldoUsado = floatval($data['valor_saldo_usado'] ?? 0);
            $valorOriginal = $data['valor_total']; // Guardar valor original para referência
            
            // CORREÇÃO 2: Definir $balanceModel ANTES de usar
            require_once __DIR__ . '/../models/CashbackBalance.php';
            $balanceModel = new CashbackBalance();
            
            // Validações de saldo
            if ($usarSaldo && $valorSaldoUsado > 0) {
                // Verificar se o cliente tem saldo suficiente
                $saldoDisponivel = $balanceModel->getStoreBalance($data['usuario_id'], $data['loja_id']);
                
                if ($saldoDisponivel < $valorSaldoUsado) {
                    return [
                        'status' => false, 
                        'message' => 'Saldo insuficiente. Cliente possui R$ ' . number_format($saldoDisponivel, 2, ',', '.') . ' disponível.'
                    ];
                }
                
                // Validar se o valor do saldo usado não é maior que o valor total
                if ($valorSaldoUsado > $data['valor_total']) {
                    return [
                        'status' => false, 
                        'message' => 'O valor do saldo usado não pode ser maior que o valor total da venda.'
                    ];
                }
            }
            
            // CORREÇÃO 3: Calcular valor efetivo SEM alterar $data['valor_total']
            $valorEfetivamentePago = $data['valor_total'] - $valorSaldoUsado;
            
            // Verificar valor mínimo após desconto do saldo
            if ($valorEfetivamentePago < 0) {
                return ['status' => false, 'message' => 'Valor da transação após desconto do saldo não pode ser negativo.'];
            }
            
            // Se sobrou algum valor após usar saldo, verificar valor mínimo
            if ($valorEfetivamentePago > 0 && $valorEfetivamentePago < MIN_TRANSACTION_VALUE) {
                return ['status' => false, 'message' => 'Valor mínimo para transação (após desconto do saldo) é R$ ' . number_format(MIN_TRANSACTION_VALUE, 2, ',', '.')];
            }
            
            // Verificar se já existe uma transação com o mesmo código
            $checkStmt = $db->prepare("
                SELECT id FROM transacoes_cashback 
                WHERE codigo_transacao = :codigo_transacao AND loja_id = :loja_id
            ");
            $checkStmt->bindParam(':codigo_transacao', $data['codigo_transacao']);
            $checkStmt->bindParam(':loja_id', $data['loja_id']);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                return ['status' => false, 'message' => 'Já existe uma transação com este código.'];
            }
            
            // Obter configurações de cashback
            $configStmt = $db->prepare("SELECT * FROM configuracoes_cashback ORDER BY id DESC LIMIT 1");
            $configStmt->execute();
            $config = $configStmt->fetch(PDO::FETCH_ASSOC);
            
            // CORREÇÃO 4: Sempre usar 10% como valor total de cashback (comissão da loja)
            $porcentagemTotal = DEFAULT_CASHBACK_TOTAL; // Sempre 10%
            
            // CORREÇÃO: Garantir que a divisão é sempre 5% cliente, 5% admin
            $porcentagemCliente = DEFAULT_CASHBACK_CLIENT; // 5%
            $porcentagemAdmin = DEFAULT_CASHBACK_ADMIN; // 5%
            
            // CORREÇÃO: Remover qualquer personalização de porcentagem por loja
            // Se a configuração do sistema for diferente do padrão, aplicar ajuste proporcional
            if (isset($config['porcentagem_cliente']) && isset($config['porcentagem_admin'])) {
                // Verificar se o total configurado é 10%
                $configTotal = $config['porcentagem_cliente'] + $config['porcentagem_admin'];
                
                if ($configTotal == 10.00) {
                    $porcentagemCliente = $config['porcentagem_cliente'];
                    $porcentagemAdmin = $config['porcentagem_admin'];
                } else {
                    // Ajustar proporcionalmente para manter a soma em 10%
                    $fator = 10.00 / $configTotal;
                    $porcentagemCliente = $config['porcentagem_cliente'] * $fator;
                    $porcentagemAdmin = $config['porcentagem_admin'] * $fator;
                }
            }
            
            // Calcular valores de cashback sobre o valor EFETIVAMENTE PAGO
            $valorCashbackTotal = ($valorEfetivamentePago * $porcentagemTotal) / 100;
            $valorCashbackCliente = ($valorEfetivamentePago * $porcentagemCliente) / 100;
            $valorCashbackAdmin = ($valorEfetivamentePago * $porcentagemAdmin) / 100;
            // Valor da loja sempre zero
            $valorLoja = 0.00;
            
            // Iniciar transação no banco de dados
            $db->beginTransaction();
            
            try {
                // Definir o status da transação (pendente por padrão)
                $transactionStatus = isset($data['status']) ? $data['status'] : TRANSACTION_PENDING;
                
                // Preparar descrição da transação
                $descricao = isset($data['descricao']) ? $data['descricao'] : 'Compra na ' . $store['nome_fantasia'];
                if ($usarSaldo && $valorSaldoUsado > 0) {
                    $descricao .= " (Usado R$ " . number_format($valorSaldoUsado, 2, ',', '.') . " do saldo)";
                }
                
                // Registrar transação principal (com valor original para histórico)
                $stmt = $db->prepare("
                    INSERT INTO transacoes_cashback (
                        usuario_id, loja_id, valor_total, valor_cashback,
                        valor_cliente, valor_admin, valor_loja, codigo_transacao, 
                        data_transacao, status, descricao
                    ) VALUES (
                        :usuario_id, :loja_id, :valor_original, :valor_cashback,
                        :valor_cliente, :valor_admin, :valor_loja, :codigo_transacao, 
                        :data_transacao, :status, :descricao
                    )
                ");
                
                $stmt->bindParam(':usuario_id', $data['usuario_id']);
                $stmt->bindParam(':loja_id', $data['loja_id']);
                $stmt->bindParam(':valor_original', $valorOriginal); // Valor original da compra
                $stmt->bindParam(':valor_cashback', $valorCashbackTotal);
                $stmt->bindParam(':valor_cliente', $valorCashbackCliente);
                $stmt->bindParam(':valor_admin', $valorCashbackAdmin);
                $stmt->bindParam(':valor_loja', $valorLoja); // Sempre 0.00
                $stmt->bindParam(':codigo_transacao', $data['codigo_transacao']);
                
                $dataTransacao = isset($data['data_transacao']) ? $data['data_transacao'] : date('Y-m-d H:i:s');
                $stmt->bindParam(':data_transacao', $dataTransacao);
                $stmt->bindParam(':status', $transactionStatus);
                $stmt->bindParam(':descricao', $descricao);
                
                $stmt->execute();
                $transactionId = $db->lastInsertId();
                
                // CORREÇÃO 5: Se usou saldo, debitar do saldo do cliente IMEDIATAMENTE
                if ($usarSaldo && $valorSaldoUsado > 0) {
                    $descricaoUso = "Uso do saldo na compra - Código: " . $data['codigo_transacao'] . " - Transação #" . $transactionId;
                    
                    error_log("REGISTRO: Tentando debitar saldo - Usuario: {$data['usuario_id']}, Loja: {$data['loja_id']}, Valor: {$valorSaldoUsado}");
                    
                    $debitResult = $balanceModel->useBalance($data['usuario_id'], $data['loja_id'], $valorSaldoUsado, $descricaoUso, $transactionId);
                    
                    if (!$debitResult) {
                        // Se falhou ao debitar saldo, reverter transação
                        $db->rollBack();
                        error_log("REGISTRO: FALHA ao debitar saldo - revertendo transação");
                        return ['status' => false, 'message' => 'Erro ao debitar saldo do cliente. Transação cancelada.'];
                    }
                    
                    error_log("REGISTRO: Saldo debitado com sucesso");
                    
                    // Registrar uso de saldo na tabela auxiliar
                    $useSaldoStmt = $db->prepare("
                        INSERT INTO transacoes_saldo_usado (transacao_id, usuario_id, loja_id, valor_usado)
                        VALUES (:transacao_id, :usuario_id, :loja_id, :valor_usado)
                    ");
                    $useSaldoStmt->bindParam(':transacao_id', $transactionId);
                    $useSaldoStmt->bindParam(':usuario_id', $data['usuario_id']);
                    $useSaldoStmt->bindParam(':loja_id', $data['loja_id']);
                    $useSaldoStmt->bindParam(':valor_usado', $valorSaldoUsado);
                    $useSaldoStmt->execute();
                }
                
                // Registrar comissão para o administrador (sobre valor efetivamente pago)
                if ($valorCashbackAdmin > 0) {
                    $comissionAdminStmt = $db->prepare("
                        INSERT INTO transacoes_comissao (
                            tipo_usuario, usuario_id, loja_id, transacao_id,
                            valor_total, valor_comissao, data_transacao, status
                        ) VALUES (
                            :tipo_usuario, :usuario_id, :loja_id, :transacao_id,
                            :valor_total, :valor_comissao, :data_transacao, :status
                        )
                    ");
                    
                    $tipoAdmin = USER_TYPE_ADMIN;
                    $adminId = 1; // Administrador padrão
                    
                    $comissionAdminStmt->bindParam(':tipo_usuario', $tipoAdmin);
                    $comissionAdminStmt->bindParam(':usuario_id', $adminId);
                    $comissionAdminStmt->bindParam(':loja_id', $data['loja_id']);
                    $comissionAdminStmt->bindParam(':transacao_id', $transactionId);
                    $comissionAdminStmt->bindParam(':valor_total', $valorEfetivamentePago); // Valor efetivamente pago
                    $comissionAdminStmt->bindParam(':valor_comissao', $valorCashbackAdmin);
                    $comissionAdminStmt->bindParam(':data_transacao', $dataTransacao);
                    $comissionAdminStmt->bindParam(':status', $transactionStatus);
                    
                    $comissionAdminStmt->execute();
                }
                
                // Preparar mensagem de sucesso
                $successMessage = 'Transação registrada com sucesso!';
                if ($usarSaldo && $valorSaldoUsado > 0) {
                    $successMessage .= ' Saldo de R$ ' . number_format($valorSaldoUsado, 2, ',', '.') . ' foi usado na compra.';
                }
                
                // Criar notificação para o cliente
                $notificationMessage = 'Você tem um novo cashback de R$ ' . number_format($valorCashbackCliente, 2, ',', '.') . ' pendente da loja ' . $store['nome_fantasia'];
                if ($usarSaldo && $valorSaldoUsado > 0) {
                    $notificationMessage .= '. Você usou R$ ' . number_format($valorSaldoUsado, 2, ',', '.') . ' do seu saldo nesta compra.';
                }
                
                self::createNotification(
                    $data['usuario_id'],
                    'Nova transação registrada',
                    $notificationMessage,
                    'info'
                );
                
                // Enviar email para o cliente (opcional, pode remover se não quiser)
                if (!empty($user['email'])) {
                    $subject = 'Novo Cashback Pendente - Klube Cash';
                    $emailMessage = "
                        <h3>Olá, {$user['nome']}!</h3>
                        <p>Uma nova transação foi registrada em sua conta no Klube Cash.</p>
                        <p><strong>Loja:</strong> {$store['nome_fantasia']}</p>
                        <p><strong>Valor total da compra:</strong> R$ " . number_format($valorOriginal, 2, ',', '.') . "</p>";
                    
                    if ($usarSaldo && $valorSaldoUsado > 0) {
                        $emailMessage .= "<p><strong>Saldo usado:</strong> R$ " . number_format($valorSaldoUsado, 2, ',', '.') . "</p>";
                        $emailMessage .= "<p><strong>Valor pago:</strong> R$ " . number_format($valorEfetivamentePago, 2, ',', '.') . "</p>";
                    }
                    
                    $emailMessage .= "
                        <p><strong>Cashback (pendente):</strong> R$ " . number_format($valorCashbackCliente, 2, ',', '.') . "</p>
                        <p><strong>Data:</strong> " . date('d/m/Y H:i', strtotime($dataTransacao)) . "</p>
                        <p>O cashback será disponibilizado assim que a loja confirmar o pagamento da comissão.</p>
                        <p>Atenciosamente,<br>Equipe Klube Cash</p>
                    ";
                    
                    // Email::send($user['email'], $subject, $emailMessage, $user['nome']); // Descomente se quiser enviar email
                }
                
                // Confirmar transação
                $db->commit();
                
                return [
                    'status' => true, 
                    'message' => $successMessage,
                    'data' => [
                        'transaction_id' => $transactionId,
                        'valor_original' => $valorOriginal,
                        'valor_efetivamente_pago' => $valorEfetivamentePago,
                        'valor_saldo_usado' => $valorSaldoUsado,
                        'valor_cashback' => $valorCashbackCliente,
                        'valor_comissao' => $valorCashbackTotal
                    ]
                ];
                
            } catch (Exception $e) {
                // Reverter transação em caso de erro
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                throw $e;
            }
            
        } catch (PDOException $e) {
            // Reverter transação em caso de erro
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            
            error_log('Erro ao registrar transação: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao registrar transação. Tente novamente.'];
        }
    }
    
    /**
     * Processa transações em lote a partir de um arquivo CSV
     * 
     * @param array $file Arquivo enviado ($_FILES['arquivo'])
     * @param int $storeId ID da loja
     * @return array Resultado da operação
     */
    public static function processBatchTransactions($file, $storeId) {
        try {
            // Verificar se o usuário está autenticado e é loja ou admin
            if (!AuthController::isAuthenticated()) {
                return ['status' => false, 'message' => 'Usuário não autenticado.'];
            }
            
            if (!AuthController::isStore() && !AuthController::isAdmin()) {
                return ['status' => false, 'message' => 'Apenas lojas e administradores podem registrar transações em lote.'];
            }
            
            // Validar o arquivo
            if (!isset($file) || !is_array($file) || $file['error'] !== UPLOAD_ERR_OK) {
                return ['status' => false, 'message' => 'Erro no upload do arquivo.'];
            }
            
            // Verificar extensão
            $fileInfo = pathinfo($file['name']);
            $extension = strtolower($fileInfo['extension']);
            
            if ($extension !== 'csv') {
                return ['status' => false, 'message' => 'Apenas arquivos CSV são permitidos.'];
            }
            
            // Verificar se a loja existe
            $db = Database::getConnection();
            $storeStmt = $db->prepare("SELECT id, nome_fantasia FROM lojas WHERE id = :loja_id AND status = :status");
            $storeStmt->bindParam(':loja_id', $storeId);
            $statusAprovado = STORE_APPROVED;
            $storeStmt->bindParam(':status', $statusAprovado);
            $storeStmt->execute();
            
            if ($storeStmt->rowCount() == 0) {
                return ['status' => false, 'message' => 'Loja não encontrada ou não aprovada.'];
            }
            
            // Ler o arquivo CSV
            $filePath = $file['tmp_name'];
            $handle = fopen($filePath, 'r');
            
            if (!$handle) {
                return ['status' => false, 'message' => 'Não foi possível abrir o arquivo.'];
            }
            
            // Ler cabeçalho
            $header = fgetcsv($handle, 1000, ',');
            
            if (!$header || count($header) < 3) {
                fclose($handle);
                return ['status' => false, 'message' => 'Formato de arquivo inválido. Verifique o modelo.'];
            }
            
            // Verificar colunas necessárias
            $requiredColumns = ['email', 'valor', 'codigo_transacao'];
            $headerMap = [];
            
            foreach ($requiredColumns as $required) {
                $found = false;
                
                foreach ($header as $index => $column) {
                    if (strtolower(trim($column)) === $required) {
                        $headerMap[$required] = $index;
                        $found = true;
                        break;
                    }
                }
                
                if (!$found) {
                    fclose($handle);
                    return ['status' => false, 'message' => 'Coluna obrigatória não encontrada: ' . $required];
                }
            }
            
            // Iniciar processamento
            $totalProcessed = 0;
            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            
            // Iniciar transação de banco de dados
            $db->beginTransaction();
            
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $totalProcessed++;
                
                // Extrair dados
                $email = trim($row[$headerMap['email']]);
                $valor = str_replace(['R$', '.', ','], ['', '', '.'], trim($row[$headerMap['valor']]));
                $codigoTransacao = trim($row[$headerMap['codigo_transacao']]);
                
                // Obter descrição se existir
                $descricao = '';
                if (isset($headerMap['descricao']) && isset($row[$headerMap['descricao']])) {
                    $descricao = trim($row[$headerMap['descricao']]);
                }
                
                // Obter data se existir
                $dataTransacao = date('Y-m-d H:i:s');
                if (isset($headerMap['data']) && isset($row[$headerMap['data']])) {
                    $dataStr = trim($row[$headerMap['data']]);
                    if (!empty($dataStr)) {
                        $timestamp = strtotime($dataStr);
                        if ($timestamp !== false) {
                            $dataTransacao = date('Y-m-d H:i:s', $timestamp);
                        }
                    }
                }
                
                // Validações básicas
                if (empty($email) || empty($valor) || empty($codigoTransacao)) {
                    $errorCount++;
                    $errors[] = "Linha {$totalProcessed}: Dados incompletos";
                    continue;
                }
                
                if (!is_numeric($valor) || $valor <= 0) {
                    $errorCount++;
                    $errors[] = "Linha {$totalProcessed}: Valor inválido";
                    continue;
                }
                
                // Buscar ID do usuário pelo email
                $userStmt = $db->prepare("SELECT id FROM usuarios WHERE email = :email AND tipo = :tipo AND status = :status");
                $userStmt->bindParam(':email', $email);
                $tipoCliente = USER_TYPE_CLIENT;
                $userStmt->bindParam(':tipo', $tipoCliente);
                $statusAtivo = USER_ACTIVE;
                $userStmt->bindParam(':status', $statusAtivo);
                $userStmt->execute();
                $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$user) {
                    $errorCount++;
                    $errors[] = "Linha {$totalProcessed}: Cliente com email {$email} não encontrado ou inativo";
                    continue;
                }
                
                // Verificar se já existe transação com este código
                $checkStmt = $db->prepare("
                    SELECT id FROM transacoes_cashback 
                    WHERE codigo_transacao = :codigo_transacao AND loja_id = :loja_id
                ");
                $checkStmt->bindParam(':codigo_transacao', $codigoTransacao);
                $checkStmt->bindParam(':loja_id', $storeId);
                $checkStmt->execute();
                
                if ($checkStmt->rowCount() > 0) {
                    $errorCount++;
                    $errors[] = "Linha {$totalProcessed}: Transação com código {$codigoTransacao} já existe";
                    continue;
                }
                
                // Preparar dados para registro
                $transactionData = [
                    'usuario_id' => $user['id'],
                    'loja_id' => $storeId,
                    'valor_total' => $valor,
                    'codigo_transacao' => $codigoTransacao,
                    'descricao' => $descricao,
                    'data_transacao' => $dataTransacao
                ];
                
                // Registrar transação
                $result = self::registerTransaction($transactionData);
                
                if ($result['status']) {
                    $successCount++;
                } else {
                    $errorCount++;
                    $errors[] = "Linha {$totalProcessed}: " . $result['message'];
                }
            }
            
            fclose($handle);
            
            // Finalizar transação
            if ($errorCount == 0) {
                $db->commit();
                return [
                    'status' => true,
                    'message' => "Processamento concluído com sucesso. {$successCount} transações registradas.",
                    'data' => [
                        'total_processado' => $totalProcessed,
                        'sucesso' => $successCount,
                        'erros' => $errorCount
                    ]
                ];
            } else {
                $db->rollBack();
                return [
                    'status' => false,
                    'message' => "Processamento concluído com erros. Nenhuma transação foi registrada.",
                    'data' => [
                        'total_processado' => $totalProcessed,
                        'sucesso' => 0,
                        'erros' => $errorCount,
                        'detalhes_erros' => $errors
                    ]
                ];
            }
            
        } catch (Exception $e) {
            // Reverter transação em caso de erro
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            
            error_log('Erro ao processar transações em lote: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao processar transações em lote. Tente novamente.'];
        }
    }
    
    /**
    * Registra pagamento de comissões (VERSÃO CORRIGIDA)
    * 
    * @param array $data Dados do pagamento
    * @return array Resultado da operação
    */
    public static function registerPayment($data) {
        try {
            error_log("registerPayment - Dados recebidos: " . print_r($data, true));
            
            // Validação básica
            if (!isset($data['loja_id']) || !isset($data['transacoes']) || !isset($data['valor_total'])) {
                return ['status' => false, 'message' => 'Dados obrigatórios faltando'];
            }
            
            // Verificar se o usuário está autenticado e é loja ou admin
            if (!AuthController::isAuthenticated()) {
                return ['status' => false, 'message' => 'Usuário não autenticado.'];
            }
            
            if (!AuthController::isStore() && !AuthController::isAdmin()) {
                return ['status' => false, 'message' => 'Apenas lojas e administradores podem registrar pagamentos.'];
            }
            
            $db = Database::getConnection();
            
            // Converter transações para array se necessário
            $transactionIds = is_array($data['transacoes']) ? $data['transacoes'] : explode(',', $data['transacoes']);
            $transactionIds = array_map('intval', $transactionIds);
            
            if (empty($transactionIds)) {
                return ['status' => false, 'message' => 'Nenhuma transação selecionada'];
            }
            
            error_log("registerPayment - IDs: " . implode(',', $transactionIds));
            
            // CORREÇÃO: Validar se todas as transações existem e calcular valor total correto
            $placeholders = implode(',', array_fill(0, count($transactionIds), '?'));
            $validateStmt = $db->prepare("
                SELECT 
                    id,
                    (valor_cliente + valor_admin) as comissao_total,
                    status,
                    loja_id
                FROM transacoes_cashback 
                WHERE id IN ($placeholders) AND loja_id = ? AND status = ?
            ");
            
            $validateParams = array_merge($transactionIds, [$data['loja_id'], TRANSACTION_PENDING]);
            $validateStmt->execute($validateParams);
            $transactions = $validateStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Verificar se todas as transações foram encontradas
            if (count($transactions) !== count($transactionIds)) {
                return [
                    'status' => false, 
                    'message' => 'Algumas transações não foram encontradas ou não estão pendentes. Esperado: ' . count($transactionIds) . ', Encontrado: ' . count($transactions)
                ];
            }
            
            // CORREÇÃO: Calcular valor total correto (soma das comissões totais)
            $totalCalculated = 0;
            foreach ($transactions as $transaction) {
                $totalCalculated += $transaction['comissao_total'];
            }
            
            // Validar se o valor informado bate com o calculado
            $valorInformado = floatval($data['valor_total']);
            if (abs($totalCalculated - $valorInformado) > 0.01) {
                error_log("registerPayment - Erro valor: Calculado=$totalCalculated, Informado=$valorInformado");
                return [
                    'status' => false, 
                    'message' => 'Valor total informado (R$ ' . number_format($valorInformado, 2, ',', '.') . 
                    ') não confere com o valor das transações selecionadas (R$ ' . number_format($totalCalculated, 2, ',', '.') . ')'
                ];
            }
            
            // Validar valores numéricos
            if ($valorInformado <= 0) {
                return ['status' => false, 'message' => 'Valor total deve ser maior que zero'];
            }
            
            // Iniciar transação no banco de dados
            $db->beginTransaction();
            
            try {
                // 1. Inserir o pagamento
                $stmt = $db->prepare("
                    INSERT INTO pagamentos_comissao 
                    (loja_id, valor_total, metodo_pagamento, numero_referencia, comprovante, observacao, status, data_registro) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pendente', NOW())
                ");
                
                $result = $stmt->execute([
                    $data['loja_id'],
                    $totalCalculated, // Usar valor calculado para garantir precisão
                    $data['metodo_pagamento'] ?? 'pix',
                    $data['numero_referencia'] ?? '',
                    $data['comprovante'] ?? '',
                    $data['observacao'] ?? ''
                ]);
                
                if (!$result) {
                    throw new Exception('Erro ao inserir pagamento');
                }
                
                $paymentId = $db->lastInsertId();
                error_log("registerPayment - Payment ID criado: $paymentId");
                
                // 2. Associar transações ao pagamento
                $assocStmt = $db->prepare("INSERT INTO pagamentos_transacoes (pagamento_id, transacao_id) VALUES (?, ?)");
                
                foreach ($transactionIds as $transId) {
                    $assocResult = $assocStmt->execute([$paymentId, $transId]);
                    if (!$assocResult) {
                        throw new Exception("Erro ao associar transação $transId");
                    }
                    error_log("registerPayment - Transação $transId associada");
                }
                
                // 3. Atualizar status das transações
                $placeholders = implode(',', array_fill(0, count($transactionIds), '?'));
                $updateStmt = $db->prepare("UPDATE transacoes_cashback SET status = 'pagamento_pendente' WHERE id IN ($placeholders)");
                
                $updateResult = $updateStmt->execute($transactionIds);
                if (!$updateResult) {
                    throw new Exception('Erro ao atualizar status das transações');
                }
                
                // 4. Criar notificação para admin
                self::createNotification(
                    1, // Admin padrão
                    'Novo pagamento registrado',
                    'Nova solicitação de pagamento de comissão de R$ ' . number_format($totalCalculated, 2, ',', '.') . ' aguardando aprovação.',
                    'info'
                );
                
                // 5. Log de sucesso
                error_log("registerPayment - Pagamento registrado com sucesso: ID=$paymentId, Valor=$totalCalculated, Transações=" . implode(',', $transactionIds));
                
                // Commit da transação
                $db->commit();
                error_log("registerPayment - Sucesso total!");
                
                return [
                    'status' => true,
                    'message' => 'Pagamento registrado com sucesso! Aguardando aprovação da administração.',
                    'data' => [
                        'payment_id' => $paymentId,
                        'valor_total' => $totalCalculated,
                        'total_transacoes' => count($transactionIds)
                    ]
                ];
                
            } catch (Exception $e) {
                // Rollback em caso de erro
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                error_log("registerPayment - Erro durante transação: " . $e->getMessage());
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("registerPayment - ERRO: " . $e->getMessage());
            return [
                'status' => false, 
                'message' => 'Erro ao registrar pagamento: ' . $e->getMessage()
            ];
        }
    }
    



    
    /**
    * Aprova um pagamento de comissão
    * 
    * @param int $paymentId ID do pagamento
    * @param string $observacao Observação opcional
    * @return array Resultado da operação
    */
    public static function approvePayment($paymentId, $observacao = '') {
        try {
            // Verificar se o usuário está autenticado e é administrador
            if (!AuthController::isAuthenticated() || !AuthController::isAdmin()) {
                return ['status' => false, 'message' => 'Acesso restrito a administradores.'];
            }
            
            $db = Database::getConnection();
            
            // Verificar se o pagamento existe e está pendente
            $paymentStmt = $db->prepare("
                SELECT p.*, l.nome_fantasia as loja_nome
                FROM pagamentos_comissao p
                JOIN lojas l ON p.loja_id = l.id
                WHERE p.id = ? AND p.status = 'pendente'
            ");
            $paymentStmt->execute([$paymentId]);
            $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$payment) {
                return ['status' => false, 'message' => 'Pagamento não encontrado ou não está pendente.'];
            }
            
            // Obter transações associadas ao pagamento ANTES de iniciar a transação
            $transStmt = $db->prepare("
                SELECT t.id, t.usuario_id, t.loja_id, t.valor_cliente
                FROM pagamentos_transacoes pt
                JOIN transacoes_cashback t ON pt.transacao_id = t.id
                WHERE pt.pagamento_id = ?
            ");
            $transStmt->execute([$paymentId]);
            $transactions = $transStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($transactions) == 0) {
                return ['status' => false, 'message' => 'Nenhuma transação encontrada para este pagamento.'];
            }
            
            // Iniciar transação principal
            $db->beginTransaction();
            
            try {
                // 1. Atualizar status do pagamento
                $updatePaymentStmt = $db->prepare("
                    UPDATE pagamentos_comissao
                    SET status = 'aprovado', data_aprovacao = NOW(), observacao_admin = ?
                    WHERE id = ?
                ");
                $updatePaymentResult = $updatePaymentStmt->execute([$observacao, $paymentId]);
                
                if (!$updatePaymentResult) {
                    throw new Exception('Erro ao atualizar status do pagamento');
                }
                
                // 2. Atualizar status das transações
                $transactionIds = array_column($transactions, 'id');
                $placeholders = implode(',', array_fill(0, count($transactionIds), '?'));
                
                $updateTransStmt = $db->prepare("
                    UPDATE transacoes_cashback 
                    SET status = 'aprovado' 
                    WHERE id IN ($placeholders)
                ");
                $updateTransResult = $updateTransStmt->execute($transactionIds);
                
                if (!$updateTransResult) {
                    throw new Exception('Erro ao atualizar status das transações');
                }
                
                // 3. Atualizar comissões
                $updateCommissionStmt = $db->prepare("
                    UPDATE transacoes_comissao 
                    SET status = 'aprovado' 
                    WHERE transacao_id IN ($placeholders)
                ");
                $updateCommissionResult = $updateCommissionStmt->execute($transactionIds);
                
                if (!$updateCommissionResult) {
                    throw new Exception('Erro ao atualizar status das comissões');
                }
                
                // Commit da transação principal ANTES de creditar saldos
                $db->commit();
                error_log("APROVAÇÃO: Transação principal commitada com sucesso");
                
                // 4. Creditar saldos FORA da transação principal para evitar conflitos
                require_once __DIR__ . '/../models/CashbackBalance.php';
                require_once __DIR__ . '/AdminController.php';
                $balanceModel = new CashbackBalance();
                $saldosCreditados = 0;
                
                foreach ($transactions as $transaction) {
                    if ($transaction['valor_cliente'] > 0) {
                        $description = "Cashback da compra - Transação #{$transaction['id']} (Pagamento #{$paymentId} aprovado)";
                        
                        error_log("APROVAÇÃO: Creditando saldo - Transação: {$transaction['id']}, Usuario: {$transaction['usuario_id']}, Loja: {$transaction['loja_id']}, Valor: {$transaction['valor_cliente']}");
                        
                        $creditResult = $balanceModel->addBalance(
                            $transaction['usuario_id'],
                            $transaction['loja_id'],
                            $transaction['valor_cliente'],
                            $description,
                            $transaction['id']
                        );
                        
                        if ($creditResult) {
                            $saldosCreditados++;
                            error_log("APROVAÇÃO: Saldo creditado com sucesso - Transação: {$transaction['id']}");
                        } else {
                            error_log("APROVAÇÃO: ERRO ao creditar saldo - Transação: {$transaction['id']}");
                            // Continuamos mesmo se um crédito falhar
                        }
                    }
                }
                
                // Atualizar saldo do administrador (após o commit principal)
                foreach ($transactions as $transaction) {
                    // Obter valor da comissão do admin para esta transação
                    $adminComissionStmt = $db->prepare("
                        SELECT valor_comissao 
                        FROM transacoes_comissao 
                        WHERE transacao_id = ? AND tipo_usuario = 'admin'
                    ");
                    $adminComissionStmt->execute([$transaction['id']]);
                    $adminComission = $adminComissionStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($adminComission && $adminComission['valor_comissao'] > 0) {
                        $descricao = "Comissão da transação #{$transaction['id']} - Pagamento #{$paymentId} aprovado";
                        
                        $updateResult = AdminController::updateAdminBalance(
                            $adminComission['valor_comissao'],
                            $transaction['id'],
                            $descricao
                        );
                        
                        if (!$updateResult) {
                            error_log("APROVAÇÃO: Falha ao atualizar saldo admin para transação #{$transaction['id']}");
                        }
                    }
                }
                
                // 5. Criar notificações (fora da transação)
                $clienteNotificados = [];
                foreach ($transactions as $transaction) {
                    if (!in_array($transaction['usuario_id'], $clienteNotificados)) {
                        // Obter detalhes do cliente
                        $clientStmt = $db->prepare("SELECT nome, email FROM usuarios WHERE id = ?");
                        $clientStmt->execute([$transaction['usuario_id']]);
                        $client = $clientStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($client) {
                            // Calcular total de cashback para este cliente
                            $clientTransStmt = $db->prepare("
                                SELECT COUNT(*) as total_trans, SUM(valor_cliente) as total_cashback
                                FROM transacoes_cashback
                                WHERE id IN ($placeholders) AND usuario_id = ?
                            ");
                            $params = array_merge($transactionIds, [$transaction['usuario_id']]);
                            $clientTransStmt->execute($params);
                            $clientTrans = $clientTransStmt->fetch(PDO::FETCH_ASSOC);
                            
                            // Criar notificação
                            if ($clientTrans['total_trans'] > 0) {
                                self::createNotification(
                                    $transaction['usuario_id'],
                                    'Cashback disponível!',
                                    'Seu cashback de R$ ' . number_format($clientTrans['total_cashback'], 2, ',', '.') . 
                                    ' da loja ' . $payment['loja_nome'] . ' está disponível.',
                                    'success'
                                );
                                
                                $clienteNotificados[] = $transaction['usuario_id'];
                            }
                        }
                    }
                }
                
                // 6. Notificar loja
                $storeUserStmt = $db->prepare("SELECT usuario_id FROM lojas WHERE id = ?");
                $storeUserStmt->execute([$payment['loja_id']]);
                $storeUser = $storeUserStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($storeUser && !empty($storeUser['usuario_id'])) {
                    self::createNotification(
                        $storeUser['usuario_id'],
                        'Pagamento aprovado',
                        'Seu pagamento de comissão no valor de R$ ' . number_format($payment['valor_total'], 2, ',', '.') . ' foi aprovado.',
                        'success'
                    );
                }
                
                return [
                    'status' => true,
                    'message' => 'Pagamento aprovado com sucesso! Cashback liberado para os clientes.',
                    'data' => [
                        'payment_id' => $paymentId,
                        'transacoes_atualizadas' => count($transactions),
                        'saldos_creditados' => $saldosCreditados
                    ]
                ];
                
            } catch (Exception $e) {
                // Rollback apenas se a transação ainda estiver ativa
                if ($db->inTransaction()) {
                    $db->rollBack();
                    error_log('APROVAÇÃO: Rollback executado devido ao erro: ' . $e->getMessage());
                }
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log('APROVAÇÃO: Erro geral ao aprovar pagamento: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao aprovar pagamento: ' . $e->getMessage()];
        }
    }
    
    /**
     * Rejeita um pagamento de comissão
     * 
     * @param int $paymentId ID do pagamento
     * @param string $motivo Motivo da rejeição
     * @return array Resultado da operação
     */
    public static function rejectPayment($paymentId, $motivo) {
        try {
            // Verificar se o usuário está autenticado e é administrador
            if (!AuthController::isAuthenticated() || !AuthController::isAdmin()) {
                return ['status' => false, 'message' => 'Acesso restrito a administradores.'];
            }
            
            if (empty($motivo)) {
                return ['status' => false, 'message' => 'É necessário informar o motivo da rejeição.'];
            }
            
            $db = Database::getConnection();
            
            // Verificar se o pagamento existe e está pendente
            $paymentStmt = $db->prepare("
                SELECT p.*, l.nome_fantasia as loja_nome
                FROM pagamentos_comissao p
                JOIN lojas l ON p.loja_id = l.id
                WHERE p.id = :payment_id AND p.status = 'pendente'
            ");
            $paymentStmt->bindParam(':payment_id', $paymentId);
            $paymentStmt->execute();
            $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$payment) {
                return ['status' => false, 'message' => 'Pagamento não encontrado ou não está pendente.'];
            }
            
            // Iniciar transação
            $db->beginTransaction();
            
            // Atualizar status do pagamento
            $updatePaymentStmt = $db->prepare("
                UPDATE pagamentos_comissao
                SET status = :status, data_aprovacao = NOW(), observacao_admin = :observacao
                WHERE id = :payment_id
            ");
            $status = 'rejeitado';
            $updatePaymentStmt->bindParam(':status', $status);
            $updatePaymentStmt->bindParam(':observacao', $motivo);
            $updatePaymentStmt->bindParam(':payment_id', $paymentId);
            $updatePaymentStmt->execute();
            
            // Obter transações associadas ao pagamento
            $transStmt = $db->prepare("
                SELECT t.id, t.usuario_id, t.valor_total, t.valor_cliente
                FROM pagamentos_transacoes pt
                JOIN transacoes_cashback t ON pt.transacao_id = t.id
                WHERE pt.pagamento_id = :payment_id
            ");
            $transStmt->bindParam(':payment_id', $paymentId);
            $transStmt->execute();
            $transactions = $transStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Atualizar status das transações para pendente novamente
            if (count($transactions) > 0) {
                $transactionIds = array_column($transactions, 'id');
                $placeholders = implode(',', array_fill(0, count($transactionIds), '?'));
                
                $updateTransStmt = $db->prepare("
                    UPDATE transacoes_cashback 
                    SET status = :novo_status 
                    WHERE id IN ($placeholders)
                ");
                
                $novoStatus = TRANSACTION_PENDING;
                $updateTransStmt->bindParam(':novo_status', $novoStatus);
                
                for ($i = 0; $i < count($transactionIds); $i++) {
                    $updateTransStmt->bindValue($i + 1, $transactionIds[$i]);
                }
                
                $updateTransStmt->execute();
            }
            
            // Notificar loja
            $storeNotifyStmt = $db->prepare("
                SELECT id, usuario_id, email FROM lojas WHERE id = :loja_id
            ");
            $storeNotifyStmt->bindParam(':loja_id', $payment['loja_id']);
            $storeNotifyStmt->execute();
            $storeNotify = $storeNotifyStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($storeNotify) {
                // Notificação no sistema (se houver usuário vinculado)
                if (!empty($storeNotify['usuario_id'])) {
                    self::createNotification(
                        $storeNotify['usuario_id'],
                        'Pagamento rejeitado',
                        'Seu pagamento de comissão no valor de R$ ' . number_format($payment['valor_total'], 2, ',', '.') . 
                        ' foi rejeitado. Motivo: ' . $motivo,
                        'error'
                    );
                }
                
                // Email
                if (!empty($storeNotify['email'])) {
                    $subject = 'Pagamento Rejeitado - Klube Cash';
                    $message = "
                        <h3>Olá, {$payment['loja_nome']}!</h3>
                        <p>Infelizmente, seu pagamento de comissão foi rejeitado.</p>
                        <p><strong>Valor:</strong> R$ " . number_format($payment['valor_total'], 2, ',', '.') . "</p>
                        <p><strong>Método:</strong> {$payment['metodo_pagamento']}</p>
                        <p><strong>Data:</strong> " . date('d/m/Y H:i:s') . "</p>
                        <p><strong>Motivo da rejeição:</strong> " . nl2br(htmlspecialchars($motivo)) . "</p>
                        <p>Por favor, verifique o motivo da rejeição e registre um novo pagamento.</p>
                        <p>Atenciosamente,<br>Equipe Klube Cash</p>
                    ";
                    
                    Email::send($storeNotify['email'], $subject, $message, $payment['loja_nome']);
                }
            }
            
            // Confirmar transação
            $db->commit();
            
            return [
                'status' => true,
                'message' => 'Pagamento rejeitado com sucesso.',
                'data' => [
                    'payment_id' => $paymentId,
                    'transacoes_atualizadas' => count($transactions)
                ]
            ];
            
        } catch (PDOException $e) {
            // Reverter transação em caso de erro
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            
            error_log('Erro ao rejeitar pagamento: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao rejeitar pagamento. Tente novamente.'];
        }
    }
    
    /**
    * Obtém lista de transações pendentes para uma loja
    * 
    * @param int $storeId ID da loja
    * @param array $filters Filtros adicionais
    * @param int $page Página atual
    * @return array Lista de transações pendentes
    */
    public static function getPendingTransactions($storeId, $filters = [], $page = 1) {
        try {
            // Verificar se o usuário está autenticado
            if (!AuthController::isAuthenticated()) {
                return ['status' => false, 'message' => 'Usuário não autenticado.'];
            }
            
            $db = Database::getConnection();
            
            // Verificar permissões - apenas a loja dona das transações ou admin podem acessar
            if (AuthController::isStore()) {
                $currentUserId = AuthController::getCurrentUserId();
                $storeOwnerQuery = $db->prepare("SELECT usuario_id FROM lojas WHERE id = :loja_id");
                $storeOwnerQuery->bindParam(':loja_id', $storeId);
                $storeOwnerQuery->execute();
                $storeOwner = $storeOwnerQuery->fetch(PDO::FETCH_ASSOC);
                
                if (!$storeOwner || $storeOwner['usuario_id'] != $currentUserId) {
                    return ['status' => false, 'message' => 'Acesso não autorizado a esta loja.'];
                }
            } elseif (!AuthController::isAdmin()) {
                return ['status' => false, 'message' => 'Acesso não autorizado.'];
            }
            
            // Verificar se a loja existe
            $storeStmt = $db->prepare("SELECT id FROM lojas WHERE id = :loja_id");
            $storeStmt->bindParam(':loja_id', $storeId);
            $storeStmt->execute();
            
            if ($storeStmt->rowCount() == 0) {
                return ['status' => false, 'message' => 'Loja não encontrada.'];
            }
            
            // Construir consulta
            $query = "
                SELECT t.*, u.nome as cliente_nome, u.email as cliente_email
                FROM transacoes_cashback t
                JOIN usuarios u ON t.usuario_id = u.id
                WHERE t.loja_id = :loja_id AND t.status = :status
            ";
            
            $params = [
                ':loja_id' => $storeId,
                ':status' => TRANSACTION_PENDING
            ];
            
            // Aplicar filtros
            if (!empty($filters)) {
                // Filtro por período
                if (isset($filters['data_inicio']) && !empty($filters['data_inicio'])) {
                    $query .= " AND t.data_transacao >= :data_inicio";
                    $params[':data_inicio'] = $filters['data_inicio'] . ' 00:00:00';
                }
                
                if (isset($filters['data_fim']) && !empty($filters['data_fim'])) {
                    $query .= " AND t.data_transacao <= :data_fim";
                    $params[':data_fim'] = $filters['data_fim'] . ' 23:59:59';
                }
                
                // Filtro por cliente
                if (isset($filters['cliente']) && !empty($filters['cliente'])) {
                    $query .= " AND (u.nome LIKE :cliente OR u.email LIKE :cliente)";
                    $params[':cliente'] = '%' . $filters['cliente'] . '%';
                }
                
                // Filtro por valor mínimo
                if (isset($filters['valor_min']) && !empty($filters['valor_min'])) {
                    $query .= " AND t.valor_total >= :valor_min";
                    $params[':valor_min'] = $filters['valor_min'];
                }
                
                // Filtro por valor máximo
                if (isset($filters['valor_max']) && !empty($filters['valor_max'])) {
                    $query .= " AND t.valor_total <= :valor_max";
                    $params[':valor_max'] = $filters['valor_max'];
                }
            }
            
            // Ordenação
            $query .= " ORDER BY t.data_transacao DESC";
            
            // Contagem total para paginação
            $countQuery = str_replace("t.*, u.nome as cliente_nome, u.email as cliente_email", "COUNT(*) as total", $query);
            $countStmt = $db->prepare($countQuery);
            
            foreach ($params as $param => $value) {
                $countStmt->bindValue($param, $value);
            }
            
            $countStmt->execute();
            $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Paginação
            $perPage = defined('ITEMS_PER_PAGE') ? ITEMS_PER_PAGE : 10;
            $totalPages = ceil($totalCount / $perPage);
            $page = max(1, min($page, $totalPages));
            $offset = ($page - 1) * $perPage;
            
            $query .= " LIMIT :offset, :limit";
            $params[':offset'] = $offset;
            $params[':limit'] = $perPage;
            
            // Executar consulta
            $stmt = $db->prepare($query);
            
            // Bind manual para offset e limit
            foreach ($params as $param => $value) {
                if ($param == ':offset' || $param == ':limit') {
                    $stmt->bindValue($param, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($param, $value);
                }
            }
            
            $stmt->execute();
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular totais
            $totalValorCompras = 0;
            $totalValorComissoes = 0;
            
            foreach ($transactions as $transaction) {
                $totalValorCompras += $transaction['valor_total'];
                $totalValorComissoes += $transaction['valor_cashback'];
            }
            
            return [
                'status' => true,
                'data' => [
                    'transacoes' => $transactions,
                    'totais' => [
                        'total_transacoes' => count($transactions),
                        'total_valor_compras' => $totalValorCompras,
                        'total_valor_comissoes' => $totalValorComissoes
                    ],
                    'paginacao' => [
                        'total' => $totalCount,
                        'por_pagina' => $perPage,
                        'pagina_atual' => $page,
                        'total_paginas' => $totalPages
                    ]
                ]
            ];
            
        } catch (PDOException $e) {
            error_log('Erro ao obter transações pendentes: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao obter transações pendentes. Tente novamente.'];
        }
    }
    
    /**
    * Obtém detalhes de um pagamento
    * 
    * @param int $paymentId ID do pagamento
    * @return array Detalhes do pagamento
    */
    public static function getPaymentDetails($paymentId) {
        try {
            // Verificar se o usuário está autenticado
            if (!AuthController::isAuthenticated()) {
                return ['status' => false, 'message' => 'Usuário não autenticado.'];
            }
            
            $db = Database::getConnection();
            
            // Obter dados do pagamento com mais informações
            $paymentStmt = $db->prepare("
                SELECT p.*, l.nome_fantasia as loja_nome, l.email as loja_email,
                    (SELECT COUNT(*) FROM pagamentos_transacoes pt WHERE pt.pagamento_id = p.id) as total_transacoes
                FROM pagamentos_comissao p
                JOIN lojas l ON p.loja_id = l.id
                WHERE p.id = :payment_id
            ");
            $paymentStmt->bindParam(':payment_id', $paymentId);
            $paymentStmt->execute();
            $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$payment) {
                return ['status' => false, 'message' => 'Pagamento não encontrado.'];
            }
            
            // Verificar permissões - admin ou a própria loja
            $currentUserId = AuthController::getCurrentUserId();
            if (!AuthController::isAdmin()) {
                if (AuthController::isStore()) {
                    // Verificar se é a loja dona do pagamento
                    $storeCheckStmt = $db->prepare("SELECT usuario_id FROM lojas WHERE id = :loja_id");
                    $storeCheckStmt->bindParam(':loja_id', $payment['loja_id']);
                    $storeCheckStmt->execute();
                    $storeCheck = $storeCheckStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$storeCheck || $storeCheck['usuario_id'] != $currentUserId) {
                        return ['status' => false, 'message' => 'Acesso não autorizado.'];
                    }
                } else {
                    return ['status' => false, 'message' => 'Acesso não autorizado.'];
                }
            }
            
            // Obter transações associadas com mais detalhes
            $transStmt = $db->prepare("
                SELECT t.*, u.nome as cliente_nome, u.email as cliente_email
                FROM pagamentos_transacoes pt
                JOIN transacoes_cashback t ON pt.transacao_id = t.id
                JOIN usuarios u ON t.usuario_id = u.id
                WHERE pt.pagamento_id = :payment_id
                ORDER BY t.data_transacao DESC
            ");
            $transStmt->bindParam(':payment_id', $paymentId);
            $transStmt->execute();
            $transactions = $transStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular totais detalhados
            $totalValorCompras = 0;
            $totalValorComissoes = 0;
            $totalCashbackClientes = 0;
            
            foreach ($transactions as $transaction) {
                $totalValorCompras += $transaction['valor_total'];
                $totalValorComissoes += $transaction['valor_cashback'];
                $totalCashbackClientes += $transaction['valor_cliente'];
            }
            
            return [
                'status' => true,
                'data' => [
                    'pagamento' => $payment,
                    'transacoes' => $transactions,
                    'totais' => [
                        'total_transacoes' => count($transactions),
                        'total_valor_compras' => $totalValorCompras,
                        'total_valor_comissoes' => $totalValorComissoes,
                        'total_cashback_clientes' => $totalCashbackClientes
                    ]
                ]
            ];
            
        } catch (PDOException $e) {
            error_log('Erro ao obter detalhes do pagamento: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro interno do servidor. Tente novamente.'];
        }
    }
    /**
     * Cria uma notificação para um usuário
     * 
     * @param int $userId ID do usuário
     * @param string $titulo Título da notificação
     * @param string $mensagem Mensagem da notificação
     * @param string $tipo Tipo da notificação (info, success, warning, error)
     * @return bool Verdadeiro se a notificação foi criada
     */
    private static function createNotification($userId, $titulo, $mensagem, $tipo = 'info') {
        try {
            $db = Database::getConnection();
            
            // Verificar se a tabela existe, criar se não existir
            $tableCheckStmt = $db->prepare("SHOW TABLES LIKE 'notificacoes'");
            $tableCheckStmt->execute();
            
            if ($tableCheckStmt->rowCount() == 0) {
                $createTableQuery = "
                    CREATE TABLE notificacoes (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        usuario_id INT NOT NULL,
                        titulo VARCHAR(100) NOT NULL,
                        mensagem TEXT NOT NULL,
                        tipo ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
                        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        lida TINYINT(1) DEFAULT 0,
                        data_leitura TIMESTAMP NULL,
                        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
                    )
                ";
                $db->exec($createTableQuery);
            }
            
            $stmt = $db->prepare("
                INSERT INTO notificacoes (usuario_id, titulo, mensagem, tipo, data_criacao, lida)
                VALUES (:usuario_id, :titulo, :mensagem, :tipo, NOW(), 0)
            ");
            
            $stmt->bindParam(':usuario_id', $userId);
            $stmt->bindParam(':titulo', $titulo);
            $stmt->bindParam(':mensagem', $mensagem);
            $stmt->bindParam(':tipo', $tipo);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log('Erro ao criar notificação: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
    * Obtém histórico de pagamentos de uma loja
    * 
    * @param int $storeId ID da loja
    * @param array $filters Filtros adicionais
    * @param int $page Página atual
    * @return array Histórico de pagamentos
    */
    public static function getPaymentHistory($storeId, $filters = [], $page = 1) {
        try {
            // Verificar se o usuário está autenticado
            if (!AuthController::isAuthenticated()) {
                return ['status' => false, 'message' => 'Usuário não autenticado.'];
            }
            
            $db = Database::getConnection();
            
            // Verificar permissões - apenas a loja dona dos pagamentos ou admin podem acessar
            if (AuthController::isStore()) {
                $currentUserId = AuthController::getCurrentUserId();
                $storeOwnerQuery = $db->prepare("SELECT usuario_id FROM lojas WHERE id = :loja_id");
                $storeOwnerQuery->bindParam(':loja_id', $storeId);
                $storeOwnerQuery->execute();
                $storeOwner = $storeOwnerQuery->fetch(PDO::FETCH_ASSOC);
                
                if (!$storeOwner || $storeOwner['usuario_id'] != $currentUserId) {
                    return ['status' => false, 'message' => 'Acesso não autorizado a esta loja.'];
                }
            } elseif (!AuthController::isAdmin()) {
                return ['status' => false, 'message' => 'Acesso não autorizado.'];
            }
            
            // Verificar se a loja existe
            $storeStmt = $db->prepare("SELECT id, nome_fantasia FROM lojas WHERE id = :loja_id");
            $storeStmt->bindParam(':loja_id', $storeId);
            $storeStmt->execute();
            
            if ($storeStmt->rowCount() == 0) {
                return ['status' => false, 'message' => 'Loja não encontrada.'];
            }
            
            // Construir consulta
            $query = "
                SELECT p.*,
                    (SELECT COUNT(*) FROM pagamentos_transacoes WHERE pagamento_id = p.id) as qtd_transacoes
                FROM pagamentos_comissao p
                WHERE p.loja_id = :loja_id
            ";
            
            $params = [
                ':loja_id' => $storeId
            ];
            
            // Aplicar filtros
            if (!empty($filters)) {
                // Filtro por status
                if (isset($filters['status']) && !empty($filters['status'])) {
                    $query .= " AND p.status = :status";
                    $params[':status'] = $filters['status'];
                }
                
                // Filtro por período
                if (isset($filters['data_inicio']) && !empty($filters['data_inicio'])) {
                    $query .= " AND p.data_registro >= :data_inicio";
                    $params[':data_inicio'] = $filters['data_inicio'] . ' 00:00:00';
                }
                
                if (isset($filters['data_fim']) && !empty($filters['data_fim'])) {
                    $query .= " AND p.data_registro <= :data_fim";
                    $params[':data_fim'] = $filters['data_fim'] . ' 23:59:59';
                }
                
                // Filtro por método de pagamento
                if (isset($filters['metodo_pagamento']) && !empty($filters['metodo_pagamento'])) {
                    $query .= " AND p.metodo_pagamento = :metodo_pagamento";
                    $params[':metodo_pagamento'] = $filters['metodo_pagamento'];
                }
            }
            
            // Ordenação
            $query .= " ORDER BY p.data_registro DESC";
            
            // Contagem total para paginação
            $countQuery = str_replace("p.*, (SELECT COUNT(*) FROM pagamentos_transacoes WHERE pagamento_id = p.id) as qtd_transacoes", "COUNT(*) as total", $query);
            $countStmt = $db->prepare($countQuery);
            
            foreach ($params as $param => $value) {
                $countStmt->bindValue($param, $value);
            }
            
            $countStmt->execute();
            $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Paginação
            $perPage = defined('ITEMS_PER_PAGE') ? ITEMS_PER_PAGE : 10;
            $totalPages = ceil($totalCount / $perPage);
            $page = max(1, min($page, $totalPages));
            $offset = ($page - 1) * $perPage;
            
            $query .= " LIMIT :offset, :limit";
            $params[':offset'] = $offset;
            $params[':limit'] = $perPage;
            
            // Executar consulta
            $stmt = $db->prepare($query);
            
            // Bind manual para offset e limit
            foreach ($params as $param => $value) {
                if ($param == ':offset' || $param == ':limit') {
                    $stmt->bindValue($param, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($param, $value);
                }
            }
            
            $stmt->execute();
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular totais
            $totalValorPagamentos = 0;
            $totalAprovados = 0;
            $totalPendentes = 0;
            $totalRejeitados = 0;
            
            foreach ($payments as $payment) {
                $totalValorPagamentos += $payment['valor_total'];
                
                if ($payment['status'] == 'aprovado') {
                    $totalAprovados++;
                } elseif ($payment['status'] == 'pendente') {
                    $totalPendentes++;
                } elseif ($payment['status'] == 'rejeitado') {
                    $totalRejeitados++;
                }
            }
            
            return [
                'status' => true,
                'data' => [
                    'pagamentos' => $payments,
                    'totais' => [
                        'total_pagamentos' => count($payments),
                        'total_valor' => $totalValorPagamentos,
                        'total_aprovados' => $totalAprovados,
                        'total_pendentes' => $totalPendentes,
                        'total_rejeitados' => $totalRejeitados
                    ],
                    'paginacao' => [
                        'total' => $totalCount,
                        'por_pagina' => $perPage,
                        'pagina_atual' => $page,
                        'total_paginas' => $totalPages
                    ]
                ]
            ];
            
        } catch (PDOException $e) {
            error_log('Erro ao obter histórico de pagamentos: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao obter histórico de pagamentos. Tente novamente.'];
        }
    }
}

// Processar requisições diretas de acesso ao controlador
if (basename($_SERVER['PHP_SELF']) === 'TransactionController.php') {
    // Verificar se o usuário está autenticado
    if (!AuthController::isAuthenticated()) {
        header('Location: ' . LOGIN_URL . '?error=' . urlencode('Você precisa fazer login para acessar esta página.'));
        exit;
    }
    
    $action = $_REQUEST['action'] ?? '';
    
    switch ($action) {
        case 'register':
            $data = $_POST;
            $result = TransactionController::registerTransaction($data);
            echo json_encode($result);
            break;
        // Adicionar este case no switch do TransactionController.php

        case 'payment_details_with_balance':
            $paymentId = intval($_POST['payment_id'] ?? 0);
            if ($paymentId <= 0) {
                echo json_encode(['status' => false, 'message' => 'ID do pagamento inválido']);
                return;
            }
            
            $result = self::getPaymentDetailsWithBalance($paymentId);
            echo json_encode($result);
            break;    
        case 'process_batch':
            $file = $_FILES['arquivo'] ?? null;
            $storeId = isset($_POST['loja_id']) ? intval($_POST['loja_id']) : 0;
            $result = TransactionController::processBatchTransactions($file, $storeId);
            echo json_encode($result);
            break;
            
        case 'pending_transactions':
            $storeId = isset($_POST['loja_id']) ? intval($_POST['loja_id']) : 0;
            $filters = $_POST['filters'] ?? [];
            $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $result = TransactionController::getPendingTransactions($storeId, $filters, $page);
            echo json_encode($result);
            break;
            
        case 'register_payment':
            $data = $_POST;
            $result = TransactionController::registerPayment($data);
            echo json_encode($result);
            break;
            
        case 'approve_payment':
            // Apenas admin pode aprovar pagamentos
            if (!AuthController::isAdmin()) {
                echo json_encode(['status' => false, 'message' => 'Acesso restrito a administradores.']);
                exit;
            }
            
            $paymentId = isset($_POST['payment_id']) ? intval($_POST['payment_id']) : 0;
            $observacao = $_POST['observacao'] ?? '';
            $result = TransactionController::approvePayment($paymentId, $observacao);
            echo json_encode($result);
            break;
            
        case 'reject_payment':
            // Apenas admin pode rejeitar pagamentos
            if (!AuthController::isAdmin()) {
                echo json_encode(['status' => false, 'message' => 'Acesso restrito a administradores.']);
                exit;
            }
            
            $paymentId = isset($_POST['payment_id']) ? intval($_POST['payment_id']) : 0;
            $motivo = $_POST['motivo'] ?? '';
            $result = TransactionController::rejectPayment($paymentId, $motivo);
            echo json_encode($result);
            break;
            
            case 'payment_details':
                $paymentId = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['payment_id']) ? intval($_POST['payment_id']) : 0);
                $result = TransactionController::getPaymentDetails($paymentId);
                echo json_encode($result);
                break;
            
        case 'payment_history':
            $storeId = isset($_POST['loja_id']) ? intval($_POST['loja_id']) : 0;
            $filters = $_POST['filters'] ?? [];
            $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $result = TransactionController::getPaymentHistory($storeId, $filters, $page);
            echo json_encode($result);
            break;
            
        default:
            // Acesso inválido ao controlador
            if (AuthController::isAdmin()) {
                header('Location: ' . ADMIN_DASHBOARD_URL);
            } elseif (AuthController::isStore()) {
                header('Location: ' . STORE_DASHBOARD_URL);
            } else {
                header('Location: ' . CLIENT_DASHBOARD_URL);
            }
            exit;
    }
}
?>