#include <SPI.h>
#include <LoRa.h>

#define LORA_SS 5
#define LORA_RST 14
#define LORA_DIO0 2

unsigned long lastRelaySendTime = 0; // Marca o último envio do comando "rele"
const unsigned long relaySendInterval = 1000; // Intervalo para enviar o comando "rele" (1 segundo)

void setup() {
  Serial.begin(115200);

  // Inicializando LoRa
  LoRa.setPins(LORA_SS, LORA_RST, LORA_DIO0);
  if (!LoRa.begin(915E6)) {
    Serial.println("Falha ao iniciar o LoRa.");
    while (1);
  }
  Serial.println("LoRa iniciado.");
}

void loop() {
  unsigned long currentMillis = millis();

  // Receber dados do transmissor
  int packetSize = LoRa.parsePacket();
  if (packetSize) {
    String receivedText = "";
    while (LoRa.available()) {
      receivedText += (char)LoRa.read();
    }
    Serial.println("Dados recebidos: " + receivedText);
  }

  // Enviar o comando "rele" automaticamente a cada 'relaySendInterval' milissegundos
  if (currentMillis - lastRelaySendTime >= relaySendInterval) {
    lastRelaySendTime = currentMillis;

    LoRa.beginPacket();
    LoRa.print("rele");
    LoRa.endPacket();
    Serial.println("Comando 'rele' enviado automaticamente ao transmissor.");
  }
}
