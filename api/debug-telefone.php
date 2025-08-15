<?php
// api/debug-telefone.php
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

echo "<h1>🔍 Debug - Verificação de Telefone</h1>";

$telefoneTest = '38991045205';
$telefoneWith55 = '5538991045205';

echo "<h3>📞 Telefones para testar:</h3>";
echo "<p><strong>Original:</strong> {$telefoneTest}</p>";
echo "<p><strong>Com 55:</strong> {$telefoneWith55}</p>";

try {
    $db = Database::getConnection();
    
    // 1. Buscar TODOS os usuários para verificar formato dos telefones
    echo "<h3>👥 Todos os usuários cadastrados:</h3>";
    $allUsers = $db->query("SELECT id, nome, telefone, email, tipo, status FROM usuarios ORDER BY id");
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Nome</th><th>Telefone</th><th>Email</th><th>Tipo</th><th>Status</th></tr>";
    
    while ($user = $allUsers->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['nome']}</td>";
        echo "<td><strong>{$user['telefone']}</strong></td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['tipo']}</td>";
        echo "<td>{$user['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Testar busca específica
    echo "<h3>🔎 Testando buscas específicas:</h3>";
    
    $testNumbers = [
        $telefoneTest,
        $telefoneWith55,
        '553891045205',
        '5538991045205',
        '38991045205'
    ];
    
    foreach ($testNumbers as $testNum) {
        echo "<h4>Testando: {$testNum}</h4>";
        
        // Limpar número
        $cleanNum = preg_replace('/[^0-9]/', '', $testNum);
        
        $stmt = $db->prepare("
            SELECT id, nome, telefone, email, tipo, status 
            FROM usuarios 
            WHERE REPLACE(REPLACE(REPLACE(REPLACE(telefone, '(', ''), ')', ''), '-', ''), ' ', '') = :telefone
            OR REPLACE(REPLACE(REPLACE(REPLACE(telefone, '(', ''), ')', ''), '-', ''), ' ', '') = :telefone_with_55
            OR REPLACE(REPLACE(REPLACE(REPLACE(telefone, '(', ''), ')', ''), '-', ''), ' ', '') = :telefone_without_55
        ");
        
        $telefoneWith55Test = '55' . $cleanNum;
        $telefoneWithout55Test = (strlen($cleanNum) > 10 && substr($cleanNum, 0, 2) === '55') ? substr($cleanNum, 2) : $cleanNum;
        
        $stmt->bindParam(':telefone', $cleanNum);
        $stmt->bindParam(':telefone_with_55', $telefoneWith55Test);
        $stmt->bindParam(':telefone_without_55', $telefoneWithout55Test);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px;'>";
            echo "✅ <strong>ENCONTRADO!</strong><br>";
            echo "ID: {$user['id']}<br>";
            echo "Nome: {$user['nome']}<br>";
            echo "Telefone BD: {$user['telefone']}<br>";
            echo "Email: {$user['email']}<br>";
            echo "Tipo: {$user['tipo']}<br>";
            echo "Status: {$user['status']}<br>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
            echo "❌ <strong>NÃO ENCONTRADO</strong>";
            echo "</div>";
        }
        echo "<br>";
    }
    
    // 3. Testar a consulta como o WhatsApp faz
    echo "<h3>📱 Simulando consulta do WhatsApp:</h3>";
    
    require_once __DIR__ . '/../classes/SaldoConsulta.php';
    
    $saldoConsulta = new SaldoConsulta();
    $resultado = $saldoConsulta->consultarSaldoPorTelefone($telefoneTest);
    
    echo "<pre>";
    echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
    echo "❌ <strong>ERRO:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<br><a href='javascript:history.back()'>← Voltar</a>";
?>