/**
 * Debug Script para Navigation
 * Arquivo para debug e teste da navegação
 */

console.log('🔧 IBYT Navigation Debug - Iniciando...');

// Verificar se os elementos existem
document.addEventListener('DOMContentLoaded', () => {
    console.log('📋 Verificando elementos da navegação:');
    
    // Elementos principais
    const header = document.getElementById('header');
    const navigation = document.getElementById('navigation');
    const mobileToggle = document.getElementById('mobile-menu-toggle');
    const navLinks = document.querySelectorAll('.nav-link');
    const scrollLinks = document.querySelectorAll('.nav-link[data-scroll]');
    
    // Verificações
    console.log('Header:', header ? '✅ Encontrado' : '❌ Não encontrado');
    console.log('Navigation:', navigation ? '✅ Encontrado' : '❌ Não encontrado');
    console.log('Mobile Toggle:', mobileToggle ? '✅ Encontrado' : '❌ Não encontrado');
    console.log('Nav Links:', navLinks.length + ' encontrados');
    console.log('Scroll Links:', scrollLinks.length + ' encontrados');
    
    // Verificar seções
    const sections = document.querySelectorAll('section[id]');
    console.log('📄 Seções encontradas:', sections.length);
    sections.forEach(section => {
        console.log(`  - Seção: #${section.id}`);
    });
    
    // Testar eventos
    if (mobileToggle) {
        mobileToggle.addEventListener('click', () => {
            console.log('📱 Menu mobile clicado');
        });
    }
    
    navLinks.forEach((link, index) => {
        link.addEventListener('click', (e) => {
            console.log(`🔗 Link ${index + 1} clicado:`, link.textContent.trim());
            console.log(`  - href:`, link.href);
            console.log(`  - hasAttribute data-scroll:`, link.hasAttribute('data-scroll'));
            
            if (link.hasAttribute('data-scroll')) {
                const target = link.getAttribute('data-scroll');
                console.log(`  - Target: #${target}`);
                const targetElement = document.getElementById(target);
                console.log(`  - Element found:`, targetElement ? '✅' : '❌');
            } else {
                console.log(`  - Link externo - navegação normal permitida`);
            }
        });
    });
    
    // Monitorar scroll
    let scrollTimeout;
    window.addEventListener('scroll', () => {
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(() => {
            const currentSection = getCurrentSectionDebug();
            if (currentSection) {
                console.log('📍 Seção atual:', currentSection);
            }
        }, 100);
    });
});

function getCurrentSectionDebug() {
    const sections = document.querySelectorAll('section[id]');
    const scrollPosition = window.scrollY + 100;

    for (let section of sections) {
        const sectionTop = section.offsetTop;
        const sectionBottom = sectionTop + section.offsetHeight;

        if (scrollPosition >= sectionTop && scrollPosition < sectionBottom) {
            return section.id;
        }
    }
    
    return null;
}

// Verificar se CSS está carregado
setTimeout(() => {
    const header = document.getElementById('header');
    if (header) {
        const styles = window.getComputedStyle(header);
        console.log('🎨 CSS Status:');
        console.log('  - Position:', styles.position);
        console.log('  - Z-index:', styles.zIndex);
        console.log('  - Background:', styles.backgroundColor);
    }
}, 1000);

console.log('✅ IBYT Navigation Debug - Configurado!');
