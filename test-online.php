<?php
// test-online.php
ob_start(); // Evitar problemas de header
?>
<!DOCTYPE html>
<html>
<head>
    <title>Teste Cliente Visitante</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #e8f5e8; padding: 10px; border-radius: 5px; }
        .error { color: red; background: #ffe8e8; padding: 10px; border-radius: 5px; }
        .info { color: blue; background: #e8f0ff; padding: 10px; border-radius: 5px; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🧪 Teste Cliente Visitante - Servidor Online</h1>
    
    <?php
    session_start();
    
    // Simular sessão de loja
    $_SESSION['user_id'] = 1;
    $_SESSION['user_type'] = 'loja';
    $_SESSION['store_id'] = 1;
    
    echo "<div class='info'>✅ Sessão simulada criada</div>";
    
    // Teste 1: Verificar se API existe
    echo "<h2>1. Verificando API</h2>";
    if (file_exists('api/store-client-search.php')) {
        echo "<div class='success'>✅ API existe</div>";
    } else {
        echo "<div class='error'>❌ API não encontrada</div>";
    }
    
    // Teste 2: Verificar constantes
    echo "<h2>2. Verificando Constantes</h2>";
    require_once 'config/constants.php';
    
    $constantes = ['CLIENT_TYPE_VISITOR', 'USER_TYPE_CLIENT', 'USER_ACTIVE'];
    foreach ($constantes as $const) {
        if (defined($const)) {
            echo "<div class='success'>✅ {$const} = " . constant($const) . "</div>";
        } else {
            echo "<div class='error'>❌ {$const} não definida</div>";
        }
    }
    
    // Teste 3: Testar criação de cliente visitante
    echo "<h2>3. Testando Criação de Cliente Visitante</h2>";
    
    $telefoneUnico = '11' . rand(100000000, 999999999);
    $testData = [
        'action' => 'create_visitor_client',
        'nome' => 'Teste Online ' . date('H:i:s'),
        'telefone' => $telefoneUnico,
        'store_id' => 1
    ];
    
    echo "<div class='info'>📤 Dados de teste: " . json_encode($testData) . "</div>";
    
    // Fazer requisição CURL para a própria API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://klubecash.com/api/store-client-search.php');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Cookie: ' . session_name() . '=' . session_id()
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    echo "<strong>HTTP Code:</strong> $httpCode<br>";
    
    if ($curlError) {
        echo "<div class='error'>CURL Error: $curlError</div>";
    } else {
        echo "<h3>📥 Resposta da API:</h3>";
        echo "<pre>$response</pre>";
        
        $result = json_decode($response, true);
        if ($result) {
            if ($result['status'] === true) {
                echo "<div class='success'>✅ SUCESSO! Cliente visitante criado!</div>";
                
                // Teste 4: Verificar no banco
                echo "<h2>4. Verificando no Banco de Dados</h2>";
                try {
                    require_once 'config/database.php';
                    $db = Database::getConnection();
                    
                    $stmt = $db->prepare("SELECT * FROM usuarios WHERE telefone = ? AND tipo_cliente = 'visitante'");
                    $stmt->execute([$telefoneUnico]);
                    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($cliente) {
                        echo "<div class='success'>✅ Cliente encontrado no banco!</div>";
                        echo "<pre>" . print_r($cliente, true) . "</pre>";
                    } else {
                        echo "<div class='error'>❌ Cliente não encontrado no banco</div>";
                    }
                    
                } catch (Exception $e) {
                    echo "<div class='error'>❌ Erro no banco: " . $e->getMessage() . "</div>";
                }
                
                // Teste 5: Buscar cliente criado
                echo "<h2>5. Testando Busca do Cliente Criado</h2>";
                
                $searchData = [
                    'action' => 'search_client',
                    'search_term' => $telefoneUnico,
                    'store_id' => 1
                ];
                
                $ch2 = curl_init();
                curl_setopt($ch2, CURLOPT_URL, 'https://klubecash.com/api/store-client-search.php');
                curl_setopt($ch2, CURLOPT_POST, 1);
                curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($searchData));
                curl_setopt($ch2, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Cookie: ' . session_name() . '=' . session_id()
                ]);
                curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch2, CURLOPT_TIMEOUT, 30);
                
                $searchResponse = curl_exec($ch2);
                curl_close($ch2);
                
                echo "<div class='info'>📤 Buscando por: $telefoneUnico</div>";
                echo "<h3>📥 Resultado da busca:</h3>";
                echo "<pre>$searchResponse</pre>";
                
                $searchResult = json_decode($searchResponse, true);
                if ($searchResult && $searchResult['status'] === true) {
                    echo "<div class='success'>✅ Cliente visitante encontrado na busca!</div>";
                } else {
                    echo "<div class='error'>❌ Problema na busca do cliente</div>";
                }
                
            } else {
                echo "<div class='error'>❌ ERRO: " . $result['message'] . "</div>";
            }
        } else {
            echo "<div class='error'>❌ Resposta não é JSON válido</div>";
        }
    }
    ?>
    
    <h2>6. Teste Manual na Interface</h2>
    <div class='info'>
        <p><strong>Para testar na interface real:</strong></p>
        <ol>
            <li>Acesse: <a href="/store/registrar-transacao" target="_blank">Registrar Nova Venda</a></li>
            <li>Digite o telefone: <strong><?php echo $telefoneUnico; ?></strong></li>
            <li>Clique em "Buscar Cliente"</li>
            <li>Deve aparecer o cliente visitante ou a opção de criar</li>
        </ol>
    </div>
</body>
</html>