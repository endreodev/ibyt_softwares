# ERP Água - Sistema de Monitoramento de Nível de Água

Sistema completo para gerenciamento de reservatórios, dispositivos IoT, clientes e monitoramento de nível de água em tempo real.

## 🚀 Acesso Rápido

- **Gestão de Reservatórios**: http://localhost/web-nivel-certo/gestao_reservatorios.html
- **Setup Direto**: http://localhost/web-nivel-certo/setup_direto.php
- **Teste de Setup**: http://localhost/web-nivel-certo/teste_setup.html
- **Dashboard Admin**: http://localhost/web-nivel-certo/admin.html
- **API IoT**: http://localhost/web-nivel-certo/api/medicao.php?D{ID}N{NIVEL}

## 👤 Credenciais Padrão

### Administrador
- **Email**: admin@sistema.com
- **Senha**: password

## 📁 Estrutura Principal

```
web-nivel-certo/
├── gestao_reservatorios.html    # Interface principal de gestão
├── setup_direto.php            # Setup simplificado do banco
├── teste_setup.html            # Página de teste do setup
├── admin.html                  # Dashboard administrativo
├── api/                        # APIs REST
│   ├── medicao.php            # API IoT para sensores
│   ├── reservatorios_completo.php # CRUD reservatórios
```

## 🛠 Funcionalidades Principais

### Sistema de Reservatórios
- ✅ **Gestão Completa** - CRUD de reservatórios
- ✅ **Vinculação de Dispositivos** - Associar sensores IoT
- ✅ **Monitoramento em Tempo Real** - Status de níveis
- ✅ **Alertas Automáticos** - Níveis críticos/baixos
- ✅ **Histórico de Medições** - Dados temporais

### API IoT
- `GET /api/medicao.php?D{ID}N{NIVEL}` - Receber medição de sensor
- `GET /api/reservatorios_completo.php?action=listar` - Listar reservatórios
- `POST /api/reservatorios_completo.php` - Criar/editar reservatório
- `GET /api/verificar_banco.php` - Status do banco de dados

### Interface Web
- **Gestão Visual** - Cards com status coloridos
- **Dashboard Executivo** - Contadores por status
- **Filtros Inteligentes** - Por cliente e nome
- **Responsive Design** - Funciona em mobile

## � Banco de Dados

### Tabelas Criadas (5 principais)
- `usuarios` - Sistema de login
- `clientes` - Empresas/pessoas cadastradas
- `dispositivos` - Sensores IoT (ID fixo)
- `reservatorios` - Tanques monitorados
- `medicoes` - Dados dos sensores em tempo real

### Dados de Exemplo Incluídos
- 1 usuário administrador
- 3 clientes (Fazenda, Condomínio, Agro Tech)
- 6 dispositivos IoT
- 5 reservatórios configurados
- Medições históricas

## 🚀 Como Usar

### 1. Setup Inicial
```bash
# Acesse o setup direto (recomendado)
http://localhost/web-nivel-certo/setup_direto.php

# Ou use a interface de setup
http://localhost/web-nivel-certo/setup_banco.html
```

### 2. Acessar o Sistema
```bash
# Interface principal
http://localhost/web-nivel-certo/gestao_reservatorios.html

# Login: admin@sistema.com / password
```

### 3. Testar IoT
```bash
# Enviar medição de teste
http://localhost/web-nivel-certo/api/medicao.php?D1N120

# Dispositivo 1, Nível 120cm
```

## 🔧 Configuração

### Requisitos
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx
- Composer (para dependências)

### Configuração do Banco
Edite `config/database.php` com suas credenciais:
```php
$host = 'localhost';
$database = 'erp_agua';
$username = 'root';
$password = '';
```

## � Monitoramento

### Status de Níveis
- 🚨 **CRÍTICO** - Abaixo do nível mínimo
- ⚠️ **BAIXO** - Próximo ao nível mínimo
- ✅ **NORMAL** - Nível adequado
- 🔵 **ALTO** - Próximo ao nível máximo

### Integração IoT
O sistema aceita medições via URL simples:
```
GET /api/medicao.php?D{DEVICE_ID}N{NIVEL_CM}

Exemplos:
- D1N150 (Dispositivo 1, 150cm)
- D5N75  (Dispositivo 5, 75cm)
```

## 📝 Logs e Debug

### Logs Disponíveis
- `logs/setup_debug.log` - Debug do setup
- Logs são criados automaticamente


## 🔧 Instalação Rápida

1. **Execute o setup direto**: `http://localhost/web-nivel-certo/setup_direto.php`
2. **Acesse o sistema**: `http://localhost/web-nivel-certo/gestao_reservatorios.html`
3. **Login**: admin@sistema.com / password
4. **Teste IoT**: `http://localhost/web-nivel-certo/api/medicao.php?D1N120`

## 🔐 Segurança

- Autenticação por token
- Senhas criptografadas (bcrypt)
- Validação de dados
- Proteção contra SQL injection
- Headers de segurança

---
**Sistema ERP Água - IBYT Software** 💧
