<?php
echo "<h2>🔧 TESTE FORÇADO DE SESSÃO</h2>";

// TESTE 1: Iniciar sessão limpa
if (session_status() !== PHP_SESSION_NONE) {
    session_destroy();
}
session_start();
session_regenerate_id(true);

echo "<h3>1. Teste básico de sessão:</h3>";
$_SESSION['teste'] = 'valor_teste';
session_write_close();
session_start();

if (isset($_SESSION['teste']) && $_SESSION['teste'] === 'valor_teste') {
    echo "✅ Sessão básica funciona<br>";
} else {
    echo "❌ Sessão básica FALHA<br>";
}

// TESTE 2: Simular dados da loja
echo "<h3>2. Teste dados da loja:</h3>";
$_SESSION['store_id'] = 34;
$_SESSION['store_name'] = 'Teste Loja';
session_write_close();
session_start();

if (isset($_SESSION['store_id']) && $_SESSION['store_id'] === 34) {
    echo "✅ Dados da loja salvos com sucesso<br>";
    echo "Store ID: {$_SESSION['store_id']}<br>";
    echo "Store Name: {$_SESSION['store_name']}<br>";
} else {
    echo "❌ Dados da loja NÃO foram salvos<br>";
}

// TESTE 3: Verificar configuração do servidor
echo "<h3>3. Configuração do servidor:</h3>";
echo "Session Save Path: " . session_save_path() . "<br>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Name: " . session_name() . "<br>";
echo "Session Status: " . session_status() . "<br>";

echo "<h3>4. Sessão atual:</h3>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";
?>