#include <SPI.h>
#include <LoRa.h>
#include <WiFi.h>
#include <WebServer.h>
#include <EEPROM.h>

// EEPROM settings
#define EEPROM_SIZE 512
#define SSID_ADDR 0
#define PASS_ADDR 32
#define CLIENT_ID_ADDR 96
#define RECIPIENT_ID_ADDR 97

// LoRa settings
#define LORA_SCK 5
#define LORA_MISO 19
#define LORA_MOSI 27
#define LORA_CS 18
#define LORA_RST 14
#define LORA_DI0 26
#define LORA_FREQ 915E6

// Web server
WebServer server(80);

// Stored settings
char ssid[32] = "";
char password[64] = "";
int clientId = 0;
int recipientId = 0;

// Interval for sending data (in milliseconds)
#define SEND_INTERVAL 10000

unsigned long previousMillis = 0;

// Function to simulate measurement
int simulateMeasurement() {
  return random(0, 101); // Simulate a level between 0 and 100
}

void handleRoot() {
  String html = "<!DOCTYPE html><html><body>";
  html += "<h1>Meter Configuration</h1>";
  html += "<form action='/save' method='POST'>";
  html += "SSID: <input type='text' name='ssid'><br>";
  html += "Password: <input type='password' name='password'><br>";
  html += "Client ID: <input type='number' name='clientId'><br>";
  html += "Recipient ID: <input type='number' name='recipientId'><br>";
  html += "<input type='submit' value='Save'>";
  html += "</form></body></html>";
  server.send(200, "text/html", html);
}

void handleSave() {
  if (server.hasArg("ssid") && server.hasArg("password") && server.hasArg("clientId") && server.hasArg("recipientId")) {
    String newSsid = server.arg("ssid");
    String newPass = server.arg("password");
    clientId = server.arg("clientId").toInt();
    recipientId = server.arg("recipientId").toInt();
    newSsid.toCharArray(ssid, 32);
    newPass.toCharArray(password, 64);
    for (int i = 0; i < 32; i++) EEPROM.write(SSID_ADDR + i, ssid[i]);
    for (int i = 0; i < 64; i++) EEPROM.write(PASS_ADDR + i, password[i]);
    EEPROM.write(CLIENT_ID_ADDR, clientId);
    EEPROM.write(RECIPIENT_ID_ADDR, recipientId);
    EEPROM.commit();
    server.send(200, "text/html", "<h1>Settings Saved. Rebooting...</h1>");
    delay(1000);
    ESP.restart();
  } else {
    server.send(400, "text/html", "<h1>Error: Missing settings</h1>");
  }
}

bool startConfigPortal() {
  WiFi.mode(WIFI_AP);
  WiFi.softAP("Meter-Config", "12345678");
  Serial.println("Config portal started. Connect to WiFi 'Meter-Config' with password '12345678'");
  Serial.print("AP IP Address: ");
  Serial.println(WiFi.softAPIP());
  server.on("/", handleRoot);
  server.on("/save", handleSave);
  server.begin();
  for (int i = 0; i < 60; i++) {
    server.handleClient();
    delay(1000);
  }
  WiFi.softAPdisconnect(true);
  return true;
}

bool connectToWiFi() {
  WiFi.begin(ssid, password);
  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 20) {
    delay(500);
    Serial.print(".");
    attempts++;
  }
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nConnected to WiFi");
    Serial.print("IP Address: ");
    Serial.println(WiFi.localIP());
    return true;
  }
  return false;
}

void setup() {
  Serial.begin(115200);
  while (!Serial);

  // Initialize EEPROM
  EEPROM.begin(EEPROM_SIZE);
  for (int i = 0; i < 32; i++) ssid[i] = EEPROM.read(SSID_ADDR + i);
  for (int i = 0; i < 64; i++) password[i] = EEPROM.read(PASS_ADDR + i);
  clientId = EEPROM.read(CLIENT_ID_ADDR);
  recipientId = EEPROM.read(RECIPIENT_ID_ADDR);

  // Start configuration portal if settings are not set
  if (strlen(ssid) == 0 || strlen(password) == 0 || clientId == 0 || recipientId == 0) {
    Serial.println("Settings not configured. Starting config portal...");
    startConfigPortal();
    for (int i = 0; i < 32; i++) ssid[i] = EEPROM.read(SSID_ADDR + i);
    for (int i = 0; i < 64; i++) password[i] = EEPROM.read(PASS_ADDR + i);
    clientId = EEPROM.read(CLIENT_ID_ADDR);
    recipientId = EEPROM.read(RECIPIENT_ID_ADDR);
  }

  // Try to connect to WiFi
  if (!connectToWiFi()) {
    Serial.println("Failed to connect to WiFi. Starting config portal...");
    startConfigPortal();
  }

  Serial.print("Client ID: "); Serial.println(clientId);
  Serial.print("Recipient ID: "); Serial.println(recipientId);

  // Initialize LoRa
  LoRa.setPins(LORA_CS, LORA_RST, LORA_DI0);
  if (!LoRa.begin(LORA_FREQ)) {
    Serial.println("LoRa initialization failed!");
    while (1);
  }
  Serial.println("LoRa initialized successfully");

  // Seed random number generator
  randomSeed(analogRead(0));
}

void loop() {
  unsigned long currentMillis = millis();

  if (currentMillis - previousMillis >= SEND_INTERVAL) {
    // Simulate measurement
    int level = simulateMeasurement();

    // Format data as C1R1N80 (e.g., C1R2N50)
    String data = "C" + String(clientId) + "R" + String(recipientId) + "N" + String(level);

    // Send data via LoRa
    LoRa.beginPacket();
    LoRa.print(data);
    LoRa.endPacket();
    Serial.println("Sent: " + data);

    previousMillis = currentMillis;
  }
}