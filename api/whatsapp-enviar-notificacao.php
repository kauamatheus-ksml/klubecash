<?php
/**
 * API para o Bot WhatsApp Enviar Notificações de Cashback
 *
 * NOVO PADRÃO: Em vez do sistema chamar o bot, o bot chama esta API
 * para buscar notificações pendentes e enviá-las.
 *
 * Funciona igual ao sistema de consulta de saldo!
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../classes/CashbackNotifier.php';

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método não permitido. Use POST.'
    ]);
    exit;
}

try {
    // Ler dados JSON da requisição
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Log da requisição
    error_log('WhatsApp Notificação API - Requisição: ' . $input);

    // Validar chave secreta
    if (!isset($data['secret']) || $data['secret'] !== WHATSAPP_BOT_SECRET) {
        error_log('WhatsApp Notificação API - Chave secreta inválida');
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Acesso não autorizado'
        ]);
        exit;
    }

    // Verificar se é requisição para notificação específica
    if (isset($data['transaction_id']) && isset($data['phone'])) {
        // Modo 1: Notificação específica
        $transactionId = $data['transaction_id'];
        $phone = $data['phone'];

        error_log("Processando notificação específica - Transação: {$transactionId}, Telefone: {$phone}");

        $notifier = new CashbackNotifier();

        // Buscar dados da transação
        $transactionData = $notifier->getTransactionData($transactionId);

        if (!$transactionData) {
            echo json_encode([
                'success' => false,
                'message' => 'Transação não encontrada'
            ]);
            exit;
        }

        // Gerar mensagem
        $clientProfile = $notifier->getClientProfile($transactionData['usuario_id']);
        $messageType = $notifier->determineMessageType($transactionData, $clientProfile);
        $message = $notifier->generateMessage($messageType, $transactionData, $clientProfile);

        // Retornar mensagem para o bot enviar
        echo json_encode([
            'success' => true,
            'message' => $message,
            'phone' => $phone,
            'transaction_id' => $transactionId,
            'message_type' => $messageType,
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        // Registrar log de sucesso
        if (!class_exists('WhatsAppLogger')) {
            require_once __DIR__ . '/../utils/WhatsAppLogger.php';
        }

        WhatsAppLogger::log(
            'notificacao_bot_call',
            $phone,
            $message,
            ['success' => true, 'messageId' => 'bot_generated'],
            ['transaction_id' => $transactionId, 'message_type' => $messageType]
        );

    } else {
        // Modo 2: Buscar notificações pendentes
        $db = Database::getConnection();

        // Buscar transações pendentes de notificação (últimas 24h)
        $stmt = $db->prepare("
            SELECT t.id, t.usuario_id, u.telefone, t.data_transacao
            FROM transacoes_cashback t
            JOIN usuarios u ON t.usuario_id = u.id
            WHERE t.status = 'pendente'
            AND t.data_transacao >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            AND NOT EXISTS (
                SELECT 1 FROM whatsapp_logs w
                WHERE w.additional_data LIKE CONCAT('%\"transaction_id\":', t.id, '%')
                AND w.success = 1
                AND w.created_at >= t.data_transacao
            )
            ORDER BY t.data_transacao DESC
            LIMIT 10
        ");

        $stmt->execute();
        $pendingTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'pending_notifications' => count($pendingTransactions),
            'transactions' => $pendingTransactions,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

} catch (Exception $e) {
    error_log('WhatsApp Notificação API - Erro: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno do servidor',
        'message' => 'Erro ao processar notificação',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>