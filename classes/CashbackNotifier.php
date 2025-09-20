<?php
// /classes/CashbackNotifier.php

// Carregar dependências essenciais uma única vez
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/WhatsAppBot.php';
require_once __DIR__ . '/../utils/CashbackRetrySystem.php';

class CashbackNotifier {

    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Ponto de entrada principal para enviar uma notificação de cashback pendente.
     *
     * @param int $transactionId O ID da transação.
     * @return array Resultado da operação.
     */
    public function notificarCashbackPendente($transactionId) {
        // Passo 1: Obter os dados necessários da transação e do usuário
        $dados = $this->getNotificationData($transactionId);

        if (!$dados['success']) {
            error_log("[CashbackNotifier] Falha ao obter dados para transação ID: {$transactionId}. Motivo: {$dados['message']}");
            // Agendar nova tentativa se a transação for válida, mas os dados do usuário/loja não foram encontrados
            if ($dados['reason'] === 'data_not_found') {
                CashbackRetrySystem::scheduleRetry($transactionId, $dados['message']);
            }
            return ['success' => false, 'message' => $dados['message']];
        }

        // Passo 2: Validar e formatar o número de telefone
        $telefoneValido = $this->validateAndFormatPhone($dados['data']['cliente_telefone']);

        if (!$telefoneValido) {
            $errorMsg = "Telefone inválido ou ausente para o usuário da transação ID: {$transactionId}. Número original: '{$dados['data']['cliente_telefone']}'";
            error_log("[CashbackNotifier] " . $errorMsg);
            // Não adianta tentar novamente se o número de telefone no banco de dados está errado.
            // A falha é registrada, mas não reagendada.
            CashbackRetrySystem::logFinalFailure($transactionId, $errorMsg);
            return ['success' => false, 'message' => $errorMsg];
        }
        
        $dados['data']['cliente_telefone'] = $telefoneValido;

        // Passo 3: Enviar a notificação através do WhatsAppBot
        return $this->sendNotification($dados['data']);
    }

    /**
     * Busca os dados consolidados para a notificação no banco de dados.
     *
     * @param int $transactionId
     * @return array
     */
    private function getNotificationData($transactionId) {
        $sql = "
            SELECT
                t.id as transacao_id,
                t.valor_cliente as valor_cashback,
                COALESCE(tsu.valor_usado, 0) as valor_saldo_usado,
                l.nome_fantasia as nome_loja,
                u.telefone as cliente_telefone
            FROM
                transacoes_cashback t
            JOIN
                usuarios u ON t.usuario_id = u.id
            JOIN
                lojas l ON t.loja_id = l.id
            LEFT JOIN
                transacoes_saldo_usado tsu ON t.id = tsu.transacao_id
            WHERE
                t.id = :transaction_id
                AND t.status = 'pendente' -- Apenas notificar transações pendentes
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':transaction_id', $transactionId, PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) {
                // Verifica se a transação existe, mesmo que não esteja pendente
                $checkStmt = $this->db->prepare("SELECT id FROM transacoes_cashback WHERE id = :id");
                $checkStmt->bindParam(':id', $transactionId, PDO::PARAM_INT);
                $checkStmt->execute();
                if ($checkStmt->fetch()) {
                    return ['success' => false, 'message' => "Transação {$transactionId} não está pendente ou já foi processada.", 'reason' => 'status_invalid'];
                }
                return ['success' => false, 'message' => "Transação com ID {$transactionId} não encontrada.", 'reason' => 'transaction_not_found'];
            }

            return ['success' => true, 'data' => $data];

        } catch (PDOException $e) {
            error_log("Erro de banco de dados em getNotificationData: " . $e->getMessage());
            return ['success' => false, 'message' => "Erro de banco de dados.", 'reason' => 'db_error'];
        }
    }

    /**
     * Valida e limpa um número de telefone, retornando um formato padronizado ou false.
     *
     * @param string|null $phone
     * @return string|false
     */
    private function validateAndFormatPhone($phone) {
        if (empty($phone)) {
            return false;
        }

        // Remove todos os caracteres que não são dígitos
        $cleanedPhone = preg_replace('/\D/', '', $phone);

        // Remove o zero à esquerda se for um número de celular brasileiro comum
        if (strlen($cleanedPhone) == 11 && substr($cleanedPhone, 2, 1) == '0') {
            $cleanedPhone = substr($cleanedPhone, 0, 2) . substr($cleanedPhone, 3);
        }

        // Garante que o número tenha entre 10 e 13 dígitos (considerando DDI)
        if (strlen($cleanedPhone) < 10 || strlen($cleanedPhone) > 13) {
            return false;
        }
        
        // Adiciona o DDI do Brasil (55) se não estiver presente
        if (strlen($cleanedPhone) <= 11 && substr($cleanedPhone, 0, 2) !== '55') {
            $cleanedPhone = '55' . $cleanedPhone;
        }

        return $cleanedPhone;
    }

    /**
     * Envia os dados para o WhatsAppBot e trata a resposta.
     *
     * @param array $data
     * @return array
     */
    private function sendNotification($data) {
        $transactionData = [
            'valor_cashback' => $data['valor_cashback'],
            'valor_usado' => $data['valor_saldo_usado'],
            'nome_loja' => $data['nome_loja']
        ];
        
        $result = WhatsAppBot::sendNewTransactionNotification(
            $data['cliente_telefone'],
            $transactionData
        );

        if (!$result['success']) {
            $errorMsg = "Falha no envio WhatsApp: " . ($result['error'] ?? 'Erro desconhecido');
            error_log("[CashbackNotifier] " . $errorMsg);
            // Agendar nova tentativa
            CashbackRetrySystem::scheduleRetry($data['transacao_id'], $errorMsg);
        } else {
            // Marca a tentativa como sucesso se houver um sistema de retentativas
             CashbackRetrySystem::markAsSuccess($data['transacao_id']);
        }

        return $result;
    }
}