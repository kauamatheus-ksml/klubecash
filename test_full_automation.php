<?php
require_once __DIR__ . '/config/database.php';

echo "🚀 TESTE DO SISTEMA TOTALMENTE AUTOMÁTICO\n";
echo "==========================================\n\n";

echo "🎯 OBJETIVO: Simular falha e verificar automação completa\n\n";

// 1. Modificar temporariamente o UltraDirectNotifier para forçar falha
echo "1. 🧪 Testando com falha forçada para ativar fallback automático...\n";

require_once __DIR__ . '/classes/UltraDirectNotifier.php';

// Criar notificador e simular transação que força erro
$testData = [
    'transaction_id' => 888,
    'cliente_nome' => 'Automação Teste',
    'cliente_telefone' => 'brutal_system', // Isso vai forçar busca e usar padrão
    'valor_total' => 200.00,
    'valor_cliente' => 14.00,
    'loja_nome' => 'Loja Automação',
    'status' => 'aprovado'
];

$notifier = new UltraDirectNotifier();

echo "Dados da transação:\n";
echo "- ID: {$testData['transaction_id']}\n";
echo "- Nome: {$testData['cliente_nome']}\n";
echo "- Telefone: {$testData['cliente_telefone']} (vai ser resolvido)\n";
echo "- Valor: R$ {$testData['valor_total']}\n\n";

echo "🔥 Executando notifyTransaction (pode falhar e ativar fallback)...\n";
echo str_repeat("=", 60) . "\n";

$result = $notifier->notifyTransaction($testData);

echo str_repeat("=", 60) . "\n";

echo "\n📋 RESULTADO FINAL:\n";
echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

// Verificar se sistema funcionou
if ($result['success']) {
    echo "✅ SUCESSO: Sistema funcionou ";

    if (isset($result['method'])) {
        switch ($result['method']) {
            case 'ultra_direct':
                echo "(envio direto)\n";
                break;
            case 'emergency_auto_processed':
                echo "(fallback automático - PERFEITO!)\n";
                break;
            case 'emergency_fallback':
                echo "(fallback com processamento automático)\n";
                break;
            default:
                echo "(método: {$result['method']})\n";
        }
    }

    echo "\n🎉 AUTOMAÇÃO FUNCIONANDO! Sistema processou sem intervenção manual.\n";
} else {
    echo "❌ FALHA: Sistema não conseguiu processar\n";
    echo "Erro: " . ($result['error'] ?? 'Erro desconhecido') . "\n";
}

echo "\n📱 Verifique seu WhatsApp para confirmar recebimento!\n";
?>