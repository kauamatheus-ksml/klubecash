<?php
// debug-whatsapp-completo.php
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/classes/SaldoConsulta.php';

echo "<h1>🧪 Debug WhatsApp - Teste Completo Clientes Visitantes</h1>";

try {
    $db = Database::getConnection();
    
    // 1. LISTAR CLIENTES VISITANTES COM TRANSAÇÕES
    echo "<h2>👥 Clientes Visitantes com Transações:</h2>";
    
    $stmt = $db->query("
        SELECT DISTINCT 
            u.id, 
            u.nome, 
            u.telefone, 
            u.email,
            u.tipo_cliente,
            u.loja_criadora_id,
            COUNT(t.id) as total_transacoes
        FROM usuarios u
        INNER JOIN transacoes_cashback t ON u.id = t.usuario_id
        WHERE u.tipo_cliente = 'visitante'
        AND u.status = 'ativo'
        GROUP BY u.id
        ORDER BY total_transacoes DESC
        LIMIT 10
    ");
    
    $visitantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Total visitantes com transações:</strong> " . count($visitantes) . "</p>";
    
    if (empty($visitantes)) {
        echo "<p style='color: red;'>❌ NENHUM CLIENTE VISITANTE COM TRANSAÇÕES ENCONTRADO!</p>";
        
        // Buscar qualquer visitante
        $stmt2 = $db->query("
            SELECT id, nome, telefone, email, tipo_cliente 
            FROM usuarios 
            WHERE tipo_cliente = 'visitante' 
            AND status = 'ativo' 
            LIMIT 5
        ");
        $visitantesSemTrans = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Visitantes sem transações:</h3>";
        foreach ($visitantesSemTrans as $v) {
            echo "<p>ID: {$v['id']} - {$v['nome']} - {$v['telefone']}</p>";
        }
        
        exit;
    }
    
    // 2. TESTAR CADA VISITANTE
    foreach ($visitantes as $visitante) {
        echo "<div style='border: 2px solid #007bff; margin: 20px 0; padding: 15px; border-radius: 8px;'>";
        echo "<h3>🔍 Testando: {$visitante['nome']} (ID: {$visitante['id']})</h3>";
        echo "<p><strong>Telefone:</strong> {$visitante['telefone']}</p>";
        echo "<p><strong>Email:</strong> {$visitante['email']}</p>";
        echo "<p><strong>Total Transações:</strong> {$visitante['total_transacoes']}</p>";
        
        // BUSCAR SALDOS DIRETO NAS TRANSAÇÕES
        echo "<h4>📊 Saldos por Loja (Direto das Transações):</h4>";
        
        $saldosStmt = $db->prepare("
            SELECT 
                t.loja_id,
                l.nome_fantasia,
                l.porcentagem_cashback,
                SUM(CASE WHEN t.status = 'aprovado' THEN t.valor_cliente ELSE 0 END) as saldo_disponivel,
                SUM(CASE WHEN t.status IN ('pendente', 'pagamento_pendente') THEN t.valor_cliente ELSE 0 END) as saldo_pendente,
                COUNT(*) as total_transacoes_loja
            FROM transacoes_cashback t
            INNER JOIN lojas l ON t.loja_id = l.id
            WHERE t.usuario_id = :user_id
            GROUP BY t.loja_id, l.nome_fantasia, l.porcentagem_cashback
            ORDER BY saldo_disponivel DESC
        ");
        $saldosStmt->bindParam(':user_id', $visitante['id']);
        $saldosStmt->execute();
        
        $saldosLoja = $saldosStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr><th>Loja</th><th>Disponível</th><th>Pendente</th><th>Transações</th><th>Cashback %</th></tr>";
        
        $totalDisponivel = 0;
        $totalPendente = 0;
        
        foreach ($saldosLoja as $saldo) {
            $totalDisponivel += $saldo['saldo_disponivel'];
            $totalPendente += $saldo['saldo_pendente'];
            
            echo "<tr>";
            echo "<td><strong>{$saldo['nome_fantasia']}</strong></td>";
            echo "<td>R$ " . number_format($saldo['saldo_disponivel'], 2, ',', '.') . "</td>";
            echo "<td>R$ " . number_format($saldo['saldo_pendente'], 2, ',', '.') . "</td>";
            echo "<td>{$saldo['total_transacoes_loja']}</td>";
            echo "<td>{$saldo['porcentagem_cashback']}%</td>";
            echo "</tr>";
        }
        
        echo "<tr style='background: #f0f8ff; font-weight: bold;'>";
        echo "<td>TOTAL</td>";
        echo "<td>R$ " . number_format($totalDisponivel, 2, ',', '.') . "</td>";
        echo "<td>R$ " . number_format($totalPendente, 2, ',', '.') . "</td>";
        echo "<td>-</td>";
        echo "<td>-</td>";
        echo "</tr>";
        echo "</table>";
        
        // TESTAR WHATSAPP COM ESTE USUÁRIO
        echo "<h4>📱 Teste WhatsApp:</h4>";
        
        $telefoneTest = preg_replace('/[^0-9]/', '', $visitante['telefone']);
        
        echo "<p><strong>Telefone limpo:</strong> {$telefoneTest}</p>";
        
        // Simular consulta WhatsApp
        $saldoConsulta = new SaldoConsulta();
        $resultadoWhatsApp = $saldoConsulta->consultarSaldoPorTelefone($telefoneTest);
        
        echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h5>Resultado WhatsApp:</h5>";
        
        if ($resultadoWhatsApp['success'] && $resultadoWhatsApp['user_found']) {
            echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px;'>";
            echo "✅ <strong>SUCESSO!</strong><br>";
            echo "<strong>Total lojas retornadas:</strong> " . ($resultadoWhatsApp['total_lojas'] ?? 0) . "<br>";
            echo "<strong>Saldo total:</strong> R$ " . number_format($resultadoWhatsApp['saldo_total'] ?? 0, 2, ',', '.') . "<br>";
            echo "<hr>";
            echo "<strong>Mensagem gerada:</strong><br>";
            echo "<pre style='white-space: pre-wrap; font-size: 12px;'>" . htmlspecialchars($resultadoWhatsApp['message']) . "</pre>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
            echo "❌ <strong>ERRO!</strong><br>";
            echo "<strong>User found:</strong> " . ($resultadoWhatsApp['user_found'] ? 'SIM' : 'NÃO') . "<br>";
            echo "<strong>Mensagem:</strong> " . htmlspecialchars($resultadoWhatsApp['message']) . "<br>";
            echo "</div>";
        }
        
        echo "</div>";
        
        // VERIFICAR TABELA CASHBACK_SALDOS
        echo "<h4>💾 Verificação Tabela cashback_saldos:</h4>";
        
        $saldosTabela = $db->prepare("
            SELECT cs.*, l.nome_fantasia
            FROM cashback_saldos cs
            LEFT JOIN lojas l ON cs.loja_id = l.id
            WHERE cs.usuario_id = :user_id
        ");
        $saldosTabela->bindParam(':user_id', $visitante['id']);
        $saldosTabela->execute();
        
        $registrosSaldos = $saldosTabela->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($registrosSaldos)) {
            echo "<p style='color: orange;'>⚠️ <strong>Nenhum registro na tabela cashback_saldos!</strong></p>";
        } else {
            echo "<p>✅ <strong>" . count($registrosSaldos) . " registros na tabela cashback_saldos</strong></p>";
            foreach ($registrosSaldos as $reg) {
                echo "<p>• {$reg['nome_fantasia']}: R$ {$reg['saldo_disponivel']}</p>";
            }
        }
        
        echo "</div>";
        
        // Testar apenas os primeiros 3 para não sobrecarregar
        static $contador = 0;
        $contador++;
        if ($contador >= 3) break;
    }
    
    // 3. TESTE ESPECÍFICO DO TELEFONE QUE VOCÊ ESTÁ USANDO
    echo "<div style='border: 3px solid #dc3545; margin: 20px 0; padding: 15px; border-radius: 8px;'>";
    echo "<h2>🎯 Teste Telefone Específico: 38991045205</h2>";
    
    $telefoneEspecifico = '38991045205';
    
    // Buscar usuário com este telefone
    $userStmt = $db->prepare("
        SELECT u.*, 
               COUNT(t.id) as total_transacoes
        FROM usuarios u
        LEFT JOIN transacoes_cashback t ON u.id = t.usuario_id
        WHERE REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(u.telefone, '(', ''), ')', ''), '-', ''), ' ', ''), '.', '') = :telefone
        AND u.tipo = 'cliente'
        AND u.status = 'ativo'
        GROUP BY u.id
    ");
    $userStmt->bindParam(':telefone', $telefoneEspecifico);
    $userStmt->execute();
    
    $userEspecifico = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userEspecifico) {
        echo "<p>✅ <strong>Usuário encontrado:</strong> {$userEspecifico['nome']} (ID: {$userEspecifico['id']})</p>";
        echo "<p><strong>Tipo Cliente:</strong> {$userEspecifico['tipo_cliente']}</p>";
        echo "<p><strong>Total Transações:</strong> {$userEspecifico['total_transacoes']}</p>";
        
        // Testar WhatsApp
        $saldoConsulta = new SaldoConsulta();
        $resultadoEspecifico = $saldoConsulta->consultarSaldoPorTelefone($telefoneEspecifico);
        
        echo "<h4>Resultado WhatsApp:</h4>";
        echo "<pre>" . json_encode($resultadoEspecifico, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
        
    } else {
        echo "<p style='color: red;'>❌ <strong>Usuário não encontrado com telefone {$telefoneEspecifico}</strong></p>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<strong>ERRO:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<hr>";
echo "<p><strong>Teste concluído em:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>