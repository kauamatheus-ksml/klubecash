<?php
// index.php
// Incluir configurações do sistema
require_once './config/constants.php';
require_once './config/database.php';
// Iniciar sessão
session_start();

// Verificar se o usuário está logado
$isLoggedIn = isset($_SESSION['user_id']);
$userType = $isLoggedIn ? ($_SESSION['user_type'] ?? '') : '';
$userName = $isLoggedIn ? ($_SESSION['user_name'] ?? '') : '';

// Determinar URL do dashboard
$dashboardURL = '';
if ($isLoggedIn) {
    if ($userType === 'admin') {
        $dashboardURL = ADMIN_DASHBOARD_URL;
    } elseif ($userType === 'cliente') {
        $dashboardURL = CLIENT_DASHBOARD_URL;
    } elseif ($userType === 'loja') {
        $dashboardURL = STORE_DASHBOARD_URL;
    }
}


// Buscar lojas parceiras aprovadas
$partnerStores = [];
try {
    $db = Database::getConnection(); // Função do seu arquivo database.php
    // Selecionar apenas lojas aprovadas e, opcionalmente, com logo
    // Você pode adicionar mais critérios, como lojas em destaque, etc.
    $stmt = $db->query("SELECT nome_fantasia, logo FROM lojas WHERE status = 'aprovado' ORDER BY RAND() LIMIT 6");
    $partnerStores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Em caso de erro, pode logar ou tratar como preferir.
    // Por enquanto, a seção de parceiros simplesmente não mostrará lojas.
    error_log("Erro ao buscar lojas parceiras para index.php: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Klube Cash - Seu Programa de Cashback Inteligente</title>
    <link rel="shortcut icon" type="image/x-icon" href="assets/images/icons/KlubeCashLOGO.ico"/>
    <meta name="description" content="Klube Cash - O melhor programa de cashback para suas compras. Ganhe de volta parte do valor em todas as suas compras nas lojas parceiras. Economize de forma inteligente!">
    <meta name="keywords" content="cashback, Klube Cash, economizar, dinheiro de volta, compras, lojas parceiras, programa de fidelidade">
    <meta name="author" content="Klube Cash">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js" defer></script>

    <link rel="stylesheet" href="assets/css/index.css">
</head>
<body>
    <header class="header" id="header">
        <div class="container">
            <nav class="nav">
                <a href="<?php echo SITE_URL; ?>" class="logo" aria-label="Klube Cash - Página Inicial">
                    <img src="assets/images/logolaranja.png" alt="Klube Cash Logo" class="logo-img">
                </a>
                
                <button class="hamburger" id="hamburger" aria-label="Abrir menu de navegação" aria-expanded="false" aria-controls="nav-menu">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </button>
                
                <ul class="nav-menu" id="nav-menu" role="menubar">
                    <li class="nav-item" role="none"><a href="#como-funciona" class="nav-link" role="menuitem">Como Funciona</a></li>
                    <li class="nav-item" role="none"><a href="#beneficios" class="nav-link" role="menuitem">Benefícios</a></li>
                    <li class="nav-item" role="none"><a href="#parceiros" class="nav-link" role="menuitem">Lojas Parceiras</a></li>
                    <li class="nav-item" role="none"><a href="#faq" class="nav-link" role="menuitem">FAQ</a></li>
                    
                    <?php if ($isLoggedIn): ?>
                        <li class="nav-item" role="none"><a href="<?php echo htmlspecialchars($dashboardURL); ?>" class="nav-link" role="menuitem">Meu Painel</a></li>
                        <li class="nav-item" role="none"><a href="<?php echo SITE_URL; ?>/controllers/AuthController.php?action=logout" class="nav-link btn btn-primary" role="menuitem">Sair</a></li>
                    <?php else: ?>
                        <li class="nav-item" role="none"><a href="<?php echo LOGIN_URL; ?>" class="nav-link" role="menuitem">Entrar</a></li>
                        <li class="nav-item" role="none"><a href="<?php echo REGISTER_URL; ?>" class="nav-link btn btn-primary" role="menuitem">Cadastre-se</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    
    <main>
        <section class="hero">
            <div class="hero-bg-shapes"></div>
            <div class="container">
                <div class="hero-content" data-aos="fade-right">
                    <?php if ($isLoggedIn): ?>
                        <h1 class="hero-title">Bem-vindo(a) de volta, <span class="highlight"><?php echo htmlspecialchars($userName); ?></span>!</h1>
                        <p class="hero-subtitle">Continue aproveitando o melhor do cashback em suas compras e economizando de verdade com o Klube Cash.</p>
                        <div class="hero-buttons">
                            <a href="<?php echo htmlspecialchars($dashboardURL); ?>" class="btn btn-white btn-with-icon">
                                <span>Acessar Meu Painel</span>
                                
                            </a>
                            <a href="#parceiros" class="btn btn-outline-white">Ver Lojas</a>
                        </div>
                    <?php else: ?>
                        <h1 class="hero-title">Sua <span class="highlight">Economia Inteligente</span> Começa Aqui!</h1>
                        <p class="hero-subtitle">Junte-se ao Klube Cash e transforme suas compras em dinheiro de volta. Simples, rápido e vantajoso!</p>
                        <div class="hero-buttons">
                            <a href="<?php echo REGISTER_URL; ?>" class="btn btn-white btn-with-icon btn-lg">
                                <span>Criar Conta Grátis</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5l7 7-7 7"></path></svg>
                            </a>
                            <a href="#como-funciona" class="btn btn-outline-white">Como Funciona?</a>
                        </div>
                    <?php endif; ?>
                </div>
                </div>
            <div class="hero-wave">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 180">
                    <path fill="#ffffff" fill-opacity="1" d="M0,128L48,138.7C96,149,192,171,288,170.7C384,171,480,149,576,128C672,107,768,85,864,90.7C960,96,1056,128,1152,133.3C1248,139,1344,117,1392,106.7L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
                </svg>
            </div>
        </section>
        
        <section class="section" id="como-funciona">
            <div class="container">
                <div class="section-title" data-aos="fade-up">
                    <span class="section-subtitle">SIMPLES ASSIM</span>
                    <h2>Como o Klube Cash Funciona?</h2>
                    <div class="line"></div>
                    <p class="section-description">Ganhar dinheiro de volta nunca foi tão fácil. Siga nossos passos simples e comece a economizar hoje mesmo!</p>
                </div>
                
                <div class="steps-container">
                    <div class="step-card" data-aos="fade-up" data-aos-delay="100">
                        <div class="step-icon"><span>1</span></div>
                        <h3 class="step-title">Cadastre-se Gratuitamente</h3>
                        <p>Crie sua conta no Klube Cash em menos de 2 minutos. É rápido, fácil e totalmente grátis!</p>
                    </div>
                    
                    <div class="step-card" data-aos="fade-up" data-aos-delay="250">
                        <div class="step-icon"><span>2</span></div>
                        <h3 class="step-title">Compre e Aproveite</h3>
                        <p>Escolha suas lojas parceiras favoritas, compre online ou em lojas físicas e identifique-se como membro Klube Cash.</p>
                    </div>
                    
                    <div class="step-card" data-aos="fade-up" data-aos-delay="400">
                        <div class="step-icon"><span>3</span></div>
                        <h3 class="step-title">Receba Seu Cashback</h3>
                        <p>Parte do valor da sua compra volta para você! Acompanhe seu saldo e resgate como quiser.</p>
                    </div>
                </div>
            </div>
        </section>
        
        <section class="section benefits" id="beneficios">
            <div class="container">
                <div class="section-title" data-aos="fade-up">
                    <span class="section-subtitle">VANTAGENS EXCLUSIVAS</span>
                    <h2>Por Que Escolher o Klube Cash?</h2>
                    <div class="line"></div>
                    <p class="section-description">Descubra os benefícios que tornam o Klube Cash a melhor escolha para suas economias.</p>
                </div>
                
                <div class="benefits-container">
                    <div class="benefit-card" data-aos="zoom-in-up" data-aos-delay="100">
                        <div class="benefit-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                        </div>
                        <h3 class="benefit-title">Cashback Real</h3>
                        <p>Dinheiro de verdade de volta na sua conta, sem truques ou pontos que expiram rapidamente.</p>
                    </div>
                    
                    <div class="benefit-card" data-aos="zoom-in-up" data-aos-delay="250">
                        <div class="benefit-icon">
                           <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
                        </div>
                        <h3 class="benefit-title">Ampla Rede de Lojas</h3>
                        <p>Milhares de lojas parceiras em diversas categorias para você aproveitar o cashback onde preferir.</p>
                    </div>
                    
                    <div class="benefit-card" data-aos="zoom-in-up" data-aos-delay="400">
                        <div class="benefit-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        </div>
                        <h3 class="benefit-title">Seguro e Confiável</h3>
                        <p>Plataforma segura para suas transações e dados. Sua tranquilidade é nossa prioridade.</p>
                    </div>
                </div>
            </div>
        </section>
        
        <section class="cta">
            <div class="cta-content" data-aos="zoom-in">
                <h2>Pronto para Começar a Economizar?</h2>
                <p>Não perca mais tempo! Cadastre-se gratuitamente no Klube Cash e veja seu dinheiro render mais a cada compra.</p>
                <a href="<?php echo REGISTER_URL; ?>" class="btn btn-white btn-lg btn-with-icon">
                    <span>Quero Economizar Agora!</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5l7 7-7 7"></path></svg>
                </a>
            </div>
        </section>
        
        <section class="section" id="parceiros">
            <div class="container">
                <div class="section-title" data-aos="fade-up">
                    <span class="section-subtitle">NOSSOS PARCEIROS</span>
                    <h2>Onde Usar Seu Klube Cash</h2>
                    <div class="line"></div>
                    <p class="section-description">Confira algumas das lojas incríveis onde você pode ganhar cashback.</p>
                </div>
                
                <div class="partners-grid" data-aos="fade-up" data-aos-delay="100">
                    <?php if (!empty($partnerStores)): ?>
                        <?php foreach ($partnerStores as $store): ?>
                        <div class="partner-card">
                            <div class="partner-logo-container">
                                <?php if (!empty($store['logo'])): ?>
                                    <div class="partner-logo"><?php echo htmlspecialchars(strtoupper(substr($store['nome_fantasia'], 0, 1))); ?></div> <?php else: ?>
                                    <div class="partner-logo"><?php echo htmlspecialchars(strtoupper(substr($store['nome_fantasia'], 0, 1))); ?></div> <?php endif; ?>
                            </div>
                            <h4><?php echo htmlspecialchars($store['nome_fantasia']); ?></h4>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="grid-column: 1 / -1; text-align: center; color: var(--medium-gray);">Nenhuma loja parceira encontrada no momento.</p>
                    <?php endif; ?>
                </div>
                
                <div class="partners-cta" data-aos="fade-up" data-aos-delay="200">
                    <a href="<?php echo STORE_REGISTER_URL; ?>" class="btn btn-primary btn-with-icon">
                        <span>Quero Ser um Parceiro</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5l7 7-7 7"></path></svg>
                    </a>
                </div>
            </div>
        </section>
        
        <section class="section faq" id="faq">
            <div class="container">
                <div class="section-title" data-aos="fade-up">
                    <span class="section-subtitle">AINDA TEM DÚVIDAS?</span>
                    <h2>Perguntas Frequentes</h2>
                    <div class="line"></div>
                    <p class="section-description">Encontre respostas para as dúvidas mais comuns sobre o Klube Cash.</p>
                </div>
                
                <div class="accordion" data-aos="fade-up" data-aos-delay="100">
                    <div class="accordion-item">
                        <button class="accordion-header" aria-expanded="false">
                            <span>O que é cashback e como funciona?</span>
                            <span class="accordion-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            </span>
                        </button>
                        <div class="accordion-content" aria-hidden="true">
                            <div class="accordion-content-inner">
                                <p>Cashback significa "dinheiro de volta". Ao comprar em lojas parceiras do Klube Cash, uma porcentagem do valor gasto retorna para sua conta Klube Cash. Você pode usar esse saldo para novas compras, transferir para sua conta bancária ou outras opções disponíveis.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <button class="accordion-header" aria-expanded="false">
                            <span>Como faço para receber meu cashback?</span>
                            <span class="accordion-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            </span>
                        </button>
                        <div class="accordion-content" aria-hidden="true">
                            <div class="accordion-content-inner">
                                <p>Após realizar uma compra em uma loja parceira e se identificar como membro Klube Cash, o valor do cashback será creditado em sua conta Klube Cash. O tempo de confirmação pode variar conforme a loja. Você pode acompanhar tudo pelo seu painel.</p>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <button class="accordion-header" aria-expanded="false">
                            <span>O cadastro no Klube Cash é gratuito?</span>
                            <span class="accordion-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            </span>
                        </button>
                        <div class="accordion-content" aria-hidden="true">
                            <div class="accordion-content-inner">
                                <p>Sim! O cadastro no Klube Cash é 100% gratuito. Não cobramos nenhuma taxa de adesão ou mensalidade para você aproveitar os benefícios do cashback.</p>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <button class="accordion-header" aria-expanded="false">
                            <span>Como posso usar meu saldo de cashback?</span>
                            <span class="accordion-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            </span>
                        </button>
                        <div class="accordion-content" aria-hidden="true">
                            <div class="accordion-content-inner">
                                <p>Você pode utilizar seu saldo de cashback acumulado para obter descontos em novas compras nas lojas parceiras, transferir para sua conta bancária (verifique o valor mínimo para saque) ou outras opções que podem ser oferecidas em nossa plataforma.</p>
                            </div>
                        </div>
                    </div>

                     <div class="accordion-item">
                        <button class="accordion-header" aria-expanded="false">
                            <span>Como minha loja pode se tornar parceira?</span>
                            <span class="accordion-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            </span>
                        </button>
                        <div class="accordion-content" aria-hidden="true">
                            <div class="accordion-content-inner">
                                <p>É simples! Clique no botão "Quero Ser um Parceiro" em nosso site, preencha o formulário de cadastro da sua loja e aguarde o contato da nossa equipe. Oferecer cashback é uma ótima forma de atrair e fidelizar clientes.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    <footer class="footer">
        <div class="footer-wave">
           
        </div>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-column">
                    <h3>Sobre o Klube Cash</h3>
                    <p>O Klube Cash é seu aliado na economia diária. Oferecemos uma plataforma simples e eficaz para você receber dinheiro de volta em suas compras em uma vasta rede de lojas parceiras.</p>
                    <div class="social-icons">
                        <a href="#" class="social-icon" aria-label="Facebook do Klube Cash">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                        </a>
                        <a href="https://www.instagram.com/klubecash/" class="social-icon" aria-label="Instagram do Klube Cash" target="_blank" rel="noopener noreferrer">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                        </a>
                        <a href="#" class="social-icon" aria-label="Twitter do Klube Cash">
                           <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path></svg>
                        </a>
                    </div>
                </div>
                
                <div class="footer-column">
                    <h3>Links Rápidos</h3>
                    <ul class="footer-links">
                        <li class="footer-link"><a href="#como-funciona">Como Funciona</a></li>
                        <li class="footer-link"><a href="#beneficios">Benefícios</a></li>
                        <li class="footer-link"><a href="#parceiros">Lojas Parceiras</a></li>
                        <li class="footer-link"><a href="#faq">FAQ</a></li>
                        <li class="footer-link"><a href="<?php echo STORE_REGISTER_URL; ?>">Seja um Parceiro</a></li>
                        <li class="footer-link"><a href="#">Termos de Uso</a></li>
                        <li class="footer-link"><a href="#">Política de Privacidade</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Contato</h3>
                    <div class="contact-info">
                        <span class="contact-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                        </span>
                        <a href="mailto:contato@klubecash.com" style="color: rgba(255,255,255,0.7);">contato@klubecash.com</a>
                    </div>
                    <div class="contact-info">
                        <span class="contact-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                        </span>
                        <span>(34) 9999-9999</span>
                    </div>
                    <div class="contact-info">
                        <span class="contact-icon">
                           <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        </span>
                        <span>Patos de Minas, MG - Brasil</span>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Klube Cash. Todos os direitos reservados. Plataforma desenvolvida com ❤️.</p>
            </div>
        </div>
    </footer>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Header Scrolled
            const header = document.getElementById('header'); // Usando o ID do seu HTML
            if (header) {
                window.addEventListener('scroll', function () {
                    if (window.scrollY > 50) {
                        header.classList.add('klube-scrolled');
                    } else {
                        header.classList.remove('klube-scrolled');
                    }
                });
            }

            // Hamburger Menu
            const hamburger = document.getElementById('hamburger'); // Usando o ID do seu HTML
            const navMenu = document.getElementById('nav-menu'); // Usando o ID do seu HTML

            if (hamburger && navMenu) {
                hamburger.addEventListener('click', function () {
                    hamburger.classList.toggle('klube-active');
                    navMenu.classList.toggle('klube-active');
                    // Travar scroll do body quando o menu estiver aberto
                    document.body.style.overflow = navMenu.classList.contains('klube-active') ? 'hidden' : '';
                    // Mudar aria-expanded
                    const isExpanded = hamburger.getAttribute('aria-expanded') === 'true' || false;
                    hamburger.setAttribute('aria-expanded', !isExpanded);
                });

                // Fechar menu ao clicar em um link (opcional)
                const navLinks = navMenu.querySelectorAll('.nav-link');
                navLinks.forEach(link => {
                    link.addEventListener('click', () => {
                        if (navMenu.classList.contains('klube-active')) {
                            hamburger.classList.remove('klube-active');
                            navMenu.classList.remove('klube-active');
                            document.body.style.overflow = '';
                            hamburger.setAttribute('aria-expanded', 'false');
                        }
                    });
                });
            }

            // Accordion
            const accordionItems = document.querySelectorAll('.accordion-item');
            if (accordionItems.length > 0) {
                accordionItems.forEach(item => {
                    const header = item.querySelector('.accordion-header');
                    const content = item.querySelector('.accordion-content');

                    if (header && content) {
                        header.addEventListener('click', () => {
                            const isActive = item.classList.contains('klube-active');
                            
                            // Fechar todos os outros itens (opcional, se quiser apenas um aberto)
                            // accordionItems.forEach(otherItem => {
                            //     if (otherItem !== item && otherItem.classList.contains('klube-active')) {
                            //         otherItem.classList.remove('klube-active');
                            //         otherItem.querySelector('.accordion-content').style.maxHeight = null;
                            //         otherItem.querySelector('.accordion-header').setAttribute('aria-expanded', 'false');
                            //     }
                            // });

                            item.classList.toggle('klube-active');
                            header.setAttribute('aria-expanded', !isActive);
                            
                            if (item.classList.contains('klube-active')) {
                                content.style.maxHeight = content.scrollHeight + "px";
                                content.setAttribute('aria-hidden', 'false');
                            } else {
                                content.style.maxHeight = null;
                                content.setAttribute('aria-hidden', 'true');
                            }
                        });
                    }
                });
            }

            // Animação de entrada suave (AOS já faz isso, mas como exemplo)
            // Se você remover AOS, pode usar algo assim com Intersection Observer:
            // const animatedElements = document.querySelectorAll('[data-aos]');
            // const observer = new IntersectionObserver((entries) => {
            //     entries.forEach(entry => {
            //         if (entry.isIntersecting) {
            //             entry.target.style.opacity = 1;
            //             entry.target.style.transform = 'translateY(0)';
            //             // Adicionar classes de animação aqui
            //             observer.unobserve(entry.target); // Para animar apenas uma vez
            //         }
            //     });
            // }, { threshold: 0.1 });

            // animatedElements.forEach(el => {
            //     el.style.opacity = 0;
            //     el.style.transform = 'translateY(20px)';
            //     el.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
            //     observer.observe(el);
            // });
        });
</script>
    
</body>
</html>