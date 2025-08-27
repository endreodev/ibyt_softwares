# 📱 IBYT Store - Loja de Aplicativos

Uma loja de aplicativos completa estilo App Store, desenvolvida para IBYT Software, com área pública para clientes e área administrativa para gerenciamento.

## 🌟 Funcionalidades

### 👥 **Área Pública (Clientes)**
- **Catálogo de Apps**: Grid responsivo com apps disponíveis
- **Busca e Filtros**: Busca por nome/tags e filtros por categoria
- **Detalhes do App**: Modal com screenshots, descrição, avaliações
- **Download Direto**: Download de arquivos APK
- **Apps em Destaque**: Seção especial para apps destacados
- **Responsive Design**: Funciona em desktop e mobile

### 🔧 **Área Administrativa**
- **Dashboard**: Estatísticas de apps, downloads e avaliações
- **Gerenciamento de Apps**: CRUD completo de aplicativos
- **Upload de Arquivos**: Upload de ícones, APKs e screenshots
- **Status de Apps**: Controle de publicação (ativo/rascunho/inativo)
- **Apps em Destaque**: Marcar apps para destaque na loja

## 📂 Estrutura de Arquivos

```
📁 IBYT Store/
├── 📄 loja.php                     # Página principal da loja
├── 📄 admin-loja.php               # Área administrativa
├── 📄 database.sql                 # Script de criação do banco
├── 📁 api/                         # Backend APIs
│   ├── apps.php                    # Lista apps públicos
│   ├── admin-apps.php              # Lista apps para admin
│   ├── save-app.php                # Salvar/editar apps
│   └── delete-app.php              # Excluir apps
├── 📁 assets/
│   ├── 📁 css/
│   │   ├── store.css               # Estilos da loja
│   │   └── admin-store.css         # Estilos da área admin
│   ├── 📁 js/
│   │   ├── store.js                # Funcionalidades da loja
│   │   └── admin-store.js          # Funcionalidades admin
│   └── 📁 img/
│       ├── 📁 app-icons/           # Ícones dos apps
│       └── 📁 screenshots/         # Capturas de tela
├── 📁 uploads/                     # Arquivos enviados pelo admin
└── 📁 downloads/                   # Arquivos APK para download
```

## 🚀 Instalação e Configuração

### 1. **Configuração do Servidor**
```bash
# Requisitos
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx
- Extensões: PDO, GD, fileinfo
```

### 2. **Configuração do Banco de Dados**
```sql
-- Execute o arquivo database.sql
mysql -u root -p < database.sql
```

### 3. **Configuração de Upload**
```bash
# Definir permissões para upload
chmod 755 uploads/
chmod 755 downloads/
chmod 755 assets/img/app-icons/
chmod 755 assets/img/screenshots/
```

### 4. **Configuração do PHP**
```ini
# php.ini - Ajustar limites de upload
upload_max_filesize = 50M
post_max_size = 60M
max_execution_time = 300
```

## 🛠️ Como Usar

### **Para Clientes:**
1. Acesse `loja.php`
2. Navegue pelos aplicativos disponíveis
3. Use filtros e busca para encontrar apps
4. Clique em um app para ver detalhes
5. Baixe o APK diretamente

### **Para Administradores:**
1. Acesse `admin-loja.php`
2. Use o dashboard para ver estatísticas
3. Adicione novos apps com o botão "Adicionar Aplicativo"
4. Faça upload de ícones, APKs e screenshots
5. Gerencie status e apps em destaque

## 📋 Estrutura do Banco de Dados

### **Tabela `apps`**
```sql
- id: ID único do app
- name: Nome do aplicativo
- category: Categoria (monitoramento, gestão, etc.)
- description: Descrição detalhada
- version: Versão atual
- size: Tamanho do arquivo
- rating: Avaliação média
- downloads: Número de downloads
- featured: App em destaque (boolean)
- status: active/draft/inactive
- icon: Caminho do ícone
- screenshots: JSON com screenshots
- apk_url: Caminho do arquivo APK
```

## 🎨 Personalização

### **Cores e Temas**
```css
/* Edite store.css e admin-store.css */
:root {
  --primary-color: #007bff;
  --secondary-color: #6c757d;
  --success-color: #28a745;
  --danger-color: #dc3545;
}
```

### **Categorias**
```php
// Adicione novas categorias em assets/js/store.js
const categories = {
    'nova-categoria': 'Nova Categoria',
    // ...
};
```

## 🔒 Segurança

### **Implementadas:**
- ✅ Validação de tipos de arquivo
- ✅ Sanitização de dados de entrada
- ✅ Proteção contra XSS
- ✅ Validação de upload de arquivos

### **Recomendações:**
- 🔐 Implementar autenticação robusta para admin
- 🛡️ Adicionar CSRF protection
- 🔍 Implementar rate limiting
- 📝 Adicionar logs de auditoria

## 📱 Apps de Exemplo

A loja vem com 3 aplicativos de exemplo:

1. **Nível Certo** - Monitoramento de reservatórios
2. **IBYT Monitor** - Gestão empresarial
3. **Sensor Config** - Configuração de sensores

## 🌐 APIs Disponíveis

### **Públicas:**
- `GET /api/apps.php` - Lista apps ativos

### **Administrativas:**
- `GET /api/admin-apps.php` - Lista todos os apps
- `POST /api/save-app.php` - Salvar/editar app
- `DELETE /api/delete-app.php?id=X` - Excluir app

## 📊 Funcionalidades Avançadas

### **Analytics**
- Contagem de downloads
- Estatísticas de uso
- Relatórios de performance

### **SEO**
- URLs amigáveis
- Meta tags otimizadas
- Schema.org markup

### **Performance**
- Lazy loading de imagens
- Compressão de assets
- Cache de dados

## 🎯 Roadmap

### **Versão 2.0**
- [ ] Sistema de avaliações e comentários
- [ ] Notificações push para novos apps
- [ ] Sistema de categorias dinâmicas
- [ ] Analytics avançados
- [ ] API REST completa
- [ ] Autenticação OAuth

### **Melhorias Futuras**
- [ ] PWA (Progressive Web App)
- [ ] Dark mode
- [ ] Múltiplos idiomas
- [ ] Sistema de assinaturas
- [ ] Integração com redes sociais

## 🤝 Contribuição

Para contribuir com o projeto:

1. Fork o repositório
2. Crie uma branch para sua feature
3. Faça commit das mudanças
4. Envie um Pull Request

## 📞 Suporte

**IBYT Software**
- 📧 Email: contato@ibyt.com.br
- 📱 WhatsApp: (65) 98171-9837
- 🌐 Website: https://ibyt.com.br

---

**Desenvolvido com ❤️ pela IBYT Software**
