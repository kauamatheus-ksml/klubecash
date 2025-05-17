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
            
            if ($data['valor_total'] < MIN_TRANSACTION_VALUE) {
                return ['status' => false, 'message' => 'Valor mínimo para transação é R$ ' . number_format(MIN_TRANSACTION_VALUE, 2, ',', '.')];
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
            
            // Calcular valores de cashback
            $porcentagemTotal = isset($config['porcentagem_total']) ? $config['porcentagem_total'] : 10.00;
            $porcentagemCliente = isset($config['porcentagem_cliente']) ? $config['porcentagem_cliente'] : 5.00;
            $porcentagemAdmin = isset($config['porcentagem_admin']) ? $config['porcentagem_admin'] : 5.00;
            
            // Verificar se a loja tem porcentagem específica
            if (isset($store['porcentagem_cashback']) && $store['porcentagem_cashback'] > 0) {
                $porcentagemTotal = $store['porcentagem_cashback'];
                // Ajustar proporcionalmente
                $fator = $porcentagemTotal / 10.00;
                $porcentagemCliente = 5.00 * $fator;
                $porcentagemAdmin = 5.00 * $fator;
            }
            
            // Calcular valores
            $valorCashbackTotal = ($data['valor_total'] * $porcentagemTotal) / 100;
            $valorCashbackCliente = ($data['valor_total'] * $porcentagemCliente) / 100;
            $valorCashbackAdmin = ($data['valor_total'] * $porcentagemAdmin) / 100;
            
            // Iniciar transação
            $db->beginTransaction();
            
            // Definir o status da transação (pendente por padrão)
            $transactionStatus = isset($data['status']) ? $data['status'] : TRANSACTION_PENDING;
            
            // Registrar transação principal
            $stmt = $db->prepare("
                INSERT INTO transacoes_cashback (
                    usuario_id, loja_id, valor_total, valor_cashback,
                    valor_cliente, valor_admin, codigo_transacao, 
                    data_transacao, status, descricao
                ) VALUES (
                    :usuario_id, :loja_id, :valor_total, :valor_cashback,
                    :valor_cliente, :valor_admin, :codigo_transacao, 
                    :data_transacao, :status, :descricao
                )
            ");
            
            $stmt->bindParam(':usuario_id', $data['usuario_id']);
            $stmt->bindParam(':loja_id', $data['loja_id']);
            $stmt->bindParam(':valor_total', $data['valor_total']);
            $stmt->bindParam(':valor_cashback', $valorCashbackTotal);
            $stmt->bindParam(':valor_cliente', $valorCashbackCliente);
            $stmt->bindParam(':valor_admin', $valorCashbackAdmin);
            $stmt->bindParam(':codigo_transacao', $data['codigo_transacao']);
            
            $dataTransacao = isset($data['data_transacao']) ? $data['data_transacao'] : date('Y-m-d H:i:s');
            $stmt->bindParam(':data_transacao', $dataTransacao);
            $stmt->bindParam(':status', $transactionStatus);
            
            $descricao = isset($data['descricao']) ? $data['descricao'] : 'Compra na ' . $store['nome_fantasia'];
            $stmt->bindParam(':descricao', $descricao);
            
            $stmt->execute();
            $transactionId = $db->lastInsertId();
            
            // Registrar comissão para o administrador
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
            $adminId = 1; // Administrador padrão, pode ser ajustado
            
            $comissionAdminStmt->bindParam(':tipo_usuario', $tipoAdmin);
            $comissionAdminStmt->bindParam(':usuario_id', $adminId);
            $comissionAdminStmt->bindParam(':loja_id', $data['loja_id']);
            $comissionAdminStmt->bindParam(':transacao_id', $transactionId);
            $comissionAdminStmt->bindParam(':valor_total', $data['valor_total']);
            $comissionAdminStmt->bindParam(':valor_comissao', $valorCashbackAdmin);
            $comissionAdminStmt->bindParam(':data_transacao', $dataTransacao);
            $comissionAdminStmt->bindParam(':status', $transactionStatus);
            
            $comissionAdminStmt->execute();
            
            // Criar notificação para o cliente
            self::createNotification(
                $data['usuario_id'],
                'Nova transação registrada',
                'Você tem um novo cashback de R$ ' . number_format($valorCashbackCliente, 2, ',', '.') . ' pendente da loja ' . $store['nome_fantasia'],
                'info'
            );
            
            // Enviar email para o cliente
            if (!empty($user['email'])) {
                $subject = 'Novo Cashback Pendente - Klube Cash';
                $message = "
                    <h3>Olá, {$user['nome']}!</h3>
                    <p>Uma nova transação foi registrada em sua conta no Klube Cash.</p>
                    <p><strong>Loja:</strong> {$store['nome_fantasia']}</p>
                    <p><strong>Valor da compra:</strong> R$ " . number_format($data['valor_total'], 2, ',', '.') . "</p>
                    <p><strong>Cashback (pendente):</strong> R$ " . number_format($valorCashbackCliente, 2, ',', '.') . "</p>
                    <p><strong>Data:</strong> " . date('d/m/Y H:i', strtotime($dataTransacao)) . "</p>
                    <p>O cashback será disponibilizado assim que a loja confirmar o pagamento da comissão.</p>
                    <p>Atenciosamente,<br>Equipe Klube Cash</p>
                ";
                
                Email::send($user['email'], $subject, $message, $user['nome']);
            }
            
            // Confirmar transação
            $db->commit();
            
            return [
                'status' => true, 
                'message' => 'Transação registrada com sucesso!',
                'data' => [
                    'transaction_id' => $transactionId,
                    'valor_cashback' => $valorCashbackCliente,
                    'valor_comissao' => $valorCashbackTotal
                ]
            ];
            
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
     * Registra pagamento de comissões
     * 
     * @param array $data Dados do pagamento
     * @return array Resultado da operação
     */
    public static function registerPayment($data) {
        try {
            // Log para depuração
            error_log('Iniciando processamento de pagamento: ' . print_r($data, true));
            
            // Validar dados obrigatórios
            $requiredFields = ['loja_id', 'transacoes', 'valor_total', 'metodo_pagamento'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    error_log('Campo obrigatório ausente: ' . $field);
                    return ['status' => false, 'message' => 'Dados do pagamento incompletos. Campo faltante: ' . $field];
                }
            }
            
            // Verificar se o usuário está autenticado e é loja ou admin
            if (!AuthController::isAuthenticated()) {
                return ['status' => false, 'message' => 'Usuário não autenticado.'];
            }
            
            if (!AuthController::isStore() && !AuthController::isAdmin()) {
                return ['status' => false, 'message' => 'Apenas lojas e administradores podem registrar pagamentos.'];
            }
            
            // Verificar se as transações pertencem à loja
            $transactionIds = is_array($data['transacoes']) ? $data['transacoes'] : explode(',', $data['transacoes']);
            if (empty($transactionIds)) {
                return ['status' => false, 'message' => 'Lista de transações inválida.'];
            }
            
            $db = Database::getConnection();
            
            // Verificar se a loja existe
            $storeStmt = $db->prepare("SELECT id, nome_fantasia FROM lojas WHERE id = :loja_id");
            $storeStmt->bindParam(':loja_id', $data['loja_id']);
            $storeStmt->execute();
            $store = $storeStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$store) {
                return ['status' => false, 'message' => 'Loja não encontrada.'];
            }
            
            // Iniciar transação
            $db->beginTransaction();
            error_log('Transação iniciada');
            
            try {
                // Verificar e validar as transações
                $placeholders = implode(',', array_fill(0, count($transactionIds), '?'));
                $transStmt = $db->prepare("
                    SELECT t.id, t.valor_total, t.valor_cashback, t.status, u.nome as cliente_nome
                    FROM transacoes_cashback t
                    JOIN usuarios u ON t.usuario_id = u.id
                    WHERE t.id IN ($placeholders) AND t.loja_id = ? AND t.status = ?
                ");
                
                $bindParams = array_merge($transactionIds, [$data['loja_id'], 'pendente']);
                for ($i = 0; $i < count($bindParams); $i++) {
                    $transStmt->bindValue($i + 1, $bindParams[$i]);
                }
                
                $transStmt->execute();
                $transactions = $transStmt->fetchAll(PDO::FETCH_ASSOC);
                error_log('Transações encontradas: ' . count($transactions));
                
                // Verificar se todas as transações foram encontradas
                if (count($transactions) != count($transactionIds)) {
                    $db->rollBack();
                    return ['status' => false, 'message' => 'Algumas transações não foram encontradas ou não estão pendentes.'];
                }
                
                // Calcular valor total das comissões
                $totalComissoes = 0;
                foreach ($transactions as $transaction) {
                    $totalComissoes += floatval($transaction['valor_cashback']);
                }
                error_log('Total comissões calculado: ' . $totalComissoes);
                
                // Validar valor do pagamento (com tolerância)
                if (abs($totalComissoes - floatval($data['valor_total'])) > 0.01) {
                    $db->rollBack();
                    return [
                        'status' => false, 
                        'message' => 'O valor do pagamento (R$ ' . number_format(floatval($data['valor_total']), 2, ',', '.') . 
                                     ') não corresponde ao total das comissões (R$ ' . number_format($totalComissoes, 2, ',', '.') . ').'
                    ];
                }
                
                // Registrar o pagamento
                $paymentStmt = $db->prepare("
                    INSERT INTO pagamentos_comissao (
                        loja_id, valor_total, metodo_pagamento, 
                        numero_referencia, comprovante, observacao, 
                        data_registro, status
                    ) VALUES (
                        :loja_id, :valor_total, :metodo_pagamento,
                        :numero_referencia, :comprovante, :observacao,
                        NOW(), :status
                    )
                ");
                
                $paymentStmt->bindParam(':loja_id', $data['loja_id']);
                $paymentStmt->bindParam(':valor_total', $data['valor_total']);
                $paymentStmt->bindParam(':metodo_pagamento', $data['metodo_pagamento']);
                
                $numeroReferencia = isset($data['numero_referencia']) ? $data['numero_referencia'] : '';
                $paymentStmt->bindParam(':numero_referencia', $numeroReferencia);
                
                $comprovante = isset($data['comprovante']) ? $data['comprovante'] : '';
                $paymentStmt->bindParam(':comprovante', $comprovante);
                
                $observacao = isset($data['observacao']) ? $data['observacao'] : '';
                $paymentStmt->bindParam(':observacao', $observacao);
                
                $status = 'pendente'; // Status inicial do pagamento
                $paymentStmt->bindParam(':status', $status);
                
                $paymentResult = $paymentStmt->execute();
                error_log('Pagamento registrado: ' . ($paymentResult ? 'sim' : 'não'));
                
                if (!$paymentResult) {
                    $db->rollBack();
                    return ['status' => false, 'message' => 'Erro ao registrar pagamento no banco de dados.'];
                }
                
                $paymentId = $db->lastInsertId();
                error_log('ID do pagamento: ' . $paymentId);
                
                // Registrar as transações associadas ao pagamento
                $transactionPaymentStmt = $db->prepare("
                    INSERT INTO pagamentos_transacoes (
                        pagamento_id, transacao_id
                    ) VALUES (
                        :pagamento_id, :transacao_id
                    )
                ");
                
                foreach ($transactionIds as $transactionId) {
                    $transactionPaymentStmt->bindParam(':pagamento_id', $paymentId);
                    $transactionPaymentStmt->bindParam(':transacao_id', $transactionId);
                    $transactionResult = $transactionPaymentStmt->execute();
                    
                    if (!$transactionResult) {
                        $db->rollBack();
                        return ['status' => false, 'message' => 'Erro ao associar transação ao pagamento.'];
                    }
                }
                error_log('Transações associadas ao pagamento');
                
                // Atualizar status das transações para 'pagamento_pendente'
                $updateTransStmt = $db->prepare("
                    UPDATE transacoes_cashback 
                    SET status = :novo_status 
                    WHERE id IN ($placeholders)
                ");
                
                $novoStatus = 'pagamento_pendente';
                $updateTransStmt->bindParam(':novo_status', $novoStatus);
                
                for ($i = 0; $i < count($transactionIds); $i++) {
                    $updateTransStmt->bindValue($i + 1, $transactionIds[$i]);
                }
                
                $updateResult = $updateTransStmt->execute();
                error_log('Status das transações atualizado: ' . ($updateResult ? 'sim' : 'não'));
                
                if (!$updateResult) {
                    $db->rollBack();
                    return ['status' => false, 'message' => 'Erro ao atualizar status das transações.'];
                }
                
                // Confirmar transação
                $db->commit();
                error_log('Transação confirmada com sucesso');
                
                return [
                    'status' => true,
                    'message' => 'Pagamento registrado com sucesso! Aguardando aprovação do administrador.',
                    'data' => [
                        'payment_id' => $paymentId,
                        'total_transacoes' => count($transactionIds),
                        'valor_total' => $data['valor_total']
                    ]
                ];
                
            } catch (Exception $e) {
                $db->rollBack();
                error_log('Exceção no processamento do pagamento: ' . $e->getMessage());
                return ['status' => false, 'message' => 'Erro ao processar pagamento: ' . $e->getMessage()];
            }
        } catch (Exception $e) {
            error_log('Exceção geral no registerPayment: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao registrar pagamento. Tente novamente.'];
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
            $status = 'aprovado';
            $updatePaymentStmt->bindParam(':status', $status);
            $updatePaymentStmt->bindParam(':observacao', $observacao);
            $updatePaymentStmt->bindParam(':payment_id', $paymentId);
            $updatePaymentStmt->execute();
            
            // Obter transações associadas ao pagamento
            $transStmt = $db->prepare("
                SELECT t.id, t.usuario_id
                FROM pagamentos_transacoes pt
                JOIN transacoes_cashback t ON pt.transacao_id = t.id
                WHERE pt.pagamento_id = :payment_id
            ");
            $transStmt->bindParam(':payment_id', $paymentId);
            $transStmt->execute();
            $transactions = $transStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Atualizar status das transações para aprovado
            if (count($transactions) > 0) {
                $transactionIds = array_column($transactions, 'id');
                $placeholders = implode(',', array_fill(0, count($transactionIds), '?'));
                
                $updateTransStmt = $db->prepare("
                    UPDATE transacoes_cashback 
                    SET status = :novo_status 
                    WHERE id IN ($placeholders)
                ");
                
                $novoStatus = TRANSACTION_APPROVED;
                $updateTransStmt->bindParam(':novo_status', $novoStatus);
                
                for ($i = 0; $i < count($transactionIds); $i++) {
                    $updateTransStmt->bindValue($i + 1, $transactionIds[$i]);
                }
                
                $updateTransStmt->execute();
                
                // Atualizar comissões associadas
                $updateComissionStmt = $db->prepare("
                    UPDATE transacoes_comissao 
                    SET status = :novo_status 
                    WHERE transacao_id IN ($placeholders)
                ");
                
                $updateComissionStmt->bindParam(':novo_status', $novoStatus);
                
                for ($i = 0; $i < count($transactionIds); $i++) {
                    $updateComissionStmt->bindValue($i + 1, $transactionIds[$i]);
                }
                
                $updateComissionStmt->execute();
                
                // Notificar clientes
                $clienteNotificados = [];
                foreach ($transactions as $transaction) {
                    if (!in_array($transaction['usuario_id'], $clienteNotificados)) {
                        // Obter detalhes do cliente
                        $clientStmt = $db->prepare("
                            SELECT id, nome, email FROM usuarios WHERE id = :usuario_id
                        ");
                        $clientStmt->bindParam(':usuario_id', $transaction['usuario_id']);
                        $clientStmt->execute();
                        $client = $clientStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($client) {
                            // Contar transações e calcular total de cashback para este cliente
                            $clientTransStmt = $db->prepare("
                                SELECT COUNT(*) as total_trans, SUM(valor_cliente) as total_cashback
                                FROM transacoes_cashback
                                WHERE id IN ($placeholders) AND usuario_id = :usuario_id
                            ");
                            
                            $clientTransStmt->bindParam(':usuario_id', $client['id']);
                            
                            for ($i = 0; $i < count($transactionIds); $i++) {
                                $clientTransStmt->bindValue($i + 1, $transactionIds[$i]);
                            }
                            
                            $clientTransStmt->execute();
                            $clientTrans = $clientTransStmt->fetch(PDO::FETCH_ASSOC);
                            
                            // Criar notificação
                            if ($clientTrans['total_trans'] > 0) {
                                self::createNotification(
                                    $client['id'],
                                    'Cashback disponível!',
                                    'Seu cashback de R$ ' . number_format($clientTrans['total_cashback'], 2, ',', '.') . 
                                    ' da loja ' . $payment['loja_nome'] . ' está disponível.',
                                    'success'
                                );
                                
                                // Enviar email
                                if (!empty($client['email'])) {
                                    $subject = 'Cashback Disponível - Klube Cash';
                                    $message = "
                                        <h3>Olá, {$client['nome']}!</h3>
                                        <p>Temos uma ótima notícia! Seu cashback foi aprovado e já está disponível em sua conta.</p>
                                        <p><strong>Valor total:</strong> R$ " . number_format($clientTrans['total_cashback'], 2, ',', '.') . "</p>
                                        <p><strong>Loja:</strong> {$payment['loja_nome']}</p>
                                        <p><strong>Transações:</strong> {$clientTrans['total_trans']}</p>
                                        <p>Acesse sua conta para visualizar os detalhes.</p>
                                        <p>Atenciosamente,<br>Equipe Klube Cash</p>
                                    ";
                                    
                                    Email::send($client['email'], $subject, $message, $client['nome']);
                                }
                                
                                $clienteNotificados[] = $client['id'];
                            }
                        }
                    }
                }
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
                        'Pagamento aprovado',
                        'Seu pagamento de comissão no valor de R$ ' . number_format($payment['valor_total'], 2, ',', '.') . 
                        ' foi aprovado.',
                        'success'
                    );
                }
                
                // Email
                if (!empty($storeNotify['email'])) {
                    $subject = 'Pagamento Aprovado - Klube Cash';
                    $message = "
                        <h3>Olá, {$payment['loja_nome']}!</h3>
                        <p>Seu pagamento de comissão foi aprovado com sucesso.</p>
                        <p><strong>Valor:</strong> R$ " . number_format($payment['valor_total'], 2, ',', '.') . "</p>
                        <p><strong>Método:</strong> {$payment['metodo_pagamento']}</p>
                        <p><strong>Data de aprovação:</strong> " . date('d/m/Y H:i:s') . "</p>";
                    
                    if (!empty($observacao)) {
                        $message .= "<p><strong>Observação:</strong> " . nl2br(htmlspecialchars($observacao)) . "</p>";
                    }
                    
                    $message .= "<p>O cashback já foi liberado para seus clientes.</p>
                        <p>Atenciosamente,<br>Equipe Klube Cash</p>
                    ";
                    
                    Email::send($storeNotify['email'], $subject, $message, $payment['loja_nome']);
                }
            }
            
            // Confirmar transação
            $db->commit();
            
            return [
                'status' => true,
                'message' => 'Pagamento aprovado com sucesso! Cashback liberado para os clientes.',
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
            
            error_log('Erro ao aprovar pagamento: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao aprovar pagamento. Tente novamente.'];
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
            // Verificar se o usuário está autenticado e é loja ou admin
            if (!AuthController::isAuthenticated()) {
                return ['status' => false, 'message' => 'Usuário não autenticado.'];
            }
            
            // Código corrigido
            if (AuthController::isStore()) {
                $db = Database::getConnection();
                $storeQuery = $db->prepare("SELECT id FROM lojas WHERE usuario_id = :usuario_id AND id = :store_id");
                $userId = AuthController::getCurrentUserId();
                $storeQuery->bindParam(':usuario_id', $userId);
                $storeQuery->bindParam(':store_id', $storeId);
                $storeQuery->execute();
                
                if ($storeQuery->rowCount() == 0) {
                    return ['status' => false, 'message' => 'Acesso não autorizado.'];
                }
            }
            
            $db = Database::getConnection();
            
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
                ':status' => 'pendente'  // Ou TRANSACTION_PENDING se for uma constante
            ];
            
            // Verifica se a consulta filtra exclusivamente pelo status 'pendente'
            // Adicione esta verificação em algum lugar da consulta SQL:
            $query .= " AND t.status = :status";
            
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
            $perPage = ITEMS_PER_PAGE;
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
            
            // Obter dados do pagamento
            $paymentStmt = $db->prepare("
                SELECT p.*, l.nome_fantasia as loja_nome
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
            
            // Verificar permissões - apenas admin ou a própria loja podem ver
            if (!AuthController::isAdmin() && AuthController::isStore() && AuthController::getCurrentUserId() != $payment['loja_id']) {
                return ['status' => false, 'message' => 'Acesso não autorizado.'];
            }
            
            // Obter transações associadas
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
            
            // Calcular totais
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
            return ['status' => false, 'message' => 'Erro ao obter detalhes do pagamento. Tente novamente.'];
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
            
            // Apenas a loja dona dos pagamentos ou admin podem acessar
            if (AuthController::isStore() && AuthController::getCurrentUserId() != $storeId) {
                return ['status' => false, 'message' => 'Acesso não autorizado.'];
            }
            
            $db = Database::getConnection();
            
            // Verificar se a loja existe
            $storeStmt = $db->prepare("SELECT id FROM lojas WHERE id = :loja_id");
            $storeStmt->bindParam(':loja_id', $storeId);
            $storeStmt->execute();
            
            if ($storeStmt->rowCount() == 0) {
                return ['status' => false, 'message' => 'Loja não encontrada.'];
            }
            
            // Construir consulta
            $query = "
                SELECT p.*,
                       (SELECT COUNT(*) FROM pagamentos_transacoes WHERE pagamento_id = p.id) as total_transacoes
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
            $countQuery = str_replace("p.*, (SELECT COUNT(*) FROM pagamentos_transacoes WHERE pagamento_id = p.id) as total_transacoes", "COUNT(*) as total", $query);
            $countStmt = $db->prepare($countQuery);
            
            foreach ($params as $param => $value) {
                $countStmt->bindValue($param, $value);
            }
            
            $countStmt->execute();
            $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Paginação
            $perPage = ITEMS_PER_PAGE;
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
            $paymentId = isset($_POST['payment_id']) ? intval($_POST['payment_id']) : 0;
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