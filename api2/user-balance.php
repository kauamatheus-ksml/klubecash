<?php
// public_html/api2/user-balance.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';

$authenticatedUserId = null;
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = null;
if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
}
$simulatedAuthToken = 'seu_super_token_secreto_aqui_para_simulacao'; 
$simulatedUserId = 9; 

if ($token === $simulatedAuthToken) {
    $authenticatedUserId = $simulatedUserId;
}

if ($authenticatedUserId === null) {
    http_response_code(401);
    echo json_encode(['message' => 'Não autorizado. Token ausente ou inválido.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $db = Database::getConnection();

        // Consulta para somar saldo disponível, total creditado e total usado de cashback_saldos
        // E somar o valor das transações pendentes do usuário em transacoes_cashback
        $stmt = $db->prepare(
            "SELECT
                 COALESCE(SUM(cs.saldo_disponivel), 0) AS saldo_disponivel,
                 COALESCE(SUM(cs.total_creditado), 0) AS total_creditado,
                 COALESCE(SUM(cs.total_usado), 0) AS total_usado,
                 COALESCE(SUM(CASE WHEN tc.status = 'pendente' AND tc.usuario_id = ? THEN tc.valor_cliente ELSE 0 END), 0) AS saldo_pendente
               FROM cashback_saldos cs
               LEFT JOIN transacoes_cashback tc ON cs.usuario_id = tc.usuario_id -- Garante que apenas transações do usuário correto sejam contadas
               WHERE cs.usuario_id = ?"
        );
        $stmt->execute([$authenticatedUserId, $authenticatedUserId]); // Passar $authenticatedUserId duas vezes

        $balanceData = $stmt->fetch(PDO::FETCH_ASSOC);

        // Garante que os valores são floats, pois podem vir como strings do DB
        $formattedBalance = [
            'saldo_disponivel' => (float)$balanceData['saldo_disponivel'],
            'total_creditado' => (float)$balanceData['total_creditado'],
            'total_usado' => (float)$balanceData['total_usado'],
            'saldo_pendente' => (float)$balanceData['saldo_pendente'],
        ];

        echo json_encode(['balance' => $formattedBalance]);

    } catch (PDOException $e) {
        error_log('Erro em user-balance.php: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['message' => 'Erro interno do servidor ao buscar saldo.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Método não permitido.']);
}
?>