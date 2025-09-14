<?php
// Exemplo de como usar a sidebar em uma página de loja
require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';

// Verificar autenticação
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'loja') {
    header('Location: ' . LOGIN_URL);
    exit;
}

// Definir menu ativo
$menuAtivo = 'dashboard'; // ou 'nova-venda', 'funcionarios', etc.
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    
    <!-- CSS específico da página -->
    <style>
        .pagina-conteudo {
            padding: 30px;
            background: #f8fafc;
            min-height: 100vh;
        }
        
        .titulo-pagina {
            font-size: 28px;
            font-weight: 700;
            color: #212529;
            margin-bottom: 8px;
        }
        
        .subtitulo-pagina {
            font-size: 16px;
            color: #6c757d;
            margin-bottom: 30px;
        }
        
        .card-exemplo {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Incluir a sidebar -->
    <?php include_once '../components/sidebar-lojista.php'; ?>
    
    <!-- Conteúdo principal da página -->
    <main class="pagina-conteudo">
        <div class="cabecalho-pagina">
            <h1 class="titulo-pagina">Dashboard</h1>
            <p class="subtitulo-pagina">Bem-vindo ao seu painel de controle</p>
        </div>
        
        <div class="card-exemplo">
            <h3>Exemplo de Conteúdo</h3>
            <p>Esta é uma página de exemplo mostrando como usar a sidebar lojista.</p>
            <p>A sidebar se ajusta automaticamente e não interfere no layout da página.</p>
        </div>
        
        <div class="card-exemplo">
            <h3>Funcionalidades Disponíveis</h3>
            <ul>
                <li>Sidebar colapsável com Ctrl+B</li>
                <li>Responsiva para todos os dispositivos</li>
                <li>Menu mobile com overlay</li>
                <li>Estado persistente no localStorage</li>
                <li>Ajuste automático do conteúdo principal</li>
            </ul>
        </div>
    </main>
    
    <!-- Scripts adicionais se necessário -->
    <script>
        // Exemplo de uso da API da sidebar
        document.addEventListener('DOMContentLoaded', function() {
            // Aguardar inicialização da sidebar
            setTimeout(() => {
                if (window.sidebarLojista) {
                    // Exemplo: adicionar badge de notificação
                    window.sidebarLojista.addBadge('pendentes-pagamento', 3);
                    
                    // Exemplo: escutar mudanças de estado
                    window.addEventListener('sidebarLojistaToggle', (e) => {
                        console.log('Sidebar toggled:', e.detail.collapsed);
                    });
                }
            }, 100);
        });
    </script>
</body>
</html>