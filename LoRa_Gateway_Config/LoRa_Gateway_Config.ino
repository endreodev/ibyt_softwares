#include <WiFi.h>
#include <HTTPClient.h>
#include <WebServer.h>
#include <EEPROM.h>
#include <LoRa.h>

// ====== CONFIGURAÇÕES EEPROM ======
#define EEPROM_SIZE 512
#define FLAG_ADDR 0
#define SSID_ADDR 1
#define PASS_ADDR 33

// ====== RESET E PINOS ======
#define RESET_PIN 0

// ====== LORA CONFIG ======
#define LORA_SS 5
#define LORA_RST 14
#define LORA_DIO0 2
#define NETWORK_ID 0x01
#define GATEWAY_ADDRESS 1
#define SECRET_KEY 0x5A

// ====== CREDENCIAIS E SERVIDOR ======
char ssid[32] = "";
char password[64] = "";
const char* serverUrl = "http://ibyt.com.br/api.php/";

// ====== OBJETOS ======
WebServer server(80);
String receivedData = "";

// ====== EEPROM ======
void clearEEPROM() {
  for (int i = 0; i < 96; i++) EEPROM.write(i, 0);
  EEPROM.write(FLAG_ADDR, 0);
  EEPROM.commit();
}

bool credentialsExist() {
  return EEPROM.read(FLAG_ADDR) == 1;
}

void saveCredentials(String newSsid, String newPass) {
  newSsid.toCharArray(ssid, 32);
  newPass.toCharArray(password, 64);
  for (int i = 0; i < 32; i++) EEPROM.write(SSID_ADDR + i, ssid[i]);
  for (int i = 0; i < 64; i++) EEPROM.write(PASS_ADDR + i, password[i]);
  EEPROM.write(FLAG_ADDR, 1);
  EEPROM.commit();
}

void loadCredentials() {
  for (int i = 0; i < 32; i++) ssid[i] = EEPROM.read(SSID_ADDR + i);
  for (int i = 0; i < 64; i++) password[i] = EEPROM.read(PASS_ADDR + i);
}

// ====== CONFIG WEB ======
void handleRoot() {
  String html = R"=====(<!DOCTYPE html><html><head>
  <meta name='viewport' content='width=device-width, initial-scale=1'>
  <title>Configuração WiFi</title></head><body>
  <h1>Gateway WiFi Configuração</h1>
  <form action='/save' method='POST'>
    SSID: <input type='text' name='ssid'><br>
    Senha: <input type='password' name='password'><br>
    <input type='submit' value='Salvar'>
  </form>
  </body></html>)=====";
  server.send(200, "text/html", html);
}

void handleSave() {
  if (server.hasArg("ssid") && server.hasArg("password")) {
    String newSsid = server.arg("ssid");
    String newPass = server.arg("password");
    saveCredentials(newSsid, newPass);
    server.send(200, "text/html", "<h1>Credenciais salvas! Reiniciando...</h1>");
    delay(1000);
    ESP.restart();
  } else {
    server.send(400, "text/html", "<h1>Erro: SSID ou senha ausente</h1>");
  }
}

void startConfigPortal() {
  WiFi.mode(WIFI_AP);
  WiFi.softAP("Gateway-Config", "12345678");

  Serial.println("Modo de configuração iniciado");
  Serial.println("Conecte-se ao WiFi 'Gateway-Config'");

  server.on("/", handleRoot);
  server.on("/save", handleSave);
  server.begin();

  unsigned long startTime = millis();
  while (millis() - startTime < 300000) {
    server.handleClient();
    delay(1);
  }

  Serial.println("Tempo esgotado. Reiniciando...");
  ESP.restart();
}

bool connectToWiFi() {
  Serial.printf("Conectando em %s\n", ssid);
  WiFi.begin(ssid, password);

  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 20) {
    delay(500);
    Serial.print(".");
    attempts++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nWiFi conectado!");
    Serial.println(WiFi.localIP());
    return true;
  }
  return false;
}

// ====== LORA ENCRYPTION ======
String encryptDecrypt(String message) {
  String result = "";
  for (int i = 0; i < message.length(); i++) {
    result += char(message[i] ^ SECRET_KEY);
  }
  return result;
}

// ====== ENVIO DADOS VIA HTTP ======
void sendDataToServer(String data) {
  if (WiFi.status() == WL_CONNECTED) {
    WiFiClient client;
    HTTPClient http;

    String url = String(serverUrl) + data;
    Serial.println("Enviando GET para: " + url);

    http.begin(client, url);
    int httpCode = http.GET();

    if (httpCode > 0) {
      Serial.printf("Código HTTP: %d\n", httpCode);
      String resposta = http.getString();
      Serial.println("Resposta do servidor: " + resposta);
    } else {
      Serial.println("Erro HTTP: " + http.errorToString(httpCode));
    }

    http.end();
  } else {
    Serial.println("WiFi desconectado. Dados não enviados.");
  }
}

// ====== SETUP ======
void setup() {
  Serial.begin(115200);
  pinMode(RESET_PIN, INPUT_PULLUP);
  EEPROM.begin(EEPROM_SIZE);

  if (digitalRead(RESET_PIN) == LOW) {
    Serial.println("Reset solicitado. Limpando EEPROM...");
    clearEEPROM();
    delay(1000);
    ESP.restart();
  }

  if (credentialsExist()) {
    loadCredentials();
    if (!connectToWiFi()) {
      Serial.println("Falha na conexão. Iniciando portal...");
      startConfigPortal();
    }
  } else {
    Serial.println("Sem credenciais. Iniciando portal...");
    startConfigPortal();
  }

  // Inicializa LoRa
  LoRa.setPins(LORA_SS, LORA_RST, LORA_DIO0);
  if (!LoRa.begin(915E6)) {
    Serial.println("Erro ao iniciar LoRa");
    while (true);
  }
  LoRa.setSyncWord(0xF3);
  Serial.println("LoRa do Gateway inicializado");
}

// ====== LOOP PRINCIPAL ======
void loop() {
  // Verifica conexão WiFi
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi caiu. Reconectando...");
    connectToWiFi();
  }

  // Recebe dados via LoRa
  int packetSize = LoRa.parsePacket();
  if (packetSize > 0) {
    uint8_t netId = LoRa.read();
    uint8_t toAddr = LoRa.read();
    uint8_t fromAddr = LoRa.read();

    if (netId != NETWORK_ID || toAddr != GATEWAY_ADDRESS) {
      while (LoRa.available()) LoRa.read(); // limpa buffer
      return;
    }

    String encrypted = "";
    while (LoRa.available()) {
      encrypted += (char)LoRa.read();
    }

    String decrypted = encryptDecrypt(encrypted);
    Serial.println("Recebido via LoRa: " + decrypted);
    sendDataToServer(decrypted);
  }

  delay(10);
}
