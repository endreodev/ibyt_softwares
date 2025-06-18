# Gateway LoRa - Documentação Técnica

## Visão Geral

O Gateway LoRa é um dispositivo baseado em ESP32 que serve como ponte entre os medidores que utilizam tecnologia LoRa e o servidor da empresa IBYT. Este componente é crucial na arquitetura da rede mesh, pois permite que dados coletados em campo sejam enviados para armazenamento e processamento na nuvem.

## Arquitetura

![Arquitetura do Gateway](diagrama_gateway.png)

O Gateway possui a seguinte arquitetura:

1. **Módulo ESP32**: Microcontrolador principal, responsável pelo processamento e conexões
2. **Módulo LoRa**: Para comunicação sem fio de longo alcance com os medidores
3. **Interface Web**: Permite configuração através de uma interface amigável
4. **Sistema de Autenticação**: Protege o acesso às configurações

## Principais Componentes do Código

### Estrutura de Configuração

```cpp
struct Config {
  char ssid[32];              // SSID da rede WiFi
  char password[64];          // Senha da rede WiFi
  char api_url[128];          // URL da API (padrão: https://ibyt.com.br/api.php/)
  char admin_password[64];    // Senha de administrador
  char allowed_devices[256];  // Lista de dispositivos permitidos
  bool check_device_ids;      // Flag para verificar IDs específicos
  bool configured;            // Flag de configuração inicial
};
```

Esta estrutura armazena todas as configurações do gateway, salvas na memória EEPROM do ESP32.

### Sistema de Segurança

O Gateway implementa um sistema de segurança em duas camadas:

1. **Autenticação de Interface Web**: Protege o acesso às configurações através de senha
2. **Filtragem de Dispositivos**: Verifica se as mensagens recebidas são de dispositivos autorizados

#### Verificação de Dispositivos Autorizados

```cpp
bool isAuthorizedDevice(String &message) {
  // Verifica se a mensagem começa com o prefixo IBYT:
  if (!message.startsWith("IBYT:")) {
    return false;
  }
  
  // Se não precisamos verificar IDs específicos, apenas o prefixo é suficiente
  if (!config.check_device_ids) {
    return true;
  }
  
  // Extrai o ID do dispositivo da mensagem
  int firstColon = message.indexOf(':');
  int secondColon = message.indexOf(':', firstColon + 1);
  
  if (secondColon == -1) {
    return false; // Formato incorreto
  }
  
  String deviceId = message.substring(firstColon + 1, secondColon);
  
  // Verifica se o ID está na lista de dispositivos permitidos
  String allowedList = String(config.allowed_devices);
  return allowedList.indexOf(deviceId) != -1;
}
```

Este método garante que apenas dispositivos da empresa IBYT possam enviar dados para o servidor.

## Fluxo de Operação

1. **Inicialização**:
   - Carrega configurações da EEPROM
   - Configura o módulo LoRa
   - Tenta conectar ao WiFi configurado
   - Se falhar, inicia em modo ponto de acesso (AP)

2. **Loop Principal**:
   - Processa requisições web
   - Verifica se há pacotes LoRa recebidos
   - Filtra pacotes de dispositivos não autorizados
   - Encaminha dados para a API

3. **Recepção e Encaminhamento**:
   - Recebe dados via LoRa
   - Verifica autenticidade (prefixo "IBYT:")
   - Remove o prefixo
   - Encaminha para a API no formato: `https://ibyt.com.br/api.php/C2R1N50`

## Interface de Configuração

O Gateway fornece uma interface web para configuração. Para acessá-la:

1. Conecte-se à rede WiFi "LoRa_Gateway_Config" (modo AP)
2. Acesse 192.168.4.1 no navegador
3. Faça login com a senha padrão "#1@2#3$4%5"

A interface permite configurar:
- Rede WiFi (SSID e senha)
- URL da API
- Senha de administrador
- Lista de dispositivos autorizados
- Modo de verificação de dispositivos

## Segurança e Validação

### Autenticação da Interface Web

```cpp
void handleLogin() {
  if (server.hasArg("password")) {
    if (server.arg("password") == String(config.admin_password)) {
      isAuthenticated = true;
      server.sendHeader("Location", "/");
      server.send(301);
      return;
    }
  }
  
  // Exibe mensagem de erro
}
```

### Verificação de Autenticação para Páginas Protegidas

```cpp
bool checkAuth() {
  if (isAuthenticated) return true;
  
  server.sendHeader("Location", "/");
  server.send(301);
  return false;
}
```

## Conclusão

O Gateway LoRa é um componente essencial da arquitetura da rede mesh, proporcionando:

1. **Conectividade**: Ponte entre os medidores LoRa e o servidor
2. **Segurança**: Filtragem de dispositivos não autorizados
3. **Flexibilidade**: Configuração através de interface web
4. **Robustez**: Operação em modo AP quando não há conexão WiFi

Esta implementação permite uma solução escalável e segura para coleta e transmissão de dados de medição, atendendo às necessidades específicas da empresa IBYT.
