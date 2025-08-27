/*=============== ENHANCED PROFESSIONAL JAVASCRIPT ===============*/

// Professional Theme Management
class ThemeManager {
    constructor() {
        this.theme = localStorage.getItem('theme') || 'light';
        this.themeButton = document.getElementById('theme-toggle');
        this.init();
    }

    init() {
        this.applyTheme();
        if (this.themeButton) {
            this.themeButton.addEventListener('click', () => this.toggleTheme());
        }
    }

    toggleTheme() {
        this.theme = this.theme === 'light' ? 'dark' : 'light';
        this.applyTheme();
        localStorage.setItem('theme', this.theme);
    }

    applyTheme() {
        document.documentElement.setAttribute('data-theme', this.theme);
        if (this.themeButton) {
            const icon = this.themeButton.querySelector('.material-icons-round');
            icon.textContent = this.theme === 'light' ? 'dark_mode' : 'light_mode';
        }
    }
}

// Professional Smooth Scrolling with Progress
class SmoothScrollManager {
    constructor() {
        this.scrollProgress = document.createElement('div');
        this.createScrollProgress();
        this.initSmoothScroll();
        this.initScrollToTop();
    }

    createScrollProgress() {
        this.scrollProgress.className = 'scroll-progress';
        this.scrollProgress.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 3px;
            background: linear-gradient(90deg, var(--md-sys-color-primary), var(--md-sys-color-secondary));
            z-index: 9999;
            transition: width 0.3s ease;
        `;
        document.body.appendChild(this.scrollProgress);

        window.addEventListener('scroll', () => {
            const scrollTop = window.pageYOffset;
            const docHeight = document.documentElement.scrollHeight - window.innerHeight;
            const scrollPercent = (scrollTop / docHeight) * 100;
            this.scrollProgress.style.width = scrollPercent + '%';
        });
    }

    initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = anchor.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    const offsetTop = targetElement.offsetTop - 80;
                    window.scrollTo({
                        top: offsetTop,
                        behavior: 'smooth'
                    });
                }
            });
        });
    }

    initScrollToTop() {
        const scrollTopBtn = document.getElementById('scroll-top');
        if (scrollTopBtn) {
            window.addEventListener('scroll', () => {
                if (window.pageYOffset > 400) {
                    scrollTopBtn.classList.add('show');
                } else {
                    scrollTopBtn.classList.remove('show');
                }
            });

            scrollTopBtn.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }
    }
}

// Professional Intersection Observer for Animations
class AnimationManager {
    constructor() {
        this.observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                    this.observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });

        this.init();
    }

    init() {
        // Observe elements for animation
        const animateElements = document.querySelectorAll(
            '.md-service-card, .md-feature-item, .md-portfolio-card, .md-hero__card'
        );
        
        animateElements.forEach(el => {
            this.observer.observe(el);
        });

        // Counter animations
        this.initCounterAnimations();
    }

    initCounterAnimations() {
        const counters = document.querySelectorAll('.stat-number, .stat-number[data-count], .md-hero__stat span:first-child, .md-stat-card span:first-child');
        
        counters.forEach(counter => {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.animateCounter(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            });
            observer.observe(counter);
        });
    }

    animateCounter(element) {
        // Ensure element has visible text and preserve original value
        const originalValue = element.getAttribute('data-original') || element.textContent.trim();
        const text = originalValue;
        
        // If text is empty or invalid, restore original
        if (!text || text === 'NaN' || text === '') {
            element.textContent = originalValue;
            return;
        }
        
        // Check if element has data-count attribute
        const dataCount = element.getAttribute('data-count');
        
        let target, suffix = '';
        
        if (dataCount) {
            // Use data-count value
            target = parseInt(dataCount);
            suffix = text.replace(/[0-9]/g, '');
        } else {
            // Handle different text patterns
            if (text.includes('%')) {
                target = parseFloat(text);
                suffix = '%';
            } else if (text.includes('Anos')) {
                target = parseInt(text);
                suffix = ' Anos';
            } else if (text.includes('+')) {
                target = parseInt(text.replace('+', ''));
                suffix = '+';
            } else {
                // Simple number
                const numMatch = text.match(/\d+/);
                target = numMatch ? parseInt(numMatch[0]) : 0;
                suffix = text.replace(/[0-9]/g, '').trim();
            }
        }
        
        // Don't animate if target is 0 or invalid
        if (!target || target === 0 || isNaN(target)) {
            element.textContent = originalValue;
            return;
        }
        
        let current = 0;
        const increment = target / 60; // 60 frames for 1 second at 60fps
        const isDecimal = text.includes('%') && target % 1 !== 0;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                if (isDecimal) {
                    element.textContent = target.toFixed(1) + suffix;
                } else {
                    element.textContent = Math.floor(target) + suffix;
                }
                clearInterval(timer);
            } else {
                if (isDecimal) {
                    element.textContent = current.toFixed(1) + suffix;
                } else {
                    element.textContent = Math.floor(current) + suffix;
                }
            }
        }, 16);
        
        // Fallback: restore original value after 3 seconds if something goes wrong
        setTimeout(() => {
            if (element.textContent === 'NaN' || element.textContent === '' || element.textContent === '0') {
                element.textContent = originalValue;
            }
        }, 3000);
    }
}

// Professional Portfolio Filter
class PortfolioManager {
    constructor() {
        this.filters = document.querySelectorAll('.md-filter-chip');
        this.items = document.querySelectorAll('.md-portfolio-card');
        this.init();
    }

    init() {
        this.filters.forEach(filter => {
            filter.addEventListener('click', (e) => {
                const category = e.target.dataset.filter;
                this.filterItems(category);
                this.updateActiveFilter(e.target);
            });
        });
    }

    filterItems(category) {
        this.items.forEach(item => {
            const itemCategory = item.dataset.category;
            
            if (category === 'all' || itemCategory === category) {
                item.style.display = 'block';
                item.style.animation = 'fadeInUp 0.5s ease forwards';
            } else {
                item.style.display = 'none';
            }
        });
    }

    updateActiveFilter(activeFilter) {
        this.filters.forEach(filter => filter.classList.remove('active'));
        activeFilter.classList.add('active');
    }
}

// Professional Form Handler
class ContactFormManager {
    constructor() {
        this.form = document.getElementById('contact-form');
        this.init();
    }

    init() {
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        }
    }

    async handleSubmit(e) {
        e.preventDefault();
        
        const submitBtn = this.form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Show loading state
        submitBtn.innerHTML = `
            <span class="material-icons-round rotating">autorenew</span>
            <span>Enviando...</span>
        `;
        submitBtn.disabled = true;

        try {
            // Simulate API call (replace with actual endpoint)
            await this.simulateSubmission();
            
            this.showNotification('Mensagem enviada com sucesso!', 'success');
            this.form.reset();
            
        } catch (error) {
            this.showNotification('Erro ao enviar mensagem. Tente novamente.', 'error');
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    }

    async simulateSubmission() {
        return new Promise(resolve => setTimeout(resolve, 2000));
    }

    showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification notification--${type}`;
        notification.innerHTML = `
            <span class="material-icons-round">
                ${type === 'success' ? 'check_circle' : 'error'}
            </span>
            <span>${message}</span>
        `;
        
        notification.style.cssText = `
            position: fixed;
            top: 2rem;
            right: 2rem;
            background: var(--md-sys-color-${type === 'success' ? 'primary' : 'error'}-container);
            color: var(--md-sys-color-on-${type === 'success' ? 'primary' : 'error'}-container);
            padding: 1rem 1.5rem;
            border-radius: var(--md-sys-shape-corner-large);
            box-shadow: var(--md-sys-elevation-level3);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            z-index: 10000;
            animation: slideInRight 0.3s ease forwards;
        `;

        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease forwards';
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }
}

// Professional Navigation Manager
class NavigationManager {
    constructor() {
        this.menuButton = document.getElementById('menu-button');
        this.drawer = document.getElementById('navigation-drawer');
        this.navLinks = document.querySelectorAll('.md-navigation__item, .md-navigation-drawer__item');
        this.init();
    }

    init() {
        if (this.menuButton && this.drawer) {
            this.menuButton.addEventListener('click', () => this.toggleDrawer());
            
            // Close drawer when clicking outside
            document.addEventListener('click', (e) => {
                if (!this.drawer.contains(e.target) && !this.menuButton.contains(e.target)) {
                    this.closeDrawer();
                }
            });
        }

        // Update active nav link on scroll
        this.initActiveNavigation();
        
        // Close mobile menu when link is clicked
        this.navLinks.forEach(link => {
            link.addEventListener('click', () => this.closeDrawer());
        });
    }

    toggleDrawer() {
        this.drawer.classList.toggle('open');
        this.menuButton.setAttribute('aria-expanded', 
            this.drawer.classList.contains('open'));
    }

    closeDrawer() {
        this.drawer.classList.remove('open');
        this.menuButton.setAttribute('aria-expanded', 'false');
    }

    initActiveNavigation() {
        const sections = document.querySelectorAll('section[id]');
        
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
            
            this.navLinks.forEach(link => {
                link.classList.remove('md-navigation__item--active');
                if (link.getAttribute('href') === `#${current}`) {
                    link.classList.add('md-navigation__item--active');
                }
            });
        });
    }
}

// Performance Monitor
class PerformanceManager {
    constructor() {
        this.init();
    }

    init() {
        // Monitor page load performance
        window.addEventListener('load', () => {
            const perfData = performance.getEntriesByType('navigation')[0];
            console.log('Page Load Time:', perfData.loadEventEnd - perfData.fetchStart, 'ms');
        });

        // Lazy load images
        this.initLazyLoading();
    }

    initLazyLoading() {
        const images = document.querySelectorAll('img[data-src]');
        
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    imageObserver.unobserve(img);
                }
            });
        });

        images.forEach(img => imageObserver.observe(img));
    }
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    @keyframes rotating {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    .rotating {
        animation: rotating 1s linear infinite;
    }
    
    .animate-in {
        animation: fadeInUp 0.6s ease forwards;
    }
    
    .md-navigation-drawer {
        position: fixed;
        top: 0;
        left: -100%;
        width: 280px;
        height: 100vh;
        background: var(--md-sys-color-surface-container);
        transition: left 0.3s ease;
        z-index: 1100;
        box-shadow: var(--md-sys-elevation-level2);
    }
    
    .md-navigation-drawer.open {
        left: 0;
    }
    
    @media (min-width: 768px) {
        .md-navigation-drawer {
            display: none;
        }
        
        .md-menu-button {
            display: none;
        }
    }
`;
document.head.appendChild(style);

// Initialize all managers when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new ThemeManager();
    new SmoothScrollManager();
    new AnimationManager();
    new PortfolioManager();
    new ContactFormManager();
    new NavigationManager();
    new PerformanceManager();
});

// Progressive Web App Support
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('ServiceWorker registered: ', registration);
            })
            .catch(registrationError => {
                console.log('ServiceWorker registration failed: ', registrationError);
            });
    });
}
