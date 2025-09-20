<?php
/**
 * Script para gerar PDF da documentação usando HTML
 */

// Ler o arquivo Markdown
$markdownContent = file_get_contents('Estrutura_KlubeCash_Documentacao.md');

// Converter Markdown básico para HTML
function markdownToHtml($markdown) {
    // Headers
    $markdown = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $markdown);
    $markdown = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $markdown);
    $markdown = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $markdown);
    $markdown = preg_replace('/^#### (.+)$/m', '<h4>$1</h4>', $markdown);
    
    // Bold and italic
    $markdown = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $markdown);
    $markdown = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $markdown);
    
    // Links
    $markdown = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2">$1</a>', $markdown);
    
    // Code blocks
    $markdown = preg_replace('/```(\w+)?\n(.*?)\n```/s', '<pre><code class="language-$1">$2</code></pre>', $markdown);
    $markdown = preg_replace('/`(.+?)`/', '<code>$1</code>', $markdown);
    
    // Lists
    $markdown = preg_replace('/^- (.+)$/m', '<li>$1</li>', $markdown);
    $markdown = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $markdown);
    
    // Numbered lists
    $markdown = preg_replace('/^\d+\. (.+)$/m', '<li>$1</li>', $markdown);
    
    // Line breaks
    $markdown = preg_replace('/\n\n/', '</p><p>', $markdown);
    $markdown = '<p>' . $markdown . '</p>';
    
    // Clean up empty paragraphs
    $markdown = preg_replace('/<p><\/p>/', '', $markdown);
    $markdown = preg_replace('/<p>(<h[1-6]>.*?<\/h[1-6]>)<\/p>/', '$1', $markdown);
    $markdown = preg_replace('/<p>(<ul>.*?<\/ul>)<\/p>/s', '$1', $markdown);
    $markdown = preg_replace('/<p>(<pre>.*?<\/pre>)<\/p>/s', '$1', $markdown);
    
    return $markdown;
}

$htmlContent = markdownToHtml($markdownContent);

// HTML completo para PDF
$fullHtml = '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentação Estrutura KlubeCash</title>
    <style>
        body {
            font-family: "Times New Roman", serif;
            line-height: 1.6;
            margin: 20mm;
            color: #333;
            font-size: 12pt;
        }
        h1 {
            color: #FF7A00;
            border-bottom: 3px solid #FF7A00;
            padding-bottom: 10px;
            margin-top: 30px;
            font-size: 24pt;
        }
        h2 {
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-top: 25px;
            font-size: 18pt;
        }
        h3 {
            color: #555;
            margin-top: 20px;
            font-size: 14pt;
        }
        h4 {
            color: #666;
            margin-top: 15px;
            font-size: 12pt;
        }
        pre {
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            overflow-x: auto;
            font-family: "Courier New", monospace;
            font-size: 10pt;
            line-height: 1.4;
        }
        code {
            background: #f0f0f0;
            padding: 2px 4px;
            border-radius: 3px;
            font-family: "Courier New", monospace;
            font-size: 10pt;
        }
        pre code {
            background: none;
            padding: 0;
        }
        ul, ol {
            margin: 10px 0;
            padding-left: 30px;
        }
        li {
            margin: 5px 0;
        }
        p {
            margin: 10px 0;
            text-align: justify;
        }
        strong {
            font-weight: bold;
            color: #333;
        }
        .page-break {
            page-break-before: always;
        }
        .toc {
            border: 1px solid #ddd;
            background: #f9f9f9;
            padding: 20px;
            margin: 20px 0;
        }
        .directory-tree {
            font-family: "Courier New", monospace;
            background: #f8f8f8;
            border: 1px solid #ddd;
            padding: 15px;
            line-height: 1.3;
            font-size: 10pt;
        }
        .architecture-diagram {
            text-align: center;
            margin: 20px 0;
            font-family: monospace;
            background: #f5f5f5;
            padding: 15px;
            border: 1px solid #ddd;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        @media print {
            body { margin: 0; }
            .page-break { page-break-before: always; }
        }
    </style>
</head>
<body>
' . $htmlContent . '
</body>
</html>';

// Salvar o HTML
file_put_contents('documentacao_temp.html', $fullHtml);

echo "✅ Arquivo HTML gerado: documentacao_temp.html\n";
echo "📄 Para gerar o PDF, você pode:\n";
echo "1. Abrir o arquivo HTML no navegador e usar 'Imprimir > Salvar como PDF'\n";
echo "2. Usar ferramentas online como html-to-pdf.net\n";
echo "3. Instalar wkhtmltopdf e executar: wkhtmltopdf documentacao_temp.html Estrutura_KlubeCash_Documentacao.pdf\n";

// Tentar criar PDF usando print do navegador
echo "\n🔄 Tentando abrir no navegador padrão...\n";
$htmlPath = realpath('documentacao_temp.html');

if (PHP_OS_FAMILY === 'Windows') {
    exec("start \"\" \"$htmlPath\"");
} elseif (PHP_OS_FAMILY === 'Darwin') {
    exec("open \"$htmlPath\"");
} else {
    exec("xdg-open \"$htmlPath\"");
}

echo "✅ Documentação HTML criada com sucesso!\n";
echo "📍 Localização: " . $htmlPath . "\n";
?>