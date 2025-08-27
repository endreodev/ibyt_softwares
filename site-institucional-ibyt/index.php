<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IBYT Software - Soluções Corporativas em TI</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="IBYT Software - Líderes em consultoria ERP Protheus e Sankhya, desenvolvimento de aplicações empresariais e transformação digital.">
    <meta name="keywords" content="consultoria ERP, Protheus, Sankhya, desenvolvimento empresarial, transformação digital, integração sistemas">
    <meta name="author" content="IBYT Software">
    
    <!-- Open Graph -->
    <meta property="og:title" content="IBYT Software - Soluções Corporativas em TI">
    <meta property="og:description" content="Transformando negócios através da tecnologia. Especialistas em ERP e desenvolvimento de software empresarial.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://www.ibyt.com.br">
    <meta property="og:image" content="https://www.ibyt.com.br/assets/img/og-image.jpg">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="assets/img/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/img/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/img/apple-touch-icon.png">
    
    <!-- Google Fonts - Inter Professional -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="assets/css/enhanced-professional.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    
    <!-- CSS para forçar header fixo -->
    <style>
        .professional-header {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            width: 100% !important;
            z-index: 9999 !important;
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(20px) !important;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05) !important;
            display: block !important;
        }
        
        body {
            padding-top: 80px !important;
            margin-top: 0 !important;
        }
        
        .main-content {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }
    </style>
    
    <!-- Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "IBYT Software",
      "url": "https://www.ibyt.com.br",
      "logo": "https://www.ibyt.com.br/assets/img/logo.png",
      "description": "Especialistas em consultoria ERP e desenvolvimento de software empresarial",
      "email": "contato@ibyt.com.br",
      "address": {
        "@type": "PostalAddress",
        "addressCountry": "BR"
      },
      "contactPoint": [
        {
          "@type": "ContactPoint",
          "telephone": "+55-65-98171-9837",
          "contactType": "customer service"
        },
        {
          "@type": "ContactPoint",
          "telephone": "+55-65-98171-9837",
          "contactType": "customer service",
          "availableLanguage": "Portuguese"
        }
      ]
    }
    </script>
</head>
<body>
    <!-- Professional Header -->
    <header class="professional-header" id="header">
        <div class="header-content">

            <div class="logo-section">
                <img src="assets/img/logo.png" alt="IBYT Software Logo" loading="eager">
                <span class="logo-text">Software</span>
            </div>
            
            <nav class="main-navigation" id="navigation">
                <a href="#home" class="nav-link active" data-scroll="home">Início</a>
                <a href="#about" class="nav-link" data-scroll="about">Sobre</a>
                <a href="#services" class="nav-link" data-scroll="services">Serviços</a>
                <a href="#products" class="nav-link" data-scroll="products">Produtos</a>
                <a href="nivel-certo.php" class="nav-link">Nível Certo</a>
                <a href="loja.php" class="nav-link store-link">
                    <span class="material-icons-round">store</span>
                    Loja
                </a>
                <a href="#contact" class="nav-link" data-scroll="contact">Contato</a>
            </nav>
            
            <div class="header-actions">
                <button class="btn btn-primary" onclick="document.getElementById('contact').scrollIntoView()">
                    <span class="material-icons-round">business_center</span>
                    Consultoria Gratuita
                </button>
                <button class="mobile-menu-toggle" id="mobile-menu-toggle" aria-label="Toggle menu">
                    <span class="material-icons-round">menu</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Mobile Navigation -->
    <nav class="mobile-navigation" id="mobile-navigation">
        <div class="mobile-nav-content">
            <div class="mobile-nav-header">
                <span class="logo-text">IBYT Software</span>
                <button class="mobile-nav-close" id="mobile-nav-close">
                    <span class="material-icons-round">close</span>
                </button>
            </div>
            <div class="mobile-nav-links">
                <a href="#home" class="mobile-nav-link">Início</a>
                <a href="#about" class="mobile-nav-link">Sobre</a>
                <a href="#services" class="mobile-nav-link">Serviços</a>
                <a href="#products" class="mobile-nav-link">Produtos</a>
                <a href="nivel-certo.php" class="mobile-nav-link">Nível Certo</a>
                <a href="loja.php" class="mobile-nav-link">
                    <span class="material-icons-round">store</span>
                    Loja
                </a>
                <a href="#contact" class="mobile-nav-link">Contato</a>
            </div>
        </div>
    </nav>

    <main>
        <!-- Hero Section -->
        <section id="home" class="hero-professional">
            <div class="hero-content">
                <div class="hero-text">
                    <div class="hero-badge animate-slide-in-up">
                        <span class="material-icons-round">trending_up</span>
                        Transformação Digital Empresarial
                    </div>
                    
                    <h1 class="text-hero hero-title animate-slide-in-left">
                        Soluções <span class="gradient-text">Inovadoras</span> para seu Negócio
                    </h1>
                    
                    <p class="hero-description animate-slide-in-left">
                        Especialistas em consultoria ERP, desenvolvimento de aplicações empresariais e integração de sistemas. 
                        Transformamos sua visão em realidade digital com soluções robustas e escaláveis.
                    </p>
                    
                    <div class="hero-actions animate-slide-in-left">
                        <button class="btn btn-primary" onclick="document.getElementById('services').scrollIntoView()">
                            <span class="material-icons-round">rocket_launch</span>
                            Nossos Serviços
                        </button>
                        <button class="btn btn-secondary" onclick="document.getElementById('contact').scrollIntoView()">
                            <span class="material-icons-round">phone</span>
                            Fale Conosco
                        </button>
                    </div>
                    
                    <!-- Stats -->
                    <div class="hero-stats animate-slide-in-left">
                        <div class="stat-item">
                            <span class="stat-number" data-count="150">0</span>
                            <span class="stat-label">Projetos Entregues</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number" data-count="15">0</span>
                            <span class="stat-label">Anos de Experiência</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number" data-count="100">0</span>
                            <span class="stat-label">% Satisfação</span>
                        </div>
                    </div>
                </div>
                
                <div class="hero-visual animate-slide-in-right">
                    <div class="floating-elements">
                        <div class="floating-card animate-float" style="animation-delay: 0s;">
                            <div class="card-icon">
                                <span class="material-icons-round">settings</span>
                            </div>
                            <div class="card-content">
                                <h4>ERP Consulting</h4>
                                <p>Protheus & Sankhya</p>
                            </div>
                        </div>
                        
                        <div class="floating-card animate-float" style="animation-delay: 2s;">
                            <div class="card-icon">
                                <span class="material-icons-round">smartphone</span>
                            </div>
                            <div class="card-content">
                                <h4>Mobile Apps</h4>
                                <p>iOS & Android</p>
                            </div>
                        </div>
                        
                        <div class="floating-card animate-float" style="animation-delay: 4s;">
                            <div class="card-icon">
                                <span class="material-icons-round">cloud</span>
                            </div>
                            <div class="card-content">
                                <h4>Cloud Solutions</h4>
                                <p>Escaláveis & Seguras</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- About Section -->
        <section id="about" class="section-professional">
            <div class="container-professional">
                <div class="section-header">
                    <p class="section-subtitle">Sobre a IBYT Software</p>
                    <h2 class="text-display section-title">Transformando Presença Digital com <span class="gradient-text">Expertise Comprovada</span></h2>
                    <p class="section-description">
                        Especializada em consultoria ERP, desenvolvimento web e soluções de integração, a IBYT Software 
                        é referência em transformação digital para empresas que buscam eficiência operacional e 
                        crescimento sustentável através da tecnologia.
                    </p>
                </div>
                
                <div class="about-content">
                    <div class="grid-professional grid-2">
                        <div class="about-text">
                            <h3 class="text-headline mb-6">Nossa Especialização</h3>
                            <p class="text-body mb-6">
                                Somos consultores especializados em <strong>ERP Sankhya e Protheus</strong>, oferecendo 
                                soluções completas de integração, customização e otimização. Nossa expertise se estende 
                                ao desenvolvimento web com <strong>WordPress, WooCommerce</strong> e tecnologias modernas 
                                como <strong>React, Node.js e Python</strong>.
                            </p>
                            
                            <h4 class="text-headline mb-4">Principais Competências</h4>
                            <div class="competencies-grid">
                                <div class="competency-item">
                                    <span class="material-icons-round">integration_instructions</span>
                                    <div>
                                        <h5>Integração ERP</h5>
                                        <p>Sankhya e Protheus com sistemas externos</p>
                                    </div>
                                </div>
                                
                                <div class="competency-item">
                                    <span class="material-icons-round">tune</span>
                                    <div>
                                        <h5>Customização Avançada</h5>
                                        <p>ADVPL/TLPP e Sankhya Java</p>
                                    </div>
                                </div>
                                
                                <div class="competency-item">
                                    <span class="material-icons-round">shopping_cart</span>
                                    <div>
                                        <h5>E-commerce</h5>
                                        <p>WooCommerce personalizado e otimizado</p>
                                    </div>
                                </div>
                                
                                <div class="competency-item">
                                    <span class="material-icons-round">language</span>
                                    <div>
                                        <h5>Desenvolvimento Web</h5>
                                        <p>Sites modernos e responsivos</p>
                                    </div>
                                </div>
                                
                                <div class="competency-item">
                                    <span class="material-icons-round">cloud</span>
                                    <div>
                                        <h5>Hospedagem & Cloud</h5>
                                        <p>Infraestrutura segura e escalável</p>
                                    </div>
                                </div>
                                
                                <div class="competency-item">
                                    <span class="material-icons-round">security</span>
                                    <div>
                                        <h5>Segurança Digital</h5>
                                        <p>Proteção 24/7 e backups automatizados</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="features-list">
                                <div class="feature-item">
                                    <span class="material-icons-round">verified</span>
                                    <div>
                                        <h4>Expertise Comprovada</h4>
                                        <p>Certificações oficiais em Protheus e Sankhya com projetos de alta complexidade</p>
                                    </div>
                                </div>
                                
                                <div class="feature-item">
                                    <span class="material-icons-round">support_agent</span>
                                    <div>
                                        <h4>Suporte Especializado</h4>
                                        <p>Atendimento rápido e eficiente para manter sua operação sempre online</p>
                                    </div>
                                </div>
                                
                                <div class="feature-item">
                                    <span class="material-icons-round">location_on</span>
                                    <div>
                                        <h4>Localização Estratégica</h4>
                                        <p>Sede em Cuiabá-MT, atendendo todo território nacional</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="about-visual">
                            <div class="about-image-wrapper">
                                <img src="assets/img/web-sob-demanda.png" alt="IBYT Software Empresa" class="about-image">
                                <div class="about-overlay">
                                    <div class="overlay-content">
                                        <h4>15+ Anos</h4>
                                        <p>de Experiência</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Technology Stack -->
                            <div class="tech-stack">
                                <h4 class="tech-stack-title">Stack Tecnológico</h4>
                                <div class="tech-grid">
                                    <div class="tech-item">
                                        <span class="tech-icon">⚡</span>
                                        <span>JavaScript</span>
                                    </div>
                                    <div class="tech-item">
                                        <span class="tech-icon">🐍</span>
                                        <span>Python</span>
                                    </div>
                                    <div class="tech-item">
                                        <span class="tech-icon">⚛️</span>
                                        <span>React</span>
                                    </div>
                                    <div class="tech-item">
                                        <span class="tech-icon">📟</span>
                                        <span>Node.js</span>
                                    </div>
                                    <div class="tech-item">
                                        <span class="tech-icon">☕</span>
                                        <span>Java</span>
                                    </div>
                                    <div class="tech-item">
                                        <span class="tech-icon">🌐</span>
                                        <span>WordPress</span>
                                    </div>
                                    <div class="tech-item">
                                        <span class="tech-icon">🛒</span>
                                        <span>WooCommerce</span>
                                    </div>
                                    <div class="tech-item">
                                        <span class="tech-icon">🔧</span>
                                        <span>ADVPL/TLPP</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Services Section -->
        <section id="services" class="section-professional bg-gray">
            <div class="container-professional">
                <div class="section-header">
                    <p class="section-subtitle">Nossos Serviços</p>
                    <h2 class="text-display section-title">Soluções Completas em TI</h2>
                    <p class="section-description">
                        Oferecemos um portfólio abrangente de serviços para atender todas as necessidades 
                        tecnológicas da sua empresa.
                    </p>
                </div>
                
                <div class="grid-professional grid-3">
                    <div class="card-professional">
                        <div class="card-icon">
                            <span class="material-icons-round">business</span>
                        </div>
                        <h3 class="card-title">Consultoria ERP</h3>
                        <p class="card-description">
                            Implementação e otimização de sistemas ERP Protheus e Sankhya, com foco em 
                            maximizar a eficiência dos seus processos empresariais.
                        </p>
                        <ul class="service-features">
                            <li>Análise de processos</li>
                            <li>Implementação completa</li>
                            <li>Customizações específicas</li>
                            <li>Treinamento da equipe</li>
                        </ul>
                    </div>
                    
                    <div class="card-professional">
                        <div class="card-icon">
                            <span class="material-icons-round">smartphone</span>
                        </div>
                        <h3 class="card-title">Desenvolvimento Mobile</h3>
                        <p class="card-description">
                            Aplicativos nativos e híbridos para iOS e Android, com foco na experiência 
                            do usuário e performance otimizada.
                        </p>
                        <ul class="service-features">
                            <li>Apps nativos iOS/Android</li>
                            <li>Progressive Web Apps</li>
                            <li>Integração com APIs</li>
                            <li>UI/UX Design</li>
                        </ul>
                    </div>
                    
                    <div class="card-professional">
                        <div class="card-icon">
                            <span class="material-icons-round">language</span>
                        </div>
                        <h3 class="card-title">Desenvolvimento Web</h3>
                        <p class="card-description">
                            Sistemas web robustos e escaláveis, desde portais corporativos até 
                            plataformas de e-commerce complexas.
                        </p>
                        <ul class="service-features">
                            <li>Sistemas web personalizados</li>
                            <li>E-commerce avançado</li>
                            <li>Portais corporativos</li>
                            <li>APIs RESTful</li>
                        </ul>
                    </div>
                    
                    <div class="card-professional">
                        <div class="card-icon">
                            <span class="material-icons-round">integration_instructions</span>
                        </div>
                        <h3 class="card-title">Integração de Sistemas</h3>
                        <p class="card-description">
                            Conectamos seus sistemas existentes, criando um ecossistema tecnológico 
                            integrado e eficiente.
                        </p>
                        <ul class="service-features">
                            <li>Integração ERP</li>
                            <li>APIs personalizadas</li>
                            <li>Middleware solutions</li>
                            <li>Data synchronization</li>
                        </ul>
                    </div>
                    
                    <div class="card-professional">
                        <div class="card-icon">
                            <span class="material-icons-round">cloud_sync</span>
                        </div>
                        <h3 class="card-title">Cloud Computing</h3>
                        <p class="card-description">
                            Migração e gerenciamento de infraestrutura em nuvem, garantindo 
                            escalabilidade e segurança.
                        </p>
                        <ul class="service-features">
                            <li>Migração para cloud</li>
                            <li>Arquitetura serverless</li>
                            <li>DevOps & CI/CD</li>
                            <li>Monitoramento 24/7</li>
                        </ul>
                    </div>
                    
                    <div class="card-professional">
                        <div class="card-icon">
                            <span class="material-icons-round">support</span>
                        </div>
                        <h3 class="card-title">Suporte Técnico</h3>
                        <p class="card-description">
                            Suporte especializado e manutenção preventiva para garantir o funcionamento 
                            contínuo dos seus sistemas.
                        </p>
                        <ul class="service-features">
                            <li>Suporte 24/7</li>
                            <li>Manutenção preventiva</li>
                            <li>Monitoramento proativo</li>
                            <li>SLA garantido</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Products Section -->
        <section id="products" class="section-professional">
            <div class="container-professional">
                <div class="section-header">
                    <p class="section-subtitle">Nossos Serviços</p>
                    <h2 class="text-display section-title">Soluções Profissionais em TI</h2>
                    <p class="section-description">
                        Oferecemos consultoria especializada e desenvolvimento sob demanda para empresas que buscam tecnologia de ponta, segurança e inovação.
                    </p>
                </div>
                <div class="grid-professional grid-3">
                    <div class="card-professional">
                        <img src="assets/img/consultor.png" alt="Consultoria ERP Protheus & Sankhya" style="width:80px;margin-bottom:12px;">
                        <h3 class="card-title">Consultoria ERP Protheus & Sankhya</h3>
                        <p class="card-description">Implantação, integração e otimização dos principais ERPs do mercado, com foco em resultados e eficiência.</p>
                    </div>
                    <div class="card-professional">
                        <img src="assets/img/monitoramento.png" alt="Linux & Infraestrutura" style="width:80px;margin-bottom:12px;">
                        <h3 class="card-title">Linux & Infraestrutura</h3>
                        <p class="card-description">Servidores, cloud, automação e suporte especializado para ambientes Linux e AWS.</p>
                    </div>
                    <div class="card-professional">
                        <img src="assets/img/app-sob-demanda.png" alt="Apps de Smartphone sob Demanda" style="width:80px;margin-bottom:12px;">
                        <h3 class="card-title">Apps de Smartphone sob Demanda</h3>
                        <p class="card-description">Desenvolvimento de aplicativos móveis personalizados para Android e iOS, integrados ao seu negócio.</p>
                    </div>
                    <div class="card-professional">
                        <img src="assets/img/app-sob-demanda.png" alt="Apps Desktop sob Demanda" style="width:80px;margin-bottom:12px;">
                        <h3 class="card-title">Apps Desktop sob Demanda</h3>
                        <p class="card-description">Soluções desktop personalizadas para Windows, Linux e Mac, com alta performance e segurança.</p>
                    </div>
                    <div class="card-professional">
                        <img src="assets/img/web-sob-demanda.png" alt="Apps Web sob Demanda" style="width:80px;margin-bottom:12px;">
                        <h3 class="card-title">Apps Web sob Demanda</h3>
                        <p class="card-description">Desenvolvimento de sistemas web modernos, responsivos e escaláveis, conforme a necessidade do cliente.</p>
                    </div>
                    <div class="card-professional">
                        <img src="assets/img/segurancaAWS.png" alt="Segurança da Informação & AWS" style="width:80px;margin-bottom:12px;">
                        <h3 class="card-title">Segurança da Informação & AWS</h3>
                        <p class="card-description">Proteção de dados, backups, monitoramento e soluções em nuvem AWS para máxima segurança e disponibilidade.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact Section -->
        <section id="contact" class="section-professional">
            <div class="container-professional">
                <div class="section-header">
                    <p class="section-subtitle">Entre em Contato</p>
                    <h2 class="text-display section-title">Vamos Conversar sobre seu Projeto</h2>
                    <p class="section-description">
                        Nossa equipe está pronta para entender suas necessidades e propor a melhor solução 
                        tecnológica para o seu negócio.
                    </p>
                </div>
                
                <div class="contact-content">
                    <div class="grid-professional grid-2">
                        <div class="contact-info">
                            <h3 class="text-headline mb-6">Fale Conosco</h3>
                            
                            <div class="contact-methods">
                                <div class="contact-method">
                                    <div class="method-icon">
                                        <span class="material-icons-round">phone</span>
                                    </div>
                                    <div class="method-content">
                                        <h4>Telefone</h4>
                                        <p>(65) 98171-9837</p>
                                    </div>
                                </div>
                                
                                <div class="contact-method">
                                    <div class="method-icon">
                                        <span class="material-icons-round">email</span>
                                    </div>
                                    <div class="method-content">
                                        <h4>E-mail</h4>
                                        <p>contato@ibyt.com.br</p>
                                    </div>
                                </div>
                                
                                <div class="contact-method">
                                    <div class="method-icon">
                                        <span class="material-icons-round">schedule</span>
                                    </div>
                                    <div class="method-content">
                                        <h4>Horário de Atendimento</h4>
                                        <p>Segunda a Sexta: 8h às 18h</p>
                                    </div>
                                </div>
                                
                                <div class="contact-method">
                                    <div class="method-icon">
                                        <span class="material-icons-round">chat</span>
                                    </div>
                                    <div class="method-content">
                                        <h4>WhatsApp</h4>
                                        <p><a href="https://wa.me/5565981719837?text=Olá! Gostaria de saber mais sobre os serviços da IBYT Software." 
                                              target="_blank" 
                                              style="color: #25D366; text-decoration: none; font-weight: 600;">
                                              (65) 98171-9837
                                           </a></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="contact-form-wrapper">
                            <form id="contact-form" class="contact-form">
                                <div class="form-group">
                                    <label for="name">Nome Completo</label>
                                    <input type="text" id="name" name="name" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">E-mail</label>
                                    <input type="email" id="email" name="email" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="company">Empresa</label>
                                    <input type="text" id="company" name="company">
                                </div>
                                
                                <div class="form-group">
                                    <label for="service">Serviço de Interesse</label>
                                    <select id="service" name="service" required>
                                        <option value="">Selecione um serviço</option>
                                        <option value="erp">Consultoria ERP</option>
                                        <option value="mobile">Desenvolvimento Mobile</option>
                                        <option value="web">Desenvolvimento Web</option>
                                        <option value="integration">Integração de Sistemas</option>
                                        <option value="cloud">Cloud Computing</option>
                                        <option value="support">Suporte Técnico</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="message">Mensagem</label>
                                    <textarea id="message" name="message" rows="5" required></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-full">
                                    <span class="material-icons-round">send</span>
                                    Enviar Mensagem
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Professional Footer -->
    <footer class="footer-professional">
        <div class="container-professional">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-brand">
                        <h3>IBYT Software</h3>
                        <p>Transformando negócios através da tecnologia.</p>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h4>Serviços</h4>
                    <ul>
                        <li><a href="#services">Consultoria ERP</a></li>
                        <li><a href="#services">Desenvolvimento Mobile</a></li>
                        <li><a href="#services">Desenvolvimento Web</a></li>
                        <li><a href="#services">Integração de Sistemas</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Empresa</h4>
                    <ul>
                        <li><a href="#about">Sobre</a></li>
                        <li><a href="/nivel-certo.php">Nível Certo</a></li>
                        <li><a href="#contact">Contato</a></li>
                        <li><a href="/admin.html">Admin</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Contato</h4>
                    <ul>
                        <li>contato@ibyt.com.br</li> 
                        <li>(65) 98171-9837 - WhatsApp</li>
                        <li>LinkedIn: IBYT Software</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 IBYT Software. Todos os direitos reservados.</p>
                <div class="footer-links">
                    <a href="privacy.php">Política de Privacidade</a>
                    <a href="/terms">Termos de Uso</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <button class="scroll-to-top" id="scroll-to-top" aria-label="Scroll to top">
        <span class="material-icons-round">keyboard_arrow_up</span>
    </button>

        <!-- WhatsApp Float Button -->
    <a href="https://wa.me/5565981719837?text=Olá! Gostaria de saber mais sobre os serviços da IBYT Software."
         class="whatsapp-float whatsapp-blink"
         target="_blank"
         aria-label="Falar no WhatsApp"
         style="position:fixed;bottom:32px;right:32px;z-index:9999;background:#25D366;color:#fff;padding:16px 20px;border-radius:50px;box-shadow:0 4px 16px rgba(0,0,0,0.15);display:flex;align-items:center;gap:8px;font-size:1.6rem;text-decoration:none;animation:whatsapp-blink 1s infinite alternate;">
        <span class="material-icons-round" style="font-size:2rem;">whatsapp</span>
    </a>
        <style>
        @keyframes whatsapp-blink {
            0% { filter: brightness(1); box-shadow:0 4px 16px rgba(0,0,0,0.15); }
            50% { filter: brightness(1.5); box-shadow:0 0 32px #25D366; }
            100% { filter: brightness(1); box-shadow:0 4px 16px rgba(0,0,0,0.15); }
        }
        .whatsapp-blink { animation: whatsapp-blink 1s infinite alternate; }
        </style>

    <!-- Scripts -->
    <script src="assets/js/professional.js"></script>
    <script src="assets/js/enhanced-interactions.js"></script>
    
    <!-- Contact Form Script -->
    <script>
    document.getElementById('contact-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const form = this;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Mostrar loading
        submitBtn.innerHTML = '<span class="material-icons-round">hourglass_empty</span> Enviando...';
        submitBtn.disabled = true;
        
        // Criar FormData
        const formData = new FormData(form);
        
        // Enviar via fetch
        fetch('send_email.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Sucesso
                alert('✅ ' + data.message);
                form.reset();
            } else {
                // Erro
                alert('❌ ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('❌ Erro ao enviar mensagem. Tente novamente ou entre em contato pelo WhatsApp.');
        })
        .finally(() => {
            // Restaurar botão
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
    </script>
</body>
</html>
