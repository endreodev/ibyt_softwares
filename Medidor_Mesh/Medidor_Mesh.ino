#define NETWORK_ID 0x01
#define GATEWAY_ADDRESS 1
#define MEDIDOR_ADDRESS 2 // ajuste conforme necessário
#define SECRET_KEY 0x5A
#include <SPI.h>
#include <LoRa.h>




// LoRa settings
#define LORA_SCK 5
#define LORA_MISO 19
#define LORA_MOSI 27
#define LORA_CS 18
#define LORA_RST 14
#define LORA_DI0 26
#define LORA_FREQ 915E6




int clientId = 1; // Valor padrão
int recipientId = 1; // Valor padrão

// Interval for sending data (in milliseconds)

#include <Arduino.h>
#include <LoRa.h>

// Definições de pinos
#define TOUCH_PIN_12 12
#define TOUCH_PIN_13 13
#define TOUCH_PIN_27 27
#define TOUCH_PIN_32 32
#define TOUCH_PIN_33 33



// Configurações de LoRa
#define LORA_SS 5
#define LORA_RST 14
#define LORA_DIO0 2

unsigned long lastDataSentTime = 0; // Marca o último envio de dados
unsigned long lastRelayCheckTime = 0; // Marca o último check do comando de rele
const unsigned long sendInterval = 300000; // Intervalo para enviar dados (5 minutos)
const unsigned long relayCheckInterval = 500; // Intervalo para verificar comando (0,5 segundo)

void setup() {
  Serial.begin(115200);
  // Inicialização direta, sem lógica de reset
  LoRa.setPins(LORA_SS, LORA_RST, LORA_DIO0);
  if (!LoRa.begin(915E6)) {  // Frequência de operação LoRa
    Serial.println("Falha ao iniciar o LoRa.");
    while (1);
  }
  LoRa.setSyncWord(0xF3); // Compatível com o gateway
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

    Serial.print("touch12: "); Serial.println(touch12);
    Serial.print("touch13: "); Serial.println(touch13);
    Serial.print("touch27: "); Serial.println(touch27);
    Serial.print("touch32: "); Serial.println(touch32);
    Serial.print("touch33: "); Serial.println(touch33);


  // Calcular nível: cada touch depende do anterior, cada um soma 20%
  // Sensores touch em ESP32 retornam valores baixos quando tocados (geralmente < 20)
  int nivel = 0;
  if (touch12 < 20) {  // Touch detectado quando valor < 20
    nivel = 20;
    if (touch13 < 20) {
      nivel = 40;
      if (touch32 < 20) {
        nivel = 60;
        if (touch27 < 20) {
          nivel = 80;
          if (touch33 < 20) {
            nivel = 100;
          }
        }
      }
    }
  }


    // Formatar dados
    String data = "D1N" + String(nivel);

    // Criptografar dados (XOR)
    String encrypted = "";
    for (size_t i = 0; i < data.length(); i++) {
      encrypted += char(data[i] ^ SECRET_KEY);
    }

    // Enviar pacote LoRa com cabeçalho
    LoRa.beginPacket();
    LoRa.write(NETWORK_ID);
    LoRa.write(GATEWAY_ADDRESS);
    LoRa.write(MEDIDOR_ADDRESS);
    for (size_t i = 0; i < encrypted.length(); i++) {
      LoRa.write(encrypted[i]);
    }
    LoRa.endPacket();
    Serial.println("Enviado: " + data);
    
    Serial.println("Dados enviados pelo transmissor");
  }

  // ...não há controle de relé neste dispositivo...
}