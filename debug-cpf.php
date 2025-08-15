<?php
// debug-cpf.php
require_once 'config/database.php';

$db = Database::getConnection();

echo "<h2>🔍 Diagnóstico de CPFs - Klube Cash</h2>";

// 1. LISTAR TODAS AS CONTAS RECENTES (últimas 10)
echo "<h3>1. Contas Criadas Recentemente</h3>";
$stmt = $db->prepare("
    SELECT id, nome, email, cpf, 
           LENGTH(cpf) as cpf_length,
           data_criacao,
           status
    FROM usuarios 
    ORDER BY data_criacao DESC 
    LIMIT 10
");
$stmt->execute();
$recentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr>
        <th>ID</th>
        <th>Nome</th>
        <th>Email</th>
        <th>CPF</th>
        <th>Tamanho CPF</th>
        <th>Data</th>
        <th>Status</th>
        <th>Problema?</th>
      </tr>";

foreach ($recentUsers as $user) {
    $cpf = $user['cpf'] ?? '';
    $cpfClean = preg_replace('/\D/', '', $cpf);
    $problema = '';
    
    // Verificar problemas no CPF
    if (empty($cpf)) {
        $problema = '❌ CPF VAZIO';
    } elseif (strlen($cpfClean) != 11) {
        $problema = '❌ CPF TAMANHO INVÁLIDO';
    } elseif (preg_match('/(\d)\1{10}/', $cpfClean)) {
        $problema = '❌ CPF SEQUENCIAL';
    } else {
        $problema = '✅ CPF OK';
    }
    
    echo "<tr>";
    echo "<td>{$user['id']}</td>";
    echo "<td>" . htmlspecialchars($user['nome']) . "</td>";
    echo "<td>" . htmlspecialchars($user['email']) . "</td>";
    echo "<td>" . ($cpf ? substr($cpf, 0, 3) . '***' . substr($cpf, -2) : 'VAZIO') . "</td>";
    echo "<td>{$user['cpf_length']}</td>";
    echo "<td>{$user['data_criacao']}</td>";
    echo "<td>{$user['status']}</td>";
    echo "<td style='color: " . (strpos($problema, '❌') !== false ? 'red' : 'green') . "'>{$problema}</td>";
    echo "</tr>";
}
echo "</table>";

// 2. BUSCAR CONTAS COM CPF PROBLEMÁTICO
echo "<h3>2. Contas com CPF Problemático</h3>";
$problemStmt = $db->prepare("
    SELECT id, nome, email, cpf, data_criacao
    FROM usuarios 
    WHERE cpf IS NULL 
       OR cpf = '' 
       OR LENGTH(REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '')) != 11
    ORDER BY data_criacao DESC
");
$problemStmt->execute();
$problemUsers = $problemStmt->fetchAll(PDO::FETCH_ASSOC);

if ($problemUsers) {
    echo "<p style='color: red;'>⚠️ Encontradas " . count($problemUsers) . " contas com CPF problemático:</p>";
    foreach ($problemUsers as $user) {
        echo "<div style='border: 1px solid red; padding: 10px; margin: 5px;'>";
        echo "<strong>ID:</strong> {$user['id']}<br>";
        echo "<strong>Nome:</strong> " . htmlspecialchars($user['nome']) . "<br>";
        echo "<strong>Email:</strong> " . htmlspecialchars($user['email']) . "<br>";
        echo "<strong>CPF:</strong> " . ($user['cpf'] ? $user['cpf'] : 'VAZIO') . "<br>";
        echo "<strong>Criado:</strong> {$user['data_criacao']}<br>";
        echo "</div>";
    }
} else {
    echo "<p style='color: green;'>✅ Nenhuma conta com CPF problemático encontrada</p>";
}

// 3. VERIFICAR DUPLICATAS
echo "<h3>3. CPFs Duplicados</h3>";
$dupStmt = $db->prepare("
    SELECT cpf, COUNT(*) as count, GROUP_CONCAT(id) as user_ids, GROUP_CONCAT(nome) as nomes
    FROM usuarios 
    WHERE cpf IS NOT NULL AND cpf != ''
    GROUP BY cpf 
    HAVING COUNT(*) > 1
");
$dupStmt->execute();
$duplicates = $dupStmt->fetchAll(PDO::FETCH_ASSOC);

if ($duplicates) {
    echo "<p style='color: orange;'>⚠️ CPFs duplicados encontrados:</p>";
    foreach ($duplicates as $dup) {
        echo "<div style='border: 1px solid orange; padding: 10px; margin: 5px;'>";
        echo "<strong>CPF:</strong> " . substr($dup['cpf'], 0, 3) . '***' . substr($dup['cpf'], -2) . "<br>";
        echo "<strong>Usado por " . $dup['count'] . " contas:</strong> IDs {$dup['user_ids']}<br>";
        echo "<strong>Nomes:</strong> " . htmlspecialchars($dup['nomes']) . "<br>";
        echo "</div>";
    }
} else {
    echo "<p style='color: green;'>✅ Nenhum CPF duplicado encontrado</p>";
}

echo "<hr>";
echo "<p><strong>💡 Próximo Passo:</strong> Se encontrou contas problemáticas, use o ID da conta para testar: <br>";
echo "<code>debug-cpf.php?test_id=ID_DA_CONTA</code></p>";
?>