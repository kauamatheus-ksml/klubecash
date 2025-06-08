<?php
// api/payments.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'loja') {
    echo json_encode(['status' => false, 'message' => 'Sessão expirada. Faça login novamente.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($method === 'POST' && $action === 'criar_pagamento') {
    try {
        $db = Database::getConnection();
        $userId = $_SESSION['user_id'];
        
        // Buscar loja
        $storeStmt = $db->prepare("SELECT id FROM lojas WHERE usuario_id = ?");
        $storeStmt->execute([$userId]);
        $store = $storeStmt->fetch();
        
        if (!$store) {
            throw new Exception('Loja não encontrada');
        }
        
        $transacoes = $_POST['transacoes'] ?? [];
        if (empty($transacoes)) {
            throw new Exception('Nenhuma transação selecionada');
        }
        
        // Calcular valor total das comissões (corrigido)
        $placeholders = str_repeat('?,', count($transacoes) - 1) . '?';
        $stmt = $db->prepare("
            SELECT SUM(valor_total * 0.10) as total_comissao
            FROM transacoes_cashback 
            WHERE id IN ($placeholders) AND loja_id = ? AND status = 'pendente'
        ");
        $params = array_merge($transacoes, [$store['id']]);
        $stmt->execute($params);
        $totalComissao = $stmt->fetchColumn() ?: 0;
        
        // Criar pagamento
        $paymentStmt = $db->prepare("
            INSERT INTO pagamentos_comissao (loja_id, valor_total, metodo_pagamento, status, data_criacao) 
            VALUES (?, ?, ?, 'pendente', NOW())
        ");
        $paymentStmt->execute([$store['id'], $totalComissao, $_POST['metodo_pagamento'] ?? 'pix_openpix']);
        
        $paymentId = $db->lastInsertId();
        
        echo json_encode([
            'status' => true,
            'payment_id' => $paymentId,
            'message' => 'Pagamento criado com sucesso'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => false, 'message' => 'Ação inválida']);
}
?>