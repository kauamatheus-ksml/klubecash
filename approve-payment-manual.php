<?php
// approve-payment-manual.php
try {
    $db = new PDO("mysql:host=localhost;dbname=u297617088_klubecash", "u297617088_root", "Aaku_2004@");
    
    // Verificar estrutura da tabela
    $stmt = $db->query("DESCRIBE cashback_saldos");
    $columns = $stmt->fetchAll();
    
    echo "Colunas da tabela cashback_saldos:<br>";
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . "<br>";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>