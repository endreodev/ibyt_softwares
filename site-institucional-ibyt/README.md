# IBYT Software - Site Institucional Moderno

## 🚀 Sobre o Projeto

Site institucional moderno e responsivo da IBYT Software, especializada em consultoria ERP (Protheus e Sankhya), desenvolvimento de aplicativos móveis, desenvolvimento web e integrações de sistemas.

### ✨ Características Principais

- **Design Moderno**: Interface limpa e profissional com animações suaves
- **Responsivo**: Otimizado para todos os dispositivos (desktop, tablet, mobile)
- **Painel Administrativo**: Sistema completo para gerenciar conteúdo dinamicamente
- **Performance**: Carregamento rápido e otimizado para SEO
- **Interativo**: Formulários funcionais e efeitos visuais atraentes

## 🛠️ Tecnologias Utilizadas

### Frontend
- HTML5 semântico
- CSS3 moderno (Grid, Flexbox, Animations)
- JavaScript ES6+
- Font Awesome 6.4.0
- Google Fonts (Inter)
- Chart.js (para gráficos do admin)

### Backend (Futuro)
- PHP 8.2
- MySQL 8.0
- Docker para containerização

### Ferramentas
- Docker & Docker Compose
- Git para versionamento

## 📂 Estrutura do Projeto

```
web-ibyt/
├── index.html              # Página principal
├── admin.html              # Painel administrativo
├── assets/
│   ├── css/
│   │   ├── style.css       # Estilos principais
│   │   └── admin.css       # Estilos do admin
│   ├── js/
│   │   ├── main.js         # JavaScript principal
│   │   └── admin.js        # JavaScript do admin
│   └── img/                # Imagens e assets
├── docker-compose.yml      # Configuração Docker
├── Dockerfile              # Imagem Docker
└── README.md              # Este arquivo
```

## 🎨 Funcionalidades

### Site Principal
- **Hero Section**: Apresentação impactante com estatísticas
- **Sobre**: Informações da empresa com recursos destacados
- **Serviços**: Grid de serviços com carregamento dinâmico
- **Tecnologias**: Showcase das tecnologias utilizadas
- **Portfólio**: Projetos realizados com filtros por categoria
- **Clientes**: Logos e depoimentos de clientes
- **Contato**: Formulário funcional com validação

### Painel Administrativo
- **Dashboard**: Visão geral com métricas e gráficos
- **Gerenciar Serviços**: CRUD completo para serviços
- **Gerenciar Portfólio**: Adicionar/editar projetos realizados
- **Gerenciar Clientes**: Administrar logos e informações de clientes
- **Gerenciar Softwares**: Catalogar tecnologias utilizadas
- **Mensagens**: Visualizar e gerenciar contatos recebidos

## 🚀 Como Executar

### Opção 1: Docker (Recomendado)

```bash
# Clone o repositório
git clone <repository-url>
cd web-ibyt

# Execute com Docker Compose
docker-compose up -d

# Acesse no navegador
http://localhost:8080
```

### Opção 2: Servidor Local

```bash
# Navegue até o diretório
cd web-ibyt

# Execute um servidor HTTP simples
python -m http.server 8080
# ou
php -S localhost:8080

# Acesse no navegador
http://localhost:8080
```

## 🔐 Acesso Administrativo

Para acessar o painel administrativo:

1. Acesse: `http://localhost:8080/admin.html`
2. Credenciais padrão:
   - **Usuário**: admin
   - **Senha**: ibyt2024

> ⚠️ **Importante**: Altere as credenciais padrão em produção!

## 📱 Design Responsivo

O site foi desenvolvido com abordagem "mobile-first" e é totalmente responsivo:

- **Desktop**: Layout completo com sidebar e múltiplas colunas
- **Tablet**: Adaptação com grid flexível
- **Mobile**: Layout otimizado com menu hamburger

## 🎯 Otimizações

### Performance
- Carregamento lazy de imagens
- Compressão de assets
- Minificação de CSS/JS (produção)
- Cache otimizado

### SEO
- HTML semântico
- Meta tags otimizadas
- Structured data
- URLs amigáveis

### Acessibilidade
- Contraste adequado
- Navegação por teclado
- Alt texts em imagens
- ARIA labels

## 🔧 Configuração Avançada

### Variáveis CSS Customizáveis

```css
:root {
  --first-color: #2563eb;        /* Cor primária */
  --second-color: #10b981;       /* Cor secundária */
  --title-color: #1f2937;        /* Cor dos títulos */
  --text-color: #6b7280;         /* Cor do texto */
  --body-color: #ffffff;         /* Cor de fundo */
}
```

### Configurações do Admin

```javascript
// admin.js - Linha 45
const adminCredentials = {
    username: 'admin',
    password: 'ibyt2024'  // Altere esta senha!
};
```

## 📊 Métricas e Analytics

O painel administrativo inclui:
- Gráfico de visitas mensais
- Distribuição de projetos por categoria
- Contadores em tempo real
- Mensagens não lidas

## 🔒 Segurança

### Implementadas
- Sanitização de inputs
- Validação client-side
- Proteção contra XSS básica

### Recomendações para Produção
- Implementar autenticação JWT
- Validação server-side
- Rate limiting
- HTTPS obrigatório
- Backup automático

## 📈 Roadmap

### Próximas Funcionalidades
- [ ] Sistema de blog integrado
- [ ] Chat online com clientes
- [ ] Integração com CRM
- [ ] Multi-idioma (PT/EN/ES)
- [ ] PWA (Progressive Web App)
- [ ] Analytics avançados

### Melhorias Técnicas
- [ ] API REST completa
- [ ] Testes automatizados
- [ ] CI/CD pipeline
- [ ] Monitoramento de performance
- [ ] CDN para assets

## 🤝 Contribuindo

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## 📝 Licença

Este projeto está sob a licença MIT. Veja o arquivo `LICENSE` para mais detalhes.

## 📞 Contato

**IBYT Software**
- Website: [www.ibytsoftware.com](http://www.ibytsoftware.com)
- Email: contato@ibytsoftware.com
- Telefone: (11) 99999-9999
- LinkedIn: [IBYT Software](https://linkedin.com/company/ibyt-software)

## 🙏 Agradecimentos

- Font Awesome pelos ícones
- Google Fonts pela tipografia Inter
- Chart.js pelos gráficos
- Comunidade open source pelas inspirações

---

**Desenvolvido com ❤️ pela equipe IBYT Software**
