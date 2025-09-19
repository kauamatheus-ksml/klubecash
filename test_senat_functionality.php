<?php
/**
 * Script de teste para funcionalidade SEST SENAT
 *
 * Este script verifica se todas as funcionalidades do SEST SENAT foram implementadas corretamente:
 * 1. Campo 'senat' na tabela usuarios
 * 2. Lógica de detecção de usuários SEST SENAT
 * 3. Aplicação do CSS personalizado
 * 4. Exibição da logo SEST SENAT
 */

require_once 'config/database.php';
require_once 'controllers/AuthController.php';

echo "<h1>Teste da Funcionalidade SEST SENAT</h1>";

// Teste 1: Verificar se o campo 'senat' existe na tabela usuarios
echo "<h2>1. Verificando estrutura do banco de dados</h2>";
try {
    $db = Database::getConnection();
    $stmt = $db->query("DESCRIBE usuarios");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $senatFieldExists = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'senat') {
            $senatFieldExists = true;
            echo "✅ Campo 'senat' encontrado na tabela usuarios<br>";
            echo "Tipo: {$column['Type']}<br>";
            echo "Default: {$column['Default']}<br>";
            break;
        }
    }

    if (!$senatFieldExists) {
        echo "❌ Campo 'senat' NÃO encontrado na tabela usuarios<br>";
        echo "Execute o SQL: database/add_senat_field.sql<br>";
    }

} catch (PDOException $e) {
    echo "❌ Erro ao verificar estrutura do banco: " . $e->getMessage() . "<br>";
}

// Teste 2: Verificar métodos do AuthController
echo "<h2>2. Verificando métodos do AuthController</h2>";

if (method_exists('AuthController', 'isSestSenat')) {
    echo "✅ Método AuthController::isSestSenat() existe<br>";
} else {
    echo "❌ Método AuthController::isSestSenat() NÃO existe<br>";
}

if (method_exists('AuthController', 'getThemeClass')) {
    echo "✅ Método AuthController::getThemeClass() existe<br>";
} else {
    echo "❌ Método AuthController::getThemeClass() NÃO existe<br>";
}

// Teste 3: Verificar arquivos CSS
echo "<h2>3. Verificando arquivos CSS</h2>";

$cssFile = 'assets/css/sest-senat-theme.css';
if (file_exists($cssFile)) {
    echo "✅ Arquivo CSS personalizado existe: $cssFile<br>";
    $cssSize = filesize($cssFile);
    echo "Tamanho: " . number_format($cssSize) . " bytes<br>";
} else {
    echo "❌ Arquivo CSS personalizado NÃO existe: $cssFile<br>";
}

// Teste 4: Verificar modificações nos componentes
echo "<h2>4. Verificando componentes modificados</h2>";

$sidebarFile = 'views/components/sidebar-store.php';
if (file_exists($sidebarFile)) {
    $sidebarContent = file_get_contents($sidebarFile);

    if (strpos($sidebarContent, 'sest-senat-theme.css') !== false) {
        echo "✅ Sidebar modificado para incluir CSS SEST SENAT<br>";
    } else {
        echo "❌ Sidebar NÃO modificado para incluir CSS SEST SENAT<br>";
    }

    if (strpos($sidebarContent, 'AuthController::isSestSenat') !== false) {
        echo "✅ Sidebar modificado para detectar usuários SEST SENAT<br>";
    } else {
        echo "❌ Sidebar NÃO modificado para detectar usuários SEST SENAT<br>";
    }

    if (strpos($sidebarContent, 'sest-senat-logo.png') !== false) {
        echo "✅ Sidebar modificado para exibir logo SEST SENAT<br>";
    } else {
        echo "❌ Sidebar NÃO modificado para exibir logo SEST SENAT<br>";
    }

} else {
    echo "❌ Arquivo sidebar NÃO encontrado: $sidebarFile<br>";
}

// Teste 5: Verificar usuários SEST SENAT existentes
echo "<h2>5. Verificando usuários SEST SENAT</h2>";
try {
    $stmt = $db->query("SELECT id, nome, email, senat FROM usuarios WHERE senat = 'Sim' LIMIT 5");
    $sestSenatUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($sestSenatUsers) > 0) {
        echo "✅ Encontrados " . count($sestSenatUsers) . " usuários SEST SENAT:<br>";
        foreach ($sestSenatUsers as $user) {
            echo "- ID: {$user['id']}, Nome: {$user['nome']}, Email: {$user['email']}<br>";
        }
    } else {
        echo "ℹ️ Nenhum usuário SEST SENAT encontrado<br>";
        echo "Para testar, execute: UPDATE usuarios SET senat = 'Sim' WHERE id = [ID_DO_USUARIO]<br>";
    }

} catch (PDOException $e) {
    echo "❌ Erro ao buscar usuários SEST SENAT: " . $e->getMessage() . "<br>";
}

// Teste 6: Verificar logo SEST SENAT
echo "<h2>6. Verificando logo SEST SENAT</h2>";

$logoFile = 'assets/images/sest-senat-logo.png';
if (file_exists($logoFile)) {
    echo "✅ Logo SEST SENAT existe: $logoFile<br>";
    $logoSize = filesize($logoFile);
    echo "Tamanho: " . number_format($logoSize) . " bytes<br>";
} else {
    echo "ℹ️ Logo SEST SENAT ainda não adicionada: $logoFile<br>";
    echo "Adicione o arquivo de logo conforme instruções em: assets/images/README_SEST_SENAT.md<br>";
}

echo "<h2>Resumo</h2>";
echo "<p>A implementação da funcionalidade SEST SENAT foi concluída. Para testar completamente:</p>";
echo "<ol>";
echo "<li>Execute o SQL em database/add_senat_field.sql no seu banco de dados</li>";
echo "<li>Atualize um usuário lojista para ser SEST SENAT: UPDATE usuarios SET senat = 'Sim' WHERE id = [ID]</li>";
echo "<li>Adicione a logo SEST SENAT em assets/images/sest-senat-logo.png</li>";
echo "<li>Faça login com o usuário modificado para ver o tema personalizado</li>";
echo "</ol>";

echo "<p><strong>Data do teste:</strong> " . date('d/m/Y H:i:s') . "</p>";
?>