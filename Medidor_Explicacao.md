# Medidor LoRa Mesh - Documentação Técnica

## Visão Geral

O Medidor LoRa Mesh é um dispositivo baseado em ESP32 que coleta dados de nível (por exemplo, de reservatórios) e os transmite através de uma rede mesh LoRa. Cada medidor funciona não apenas como sensor, mas também como possível retransmissor para outros medidores, criando uma rede resiliente que minimiza falhas de comunicação.

## Arquitetura

![Arquitetura do Medidor Mesh](diagrama_medidor.png)

O Medidor possui a seguinte arquitetura:

1. **Módulo ESP32**: Microcontrolador principal
2. **Módulo LoRa**: Para comunicação sem fio de longo alcance
3. **Sensor de Nível**: Para medir o nível do reservatório
4. **Interface Web**: Para configuração do dispositivo
5. **Sistema de Mesh**: Para retransmissão de dados de outros medidores

## Principais Componentes do Código

### Estrutura de Configuração

```cpp
struct Config {
  char ssid[32];            // SSID da rede WiFi
  char password[64];        // Senha da rede WiFi
  char cliente[8];          // Código do cliente (C)
  char reservatorio[8];     // Código do reservatório (R)
  char endpoint[128];       // URL do endpoint da API
  char recipiente[16];      // ID do gateway ou outro medidor próximo
  bool configurado;         // Flag de configuração inicial
};
```

Esta estrutura armazena todas as configurações do medidor, persistidas na EEPROM.

### Fluxo de Medição e Transmissão

O medidor executa duas funções principais:

1. **Medição e envio de seus próprios dados**
2. **Retransmissão de dados de outros medidores**

#### Medição e Envio

```cpp
void enviarMedicao() {
  String nivel = lerNivel(); // Leitura do sensor
  String dado = String(config.cliente) + String(config.reservatorio) + "N" + nivel;
  if (!enviarParaGateway(dado)) {
    encaminharParaOutroMedidor(dado);
  }
}
```

#### Recepção e Retransmissão

```cpp
void loop() {
  // ...
  
  // Recebe dados LoRa
  int tam = LoRa.parsePacket();
  if (tam) {
    String recebido = "";
    while (LoRa.available()) recebido += (char)LoRa.read();
    
    // Tenta enviar para gateway
    if (!enviarParaGateway(recebido)) {
      // Se não conseguir e tiver WiFi, envia para API
      if (WiFi.status() == WL_CONNECTED) {
        enviarParaAPI(recebido);
      } else {
        // Encaminha para outro medidor
        encaminharParaOutroMedidor(recebido);
      }
    }
  }
}
```

## Rede Mesh

A funcionalidade de rede mesh é implementada através de três métodos principais:

### 1. Envio para o Gateway

```cpp
bool enviarParaGateway(String dado) {
  LoRa.beginPacket();
  LoRa.print(dado);
  LoRa.endPacket();
  Serial.println("Enviado para gateway: " + dado);
  return false; // Simula falha para testar mesh
}
```

### 2. Encaminhamento para Outro Medidor

```cpp
void encaminharParaOutroMedidor(String dado) {
  LoRa.beginPacket();
  LoRa.print(dado);
  LoRa.endPacket();
  Serial.println("Encaminhado para outro medidor: " + dado);
}
```

### 3. Envio Direto para API (Backup)

```cpp
void enviarParaAPI(String dado) {
  HTTPClient http;
  String url = String(config.endpoint) + dado;
  http.begin(url);
  int resp = http.GET();
  Serial.println("Enviado para API: " + url + " Código: " + String(resp));
  http.end();
}
```

## Formato dos Dados

Os dados são enviados no formato `C2R1N50`, onde:
- `C2` = Código do cliente (configurável)
- `R1` = Código do reservatório (configurável)
- `N50` = Nível medido (50% no exemplo)

## Interface de Configuração

O Medidor fornece uma interface web para configuração. Para acessá-la:

1. Conecte-se à rede WiFi "Medidor_LoRa_Config" (modo AP)
2. Acesse 192.168.4.1 no navegador
3. Faça login com a senha padrão "#1@2#3$4%5"

A interface permite configurar:
- Rede WiFi (SSID e senha)
- Código do cliente
- Código do reservatório
- URL do endpoint da API
- ID do recipiente (gateway ou outro medidor próximo)

## Segurança

### Autenticação da Interface Web

```cpp
void handleLogin() {
  if (server.hasArg("password")) {
    if (server.arg("password") == String(SENHA_PADRAO)) {
      autenticado = true;
      server.sendHeader("Location", "/");
      server.send(301);
      return;
    }
  }
  
  // Exibe mensagem de erro
}
```

## Estratégia de Minimização de Falhas

A rede mesh implementa as seguintes estratégias para minimizar falhas:

1. **Múltiplos Caminhos**: Os dados podem seguir diferentes rotas até o servidor
2. **Envio Direto para API**: Se o medidor tiver WiFi, pode enviar diretamente
3. **Armazenamento e Encaminhamento**: Os medidores podem retransmitir dados de outros dispositivos
4. **Tentativas de Reconexão**: Tentativas periódicas de conectar ao WiFi

## Conclusão

O Medidor LoRa Mesh é um componente fundamental da rede mesh, proporcionando:

1. **Coleta de Dados**: Medição periódica de níveis
2. **Resiliência**: Capacidade de encaminhar dados por múltiplos caminhos
3. **Flexibilidade**: Configuração através de interface web
4. **Operação Autônoma**: Funcionamento mesmo sem conexão direta com o gateway

Esta implementação cria uma rede inteligente de medidores que cooperam entre si para garantir que os dados cheguem ao servidor, mesmo em condições adversas ou em locais com cobertura limitada.
