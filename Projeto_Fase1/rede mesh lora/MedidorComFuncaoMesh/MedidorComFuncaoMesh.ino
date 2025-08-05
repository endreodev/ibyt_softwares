#include <LoRa.h>

#define NODE_ADDRESS 2
#define GATEWAY_ADDRESS 1
#define NETWORK_ID 0x01
#define SECRET_KEY 0x5A

// Define LoRa pins (adjust these based on your wiring)
#define LORA_SS 5   // Chip Select (NSS)
#define LORA_RST 14 // Reset
#define LORA_DIO0 2 // DIO0 (Interrupt)

void setup() {
  Serial.begin(115200);
  delay(1000);

  // Set LoRa pins
  LoRa.setPins(LORA_SS, LORA_RST, LORA_DIO0);

  if (!LoRa.begin(915E6)) {
    Serial.println("Erro ao iniciar LoRa");
    while (true);
  }
  LoRa.setSyncWord(0xF3);
  Serial.println("LoRa inicializado");
}

void loop() {
  sendSensorData();
  delay(30000); // 30 seconds
}

int readNivel() {
  return random(0, 101); // Random level between 0 and 100
}

void sendSensorData() {
  String payload = "C1R1N" + String(readNivel());
  String encrypted = encryptDecrypt(payload);

  LoRa.beginPacket();
  LoRa.write(NETWORK_ID);
  LoRa.write(GATEWAY_ADDRESS);
  LoRa.write(NODE_ADDRESS);
  LoRa.print(encrypted);
  LoRa.endPacket();

  Serial.println("Enviado via LoRa: " + payload);
}

String encryptDecrypt(String message) {
  String result = "";
  for (int i = 0; i < message.length(); i++) {
    result += char(message[i] ^ SECRET_KEY);
  }
  return result;
}