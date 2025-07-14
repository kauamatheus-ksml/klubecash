<?php
// debug-sessao.php - Verificar dados da sessão de funcionários
// DELETAR após uso por segurança

session_start();

echo "<h1>Análise da Sessão de Funcionário</h1>";
echo "<h2>Dados Básicos da Sessão:</h2>";

if (isset($_SESSION['user_id'])) {
    echo "<p>✅ user_id: " . $_SESSION['user_id'] . "</p>";
} else {
    echo "<p>❌ user_id não definido</p>";
}

if (isset($_SESSION['user_name'])) {
    echo "<p>✅ user_name: " . $_SESSION['user_name'] . "</p>";
} else {
    echo "<p>❌ user_name não definido</p>";
}

if (isset($_SESSION['user_type'])) {
    echo "<p>✅ user_type: " . $_SESSION['user_type'] . "</p>";
} else {
    echo "<p>❌ user_type não definido</p>";
}

echo "<h2>Dados Específicos de Funcionário:</h2>";

if (isset($_SESSION['employee_subtype'])) {
    echo "<p>✅ employee_subtype: " . $_SESSION['employee_subtype'] . "</p>";
} else {
    echo "<p>❌ employee_subtype não definido</p>";
}

if (isset($_SESSION['store_id'])) {
    echo "<p>✅ store_id: " . $_SESSION['store_id'] . "</p>";
} else {
    echo "<p>❌ store_id não definido</p>";
}

if (isset($_SESSION['store_name'])) {
    echo "<p>✅ store_name: " . $_SESSION['store_name'] . "</p>";
} else {
    echo "<p>❌ store_name não definido</p>";
}

echo "<h2>Teste de Lógica Condicional:</h2>";

$userType = $_SESSION['user_type'] ?? '';
echo "<p>Tipo de usuário detectado: '{$userType}'</p>";

if ($userType === 'funcionario') {
    echo "<p>✅ Condição funcionário detectada corretamente</p>";
    
    $employeeSubtype = $_SESSION['employee_subtype'] ?? '';
    echo "<p>Subtipo detectado: '{$employeeSubtype}'</p>";
    
    // Testando a lógica de mapeamento de subtipos
    $subtypeDisplay = '';
    switch($employeeSubtype) {
        case 'gerente':
            $subtypeDisplay = 'Gerente';
            break;
        case 'financeiro':
            $subtypeDisplay = 'Financeiro';
            break;
        case 'vendedor':
            $subtypeDisplay = 'Vendedor';
            break;
        default:
            $subtypeDisplay = 'Funcionário';
    }
    
    echo "<p>✅ Mapeamento de subtipo: '{$subtypeDisplay}'</p>";
} else {
    echo "<p>❌ Condição funcionário não detectada</p>";
}

echo "<h2>Dump Completo da Sessão:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>