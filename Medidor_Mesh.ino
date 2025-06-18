#include <WiFi.h>
#include <WebServer.h>
#include <EEPROM.h>
#include <LoRa.h>
#include <SPI.h>
#include <HTTPClient.h>
#include <DNSServer.h>

// Pinos LoRa
#define SS      18
#define RST     14
#define DIO0    26

// Estrutura de configuração
struct Config {
  char ssid[32];
  char password[64];
  char cliente[8];
  char reservatorio[8];
  char endpoint[128];
  char recipiente[16]; // ID do gateway ou outro medidor
  bool configurado;
};

Config config;
WebServer server(80);
DNSServer dnsServer;
bool modoAP = false;
const byte DNS_PORT = 53;
bool autenticado = false;
const char* SENHA_PADRAO = "#1@2#3$4%5";

void setup() {
  Serial.begin(115200);
  delay(1000);
  EEPROM.begin(sizeof(Config));
  carregarConfig();
  setupLoRa();
  if (!config.configurado || !conectarWiFi()) {
    iniciarAP();
    modoAP = true;
  }
  // Rotas web
  server.on("/", handleRoot);
  server.on("/login", HTTP_POST, handleLogin);
  server.on("/logout", handleLogout);
  server.on("/save", HTTP_POST, handleSave);
  server.on("/scan", handleWifiScan);
  server.onNotFound([]() { server.send(404, "text/plain", "Não encontrado"); });
  server.begin();
  Serial.println("Servidor HTTP iniciado");
}

void loop() {
  if (modoAP) dnsServer.processNextRequest();
  server.handleClient();

  // Medição periódica (exemplo: a cada 60s)
  static unsigned long ultimaMedicao = 0;
  if (millis() - ultimaMedicao > 60000 && !modoAP) {
    ultimaMedicao = millis();
    enviarMedicao();
  }

  // Recebe dados LoRa
  int tam = LoRa.parsePacket();
  if (tam) {
    String recebido = "";
    while (LoRa.available()) recebido += (char)LoRa.read();
    Serial.println("Recebido via LoRa: " + recebido);
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

// Função para realizar a medição e enviar
void enviarMedicao() {
  String nivel = lerNivel(); // Implemente a leitura real do sensor
  String dado = String(config.cliente) + String(config.reservatorio) + "N" + nivel;
  if (!enviarParaGateway(dado)) {
    encaminharParaOutroMedidor(dado);
  }
}

// Envia via LoRa para o gateway
bool enviarParaGateway(String dado) {
  // Supondo que o gateway tem um endereço LoRa conhecido (exemplo: config.recipiente)
  LoRa.beginPacket();
  LoRa.print(dado);
  LoRa.endPacket();
  Serial.println("Enviado para gateway: " + dado);
  // Não há confirmação LoRa, então retorna false se necessário implementar ACK
  // Aqui, sempre retorna false para simular falha e testar mesh
  return false;
}

// Encaminha para outro medidor (mesh)
void encaminharParaOutroMedidor(String dado) {
  // Implemente lógica para escolher outro medidor (broadcast ou endereço específico)
  LoRa.beginPacket();
  LoRa.print(dado);
  LoRa.endPacket();
  Serial.println("Encaminhado para outro medidor: " + dado);
}

// Envia para API via HTTP
void enviarParaAPI(String dado) {
  HTTPClient http;
  String url = String(config.endpoint) + dado;
  http.begin(url);
  int resp = http.GET();
  Serial.println("Enviado para API: " + url + " Código: " + String(resp));
  http.end();
}

// Simulação de leitura de nível
String lerNivel() {
  // Substitua por leitura real do sensor
  int nivel = random(0, 100);
  return String(nivel);
}

void carregarConfig() {
  EEPROM.get(0, config);
  
  // Define valores padrão se não estiver configurado
  if (!config.configurado) {
    strcpy(config.endpoint, "https://ibyt.com.br/api.php/");
    strcpy(config.cliente, "C1");
    strcpy(config.reservatorio, "R1");
    strcpy(config.recipiente, "GATEWAY");
  }
  
  Serial.println("Configuração carregada:");
  Serial.println("SSID: " + String(config.ssid));
  Serial.println("Cliente: " + String(config.cliente));
  Serial.println("Reservatório: " + String(config.reservatorio));
  Serial.println("Endpoint: " + String(config.endpoint));
  Serial.println("Recipiente: " + String(config.recipiente));
  Serial.println("Configurado: " + String(config.configurado));
}

void salvarConfig() {
  EEPROM.put(0, config);
  EEPROM.commit();
  Serial.println("Configuração salva na EEPROM");
}

bool conectarWiFi() {
  if (strlen(config.ssid) == 0) return false;
  
  WiFi.mode(WIFI_STA);
  WiFi.begin(config.ssid, config.password);
  
  Serial.print("Conectando ao WiFi");
  int tentativas = 0;
  while (WiFi.status() != WL_CONNECTED && tentativas < 20) {
    delay(500);
    Serial.print(".");
    tentativas++;
  }
  
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nConectado ao WiFi");
    Serial.println("Endereço IP: " + WiFi.localIP().toString());
    return true;
  } else {
    Serial.println("\nFalha ao conectar ao WiFi");
    return false;
  }
}

void iniciarAP() {
  WiFi.mode(WIFI_AP);
  WiFi.softAP("Medidor_LoRa_Config");
  
  IPAddress IP = WiFi.softAPIP();
  Serial.print("Endereço IP do AP: ");
  Serial.println(IP);
  
  dnsServer.start(DNS_PORT, "*", IP);
}

bool verificarAuth() {
  if (autenticado) return true;
  
  server.sendHeader("Location", "/");
  server.send(301);
  return false;
}

void handleLogin() {
  if (server.hasArg("password")) {
    if (server.arg("password") == String(SENHA_PADRAO)) {
      autenticado = true;
      server.sendHeader("Location", "/");
      server.send(301);
      return;
    }
  }
  
  String html = "<!DOCTYPE html>"
                "<html>"
                "<head>"
                "<meta name='viewport' content='width=device-width, initial-scale=1.0'>"
                "<title>Falha no Login</title>"
                "<style>"
                "body{font-family:Arial,sans-serif;margin:0;padding:20px;max-width:800px;margin:0 auto;text-align:center;}"
                "h1{color:#333;}"
                "button{background-color:#4CAF50;color:white;padding:10px 15px;border:none;cursor:pointer;margin-top:20px;}"
                "</style>"
                "</head>"
                "<body>"
                "<h1>Falha no Login</h1>"
                "<p>Senha incorreta. Por favor, tente novamente.</p>"
                "<button onclick='location.href=\"/\"'>Voltar ao Login</button>"
                "</body>"
                "</html>";
                
  server.send(200, "text/html", html);
}

void handleLogout() {
  autenticado = false;
  server.sendHeader("Location", "/");
  server.send(301);
}

void handleRoot() {
  if (autenticado) {
    String html = "<!DOCTYPE html>"
                  "<html>"
                  "<head>"
                  "<meta name='viewport' content='width=device-width, initial-scale=1.0'>"
                  "<title>Configuração do Medidor LoRa</title>"
                  "<style>"
                  "body{font-family:Arial,sans-serif;margin:0;padding:20px;max-width:800px;margin:0 auto;}"
                  "h1{color:#333;}"
                  ".form-group{margin-bottom:15px;}"
                  "label{display:block;margin-bottom:5px;font-weight:bold;}"
                  "input[type=text],input[type=password]{width:100%;padding:8px;box-sizing:border-box;}"
                  "button{background-color:#4CAF50;color:white;padding:10px 15px;border:none;cursor:pointer;}"
                  "button:hover{background-color:#45a049;}"
                  "#networks{margin-top:10px;}"
                  ".network{padding:5px;cursor:pointer;}"
                  ".network:hover{background-color:#f0f0f0;}"
                  ".logout{position:absolute;top:20px;right:20px;background-color:#f44336;}"
                  "</style>"
                  "</head>"
                  "<body>"
                  "<h1>Configuração do Medidor LoRa</h1>"
                  "<button class='logout' onclick='location.href=\"/logout\"'>Sair</button>"
                  "<form id='configForm' method='post' action='/save'>"
                  "<div class='form-group'>"
                  "<label for='ssid'>Rede WiFi:</label>"
                  "<input type='text' id='ssid' name='ssid' value='" + String(config.ssid) + "' required>"
                  "<button type='button' onclick='scanNetworks()'>Buscar Redes</button>"
                  "<div id='networks'></div>"
                  "</div>"
                  "<div class='form-group'>"
                  "<label for='password'>Senha do WiFi:</label>"
                  "<input type='password' id='password' name='password' value='" + String(config.password) + "'>"
                  "</div>"
                  "<div class='form-group'>"
                  "<label for='cliente'>Código do Cliente (C):</label>"
                  "<input type='text' id='cliente' name='cliente' value='" + String(config.cliente) + "' required>"
                  "</div>"
                  "<div class='form-group'>"
                  "<label for='reservatorio'>Código do Reservatório (R):</label>"
                  "<input type='text' id='reservatorio' name='reservatorio' value='" + String(config.reservatorio) + "' required>"
                  "</div>"
                  "<div class='form-group'>"
                  "<label for='endpoint'>URL do Endpoint API:</label>"
                  "<input type='text' id='endpoint' name='endpoint' value='" + String(config.endpoint) + "' required>"
                  "</div>"
                  "<div class='form-group'>"
                  "<label for='recipiente'>ID do Recipiente (Gateway ou Medidor próximo):</label>"
                  "<input type='text' id='recipiente' name='recipiente' value='" + String(config.recipiente) + "' required>"
                  "</div>"
                  "<button type='submit'>Salvar Configuração</button>"
                  "</form>"
                  "<script>"
                  "function scanNetworks() {"
                  "  document.getElementById('networks').innerHTML = 'Buscando...';"
                  "  fetch('/scan')"
                  "    .then(response => response.json())"
                  "    .then(data => {"
                  "      let networksDiv = document.getElementById('networks');"
                  "      networksDiv.innerHTML = '';"
                  "      data.networks.forEach(network => {"
                  "        let div = document.createElement('div');"
                  "        div.className = 'network';"
                  "        div.textContent = network;"
                  "        div.onclick = function() { document.getElementById('ssid').value = network; };"
                  "        networksDiv.appendChild(div);"
                  "      });"
                  "    })"
                  "    .catch(error => {"
                  "      document.getElementById('networks').innerHTML = 'Erro ao buscar redes';"
                  "    });"
                  "}"
                  "</script>"
                  "</body>"
                  "</html>";

    server.send(200, "text/html", html);
  } else {
    // Exibe formulário de login
    String html = "<!DOCTYPE html>"
                  "<html>"
                  "<head>"
                  "<meta name='viewport' content='width=device-width, initial-scale=1.0'>"
                  "<title>Login do Medidor LoRa</title>"
                  "<style>"
                  "body{font-family:Arial,sans-serif;margin:0;padding:20px;max-width:800px;margin:0 auto;}"
                  "h1{color:#333;}"
                  ".form-group{margin-bottom:15px;}"
                  "label{display:block;margin-bottom:5px;font-weight:bold;}"
                  "input[type=password]{width:100%;padding:8px;box-sizing:border-box;}"
                  "button{background-color:#4CAF50;color:white;padding:10px 15px;border:none;cursor:pointer;}"
                  "button:hover{background-color:#45a049;}"
                  "</style>"
                  "</head>"
                  "<body>"
                  "<h1>Login do Medidor LoRa</h1>"
                  "<form method='post' action='/login'>"
                  "<div class='form-group'>"
                  "<label for='password'>Senha de Administrador:</label>"
                  "<input type='password' id='password' name='password' required>"
                  "</div>"
                  "<button type='submit'>Entrar</button>"
                  "</form>"
                  "</body>"
                  "</html>";

    server.send(200, "text/html", html);
  }
}

void handleSave() {
  if (!verificarAuth()) return;
  
  if (server.hasArg("ssid") && server.hasArg("password") && 
      server.hasArg("cliente") && server.hasArg("reservatorio") && 
      server.hasArg("endpoint") && server.hasArg("recipiente")) {
    
    server.arg("ssid").toCharArray(config.ssid, sizeof(config.ssid));
    server.arg("password").toCharArray(config.password, sizeof(config.password));
    server.arg("cliente").toCharArray(config.cliente, sizeof(config.cliente));
    server.arg("reservatorio").toCharArray(config.reservatorio, sizeof(config.reservatorio));
    server.arg("endpoint").toCharArray(config.endpoint, sizeof(config.endpoint));
    server.arg("recipiente").toCharArray(config.recipiente, sizeof(config.recipiente));
    
    config.configurado = true;
    salvarConfig();
    
    String html = "<!DOCTYPE html>"
                  "<html>"
                  "<head>"
                  "<meta name='viewport' content='width=device-width, initial-scale=1.0'>"
                  "<title>Configuração Salva</title>"
                  "<style>"
                  "body{font-family:Arial,sans-serif;margin:0;padding:20px;max-width:800px;margin:0 auto;text-align:center;}"
                  "h1{color:#333;}"
                  "button{background-color:#4CAF50;color:white;padding:10px 15px;border:none;cursor:pointer;margin-top:20px;}"
                  "</style>"
                  "</head>"
                  "<body>"
                  "<h1>Configuração Salva com Sucesso!</h1>"
                  "<p>O dispositivo agora tentará conectar-se à rede WiFi configurada.</p>"
                  "<p>Se a conexão for bem-sucedida, o dispositivo começará a funcionar no modo normal.</p>"
                  "<p>Se a conexão falhar, o dispositivo permanecerá no modo AP.</p>"
                  "<button onclick='location.href=\"/\"'>Voltar à Configuração</button>"
                  "</body>"
                  "</html>";
                  
    server.send(200, "text/html", html);
    
    // Tenta conectar-se à rede WiFi configurada
    if (conectarWiFi()) {
      modoAP = false;
      dnsServer.stop();
    }
  } else {
    server.send(400, "text/plain", "Campos obrigatórios ausentes");
  }
}

void handleWifiScan() {
  if (!verificarAuth()) return;
  
  String response = "{\"networks\":[";
  
  int n = WiFi.scanNetworks();
  for (int i = 0; i < n; i++) {
    if (i > 0) response += ",";
    response += "\"" + WiFi.SSID(i) + "\"";
  }
  
  response += "]}";
  server.send(200, "application/json", response);
}