<?php
// api/store-saldos.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../controllers/AuthController.php';
require_once '../models/CashbackBalance.php';

session_start();

// Verificar autenticação
if (!AuthController::isAuthenticated()) {
    echo json_encode(['status' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

// Verificar se é loja
if (!AuthController::isStore()) {
    echo json_encode(['status' => false, 'message' => 'Acesso restrito a lojas']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

$user = AuthController::getUser();
$storeId = $user['loja_id'];

try {
    $db = Database::getConnection();
    
    switch ($action) {
        case 'get_saldos':
            echo json_encode(getSaldos($db, $storeId));
            break;
            
        case 'get_historico':
            echo json_encode(getHistorico($db, $storeId));
            break;
            
        default:
            echo json_encode(['status' => false, 'message' => 'Ação inválida']);
            break;
    }
    
} catch (Exception $e) {
    error_log('Erro na API store-saldos: ' . $e->getMessage());
    echo json_encode(['status' => false, 'message' => 'Erro interno do servidor']);
}

function getSaldos($db, $storeId) {
    // Saldo disponível com clientes (total de cashback que os clientes têm para usar nesta loja)
    $stmt = $db->prepare("
        SELECT 
            SUM(cb.saldo) as saldo_total_clientes,
            COUNT(DISTINCT cb.usuario_id) as total_clientes,
            AVG(cb.saldo) as saldo_medio
        FROM cashback_balance cb
        WHERE cb.loja_id = ? AND cb.saldo > 0
    ");
    $stmt->execute([$storeId]);
    $saldoClientes = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Saldo de devolução da plataforma (valores que a loja tem direito a receber)
    $stmt = $db->prepare("
        SELECT 
            SUM(valor_loja) as saldo_devolucao_total
        FROM transacoes_cashback 
        WHERE loja_id = ? 
        AND status = 'aprovado' 
        AND valor_loja > 0
        AND cashback_utilizado = 1
    ");
    $stmt->execute([$storeId]);
    $saldoDevolucao = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Devolução deste mês
    $stmt = $db->prepare("
        SELECT 
            SUM(valor_loja) as devolucao_mes
        FROM transacoes_cashback 
        WHERE loja_id = ? 
        AND status = 'aprovado' 
        AND valor_loja > 0
        AND cashback_utilizado = 1
        AND YEAR(data_transacao) = YEAR(CURDATE())
        AND MONTH(data_transacao) = MONTH(CURDATE())
    ");
    $stmt->execute([$storeId]);
    $devolucaoMes = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Total já devolvido
    $stmt = $db->prepare("
        SELECT 
            SUM(valor_loja) as total_devolvido
        FROM transacoes_cashback 
        WHERE loja_id = ? 
        AND status = 'aprovado' 
        AND valor_loja > 0
        AND cashback_utilizado = 1
    ");
    $stmt->execute([$storeId]);
    $totalDevolvido = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'status' => true,
        'data' => [
            'saldo_clientes' => floatval($saldoClientes['saldo_total_clientes'] ?? 0),
            'total_clientes' => intval($saldoClientes['total_clientes'] ?? 0),
            'saldo_medio' => floatval($saldoClientes['saldo_medio'] ?? 0),
            'saldo_devolucao' => floatval($saldoDevolucao['saldo_devolucao_total'] ?? 0),
            'devolucao_mes' => floatval($devolucaoMes['devolucao_mes'] ?? 0),
            'total_devolvido' => floatval($totalDevolvido['total_devolvido'] ?? 0)
        ]
    ];
}

function getHistorico($db, $storeId) {
    $stmt = $db->prepare("
        SELECT 
            t.data_transacao as data,
            u.nome as cliente_nome,
            'cashback_gerado' as tipo,
            t.valor_cliente as valor,
            t.status
        FROM transacoes_cashback t
        JOIN usuarios u ON t.usuario_id = u.id
        WHERE t.loja_id = ?
        
        UNION ALL
        
        SELECT 
            t.data_utilizacao as data,
            u.nome as cliente_nome,
            'cashback_utilizado' as tipo,
            t.valor_cliente as valor,
            'utilizado' as status
        FROM transacoes_cashback t
        JOIN usuarios u ON t.usuario_id = u.id
        WHERE t.loja_id = ? 
        AND t.cashback_utilizado = 1
        
        ORDER BY data DESC
        LIMIT 20
    ");
    $stmt->execute([$storeId, $storeId]);
    $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'status' => true,
        'data' => $historico
    ];
}
?>