<?php
// index.php
// Incluir configurações do sistema
require_once './config/constants.php';

// Iniciar sessão
session_start();

// Verificar se o usuário está logado
$isLoggedIn = isset($_SESSION['user_id']);
$userType = $isLoggedIn ? $_SESSION['user_type'] : '';

// Redirecionar para dashboard se já estiver logado
if ($isLoggedIn) {
    if ($userType === 'admin') {
        header('Location: ' . ADMIN_DASHBOARD_URL);
        exit;
    } elseif ($userType === 'cliente') {
        header('Location: ' . CLIENT_DASHBOARD_URL);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Klube Cash - Programa de Cashback</title>
    <link rel="shortcut icon" type="image/jpg" href="assets/images/icons/KlubeCashLOGO.ico"/>
    <meta name="description" content="Klube Cash - O melhor programa de cashback para suas compras. Ganhe de volta parte do valor em todas as suas compras nas lojas parceiras.">
    
    <style>
        :root {
            --primary-color: #FF7A00;
            --primary-dark: #E06E00;
            --primary-light: #FFF0E6;
            --secondary-color: #333333;
            --white: #FFFFFF;
            --light-gray: #F5F5F5;
            --dark-gray: #333333;
            --medium-gray: #666666;
            --success-color: #4CAF50;
            --border-radius: 15px;
            --shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            --font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Reset e estilos gerais */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: var(--font-family);
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        body {
            background-color: #FFF9F2;
            overflow-x: hidden;
            color: var(--dark-gray);
            line-height: 1.6;
        }
        
        a {
            text-decoration: none;
            color: var(--primary-color);
            transition: color 0.3s;
        }
        
        a:hover {
            color: var(--primary-dark);
        }
        
        h1, h2, h3, h4, h5, h6 {
            margin-bottom: 1rem;
            color: var(--secondary-color);
        }
        
        /* Header */
        .header {
            background-color: var(--white);
            box-shadow: var(--shadow);
            padding: 1rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
        }
        
        .logo img {
            height: 40px;
            margin-right: 10px;
        }
        
        .logo-text {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .nav-menu {
            display: flex;
            flex-direction: row;
            flex-wrap: nowrap;
            align-content: normal;
            justify-content: normal;
            align-items: center;

            list-style: none;
        }
        
        .nav-item {
            margin-left: 1.5rem;
        }
        
        .nav-link {
            color: var(--secondary-color);
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-link:hover {
            color: var(--primary-color);
        }
        
        .nav-link.btn {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 0.5rem 1.5rem;
            border-radius: 30px;
            transition: background-color 0.3s;
        }
        
        .nav-link.btn:hover {
            background-color: var(--primary-dark);
            color: var(--white);
        }
        
        .nav-link.btn-outline {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            padding: 0.5rem 1.5rem;
            border-radius: 30px;
            transition: all 0.3s;
        }
        
        .nav-link.btn-outline:hover {
            background-color: var(--primary-color);
            color: var(--white);
        }
        
        .hamburger {
            display: none;
            cursor: pointer;
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #FF7A00, #FF9A40);
            color: var(--white);
            padding: 8rem 0 6rem;
            position: relative;
            overflow: hidden;
        }
        
        .hero-content {
            max-width: 600px;
            position: relative;
            z-index: 1;
        }
        
        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            color: var(--white);
        }
        
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .hero-buttons {
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            display: inline-block;
            padding: 0.8rem 2rem;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            font-size: 1rem;
        }
        
        .btn-white {
            background-color: var(--white);
            color: var(--primary-color);
        }
        
        .btn-white:hover {
            background-color: rgba(255, 255, 255, 0.9);
            transform: translateY(-3px);
        }
        
        .btn-outline-white {
            border: 2px solid var(--white);
            color: var(--white);
            background-color: transparent;
        }
        
        .btn-outline-white:hover {
            background-color: var(--white);
            color: var(--primary-color);
            transform: translateY(-3px);
        }
        
        .hero-image {
            position: absolute;
            right: -5%;
            top: 50%;
            transform: translateY(-50%);
            width: 50%;
            max-width: 600px;
            z-index: 0;
        }
        
        /* Como Funciona */
        .section {
            padding: 5rem 0;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .section-title h2 {
            font-size: 2.5rem;
            position: relative;
            display: inline-block;
            padding-bottom: 1rem;
        }
        
        .section-title h2::after {
            content: "";
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background-color: var(--primary-color);
        }
        
        .steps-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .step-card {
            background-color: var(--white);
            box-shadow: var(--shadow);
            border-radius: var(--border-radius);
            padding: 2rem;
            text-align: center;
            transition: transform 0.3s;
        }
        
        .step-card:hover {
            transform: translateY(-10px);
        }
        
        .step-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            background-color: var(--primary-light);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 2rem;
            color: var(--primary-color);
        }
        
        .step-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        /* Benefícios */
        .benefits {
            background-color: var(--light-gray);
        }
        
        .benefits-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .benefit-card {
            background-color: var(--white);
            box-shadow: var(--shadow);
            border-radius: var(--border-radius);
            padding: 2rem;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .benefit-card:hover {
            transform: translateY(-5px);
        }
        
        .benefit-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background-color: var(--primary-color);
        }
        
        .benefit-icon {
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .benefit-title {
            font-size: 1.3rem;
            margin-bottom: 1rem;
        }
        
        /* CTA Section */
        .cta {
            background: linear-gradient(135deg, #FF7A00, #FF9A40);
            color: var(--white);
            text-align: center;
            padding: 5rem 0;
        }
        
        .cta h2 {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            color: var(--white);
        }
        
        .cta p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Lojas Parceiras */
        .partners-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 2rem;
        }
        
        .partner-card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s;
        }
        
        .partner-card:hover {
            transform: translateY(-5px);
        }
        
        .partner-logo {
            width: 100%;
            height: 100px;
            object-fit: contain;
            margin-bottom: 1rem;
        }
        
        /* FAQ */
        .faq {
            background-color: var(--light-gray);
        }
        
        .accordion {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .accordion-item {
            background-color: var(--white);
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        
        .accordion-header {
            padding: 1.5rem;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .accordion-icon {
            transition: transform 0.3s;
        }
        
        .accordion-content {
            padding: 0 1.5rem;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        
        .accordion-content-inner {
            padding-bottom: 1.5rem;
        }
        
        .accordion-item.active .accordion-icon {
            transform: rotate(180deg);
        }
        
        .accordion-item.active .accordion-content {
            max-height: 500px;
        }
        
        /* Footer */
        .footer {
            background-color: var(--secondary-color);
            color: var(--white);
            padding: 4rem 0 2rem;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .footer-column h3 {
            color: var(--white);
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.8rem;
        }
        
        .footer-column h3::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 3px;
            background-color: var(--primary-color);
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-link {
            margin-bottom: 0.8rem;
        }
        
        .footer-link a {
            color: #ccc;
            transition: color 0.3s;
        }
        
        .footer-link a:hover {
            color: var(--primary-color);
        }
        
        .contact-info {
            margin-bottom: 0.8rem;
            display: flex;
            align-items: flex-start;
        }
        
        .contact-icon {
            margin-right: 10px;
            color: var(--primary-color);
        }
        
        .social-icons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .social-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: center;
            align-items: center;
            transition: all 0.3s;
        }
        
        .social-icon:hover {
            background-color: var(--primary-color);
            transform: translateY(-3px);
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Responsividade */
        @media (max-width: 992px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero-image {
                opacity: 0.3;
                width: 70%;
            }
        }
        
        @media (max-width: 768px) {
            .hamburger {
                display: block;
            }
            
            .nav-menu {
                position: fixed;
                top: 70px;
                left: -100%;
                width: 100%;
                background-color: var(--white);
                flex-direction: column;
                align-items: center;
                padding: 2rem 0;
                box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
                transition: all 0.3s;
            }
            
            .nav-menu.active {
                left: 0;
            }
            
            .nav-item {
                margin: 1rem 0;
            }
            
            .hero {
                text-align: center;
                padding: 7rem 0 5rem;
            }
            
            .hero-content {
                margin: 0 auto;
            }
            
            .hero-buttons {
                justify-content: center;
            }
            
            .hero-image {
                display: none;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
        }
        
        @media (max-width: 576px) {
            .hero h1 {
                font-size: 2rem;
            }
            
            .section {
                padding: 3rem 0;
            }
            
            .hero-buttons {
                flex-direction: column;
                gap: 1rem;
            }
            
            .btn {
                display: block;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header" id="header">
        <div class="container">
            <nav class="nav">
                <a href="#" class="logo">
                    <img src="assets/images/logolaranja.png" alt="Klube Cash Logo">
                    
                </a>
                
                <div class="hamburger" id="hamburger">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </div>
                
                <ul class="nav-menu" id="nav-menu">
                    <li class="nav-item"><a href="#como-funciona" class="nav-link">Como Funciona</a></li>
                    <li class="nav-item"><a href="#beneficios" class="nav-link">Benefícios</a></li>
                    <li class="nav-item"><a href="#parceiros" class="nav-link">Lojas Parceiras</a></li>
                    <li class="nav-item"><a href="#faq" class="nav-link">FAQ</a></li>
                    <li class="nav-item"><a href="<?php echo LOGIN_URL; ?>" class="nav-link">Entrar</a></li>
                    <li class="nav-item"><a href="<?php echo REGISTER_URL; ?>" class="nav-link btn">Cadastre-se</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Ganhe dinheiro de volta em cada compra</h1>
                <p>Junte-se a milhares de pessoas que economizam com o Klube Cash. Receba cashback em todas as compras nas lojas parceiras.</p>
                <div class="hero-buttons">
                    <a href="<?php echo REGISTER_URL; ?>" class="btn btn-white">Começar Agora</a>
                    <a href="#como-funciona" class="btn btn-outline-white">Saiba Mais</a>
                </div>
            </div>
            <img src="assets/images/hero-image.png" alt="Pessoas economizando com cashback" class="hero-image">
        </div>
    </section>
    
    <!-- Como Funciona -->
    <section class="section" id="como-funciona">
        <div class="container">
            <div class="section-title">
                <h2>Como Funciona</h2>
                <p>Ganhar cashback nunca foi tão fácil</p>
            </div>
            
            <div class="steps-container">
                <div class="step-card">
                    <div class="step-icon">1</div>
                    <h3 class="step-title">Cadastre-se</h3>
                    <p>Crie sua conta gratuitamente no Klube Cash em menos de 2 minutos.</p>
                </div>
                
                <div class="step-card">
                    <div class="step-icon">2</div>
                    <h3 class="step-title">Compre nas Lojas Parceiras</h3>
                    <p>Faça suas compras normalmente nas lojas parceiras online ou físicas.</p>
                </div>
                
                <div class="step-card">
                    <div class="step-icon">3</div>
                    <h3 class="step-title">Receba Cashback</h3>
                    <p>O valor do cashback será creditado automaticamente em sua conta Klube Cash.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Benefícios -->
    <section class="section benefits" id="beneficios">
        <div class="container">
            <div class="section-title">
                <h2>Benefícios</h2>
                <p>Por que escolher o Klube Cash?</p>
            </div>
            
            <div class="benefits-container">
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M16 12l-4 4-4-4"></path>
                            <path d="M12 8v8"></path>
                        </svg>
                    </div>
                    <h3 class="benefit-title">Sem Valor Mínimo</h3>
                    <p>Não há valor mínimo para receber o cashback. Ganhe de volta em cada compra.</p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <h3 class="benefit-title">Rápido e Fácil</h3>
                    <p>O cashback é processado rapidamente e você pode acompanhar tudo pelo seu painel.</p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                        </svg>
                    </div>
                    <h3 class="benefit-title">Milhares de Lojas</h3>
                    <p>Compre nas melhores lojas e marcas parceiras em todo o Brasil.</p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                            <path d="M7 15h0"></path>
                            <path d="M2 9.5h20"></path>
                        </svg>
                    </div>
                    <h3 class="benefit-title">Diversas Formas de Resgate</h3>
                    <p>Transfira para sua conta, converta em produtos ou use em novas compras.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <h2>Comece a economizar hoje mesmo!</h2>
            <p>Junte-se a milhares de pessoas que já economizaram com o Klube Cash. Cadastre-se gratuitamente e comece a ganhar cashback em suas compras.</p>
            <a href="<?php echo REGISTER_URL; ?>" class="btn btn-white">Criar Conta Grátis</a>
        </div>
    </section>
    
    <!-- Lojas Parceiras -->
    <section class="section" id="parceiros">
        <div class="container">
            <div class="section-title">
                <h2>Lojas Parceiras</h2>
                <p>Algumas das principais lojas que fazem parte do nosso programa</p>
            </div>
            
            <div class="partners-grid">
                <!-- As logos seriam carregadas dinamicamente do banco de dados -->
                <div class="partner-card">
                    <div style="width:100%; height:100px; background:#f0f0f0; display:flex; align-items:center; justify-content:center; color:#999; font-weight:bold;">LOGO</div>
                    <h4>Loja 1</h4>
                </div>
                <div class="partner-card">
                    <div style="width:100%; height:100px; background:#f0f0f0; display:flex; align-items:center; justify-content:center; color:#999; font-weight:bold;">LOGO</div>
                    <h4>Loja 2</h4>
                </div>
                <div class="partner-card">
                    <div style="width:100%; height:100px; background:#f0f0f0; display:flex; align-items:center; justify-content:center; color:#999; font-weight:bold;">LOGO</div>
                    <h4>Loja 3</h4>
                </div>
                <div class="partner-card">
                    <div style="width:100%; height:100px; background:#f0f0f0; display:flex; align-items:center; justify-content:center; color:#999; font-weight:bold;">LOGO</div>
                    <h4>Loja 4</h4>
                </div>
                <div class="partner-card">
                    <div style="width:100%; height:100px; background:#f0f0f0; display:flex; align-items:center; justify-content:center; color:#999; font-weight:bold;">LOGO</div>
                    <h4>Loja 5</h4>
                </div>
                <div class="partner-card">
                    <div style="width:100%; height:100px; background:#f0f0f0; display:flex; align-items:center; justify-content:center; color:#999; font-weight:bold;">LOGO</div>
                    <h4>Loja 6</h4>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 2rem;">
                <a href="<?php echo STORE_REGISTER_URL; ?>" class="btn btn-primary">Seja um Parceiro</a>
            </div>
        </div>
    </section>
    
    <!-- FAQ -->
    <section class="section faq" id="faq">
        <div class="container">
            <div class="section-title">
                <h2>Perguntas Frequentes</h2>
                <p>Tire suas dúvidas sobre o Klube Cash</p>
            </div>
            
            <div class="accordion">
                <div class="accordion-item">
                    <div class="accordion-header">
                        <span>O que é cashback?</span>
                        <span class="accordion-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </span>
                    </div>
                    <div class="accordion-content">
                        <div class="accordion-content-inner">
                            <p>Cashback é um sistema onde você recebe de volta uma porcentagem do valor gasto em suas compras. É como um desconto, mas que você recebe depois da compra realizada.</p>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <div class="accordion-header">
                        <span>Como faço para receber meu cashback?</span>
                        <span class="accordion-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </span>
                    </div>
                    <div class="accordion-content">
                        <div class="accordion-content-inner">
                            <p>Após realizar uma compra em uma loja parceira, o valor do cashback é automaticamente creditado em sua conta Klube Cash em até 48 horas, dependendo da loja. Você pode acompanhar todas as suas transações e saldo pelo seu painel.</p>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <div class="accordion-header">
                        <span>O cadastro no Klube Cash é gratuito?</span>
                        <span class="accordion-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </span>
                    </div>
                    <div class="accordion-content">
                        <div class="accordion-content-inner">
                            <p>Sim, o cadastro no Klube Cash é totalmente gratuito. Não cobramos nenhuma taxa de adesão ou mensalidade.</p>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <div class="accordion-header">
                        <span>Como posso resgatar meu saldo de cashback?</span>
                        <span class="accordion-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </span>
                    </div>
                    <div class="accordion-content">
                        <div class="accordion-content-inner">
                            <p>Você pode transferir o saldo para sua conta bancária, converter em produtos ou usar como desconto em novas compras nas lojas parceiras. O valor mínimo para saque é de R$ 20,00.</p>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <div class="accordion-header">
                        <span>Como posso me tornar um lojista parceiro?</span>
                        <span class="accordion-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </span>
                    </div>
                    <div class="accordion-content">
                        <div class="accordion-content-inner">
                            <p>É muito simples! Basta clicar em "Seja um Parceiro" e preencher o formulário com os dados da sua loja. Nossa equipe entrará em contato para finalizar o processo.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-column">
                    <h3>Klube Cash</h3>
                    <p>O melhor programa de cashback para suas compras. Ganhe dinheiro de volta em cada compra nas lojas parceiras.</p>
                    <div class="social-icons">
                        <a href="#" class="social-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
                            </svg>
                        </a>
                        <a href="https://www.instagram.com/klubecash/" class="social-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                                <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                            </svg>
                        </a>
                        <a href="#" class="social-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path>
                            </svg>
                        </a>
                    </div>
                </div>
                
                <div class="footer-column">
                    <h3>Links Úteis</h3>
                    <ul class="footer-links">
                        <li class="footer-link"><a href="#como-funciona">Como Funciona</a></li>
                        <li class="footer-link"><a href="#beneficios">Benefícios</a></li>
                        <li class="footer-link"><a href="#parceiros">Lojas Parceiras</a></li>
                        <li class="footer-link"><a href="#faq">FAQ</a></li>
                        <li class="footer-link"><a href="<?php echo STORE_REGISTER_URL; ?>">Seja um Parceiro</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Contato</h3>
                    <div class="contact-info">
                        <span class="contact-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                        </span>
                        <span>contato@klubecash.com</span>
                    </div>
                    <div class="contact-info">
                        <span class="contact-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                        </span>
                        <span>(34) 9999-9999</span>
                    </div>
                    <div class="contact-info">
                        <span class="contact-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                        </span>
                        <span>Patos de Minas, MG - Brasil</span>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Klube Cash. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>
    
    <script>
        // Mobile Menu Toggle
        const hamburger = document.getElementById('hamburger');
        const navMenu = document.getElementById('nav-menu');
        
        hamburger.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });
        
        // Accordion Function for FAQ
        const accordionItems = document.querySelectorAll('.accordion-item');
        
        accordionItems.forEach(item => {
            const header = item.querySelector('.accordion-header');
            
            header.addEventListener('click', () => {
                const isActive = item.classList.contains('active');
                
                // Close all items
                accordionItems.forEach(accordionItem => {
                    accordionItem.classList.remove('active');
                });
                
                // If the clicked item wasn't active, open it
                if (!isActive) {
                    item.classList.add('active');
                }
            });
        });
        
        // Smooth Scroll for Navigation
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                
                // Close mobile menu if open
                navMenu.classList.remove('active');
                
                const target = document.querySelector(this.getAttribute('href'));
                
                if (target) {
                    window.scrollTo({
                        top: target.offsetTop - 70, // Adjust for header height
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>