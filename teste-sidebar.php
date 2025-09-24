<?php
// Simular sessão de lojista para teste
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_type'] = 'loja';
$_SESSION['user_name'] = 'João Silva';
$_SESSION['user_email'] = 'joao@minhaloja.com';

// Definir constantes básicas se não existirem
if (!defined('SITE_URL')) {
    define('SITE_URL', 'http://localhost/klube-cash');
}
if (!defined('STORE_DASHBOARD_URL')) {
    define('STORE_DASHBOARD_URL', '/store/dashboard');
    define('STORE_REGISTER_TRANSACTION_URL', '/store/registrar-transacao');
    define('STORE_PAYMENT_HISTORY_URL', '/store/historico-pagamentos');
    define('STORE_PENDING_TRANSACTIONS_URL', '/store/transacoes-pendentes');
    define('STORE_PROFILE_URL', '/store/perfil');
    define('LOGOUT_URL', '/logout');
}

$activeMenu = 'dashboard';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Sidebar Lojista - Klube Cash</title>
    
    <!-- CSS da Sidebar -->
    <link rel="stylesheet" href="/assets/css/sidebar-lojista.css">
    
    <!-- CSS de teste para o conteúdo principal -->
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            color: #1f2937;
        }
        
        .conteudo-teste {
            min-height: 100vh;
            padding: 40px;
            transition: margin-left 0.3s ease;
        }
        
        .card-teste {
            background: white;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 24px;
        }
        
        .titulo-teste {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 16px;
        }
        
        .subtitulo-teste {
            font-size: 20px;
            font-weight: 600;
            color: #374151;
            margin: 24px 0 12px;
        }
        
        .lista-teste {
            list-style: none;
            padding: 0;
        }
        
        .item-teste {
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .item-teste:last-child {
            border-bottom: none;
        }
        
        .botao-teste {
            background: #F1780C;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            cursor: pointer;
            margin: 8px;
            transition: all 0.2s;
        }
        
        .botao-teste:hover {
            background: #ff7700;
            transform: translateY(-1px);
        }
        
        .grid-teste {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }
        
        .status-sidebar {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #1f2937;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
            z-index: 1000;
        }
        
        @media (max-width: 768px) {
            .conteudo-teste {
                padding: 20px;
            }
            
            .card-teste {
                padding: 24px;
            }
            
            .titulo-teste {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <!-- Incluir Sidebar -->
    <?php include 'views/components/sidebar-lojista-responsiva.php'; ?>
    
    <!-- Conteúdo Principal -->
    <main class="conteudo-teste main-content">
        <div class="card-teste">
            <h1 class="titulo-teste">🧪 Teste da Sidebar Lojista</h1>
            <p>Esta página testa a sidebar responsiva do sistema Klube Cash para lojistas.</p>
            
            <h2 class="subtitulo-teste">Funcionalidades Testadas</h2>
            <ul class="lista-teste">
                <li class="item-teste">✅ Menu colapsável no desktop</li>
                <li class="item-teste">✅ Menu deslizante no mobile</li>
                <li class="item-teste">✅ Atalho Ctrl+B para alternar</li>
                <li class="item-teste">✅ Ajuste automático do conteúdo</li>
                <li class="item-teste">✅ Design responsivo</li>
                <li class="item-teste">✅ Overlay no mobile</li>
                <li class="item-teste">✅ Estados ativos dos menus</li>
                <li class="item-teste">✅ Informações do usuário</li>
            </ul>
            
            <h2 class="subtitulo-teste">Controles de Teste</h2>
            <button class="botao-teste" onclick="testarColapsar()">Colapsar Sidebar</button>
            <button class="botao-teste" onclick="testarExpandir()">Expandir Sidebar</button>
            <button class="botao-teste" onclick="testarMenuAtivo()">Alterar Menu Ativo</button>
            <button class="botao-teste" onclick="testarContador()">Testar Contadores</button>
            <button class="botao-teste" onclick="simularMobile()">Simular Mobile</button>
        </div>
        
        <div class="grid-teste">
            <div class="card-teste">
                <h3 class="subtitulo-teste">Instruções de Teste</h3>
                <ul class="lista-teste">
                    <li class="item-teste">Use <strong>Ctrl+B</strong> para alternar a sidebar</li>
                    <li class="item-teste">Redimensione a janela para testar responsividade</li>
                    <li class="item-teste">No mobile, toque no botão hambúrguer</li>
                    <li class="item-teste">Clique fora da sidebar mobile para fechar</li>
                </ul>
            </div>
            
            <div class="card-teste">
                <h3 class="subtitulo-teste">Status do Sistema</h3>
                <ul class="lista-teste">
                    <li class="item-teste">Usuário: <strong>João Silva</strong></li>
                    <li class="item-teste">Tipo: <strong>Lojista</strong></li>
                    <li class="item-teste">Menu Ativo: <strong id="menu-ativo-display">Dashboard</strong></li>
                    <li class="item-teste">Dispositivo: <strong id="dispositivo-display">Desktop</strong></li>
                </ul>
            </div>
        </div>
        
        <!-- Mais conteúdo para testar scroll -->
        <div class="card-teste">
            <h2 class="subtitulo-teste">Conteúdo Adicional para Teste</h2>
            <p>Este conteúdo adicional serve para testar como a sidebar se comporta com diferentes alturas de página e situações de scroll.</p>
            
            <?php for($i = 1; $i <= 5; $i++): ?>
            <div style="margin: 20px 0; padding: 20px; background: #f8fafc; border-radius: 8px;">
                <h4>Seção de Teste <?php echo $i; ?></h4>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.</p>
            </div>
            <?php endfor; ?>
        </div>
    </main>
    
    <!-- Status da Sidebar -->
    <div class="status-sidebar" id="statusSidebar">
        Sidebar: Expandida | Desktop
    </div>
    
    <!-- JavaScript da Sidebar -->
    <script src="/assets/js/sidebar-lojista.js"></script>
    
    <!-- JavaScript de teste -->
    <script>
        // Funções de teste
        function testarColapsar() {
            if (window.sidebarLojistaResponsiva) {
                window.sidebarLojistaResponsiva.colapsar();
            }
        }
        
        function testarExpandir() {
            if (window.sidebarLojistaResponsiva) {
                window.sidebarLojistaResponsiva.expandir();
            }
        }
        
        function testarMenuAtivo() {
            const menus = ['dashboard', 'nova-venda', 'funcionarios', 'pagamentos', 'perfil'];
            const menuAleatorio = menus[Math.floor(Math.random() * menus.length)];
            
            if (window.sidebarLojistaResponsiva) {
                window.sidebarLojistaResponsiva.definirMenuAtivo(menuAleatorio);
                document.getElementById('menu-ativo-display').textContent = menuAleatorio;
            }
        }
        
        function testarContador() {
            if (window.sidebarLojistaResponsiva) {
                const contador = Math.floor(Math.random() * 10) + 1;
                window.sidebarLojistaResponsiva.atualizarContador('pendentes-pagamento', contador);
            }
        }
        
        function simularMobile() {
            const largura = window.innerWidth <= 768 ? '1200px' : '600px';
            document.body.style.width = largura;
            window.dispatchEvent(new Event('resize'));
            
            setTimeout(() => {
                document.body.style.width = '';
                window.dispatchEvent(new Event('resize'));
            }, 3000);
        }
        
        // Monitorar eventos da sidebar
        window.addEventListener('sidebarLojistaToggle', function(e) {
            const status = document.getElementById('statusSidebar');
            const dispositivo = document.getElementById('dispositivo-display');
            
            const estadoSidebar = e.detail.colapsada ? 'Colapsada' : 'Expandida';
            const tipoDispositivo = e.detail.mobile ? 'Mobile' : 'Desktop';
            
            status.textContent = `Sidebar: ${estadoSidebar} | ${tipoDispositivo}`;
            dispositivo.textContent = tipoDispositivo;
        });
        
        // Atualizar status do dispositivo
        function atualizarStatusDispositivo() {
            const dispositivo = document.getElementById('dispositivo-display');
            const status = document.getElementById('statusSidebar');
            const isMobile = window.innerWidth <= 768;
            
            dispositivo.textContent = isMobile ? 'Mobile' : 'Desktop';
            
            // Atualizar status da sidebar também
            const sidebarElement = document.getElementById('sidebarLojistaResponsiva');
            const isColapsed = sidebarElement && sidebarElement.classList.contains('colapsada');
            const estadoSidebar = isColapsed ? 'Colapsada' : 'Expandida';
            
            status.textContent = `Sidebar: ${estadoSidebar} | ${isMobile ? 'Mobile' : 'Desktop'}`;
        }
        
        // Monitorar redimensionamento
        window.addEventListener('resize', atualizarStatusDispositivo);
        
        // Status inicial
        document.addEventListener('DOMContentLoaded', atualizarStatusDispositivo);
        
        console.log('🧪 Página de teste da sidebar carregada com sucesso!');
        console.log('📱 Use Ctrl+B para alternar a sidebar');
        console.log('🔧 Use os botões de teste para experimentar as funcionalidades');
    </script>
</body>
</html>