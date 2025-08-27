/*=============== ENHANCED PROFESSIONAL INTERACTIONS ===============*/

class ProfessionalWebsite {
    constructor() {
        this.init();
    }

    init() {
        this.initHeader();
        this.initNavigation();
        this.initAnimations();
        this.initCounters();
        this.initContactForm();
        this.initScrollToTop();
        this.initMobileMenu();
        this.initPerformance();
    }

    // Enhanced Header with Scroll Effects
    initHeader() {
        const header = document.getElementById('header');
        if (!header) return; // Guard when page doesn't have the main header

        window.addEventListener('scroll', () => {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            // Add scrolled class for backdrop blur effect
            if (scrollTop > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }

            // Keep header always visible (removed hide/show functionality)
            header.style.transform = 'translateY(0)';
        });
    }

    // Enhanced Navigation with Active States
    initNavigation() {
        const navLinks = document.querySelectorAll('.nav-link, .mobile-nav-link');
        const sections = document.querySelectorAll('section[id]');

        // Smooth scrolling only for in-page hash links; allow normal page navigation
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                const href = link.getAttribute('href') || '';
                const isHashLink = href.startsWith('#');

                if (isHashLink) {
                    e.preventDefault();
                    const targetId = href.substring(1);
                    const targetSection = document.getElementById(targetId);

                    if (targetSection) {
                        const offsetTop = targetSection.offsetTop - 80;
                        window.scrollTo({
                            top: offsetTop,
                            behavior: 'smooth'
                        });
                    }
                }

                // Close mobile menu if open (do not block normal navigation)
                this.closeMobileMenu();
            });
        });

        // Update active navigation on scroll
        window.addEventListener('scroll', () => {
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop - 100;
                const sectionHeight = section.clientHeight;
                
                if (window.pageYOffset >= sectionTop && 
                    window.pageYOffset < sectionTop + sectionHeight) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${current}`) {
                    link.classList.add('active');
                }
            });
        });
    }

    // Enhanced Animation System
    initAnimations() {
        // Intersection Observer for scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const element = entry.target;
                    
                    // Add animation classes based on element position
                    if (element.classList.contains('animate-slide-in-left') ||
                        element.classList.contains('animate-slide-in-right') ||
                        element.classList.contains('animate-slide-in-up') ||
                        element.classList.contains('animate-fade-in')) {
                        element.style.opacity = '1';
                        element.style.transform = 'translateX(0) translateY(0)';
                    }

                    // Special handling for cards
                    if (element.classList.contains('card-professional')) {
                        element.style.animation = 'slideInUp 0.6s ease forwards';
                    }

                    observer.unobserve(element);
                }
            });
        }, observerOptions);

        // Observe elements for animation
        const animateElements = document.querySelectorAll(
            '.animate-slide-in-left, .animate-slide-in-right, .animate-slide-in-up, .animate-fade-in, .card-professional'
        );
        
        animateElements.forEach(el => {
            // Set initial states
            if (el.classList.contains('animate-slide-in-left')) {
                el.style.opacity = '0';
                el.style.transform = 'translateX(-50px)';
            } else if (el.classList.contains('animate-slide-in-right')) {
                el.style.opacity = '0';
                el.style.transform = 'translateX(50px)';
            } else if (el.classList.contains('animate-slide-in-up')) {
                el.style.opacity = '0';
                el.style.transform = 'translateY(50px)';
            } else if (el.classList.contains('animate-fade-in')) {
                el.style.opacity = '0';
            }

            el.style.transition = 'all 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
            observer.observe(el);
        });

        // Parallax effect for hero section
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const heroElements = document.querySelectorAll('.floating-card');
            
            heroElements.forEach((element, index) => {
                const speed = 0.5 + (index * 0.1);
                element.style.transform = `translateY(${scrolled * speed}px)`;
            });
        });
    }

    // Enhanced Counter Animation
    initCounters() {
        const counters = document.querySelectorAll('.stat-number');
        
        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.animateCounter(entry.target);
                    counterObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        counters.forEach(counter => {
            counterObserver.observe(counter);
        });
    }

    animateCounter(element) {
        const target = parseInt(element.dataset.count);
        const duration = 2000;
        const step = target / (duration / 16);
        let current = 0;

        const timer = setInterval(() => {
            current += step;
            if (current >= target) {
                element.textContent = target;
                clearInterval(timer);
            } else {
                element.textContent = Math.floor(current);
            }
        }, 16);
    }

    // Enhanced Contact Form
    initContactForm() {
        const form = document.getElementById('contact-form');
        if (!form) return;

        // Form validation
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearFieldError(input));
        });

        form.addEventListener('submit', (e) => this.handleFormSubmit(e));
    }

    validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let errorMessage = '';

        // Remove previous error
        this.clearFieldError(field);

        // Required field validation
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            errorMessage = 'Este campo é obrigatório';
        }

        // Email validation
        if (field.type === 'email' && value && !this.isValidEmail(value)) {
            isValid = false;
            errorMessage = 'Digite um e-mail válido';
        }

        if (!isValid) {
            this.showFieldError(field, errorMessage);
        }

        return isValid;
    }

    showFieldError(field, message) {
        field.classList.add('error');
        
        const errorElement = document.createElement('span');
        errorElement.className = 'field-error';
        errorElement.textContent = message;
        errorElement.style.cssText = `
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: block;
        `;
        
        field.parentNode.appendChild(errorElement);
    }

    clearFieldError(field) {
        field.classList.remove('error');
        const errorElement = field.parentNode.querySelector('.field-error');
        if (errorElement) {
            errorElement.remove();
        }
    }

    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    async handleFormSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const formData = new FormData(form);
        
        // Validate all fields
        const inputs = form.querySelectorAll('input, select, textarea');
        let isFormValid = true;
        
        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isFormValid = false;
            }
        });

        if (!isFormValid) {
            this.showNotification('Por favor, corrija os erros no formulário', 'error');
            return;
        }

        // Show loading state
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = `
            <span class="material-icons-round rotating">autorenew</span>
            <span>Enviando...</span>
        `;
        submitBtn.disabled = true;

        try {
            // Simulate form submission (replace with actual endpoint)
            await this.submitForm(formData);
            
            this.showNotification('Mensagem enviada com sucesso! Entraremos em contato em breve.', 'success');
            form.reset();
            
        } catch (error) {
            this.showNotification('Erro ao enviar mensagem. Tente novamente ou entre em contato por telefone.', 'error');
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    }

    async submitForm(formData) {
        // Simulate API call - replace with actual implementation
        return new Promise((resolve, reject) => {
            setTimeout(() => {
                // Simulate success (90% chance) or failure (10% chance)
                if (Math.random() > 0.1) {
                    resolve({ success: true });
                } else {
                    reject(new Error('Simulation error'));
                }
            }, 2000);
        });
    }

    // Enhanced Notification System
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification--${type}`;
        
        const icon = type === 'success' ? 'check_circle' : 
                    type === 'error' ? 'error' : 'info';
        
        notification.innerHTML = `
            <span class="material-icons-round">${icon}</span>
            <span>${message}</span>
            <button class="notification-close">
                <span class="material-icons-round">close</span>
            </button>
        `;
        
        notification.style.cssText = `
            position: fixed;
            top: 2rem;
            right: 2rem;
            background: white;
            color: var(--corporate-gray);
            padding: 1rem 1.5rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            z-index: 10000;
            max-width: 400px;
            animation: slideInRight 0.3s ease forwards;
            border-left: 4px solid ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        `;

        // Close button functionality
        const closeBtn = notification.querySelector('.notification-close');
        closeBtn.style.cssText = `
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--corporate-gray-light);
        `;
        
        closeBtn.addEventListener('click', () => this.removeNotification(notification));

        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => this.removeNotification(notification), 5000);
    }

    removeNotification(notification) {
        notification.style.animation = 'slideOutRight 0.3s ease forwards';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }

    // Scroll to Top Button
    initScrollToTop() {
        const scrollTopBtn = document.getElementById('scroll-to-top');
        if (!scrollTopBtn) return;

        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 400) {
                scrollTopBtn.classList.add('visible');
            } else {
                scrollTopBtn.classList.remove('visible');
            }
        });

        scrollTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // Mobile Menu Management
    initMobileMenu() {
        const menuToggle = document.getElementById('mobile-menu-toggle');
        const mobileNav = document.getElementById('mobile-navigation');
        const navClose = document.getElementById('mobile-nav-close');

        if (!menuToggle || !mobileNav) return;

        menuToggle.addEventListener('click', () => this.openMobileMenu());
        navClose?.addEventListener('click', () => this.closeMobileMenu());

        // Close on overlay click
        mobileNav.addEventListener('click', (e) => {
            if (e.target === mobileNav) {
                this.closeMobileMenu();
            }
        });

        // Close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && mobileNav.classList.contains('open')) {
                this.closeMobileMenu();
            }
        });
    }

    openMobileMenu() {
        const mobileNav = document.getElementById('mobile-navigation');
        mobileNav.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    closeMobileMenu() {
        const mobileNav = document.getElementById('mobile-navigation');
        mobileNav.classList.remove('open');
        document.body.style.overflow = '';
    }

    // Performance Optimization
    initPerformance() {
        // Lazy load images
        this.initLazyLoading();
        
        // Preload critical resources
        this.preloadCriticalResources();
        
        // Monitor performance
        this.monitorPerformance();
    }

    initLazyLoading() {
        const images = document.querySelectorAll('img[data-src]');
        
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        img.classList.add('loaded');
                        imageObserver.unobserve(img);
                    }
                });
            });

            images.forEach(img => imageObserver.observe(img));
        } else {
            // Fallback for older browsers
            images.forEach(img => {
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
            });
        }
    }

    preloadCriticalResources() {
        // Preload hero image
        const heroImage = new Image();
        heroImage.src = 'assets/img/hero-bg.jpg';
        
        // Preload fonts
        const fontLink = document.createElement('link');
        fontLink.rel = 'preload';
        fontLink.href = 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap';
        fontLink.as = 'style';
        document.head.appendChild(fontLink);
    }

    monitorPerformance() {
        // Monitor page load performance
        window.addEventListener('load', () => {
            if ('performance' in window) {
                const perfData = performance.getEntriesByType('navigation')[0];
                const loadTime = perfData.loadEventEnd - perfData.fetchStart;
                
                console.log(`Page Load Time: ${loadTime}ms`);
                
                // Send analytics if load time is concerning
                if (loadTime > 3000) {
                    console.warn('Page load time exceeds 3 seconds');
                }
            }
        });
    }
}

// Add CSS animations
const animationStyles = document.createElement('style');
animationStyles.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    @keyframes slideInUp {
        from { transform: translateY(50px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    @keyframes rotating {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    .rotating {
        animation: rotating 1s linear infinite;
    }
    
    .field-error {
        animation: fadeIn 0.3s ease;
    }
    
    .error {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
    }
    
    img.loaded {
        animation: fadeIn 0.5s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
`;
document.head.appendChild(animationStyles);

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new ProfessionalWebsite();
});

// Service Worker for PWA capabilities
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('ServiceWorker registered successfully');
            })
            .catch(registrationError => {
                console.log('ServiceWorker registration failed');
            });
    });
}
