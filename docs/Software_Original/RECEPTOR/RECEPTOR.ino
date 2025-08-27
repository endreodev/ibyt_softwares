#include <WiFi.h>
#include <WebServer.h>
#include <EEPROM.h>
#include <LoRa.h>
#include <WiFiClientSecure.h>

// ==== EEPROM ====
#define EEPROM_SIZE 96
#define FLAG_ADDR 0
#define SSID_ADDR 1
#define PASS_ADDR 33

// ==== PINO DE RESET PARA RECONFIGURAR ====
#define RESET_PIN 0

// ==== LoRa ====
#define LORA_SS 5
#define LORA_RST 14
#define LORA_DIO0 2
const uint8_t syncWord = 0x34; // Sync word para dispositivos ibyt

// ==== Servidor de configuração ====
WebServer server(80);
const char* endpointBase = "https://ibyt.com.br/nivelcerto-view/api.php/";

char ssid[32], senha[32];

void salvaWiFi(const char* s, const char* p) {
  EEPROM.write(FLAG_ADDR, 1);
  EEPROM.put(SSID_ADDR, s);
  EEPROM.put(PASS_ADDR, p);
  EEPROM.commit();
}

void carregaWiFi() {
  EEPROM.get(SSID_ADDR, ssid);
  EEPROM.get(PASS_ADDR, senha);
}

bool wifiSalvo() {
  return EEPROM.read(FLAG_ADDR) == 1;
}

void modoConfigAP() {
  WiFi.softAP("ConfigGateway", "12345678");
  Serial.println("Modo AP iniciado: conecte-se à rede 'ConfigGateway'.");

  server.on("/", HTTP_GET, []() {
    server.send(200, "text/html", R"rawliteral(
      <h2>Configuração Wi-Fi</h2>
      <form action="/save">
        SSID: <input name="ssid"><br>
        Senha: <input name="pass"><br>
        <input type="submit" value="Salvar e reiniciar">
      </form>
    )rawliteral");
  });

  server.on("/save", HTTP_GET, []() {
    String s = server.arg("ssid");
    String p = server.arg("pass");
    salvaWiFi(s.c_str(), p.c_str());
    server.send(200, "text/html", "<h3>Salvo! Reiniciando...</h3>");
    delay(3000);
    ESP.restart();
  });

  server.begin();
  while (true) {
    server.handleClient();
    delay(10);
  }
}

void conectaWiFi() {
  WiFi.begin(ssid, senha);
  Serial.print("Conectando ao Wi-Fi");

  unsigned long t0 = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - t0 < 15000) {
    Serial.print(".");
    delay(500);
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nWi-Fi conectado!");
    Serial.print("IP: "); Serial.println(WiFi.localIP());
  } else {
    Serial.println("\nFalha na conexão.");
  }
}

void enviaParaServidor(String dados) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("Erro: Wi-Fi não conectado. Dados não enviados: " + dados);
    return;
  }

  WiFiClientSecure client;
  client.setInsecure(); // ignora SSL

  String url = endpointBase + dados;
  Serial.println("Enviando para API: " + url);

  if (client.connect("ibyt.com.br", 443)) {
    client.print(String("GET ") + url + " HTTP/1.1\r\n" +
                 "Host: ibyt.com.br\r\nConnection: close\r\n\r\n");

    // Aguardar resposta com timeout
    unsigned long timeout = millis();
    while (client.connected() && millis() - timeout < 5000) {
      if (client.available()) {
        String response = client.readString();
        Serial.println("Resposta da API: " + response);
        break;
      }
      delay(10);
    }
    if (millis() - timeout >= 5000) {
      Serial.println("Erro: Timeout ao aguardar resposta da API.");
    }
  } else {
    Serial.println("Erro: Falha ao conectar ao servidor ibyt.com.br.");
  }

  client.stop();
}

void setup() {
  Serial.begin(115200);
  EEPROM.begin(EEPROM_SIZE);
  pinMode(RESET_PIN, INPUT_PULLUP);

  if (!wifiSalvo() || digitalRead(RESET_PIN) == LOW) {
    modoConfigAP();
  }

  carregaWiFi();
  conectaWiFi();

  LoRa.setPins(LORA_SS, LORA_RST, LORA_DIO0);
  if (!LoRa.begin(915E6)) {
    Serial.println("Erro ao iniciar LoRa");
    while (true);
  }
  LoRa.setSyncWord(syncWord); // Configura sync word para ibyt
  Serial.println("LoRa iniciado com sync word ibyt.");
}

void loop() {
  int packetSize = LoRa.parsePacket();
  if (packetSize) {
    String msg = "";
    while (LoRa.available()) {
      msg += (char)LoRa.read();
    }
    msg.trim();
    Serial.println("Recebido via LoRa: " + msg);

    if (msg.startsWith("C")) {
      enviaParaServidor(msg);
    }
  }
}