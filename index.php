<?php
// index.php
// Incluir configurações do sistema
require_once './config/constants.php';

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

    <style>
        /* KlubeCash - Modernized CSS for index.php */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        :root {
            --primary-color: #FF7A00;
            --primary-dark: #E06E00;
            --primary-light: #FFF0E6;
            --secondary-color: #333333;
            --white: #FFFFFF;
            --light-gray: #F8F9FA;
            --medium-gray: #6c757d;
            --dark-gray: #343A40;
            --success-color: #28a745;
            --border-radius-sm: 8px;
            --border-radius-md: 15px;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 5px 15px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.1);
            --font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            --transition-speed: 0.3s;
            --container-width: 1200px;
            --header-height: 70px;
        }

        /* === Reset Básico e Estilos Globais === */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
            font-size: 16px;
        }

        body {
            font-family: var(--font-family);
            color: var(--dark-gray);
            background-color: var(--white);
            line-height: 1.7;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            overflow-x: hidden;
        }

        .container {
            width: 100%;
            max-width: var(--container-width);
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        a {
            text-decoration: none;
            color: var(--primary-color);
            transition: color var(--transition-speed) ease;
        }

        a:hover, a:focus {
            color: var(--primary-dark);
        }

        h1, h2, h3, h4 {
            font-weight: 600;
            line-height: 1.3;
            color: var(--secondary-color);
        }

        /* === Utilitários === */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.8rem;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-speed) cubic-bezier(0.25, 1, 0.5, 1);
            border: 2px solid transparent;
            font-size: 1rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            z-index: 1;
            min-height: 3rem;
            -webkit-tap-highlight-color: transparent;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.15);
            transition: width var(--transition-speed) ease;
            z-index: -1;
        }

        .btn:hover::before, .btn:focus::before {
            width: 100%;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
            box-shadow: 0 4px 15px rgba(255, 122, 0, 0.25);
        }

        .btn-primary:hover, .btn-primary:focus {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 122, 0, 0.3);
        }

        .btn-white {
            background-color: var(--white);
            color: var(--primary-color);
            box-shadow: var(--shadow-sm);
        }

        .btn-white:hover, .btn-white:focus {
            background-color: var(--light-gray);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-outline-white {
            border-color: var(--white);
            color: var(--white);
            background-color: transparent;
        }

        .btn-outline-white:hover, .btn-outline-white:focus {
            background-color: var(--white);
            color: var(--primary-color);
            transform: translateY(-2px);
        }
        
        .btn-lg {
             padding: 0.9rem 2.2rem;
             font-size: 1.05rem;
        }

        .btn-with-icon svg {
            transition: transform var(--transition-speed) ease;
        }

        .btn-with-icon:hover svg, .btn-with-icon:focus svg {
            transform: translateX(3px);
        }

        /* === Header === */
        .header {
            background-color: var(--white);
            padding: 0.8rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
            z-index: 1000;
            transition: all var(--transition-speed) ease-in-out;
            box-shadow: var(--shadow-sm);
        }

        .header.scrolled {
            padding: 0.6rem 0;
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            box-shadow: var(--shadow-md);
        }

        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: var(--header-height);
        }

        .logo-img {
            height: 40px;
            transition: transform var(--transition-speed) ease;
        }

        .logo:hover .logo-img, .logo:focus .logo-img {
            transform: scale(1.05);
        }

        .nav-menu {
            display: flex;
            align-items: center;
            list-style: none;
        }

        .nav-item {
            margin-left: 1.8rem;
        }

        .nav-link {
            color: var(--dark-gray);
            font-weight: 500;
            font-size: 0.95rem;
            padding: 0.5rem 0.2rem;
            position: relative;
            transition: color var(--transition-speed) ease;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -3px;
            left: 50%;
            transform: translateX(-50%);
            background-color: var(--primary-color);
            transition: width var(--transition-speed) ease;
            border-radius: 2px;
        }

        .nav-link:hover::after, .nav-link:focus::after, .nav-link.active-link::after {
            width: 100%;
        }

        .nav-link:hover, .nav-link:focus, .nav-link.active-link {
            color: var(--primary-color);
        }

        .nav-link.btn {
            padding: 0.6rem 1.5rem;
        }

        /* Hamburger Menu */
        .hamburger {
            display: none;
            cursor: pointer;
            padding: 0.5rem;
            background: transparent;
            border: none;
            z-index: 1001;
        }

        .hamburger-line {
            display: block;
            width: 26px;
            height: 3px;
            margin: 5px auto;
            background-color: var(--primary-color);
            border-radius: 3px;
            transition: all var(--transition-speed) ease-in-out;
        }

        .hamburger.active .hamburger-line:nth-child(1) {
            transform: translateY(8px) rotate(45deg);
        }

        .hamburger.active .hamburger-line:nth-child(2) {
            opacity: 0;
        }

        .hamburger.active .hamburger-line:nth-child(3) {
            transform: translateY(-8px) rotate(-45deg);
        }

        /* === Hero Section === */
        .hero {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: calc(var(--header-height) + 4rem) 0 8rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero .container {
            display: grid; /* Changed to grid for better layout control */
            grid-template-columns: 1fr; /* Default to single column */
            align-items: center;
            gap: 2rem;
            position: relative;
            z-index: 2;
        }

        .hero-content {
            max-width: 650px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-title {
            font-size: clamp(2.5rem, 5vw, 3.8rem);
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--white);
            line-height: 1.2;
        }

        .hero-title .highlight {
            color: var(--primary-light);
            text-shadow: 0 0 10px rgba(0,0,0,0.2);
        }

        .hero-subtitle {
            font-size: clamp(1rem, 2.5vw, 1.25rem);
            margin-bottom: 2rem;
            opacity: 0.9;
            max-width: 550px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .hero-image-container {
            margin-top: 2rem;
            max-width: 450px; /* Ajustado */
            width: 70%;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-image {
            border-radius: var(--border-radius-md);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            animation: subtleFloat 6s ease-in-out infinite;
        }

        @keyframes subtleFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .hero-bg-shapes {
            content: "";
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-image:
                radial-gradient(circle at 10% 20%, rgba(255,255,255,0.08) 0%, transparent 30%),
                radial-gradient(circle at 80% 70%, rgba(255,255,255,0.06) 0%, transparent 25%);
            z-index: 0;
            pointer-events: none;
        }

        .hero-wave {
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            line-height: 0;
            z-index: 1;
        }

        .hero-wave svg path {
            fill: var(--white);
        }

        /* === Estilos de Seção === */
        .section {
            padding: 5rem 0;
            position: relative; /* Para pseudo-elementos decorativos */
        }

        .section-title {
            text-align: center;
            margin-bottom: 3.5rem;
        }

        .section-subtitle {
            display: inline-block;
            background-color: var(--primary-light);
            color: var(--primary-color);
            padding: 0.4rem 1.2rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .section-title h2 {
            font-size: clamp(2rem, 4vw, 2.8rem);
            color: var(--secondary-color);
            margin-bottom: 0.8rem;
        }

        .section-title .line { /* Linha decorativa abaixo do título */
            width: 70px;
            height: 4px;
            background-color: var(--primary-color);
            margin: 0 auto 1.5rem;
            border-radius: 2px;
        }

        .section-description {
            max-width: 650px;
            margin: 0 auto;
            color: var(--medium-gray);
            font-size: 1.1rem;
        }

        /* Como Funciona Section */
        .steps-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2.5rem;
            margin-top: 3rem;
        }

        .step-card {
            background-color: var(--white);
            border-radius: var(--border-radius-md);
            padding: 2.5rem 2rem;
            text-align: center;
            box-shadow: var(--shadow-sm);
            transition: transform var(--transition-speed) ease, box-shadow var(--transition-speed) ease;
            border: 1px solid #eee; /* Borda sutil */
        }

        .step-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
        }

        .step-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 1.5rem;
            background: linear-gradient(45deg, var(--primary-color), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--white);
            box-shadow: 0 8px 15px rgba(255, 122, 0, 0.3);
        }

        .step-title {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 0.8rem;
            color: var(--secondary-color);
        }

        .step-card p {
            font-size: 0.95rem;
            color: var(--medium-gray);
        }

        /* Benefícios Section */
        .benefits {
            background-color: var(--light-gray); /* Fundo suave para contraste */
        }

        .benefits-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2.5rem;
            margin-top: 3rem;
        }

        .benefit-card {
            background-color: var(--white);
            border-radius: var(--border-radius-md);
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            transition: transform var(--transition-speed) ease, box-shadow var(--transition-speed) ease;
            border: 1px solid #eee;
            display: flex;
            flex-direction: column;
            align-items: center; /* Centraliza ícone e texto */
            text-align: center;
        }

        .benefit-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
        }

        .benefit-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: var(--primary-light);
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 1.2rem;
            color: var(--primary-color);
            transition: background-color var(--transition-speed) ease, color var(--transition-speed) ease;
        }
        .benefit-icon svg { width: 28px; height: 28px; }

        .benefit-card:hover .benefit-icon {
            background-color: var(--primary-color);
            color: var(--white);
        }

        .benefit-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.8rem;
            color: var(--secondary-color);
        }
        .benefit-card p { font-size: 0.95rem; color: var(--medium-gray); }


        /* CTA Section */
        .cta {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
            color: var(--white);
            text-align: center;
            padding: 5rem 1.5rem;
            position: relative;
            overflow: hidden;
        }
        .cta::before { /* Efeito de background sutil */
            content: "";
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.5;
            z-index: 0;
        }


        .cta-content {
            position: relative; /* Para ficar acima do ::before */
            z-index: 1;
        }

        .cta h2 {
            font-size: clamp(2rem, 5vw, 2.8rem);
            margin-bottom: 1rem;
            color: var(--white);
        }

        .cta p {
            font-size: clamp(1rem, 2.5vw, 1.2rem);
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            opacity: 0.9;
        }

        /* Lojas Parceiras Section */
        .partners-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .partner-card {
            background-color: var(--white);
            border-radius: var(--border-radius-sm);
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--shadow-sm);
            transition: transform var(--transition-speed) ease, box-shadow var(--transition-speed) ease;
            border: 1px solid #eee;
        }

        .partner-card:hover {
            transform: translateY(-8px) scale(1.03); /* Efeito de leve elevação e zoom */
            box-shadow: var(--shadow-md);
        }

        .partner-logo-container {
            height: 80px; /* Altura fixa para os logos */
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            background-color: var(--light-gray); /* Fundo para placeholders */
            border-radius: var(--border-radius-sm);
            overflow: hidden; /* Garante que o logo não ultrapasse */
        }

        .partner-logo { /* Estilo para o texto "LOGO" */
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--medium-gray);
        }
         .partner-card img { /* Se for usar imagens reais para os logos */
            max-height: 100%;
            max-width: 100%;
            object-fit: contain; /* Para logos não distorcerem */
        }

        .partner-card h4 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--secondary-color);
            margin-top: 0.5rem;
        }

        .partners-cta {
            text-align: center;
            margin-top: 3rem;
        }

        /* FAQ Section */
        .faq {
            background-color: var(--light-gray);
        }
        .accordion {
            max-width: 800px;
            margin: 3rem auto 0;
        }

        .accordion-item {
            background-color: var(--white);
            border-radius: var(--border-radius-sm);
            margin-bottom: 1rem;
            box-shadow: var(--shadow-sm);
            transition: box-shadow var(--transition-speed) ease;
            border: 1px solid #eee; /* Borda sutil */
        }

        .accordion-item:hover {
            box-shadow: var(--shadow-md);
        }
        .accordion-item.active { /* Destaque para item ativo */
            border-left: 3px solid var(--primary-color);
        }


        .accordion-header {
            width: 100%;
            padding: 1.2rem 1.5rem;
            cursor: pointer;
            font-weight: 600;
            font-size: 1.1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: transparent; /* Removido fundo para melhor efeito de hover */
            border: none;
            text-align: left;
            color: var(--secondary-color);
            transition: background-color var(--transition-speed) ease;
        }
        .accordion-header:hover {
            background-color: var(--primary-light); /* Efeito sutil no hover */
        }
        .accordion-item.active .accordion-header {
            color: var(--primary-color); /* Cor do texto quando ativo */
        }

        .accordion-icon {
            transition: transform var(--transition-speed) ease;
            color: var(--primary-color); /* Ícone sempre laranja */
        }

        .accordion-item.active .accordion-icon {
            transform: rotate(45deg); /* Efeito de "X" */
        }

        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out, padding 0.3s ease-out;
        }
        .accordion-item.active .accordion-content { /* Adiciona padding quando ativo */
             padding: 0 1.5rem 1.5rem;
        }

        .accordion-content-inner p {
            font-size: 0.95rem;
            color: var(--medium-gray);
            line-height: 1.8; /* Melhora a legibilidade */
        }


        /* === Footer === */
        .footer {
            background-color: var(--secondary-color);
            color: rgba(255, 255, 255, 0.8); /* Texto um pouco mais opaco */
            padding-top: 5rem; /* Espaçamento para a onda */
            position: relative; /* Para a onda SVG */
        }

        .footer-wave {
            position: absolute;
            top: -1px; /* Ajuste para sobrepor a borda da seção anterior */
            left: 0;
            width: 100%;
            line-height: 0; /* Remove espaço extra do SVG */
            transform: scaleY(-1); /* Inverte a onda */
        }
        .footer-wave svg path{
            fill: var(--secondary-color); /* Cor do footer */
        }


        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2.5rem;
            padding: 3rem 0; /* Espaçamento interno do conteúdo do footer */
            position: relative; /* Para garantir que o conteúdo fique acima da onda */
            z-index: 1;
        }

        .footer-column h3 {
            color: var(--white);
            margin-bottom: 1.2rem;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .footer-column p {
            font-size: 0.9rem;
            margin-bottom: 1.2rem;
            line-height: 1.8;
        }

        .footer-links {
            list-style: none;
        }

        .footer-link {
            margin-bottom: 0.8rem;
        }

        .footer-link a {
            color: rgba(255, 255, 255, 0.7);
            transition: color var(--transition-speed) ease, padding-left var(--transition-speed) ease;
        }

        .footer-link a:hover, .footer-link a:focus {
            color: var(--primary-color);
            padding-left: 5px; /* Efeito sutil de deslocamento */
        }

        .contact-info {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 0.8rem;
            font-size: 0.9rem;
        }
        .contact-icon svg {
            color: var(--primary-color); /* Destaca o ícone */
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
            background-color: rgba(255, 255, 255, 0.15);
            display: flex;
            justify-content: center;
            align-items: center;
            transition: background-color var(--transition-speed) ease, transform var(--transition-speed) ease;
            color: var(--white);
        }

        .social-icon:hover, .social-icon:focus {
            background-color: var(--primary-color);
            transform: translateY(-3px);
        }
        .social-icon svg { width: 18px; height: 18px; }


        .footer-bottom {
            text-align: center;
            padding: 2rem 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.85rem;
            position: relative; /* Para garantir que o conteúdo fique acima da onda */
            z-index: 1;
        }

        /* === Animações === */
        @keyframes pulse { /* Animação sutil para ícones */
            0% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.15); opacity: 0.2; }
            100% { transform: scale(1); opacity: 0.5; }
        }


        /* === Media Queries para Responsividade === */

        @media (min-width: 993px) { /* Telas maiores: Hero section com imagem ao lado */
            .hero .container {
                grid-template-columns: 1.2fr 1fr; /* Coluna de texto maior */
                text-align: left; /* Alinha texto à esquerda */
                gap: 3rem;
            }
            .hero-content {
                margin-left: 0;
                margin-right: 0;
                align-items: flex-start; /* Alinha itens do conteúdo à esquerda */
            }
             .hero-title {
                text-align: left;
            }
            .hero-subtitle {
                margin-left: 0;
                 text-align: left;
            }
            .hero-buttons {
                justify-content: flex-start; /* Alinha botões à esquerda */
            }
            .hero-image-container {
                order: 2; /* Imagem à direita */
                margin-top: 0;
                width: 100%; /* Ocupa a coluna do grid */
                max-width: 500px; /* Mantém um tamanho máximo razoável */
            }
        }


        @media (max-width: 992px) {
            .nav-menu {
                display: none; /* Esconde o menu normal */
                flex-direction: column;
                align-items: flex-start; /* Alinha itens à esquerda no menu mobile */
                position: fixed; /* Alterado para fixed para melhor comportamento */
                top: 0; /* Começa do topo */
                left: -100%; /* Começa fora da tela */
                width: 80%; /* Largura do menu mobile */
                max-width: 300px; /* Largura máxima */
                height: 100vh;
                background-color: var(--white);
                box-shadow: var(--shadow-lg); /* Sombra mais pronunciada */
                padding: calc(var(--header-height) + 1rem) 1.5rem 1.5rem; /* Espaçamento interno */
                transition: left var(--transition-speed) cubic-bezier(0.77, 0, 0.175, 1);
                overflow-y: auto;
                z-index: 999; /* Abaixo do hamburger */
            }

            .nav-menu.active {
                left: 0; /* Menu desliza da esquerda */
            }

            .nav-item {
                margin: 0;
                width: 100%;
            }

            .nav-link {
                display: block;
                padding: 0.8rem 0; /* Padding vertical nos links */
                width: 100%;
                border-bottom: 1px solid var(--light-gray); /* Separador entre links */
                font-size: 1rem;
            }
            .nav-link:last-child { border-bottom: none; }

            .nav-link.btn { /* Estilo do botão no menu mobile */
                margin-top: 1rem;
                text-align: center;
                width: 100%;
            }
            .nav-link::after { display: none; }

            .hamburger {
                display: block;
            }
            .hero-title { font-size: clamp(2rem, 6vw, 2.8rem); }

        }

        @media (max-width: 768px) {
            .hero { padding: calc(var(--header-height) + 3rem) 0 6rem; }
            .hero-subtitle { font-size: 1.1rem; }
            .section { padding: 4rem 0; }
            .section-title h2 { font-size: 2rem; }
            .cta h2 { font-size: 1.8rem; }
            .partners-grid { grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1.5rem; }
            .footer-grid { grid-template-columns: 1fr; text-align: center; }
            .footer-column h3::after { left: 50%; transform: translateX(-50%); }
            .social-icons { justify-content: center; }
            .contact-info { justify-content: center; }
        }

        @media (max-width: 480px) {
            :root { --header-height: 60px; }
            .container { padding: 0 1rem; }
             .nav-menu { padding-top: calc(var(--header-height) + 0.5rem); }
            .nav-link.btn { margin-top: 0.8rem; }
            .hero-title { font-size: 1.8rem; }
            .hero-subtitle { font-size: 1rem; }
            .hero-buttons { flex-direction: column; gap: 0.8rem; }
            .btn { padding: 0.7rem 1.5rem; font-size: 0.9rem; }
            .section-title h2 { font-size: 1.6rem; }
            .section-description { font-size: 0.95rem; }
            .steps-container, .benefits-container, .partners-grid { gap: 1.5rem; }
            .step-icon { width: 60px; height: 60px; font-size: 1.5rem; }
            .benefit-icon { width: 50px; height: 50px; }
            .benefit-icon svg { width: 24px; height: 24px; }
            .partner-logo-container { height: 70px; }
            .accordion-header { padding: 1rem; font-size: 1rem; }
            .accordion-content-inner { padding: 0 1rem 1rem; }
        }

        /* Melhorias de acessibilidade para foco */
        .btn:focus-visible, .nav-link:focus-visible, .accordion-header:focus-visible, .social-icon:focus-visible, .hamburger:focus-visible {
            outline: 3px solid var(--primary-dark);
            outline-offset: 2px;
            box-shadow: 0 0 0 4px rgba(255, 122, 0, 0.3); /* Sombra de foco mais visível */
        }
    </style>
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
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5l7 7-7 7"></path></svg>
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
                <div class="hero-image-container" data-aos="fade-left" data-aos-delay="200">
                    
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
                    <?php for ($i = 1; $i <= 6; $i++): ?>
                    <div class="partner-card">
                        <div class="partner-logo-container">
                            <div class="partner-logo">LOGO</div> </div>
                        <h4>Loja <?php echo $i; ?></h4>
                    </div>
                    <?php endfor; ?>
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
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 180">
                <path fill="#333333" fill-opacity="1" d="M0,128L48,117.3C96,107,192,85,288,96C384,107,480,149,576,160C672,171,768,149,864,122.7C960,96,1056,64,1152,58.7C1248,53,1344,75,1392,85.3L1440,96L1440,0L1392,0C1344,0,1248,0,1152,0C1056,0,960,0,864,0C768,0,672,0,576,0C480,0,384,0,288,0C192,0,96,0,48,0L0,0Z"></path>
            </svg>
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
    
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
        // Inicializa AOS (Animate On Scroll)
        if (typeof AOS !== 'undefined') {
            AOS.init({
                once: true,
                offset: 80, // Ajustado para melhor timing
                duration: 800,
                easing: 'ease-in-out-quad', // Easing mais suave
            });
        }

        // Menu Mobile
        const hamburger = document.getElementById('hamburger');
        const navMenu = document.getElementById('nav-menu');
        const navLinks = navMenu.querySelectorAll('.nav-link'); // Pega todos os links do menu

        if (hamburger && navMenu) {
            hamburger.addEventListener('click', () => {
                hamburger.classList.toggle('active');
                navMenu.classList.toggle('active');
                // Trava o scroll do body quando o menu estiver aberto
                document.body.style.overflow = navMenu.classList.contains('active') ? 'hidden' : '';
            });

            // Fecha o menu mobile ao clicar em um link
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    if (navMenu.classList.contains('active')) {
                        hamburger.classList.remove('active');
                        navMenu.classList.remove('active');
                        document.body.style.overflow = '';
                    }
                });
            });
        }

        // Accordion FAQ
        const accordionItems = document.querySelectorAll('.accordion-item');
        accordionItems.forEach(item => {
            const header = item.querySelector('.accordion-header');
            const content = item.querySelector('.accordion-content');

            if (header && content) {
                // Garantir que o conteúdo comece fechado e com acessibilidade
                content.style.maxHeight = "0px";
                header.setAttribute('aria-expanded', 'false');
                content.setAttribute('aria-hidden', 'true');

                header.addEventListener('click', () => {
                    const isActive = item.classList.contains('active');

                    // Fechar todos os outros itens
                    accordionItems.forEach(otherItem => {
                        if (otherItem !== item) {
                            otherItem.classList.remove('active');
                            otherItem.querySelector('.accordion-content').style.maxHeight = "0px";
                            otherItem.querySelector('.accordion-header').setAttribute('aria-expanded', 'false');
                            otherItem.querySelector('.accordion-content').setAttribute('aria-hidden', 'true');
                        }
                    });

                    // Abrir/Fechar o item clicado
                    if (!isActive) {
                        item.classList.add('active');
                        content.style.maxHeight = content.scrollHeight + "px";
                        header.setAttribute('aria-expanded', 'true');
                        content.setAttribute('aria-hidden', 'false');
                    } else {
                        item.classList.remove('active');
                        content.style.maxHeight = "0px";
                        header.setAttribute('aria-expanded', 'false');
                        content.setAttribute('aria-hidden', 'true');
                    }
                });
            }
        });

        // Smooth Scroll para links de navegação interna
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const hrefAttribute = this.getAttribute('href');
                if (hrefAttribute && hrefAttribute.length > 1) { // Garante que não é apenas "#"
                    const targetElement = document.querySelector(hrefAttribute);
                    if (targetElement) {
                        e.preventDefault();
                        const headerOffset = document.getElementById('header')?.offsetHeight || 70;
                        const elementPosition = targetElement.getBoundingClientRect().top;
                        const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                        window.scrollTo({
                            top: offsetPosition,
                            behavior: 'smooth'
                        });
                    }
                }
            });
        });

        // Adiciona classe ao header durante o scroll
        const header = document.getElementById('header');
        if (header) {
            const scrollThreshold = 50; // Distância de scroll para ativar o efeito
            window.addEventListener('scroll', function() {
                if (window.scrollY > scrollThreshold) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
            }, { passive: true }); // Melhora performance
        }

        // Lazy loading para Lottie player (se existir)
        const lottiePlayer = document.querySelector('lottie-player');
        if (lottiePlayer && 'IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        if (lottiePlayer.hasAttribute('data-src')) {
                        lottiePlayer.load(lottiePlayer.getAttribute('data-src'));
                        }
                        lottiePlayer.play();
                        observer.unobserve(lottiePlayer);
                    }
                });
            }, { threshold: 0.1 });
            observer.observe(lottiePlayer);
        }
    });
    </script>
</body>
</html>