#include <Arduino.h>
#include <LoRa.h>

// Definições de pinos
#define TOUCH_PIN_12 12
#define TOUCH_PIN_13 13
#define TOUCH_PIN_27 27
#define TOUCH_PIN_32 32
#define TOUCH_PIN_33 33
#define RELE_PIN 15
#define PH_PIN 36        // Saída do módulo de pH
#define TEMP_PIN 39      // Saída de temperatura do módulo de pH

// Configurações de LoRa
#define LORA_SS 5
#define LORA_RST 14
#define LORA_DIO0 2

unsigned long lastDataSentTime = 0; // Marca o último envio de dados
unsigned long lastRelayCheckTime = 0; // Marca o último check do comando de rele
const unsigned long sendInterval = 2000; // Intervalo para enviar dados (2 segundos)
const unsigned long relayCheckInterval = 500; // Intervalo para verificar comando (0,5 segundo)

void setup() {
  // Configurações iniciais
  Serial.begin(115200);
  pinMode(RELE_PIN, OUTPUT);
  digitalWrite(RELE_PIN, LOW); // Relé desativado inicialmente

  // Inicializando LoRa
  LoRa.setPins(LORA_SS, LORA_RST, LORA_DIO0);
  if (!LoRa.begin(915E6)) {  // Frequência de operação LoRa
    Serial.println("Falha ao iniciar o LoRa.");
    while (1);
  }
  Serial.println("LoRa iniciado.");
}

void loop() {
  unsigned long currentMillis = millis();

  // Enviar dados a cada 'sendInterval' milissegundos
  if (currentMillis - lastDataSentTime >= sendInterval) {
    lastDataSentTime = currentMillis;

    // Leitura dos sensores touch
    int touch12 = touchRead(TOUCH_PIN_12);
    int touch13 = touchRead(TOUCH_PIN_13);
    int touch27 = touchRead(TOUCH_PIN_27);
    int touch32 = touchRead(TOUCH_PIN_32);
    int touch33 = touchRead(TOUCH_PIN_33);

    // Leitura do pH e da temperatura
    int phValueRaw = analogRead(PH_PIN);
    float phValue = (phValueRaw / 4095.0) * 3.3;  // Converte para tensão (3.3V é o máximo no ESP32)
    
    int tempValueRaw = analogRead(TEMP_PIN);
    float temperature = (tempValueRaw / 4095.0) * 3.3; // Converte para tensão

    // Enviar dados via LoRa
    LoRa.beginPacket();
    LoRa.print("pH: "); LoRa.print(phValue);
    LoRa.print(", Temp: "); LoRa.print(temperature);
    LoRa.print(", Touch12: "); LoRa.print(touch12);
    LoRa.print(", Touch13: "); LoRa.print(touch13);
    LoRa.print(", Touch27: "); LoRa.print(touch27);
    LoRa.print(", Touch32: "); LoRa.print(touch32);
    LoRa.print(", Touch33: "); LoRa.print(touch33);
    LoRa.endPacket();
    
    Serial.println("Dados enviados pelo transmissor");
  }

  // Verificar se há comando do receptor para ativar o relé a cada 'relayCheckInterval' milissegundos
    int packetSize = LoRa.parsePacket();
    if (packetSize) {
      String receivedText = "";
      while (LoRa.available()) {
        receivedText += (char)LoRa.read();
      }
      if (receivedText == "rele") {
        Serial.println("Comando para ativar relé recebido.");
        digitalWrite(RELE_PIN, HIGH);
        delay(1000);  // Mantenha o relé ativado por 1 segundo
        digitalWrite(RELE_PIN, LOW);
      }
    }
}
