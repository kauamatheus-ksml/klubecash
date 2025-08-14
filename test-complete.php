<?php
// test-complete.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🧪 TESTE COMPLETO - Cliente Visitante</h1>";

// Simular sessão
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_type'] = 'loja';
$_SESSION['store_id'] = 1;

echo "<h2>1. Testando criação via CURL:</h2>";

$testData = [
    'action' => 'create_visitor_client',
    'nome' => 'Cliente Teste ' . time(),
    'telefone' => '11' . rand(100000000, 999999999),
    'store_id' => 1
];

echo "📤 Enviando: " . json_encode($testData) . "<br><br>";

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => 'https://klubecash.com/api/store-client-search.php',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($testData),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Cookie: ' . session_name() . '=' . session_id()
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curlError = curl_error($curl);
curl_close($curl);

echo "<strong>HTTP Code:</strong> $httpCode<br>";
if ($curlError) {
    echo "<strong>CURL Error:</strong> $curlError<br>";
}

echo "<strong>📥 Resposta:</strong><br>";
echo "<pre style='background: #f4f4f4; padding: 15px; border-radius: 5px;'>";

if ($response) {
    $decodedResponse = json_decode($response, true);
    if ($decodedResponse) {
        print_r($decodedResponse);
        
        if ($decodedResponse['status'] === true) {
            echo "\n✅ SUCESSO! Cliente visitante criado com ID: " . $decodedResponse['data']['id'];
        } else {
            echo "\n❌ ERRO: " . $decodedResponse['message'];
        }
    } else {
        echo "Resposta não é JSON válido:\n$response";
    }
} else {
    echo "Nenhuma resposta recebida";
}

echo "</pre>";

echo "<h2>2. Verificando no banco:</h2>";

try {
    require_once 'config/database.php';
    $db = Database::getConnection();
    
    $stmt = $db->prepare("
        SELECT id, nome, telefone, email, tipo_cliente, loja_criadora_id, data_criacao
        FROM usuarios 
        WHERE tipo_cliente = 'visitante' 
        ORDER BY data_criacao DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($visitors) {
        echo "<table border='1' cellpadding='8' cellspacing='0'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Nome</th><th>Telefone</th><th>Email</th><th>Loja</th><th>Criado</th></tr>";
        foreach ($visitors as $visitor) {
            echo "<tr>";
            echo "<td>{$visitor['id']}</td>";
            echo "<td>{$visitor['nome']}</td>";
            echo "<td>{$visitor['telefone']}</td>";
            echo "<td>" . ($visitor['email'] ?: 'Sem email') . "</td>";
            echo "<td>{$visitor['loja_criadora_id']}</td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($visitor['data_criacao'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ Nenhum cliente visitante encontrado no banco";
    }
    
} catch (Exception $e) {
    echo "❌ Erro ao consultar banco: " . $e->getMessage();
}

echo "<h2>3. Testando busca de cliente:</h2>";

if (!empty($visitors)) {
    $lastVisitor = $visitors[0];
    $searchData = [
        'action' => 'search_client',
        'search_term' => $lastVisitor['telefone'],
        'store_id' => 1
    ];
    
    echo "📤 Buscando por: " . $lastVisitor['telefone'] . "<br>";
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://klubecash.com/api/store-client-search.php',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($searchData),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Cookie: ' . session_name() . '=' . session_id()
        ]
    ]);
    
    $searchResponse = curl_exec($curl);
    curl_close($curl);
    
    echo "📥 Resultado da busca:<br>";
    echo "<pre style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
    $searchResult = json_decode($searchResponse, true);
    if ($searchResult) {
        print_r($searchResult);
    } else {
        echo $searchResponse;
    }
    echo "</pre>";
}

?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1 { color: #2c3e50; }
h2 { color: #34495e; border-bottom: 1px solid #ecf0f1; padding-bottom: 5px; }
table { border-collapse: collapse; margin: 10px 0; width: 100%; }
th { background-color: #3498db; color: white; }
td, th { padding: 8px 12px; border: 1px solid #ddd; }
</style>