<?php
// debug_form_data.php - Capturar dados exatos do formulário

require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';

session_start();

// Simular autenticação
$_SESSION['user_id'] = 1;
$_SESSION['user_type'] = 'loja';

echo "<pre>";
echo "=== DEBUG DADOS DO FORMULÁRIO ===\n\n";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "1. Dados recebidos via POST:\n";
    foreach ($_POST as $key => $value) {
        echo "  {$key}: " . var_export($value, true) . "\n";
    }
    
    echo "\n2. Análise específica dos campos de saldo:\n";
    echo "  usar_saldo: " . var_export($_POST['usar_saldo'] ?? 'NÃO DEFINIDO', true) . "\n";
    echo "  valor_saldo_usado: " . var_export($_POST['valor_saldo_usado'] ?? 'NÃO DEFINIDO', true) . "\n";
    
    echo "\n3. Conversões que serão feitas:\n";
    $usarSaldo = isset($_POST['usar_saldo']) && $_POST['usar_saldo'] === 'sim';
    echo "  usarSaldo (boolean): " . var_export($usarSaldo, true) . "\n";
    
    $valorSaldoUsado = floatval($_POST['valor_saldo_usado'] ?? 0);
    echo "  valorSaldoUsado (float): " . var_export($valorSaldoUsado, true) . "\n";
    
    echo "\n4. Dados formatados para TransactionController:\n";
    $transactionData = [
        'usuario_id' => intval($_POST['usuario_id'] ?? 0),
        'loja_id' => intval($_POST['loja_id'] ?? 0),
        'valor_total' => floatval($_POST['valor_total'] ?? 0),
        'codigo_transacao' => $_POST['codigo_transacao'] ?? '',
        'usar_saldo' => $usarSaldo,
        'valor_saldo_usado' => $valorSaldoUsado,
        'descricao' => $_POST['descricao'] ?? ''
    ];
    
    echo "Data para TransactionController:\n";
    foreach ($transactionData as $key => $value) {
        echo "  {$key}: " . var_export($value, true) . "\n";
    }
    
    // Testar se vai funcionar
    echo "\n5. Teste rápido:\n";
    if ($usarSaldo && $valorSaldoUsado > 0) {
        echo "✓ Sistema detectou que deve usar saldo\n";
        echo "  Valor a ser debitado: R$ " . number_format($valorSaldoUsado, 2, ',', '.') . "\n";
    } else {
        echo "✗ Sistema NÃO detectou uso de saldo\n";
        if (!$usarSaldo) {
            echo "  Motivo: usar_saldo = false\n";
        }
        if ($valorSaldoUsado <= 0) {
            echo "  Motivo: valor_saldo_usado <= 0\n";
        }
    }
    
} else {
    // Mostrar formulário de teste
    echo "Envie dados de teste:\n\n";
    ?>
    </pre>
    <form method="POST" action="">
        <h3>Teste de Dados do Formulário</h3>
        
        <p>
            <label>Cliente Email:</label><br>
            <input type="text" name="cliente_email" value="kauamatheus920@gmail.com">
        </p>
        
        <p>
            <label>Valor Total:</label><br>
            <input type="number" name="valor_total" value="100.00" step="0.01">
        </p>
        
        <p>
            <label>Código Transação:</label><br>
            <input type="text" name="codigo_transacao" value="TESTE_FORM_<?php echo time(); ?>">
        </p>
        
        <p>
            <label>
                <input type="checkbox" name="usar_saldo_check" id="usarSaldoCheck">
                Usar saldo do cliente
            </label>
        </p>
        
        <p>
            <label>Valor do saldo a usar:</label><br>
            <input type="number" name="valor_saldo_usado" value="50.00" step="0.01">
        </p>
        
        <p>
            <label>Descrição:</label><br>
            <textarea name="descricao">Teste de formulário</textarea>
        </p>
        
        <!-- Campos ocultos (como no form real) -->
        <input type="hidden" name="usar_saldo" id="usar_saldo" value="nao">
        <input type="hidden" name="usuario_id" value="9">
        <input type="hidden" name="loja_id" value="13">
        
        <p>
            <button type="submit">Testar Envio</button>
        </p>
    </form>
    
    <script>
        // Simular o JavaScript do formulário real
        document.getElementById('usarSaldoCheck').addEventListener('change', function() {
            const usarSaldoHidden = document.getElementById('usar_saldo');
            if (this.checked) {
                usarSaldoHidden.value = 'sim';
            } else {
                usarSaldoHidden.value = 'nao';
            }
        });
    </script>
    <pre>
    <?php
}

echo "</pre>";
?>