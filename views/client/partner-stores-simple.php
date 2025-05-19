<?php
// views/client/partner-stores-simple.php
// Versão simplificada para teste
require_once '../../config/database.php';
require_once '../../config/constants.php';

session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $db = Database::getConnection();
    
    // Query simples para testar
    $query = "SELECT * FROM lojas WHERE status = 'aprovado' ORDER BY nome_fantasia LIMIT 10";
    $stmt = $db->query($query);
    $lojas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h1>Lojas Parceiras (Versão Teste)</h1>";
    echo "<p>Total encontradas: " . count($lojas) . "</p>";
    
    foreach ($lojas as $loja) {
        echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
        echo "<h3>" . htmlspecialchars($loja['nome_fantasia']) . "</h3>";
        echo "<p>Categoria: " . htmlspecialchars($loja['categoria']) . "</p>";
        echo "<p>Cashback: " . $loja['porcentagem_cashback'] . "%</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>