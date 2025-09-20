<?php
/**
 * INVESTIGADOR DE CAMINHOS DE TRANSAÇÃO
 * 
 * Este arquivo vai procurar TODOS os lugares no código onde
 * transações podem ser criadas, nos ajudando a descobrir
 * qual caminho está sendo realmente usado.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

function scanForTransactionCreation($directory = '.') {
    $results = [];
    $extensions = ['php'];
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $extension = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
            if (in_array($extension, $extensions)) {
                $content = file_get_contents($file->getPathname());
                
                // Procurar padrões de criação de transação
                $patterns = [
                    'INSERT INTO transacoes_cashback' => 'SQL Insert direto',
                    'transacoes_cashback.*INSERT' => 'SQL Insert variação',
                    'registerTransaction' => 'Método registerTransaction',
                    'Transaction.*save' => 'Modelo Transaction save',
                    'new Transaction' => 'Instância de Transaction',
                    'lastInsertId' => 'Obtenção de ID após insert',
                    'cashback.*insert' => 'Insert relacionado a cashback'
                ];
                
                foreach ($patterns as $pattern => $description) {
                    if (preg_match("/$pattern/i", $content)) {
                        $results[] = [
                            'file' => $file->getPathname(),
                            'pattern' => $pattern,
                            'description' => $description,
                            'line_count' => substr_count($content, "\n") + 1
                        ];
                    }
                }
            }
        }
    }
    
    return $results;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>🔍 Investigador de Caminhos de Transação</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #FF7A00; padding-bottom: 10px; }
        .file-group { margin: 15px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        .pattern { background: #e9ecef; padding: 5px 10px; margin: 5px 0; border-radius: 3px; font-family: monospace; }
        .priority { background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Investigador de Caminhos de Transação</h1>
    
    <div class="priority">
        <strong>🎯 Objetivo:</strong> Encontrar TODOS os lugares no código onde transações podem ser criadas,
        para descobrir qual caminho está sendo usado na prática.
    </div>

    <h2>📂 Resultados da Varredura</h2>

<?php
$results = scanForTransactionCreation();

if (empty($results)) {
    echo "<p>Nenhum padrão de criação de transação encontrado.</p>";
} else {
    // Agrupar por arquivo
    $byFile = [];
    foreach ($results as $result) {
        $byFile[$result['file']][] = $result;
    }
    
    foreach ($byFile as $file => $patterns) {
        echo "<div class='file-group'>";
        echo "<h3>📄 " . htmlspecialchars($file) . "</h3>";
        
        foreach ($patterns as $pattern) {
            echo "<div class='pattern'>";
            echo "<strong>Padrão:</strong> " . htmlspecialchars($pattern['pattern']) . "<br>";
            echo "<strong>Descrição:</strong> " . htmlspecialchars($pattern['description']) . "<br>";
            echo "<strong>Linhas no arquivo:</strong> " . $pattern['line_count'];
            echo "</div>";
        }
        echo "</div>";
    }
}
?>

    <h2>🎯 Estratégia de Rastreamento Abrangente</h2>
    <p>Com base nos arquivos encontrados acima, vamos adicionar rastreamento em TODOS os locais possíveis.</p>
    
    <h3>📋 Instruções para Rastreamento Completo</h3>
    <ol>
        <li><strong>Identifique todos os arquivos listados acima</strong></li>
        <li><strong>Em cada arquivo que contém "INSERT INTO transacoes_cashback" ou "lastInsertId":</strong></li>
        <ul>
            <li>Adicione um log de trace IMEDIATAMENTE após o INSERT</li>
            <li>Use este código: <code>error_log("[TRACE] ARQUIVO_NOME - Transação inserida: ID " . $db->lastInsertId(), 3, 'integration_trace.log');</code></li>
        </ul>
        <li><strong>Teste novamente registrando uma transação</strong></li>
        <li><strong>Veja qual arquivo realmente executa</strong></li>
    </ol>

</div>
</body>
</html>