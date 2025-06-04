<?php
// debug_profile.php - Script para diagnosticar problemas do perfil

require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'utils/Validator.php';

// Iniciar sessão para pegar o ID do usuário
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "Usuário não logado. Faça login primeiro.\n";
    exit;
}

$userId = $_SESSION['user_id'];
echo "=== DIAGNÓSTICO DO PERFIL - Usuário ID: $userId ===\n\n";

try {
    $db = Database::getConnection();
    
    // 1. Verificar se a coluna CPF existe
    echo "1. Verificando coluna CPF...\n";
    $checkColumn = $db->prepare("SHOW COLUMNS FROM usuarios LIKE 'cpf'");
    $checkColumn->execute();
    $cpfExists = $checkColumn->rowCount() > 0;
    echo "Coluna CPF existe: " . ($cpfExists ? "SIM" : "NÃO") . "\n\n";
    
    if (!$cpfExists) {
        echo "Criando coluna CPF...\n";
        $db->exec("ALTER TABLE usuarios ADD COLUMN cpf VARCHAR(14) NULL AFTER telefone");
        echo "Coluna CPF criada!\n\n";
    }
    
    // 2. Verificar dados do usuário
    echo "2. Dados atuais do usuário:\n";
    $userStmt = $db->prepare("SELECT * FROM usuarios WHERE id = :id");
    $userStmt->bindParam(':id', $userId);
    $userStmt->execute();
    $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    foreach ($userData as $campo => $valor) {
        echo "   $campo: " . ($valor ?? 'NULL') . "\n";
    }
    echo "\n";
    
    // 3. Verificar dados de contato
    echo "3. Dados de contato:\n";
    $contactStmt = $db->prepare("SELECT * FROM usuarios_contato WHERE usuario_id = :id");
    $contactStmt->bindParam(':id', $userId);
    $contactStmt->execute();
    $contactData = $contactStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($contactData) {
        foreach ($contactData as $campo => $valor) {
            echo "   $campo: " . ($valor ?? 'NULL') . "\n";
        }
    } else {
        echo "   Nenhum dado de contato encontrado\n";
    }
    echo "\n";
    
    // 4. Verificar dados de endereço
    echo "4. Dados de endereço:\n";
    $addrStmt = $db->prepare("SELECT * FROM usuarios_endereco WHERE usuario_id = :id");
    $addrStmt->bindParam(':id', $userId);
    $addrStmt->execute();
    $addrData = $addrStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($addrData) {
        foreach ($addrData as $campo => $valor) {
            echo "   $campo: " . ($valor ?? 'NULL') . "\n";
        }
    } else {
        echo "   Nenhum dado de endereço encontrado\n";
    }
    echo "\n";
    
    // 5. Analisar completude do perfil
    echo "5. Análise de completude:\n";
    $missingItems = [];
    
    if (empty($userData['nome'])) $missingItems[] = 'Nome';
    if (empty($userData['cpf'])) $missingItems[] = 'CPF';
    if (empty($userData['telefone']) && (!$contactData || (empty($contactData['telefone']) && empty($contactData['celular'])))) {
        $missingItems[] = 'Telefone';
    }
    if (!$addrData || empty($addrData['cep']) || empty($addrData['cidade'])) {
        $missingItems[] = 'Endereço';
    }
    
    if (empty($missingItems)) {
        echo "   ✅ Perfil COMPLETO!\n";
    } else {
        echo "   ❌ Perfil INCOMPLETO. Faltam: " . implode(', ', $missingItems) . "\n";
    }
    
    echo "\n=== FIM DO DIAGNÓSTICO ===\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>