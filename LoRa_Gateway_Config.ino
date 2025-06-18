#include <WiFi.h>
#include <WiFiClient.h>
#include <WebServer.h>
#include <EEPROM.h>
#include <LoRa.h>
#include <SPI.h>
#include <HTTPClient.h>
#include <DNSServer.h>

// Pinos do LoRa
#define SS      18
#define RST     14
#define DIO0    26

// Estrutura de configuração
struct Config {
  char ssid[32];
  char password[64];
  char api_url[128];
  char admin_password[64];
  char allowed_devices[256]; // Lista de dispositivos permitidos, separados por vírgula
  bool check_device_ids; // Se true, verifica IDs específicos além do prefixo
  bool configured;
};

Config config;
WebServer server(80);
DNSServer dnsServer;
bool apMode = false;
const byte DNS_PORT = 53;
bool isAuthenticated = false;
const char* DEFAULT_ADMIN_PASSWORD = "#1@2#3$4%5";

// Protótipos de funções
void setupLoRa();
void handleRoot();
void handleSave();
void handleWifiScan();
void handleLogin();
void loadConfig();
void saveConfig();
void setupAP();
void forwardDataToAPI(String data);
bool checkAuth();
void handleLogout();
bool isAuthorizedDevice(String &message);

void setup() {
  Serial.begin(115200);
  delay(1000);
  
  // Inicializa a EEPROM
  EEPROM.begin(sizeof(Config));
  
  // Carrega a configuração
  loadConfig();
  
  // Configura o LoRa
  setupLoRa();
  
  // Se não estiver configurado ou a conexão falhar, inicia modo AP
  if (!config.configured || !connectToWiFi()) {
    setupAP();
    apMode = true;
  }
  
  // Configura o servidor web
  server.on("/", handleRoot);
  server.on("/login", HTTP_POST, handleLogin);
  server.on("/logout", handleLogout);
  server.on("/save", HTTP_POST, handleSave);
  server.on("/scan", handleWifiScan);
  server.onNotFound([]() {
    server.send(404, "text/plain", "Não encontrado");
  });
  
  server.begin();
  Serial.println("Servidor HTTP iniciado");
}

void loop() {
  if (apMode) {
    dnsServer.processNextRequest();
  }
  
  server.handleClient();
  
  // Verifica se há pacote LoRa
  int packetSize = LoRa.parsePacket();
  if (packetSize) {
    String receivedData = "";
    
    while (LoRa.available()) {
      receivedData += (char)LoRa.read();
    }
    
    Serial.println("Dados recebidos: " + receivedData);
    
    // Verifica se a mensagem é de um dispositivo autorizado
    if (isAuthorizedDevice(receivedData)) {
      // Remove o prefixo IBYT: da mensagem
      String cleanData = receivedData.substring(5); // Remove "IBYT:"
      
      // Encaminha para API se estiver no modo cliente
      if (!apMode) {
        forwardDataToAPI(cleanData);
      }
    } else {
      Serial.println("Mensagem ignorada: dispositivo não autorizado");
    }
  }
}

// Verifica se o dispositivo é autorizado
bool isAuthorizedDevice(String &message) {
  // Verifica se a mensagem começa com o prefixo IBYT:
  if (!message.startsWith("IBYT:")) {
    return false;
  }
  
  // Se não precisamos verificar IDs específicos, apenas o prefixo é suficiente
  if (!config.check_device_ids) {
    return true;
  }
  
  // Extrai o ID do dispositivo da mensagem (assumindo formato IBYT:ID:DADOS)
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

bool connectToWiFi() {
  if (strlen(config.ssid) == 0) return false;
  
  WiFi.mode(WIFI_STA);
  WiFi.begin(config.ssid, config.password);
  
  Serial.print("Conectando ao WiFi");
  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 20) {
    delay(500);
    Serial.print(".");
    attempts++;
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

void setupAP() {
  WiFi.mode(WIFI_AP);
  WiFi.softAP("LoRa_Gateway_Config");
  
  IPAddress IP = WiFi.softAPIP();
  Serial.print("Endereço IP do AP: ");
  Serial.println(IP);
  
  dnsServer.start(DNS_PORT, "*", IP);
}

void setupLoRa() {
  SPI.begin(5, 19, 27, 18);
  LoRa.setPins(SS, RST, DIO0);
  
  if (!LoRa.begin(915E6)) {
    Serial.println("Falha ao iniciar LoRa!");
    while (1);
  }
  
  Serial.println("LoRa inicializado com sucesso!");
}

void loadConfig() {
  EEPROM.get(0, config);
  
  // Define valores padrão se não estiver configurado
  if (!config.configured) {
    strcpy(config.api_url, "https://ibyt.com.br/api.php/");
    strcpy(config.admin_password, DEFAULT_ADMIN_PASSWORD);
    strcpy(config.allowed_devices, ""); // Lista vazia por padrão
    config.check_device_ids = false; // Por padrão, não verifica IDs específicos
  }
  
  // Se a senha de administrador estiver vazia, define o padrão
  if (strlen(config.admin_password) == 0) {
    strcpy(config.admin_password, DEFAULT_ADMIN_PASSWORD);
  }
  
  Serial.println("Configuração carregada:");
  Serial.println("SSID: " + String(config.ssid));
  Serial.println("URL da API: " + String(config.api_url));
  Serial.println("Verificar IDs: " + String(config.check_device_ids));
  Serial.println("Dispositivos permitidos: " + String(config.allowed_devices));
  Serial.println("Configurado: " + String(config.configured));
}

void saveConfig() {
  EEPROM.put(0, config);
  EEPROM.commit();
  Serial.println("Configuração salva na EEPROM");
}

void forwardDataToAPI(String data) {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    
    // Constrói URL completa (api_url + data)
    String url = String(config.api_url) + data;
    
    Serial.println("Enviando dados para: " + url);
    http.begin(url);
    
    int httpResponseCode = http.GET();
    
    if (httpResponseCode > 0) {
      String response = http.getString();
      Serial.println("Código de resposta HTTP: " + String(httpResponseCode));
      Serial.println("Resposta: " + response);
    } else {
      Serial.println("Erro na requisição HTTP. Código: " + String(httpResponseCode));
    }
    
    http.end();
  }
}

bool checkAuth() {
  if (isAuthenticated) return true;
  
  server.sendHeader("Location", "/");
  server.send(301);
  return false;
}

void handleLogin() {
  if (server.hasArg("password")) {
    if (server.arg("password") == String(config.admin_password)) {
      isAuthenticated = true;
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
  isAuthenticated = false;
  server.sendHeader("Location", "/");
  server.send(301);
}

void handleRoot() {
  if (isAuthenticated) {
    String html = "<!DOCTYPE html>"
                  "<html>"
                  "<head>"
                  "<meta name='viewport' content='width=device-width, initial-scale=1.0'>"
                  "<title>Configuração do Gateway LoRa</title>"
                  "<style>"
                  "body{font-family:Arial,sans-serif;margin:0;padding:20px;max-width:800px;margin:0 auto;}"
                  "h1{color:#333;}"
                  ".form-group{margin-bottom:15px;}"
                  "label{display:block;margin-bottom:5px;font-weight:bold;}"
                  "input[type=text],input[type=password],textarea{width:100%;padding:8px;box-sizing:border-box;}"
                  "button{background-color:#4CAF50;color:white;padding:10px 15px;border:none;cursor:pointer;}"
                  "button:hover{background-color:#45a049;}"
                  "#networks{margin-top:10px;}"
                  ".network{padding:5px;cursor:pointer;}"
                  ".network:hover{background-color:#f0f0f0;}"
                  ".logout{position:absolute;top:20px;right:20px;background-color:#f44336;}"
                  ".checkbox-group{display:flex;align-items:center;margin-bottom:15px;}"
                  ".checkbox-group input[type=checkbox]{margin-right:10px;}"
                  "</style>"
                  "</head>"
                  "<body>"
                  "<h1>Configuração do Gateway LoRa</h1>"
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
                  "<label for='api_url'>URL da API:</label>"
                  "<input type='text' id='api_url' name='api_url' value='" + String(config.api_url) + "' required>"
                  "</div>"
                  "<div class='form-group'>"
                  "<label for='admin_password'>Senha de Administrador:</label>"
                  "<input type='password' id='admin_password' name='admin_password' value='" + String(config.admin_password) + "' required>"
                  "</div>"
                  "<div class='checkbox-group'>"
                  "<input type='checkbox' id='check_device_ids' name='check_device_ids' " + (config.check_device_ids ? "checked" : "") + ">"
                  "<label for='check_device_ids'>Verificar IDs específicos de dispositivos</label>"
                  "</div>"
                  "<div class='form-group'>"
                  "<label for='allowed_devices'>Dispositivos Autorizados (separados por vírgula):</label>"
                  "<textarea id='allowed_devices' name='allowed_devices' rows='4'>" + String(config.allowed_devices) + "</textarea>"
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
                  "<title>Login do Gateway LoRa</title>"
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
                  "<h1>Login do Gateway LoRa</h1>"
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
  if (!checkAuth()) return;
  
  if (server.hasArg("ssid") && server.hasArg("password") && 
      server.hasArg("api_url") && server.hasArg("admin_password")) {
    
    server.arg("ssid").toCharArray(config.ssid, sizeof(config.ssid));
    server.arg("password").toCharArray(config.password, sizeof(config.password));
    server.arg("api_url").toCharArray(config.api_url, sizeof(config.api_url));
    server.arg("admin_password").toCharArray(config.admin_password, sizeof(config.admin_password));
    
    // Processa novas configurações de autorização
    config.check_device_ids = server.hasArg("check_device_ids");
    
    if (server.hasArg("allowed_devices")) {
      server.arg("allowed_devices").toCharArray(config.allowed_devices, sizeof(config.allowed_devices));
    }
    
    config.configured = true;
    saveConfig();
    
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
                  "<p>Se a conexão for bem-sucedida, o dispositivo reiniciará no modo cliente.</p>"
                  "<p>Se a conexão falhar, o dispositivo permanecerá no modo AP.</p>"
                  "<button onclick='location.href=\"/\"'>Voltar à Configuração</button>"
                  "</body>"
                  "</html>";
                  
    server.send(200, "text/html", html);
    
    // Tenta conectar-se à rede WiFi configurada
    if (connectToWiFi()) {
      apMode = false;
      dnsServer.stop();
    }
  } else {
    server.send(400, "text/plain", "Campos obrigatórios ausentes");
  }
}

void handleWifiScan() {
  if (!checkAuth()) return;
  
  String response = "{\"networks\":[";
  
  int n = WiFi.scanNetworks();
  for (int i = 0; i < n; i++) {
    if (i > 0) response += ",";
    response += "\"" + WiFi.SSID(i) + "\"";
  }
  
  response += "]}";
  server.send(200, "application/json", response);
}
