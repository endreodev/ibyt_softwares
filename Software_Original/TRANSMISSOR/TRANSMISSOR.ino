#include <Arduino.h>
#include <LoRa.h>

// ========== DEFINIÇÕES ==========
const int TOUCH_PIN_12 = 12;
const int TOUCH_PIN_13 = 13;
const int TOUCH_PIN_27 = 27;
const int TOUCH_PIN_32 = 32;
const int TOUCH_PIN_33 = 33;

const int RELE_PIN = 15;
const int LORA_SS = 5;
const int LORA_RST = 14;
const int LORA_DIO0 = 2;

unsigned long lastDataSentTime = 0;
const unsigned long sendIntervalLow = 60000;   // 1 minuto
const unsigned long sendIntervalHigh = 300000; // 5 minutos
const unsigned long restartInterval = 86400000; // 24 horas
const uint8_t syncWord = 0x34; // Sync word para dispositivos ibyt

// ====== Leitura do nível ======
int lerNivel() {
  int t12 = touchRead(TOUCH_PIN_12);
  int t13 = touchRead(TOUCH_PIN_13);
  int t27 = touchRead(TOUCH_PIN_27);
  int t32 = touchRead(TOUCH_PIN_32);
  int t33 = touchRead(TOUCH_PIN_33);

  int nivel = 0;
  if (t13 <= 15) nivel = 20;
  if (t13 <= 15 && t12 <= 15) nivel = 40;
  if (t13 <= 15 && t12 <= 15 && t32 <= 15) nivel = 60;
  if (t13 <= 15 && t12 <= 15 && t32 <= 15 && t27 <= 15) nivel = 80;
  if (t13 <= 15 && t12 <= 15 && t32 <= 15 && t27 <= 15 && t33 <= 15) nivel = 100;

  return nivel;
}

// ====== Setup ======
void setup() {
  Serial.begin(115200);
  delay(1000);
  Serial.println("Inicializando...");

  pinMode(RELE_PIN, OUTPUT);
  digitalWrite(RELE_PIN, LOW);

  // Iniciar LoRa
  LoRa.setPins(LORA_SS, LORA_RST, LORA_DIO0);
  if (!LoRa.begin(915E6)) {
    Serial.println("Erro ao iniciar LoRa.");
    while (1);
  }
  LoRa.setSyncWord(syncWord); // Configura sync word para ibyt
  Serial.println("LoRa iniciado com sync word ibyt.");
}

// ====== Loop ======
void loop() {
  unsigned long agora = millis();

  // Reiniciar a cada 24 horas para estabilidade
  if (agora >= restartInterval) {
    Serial.println("Reiniciando dispositivo para estabilidade...");
    ESP.restart();
  }

  // Enviar dados apenas no intervalo apropriado
  if (agora - lastDataSentTime >= sendIntervalLow || agora - lastDataSentTime >= sendIntervalHigh) {
    int nivel = lerNivel();
    unsigned long sendInterval = (nivel < 60) ? sendIntervalLow : sendIntervalHigh;

    if (agora - lastDataSentTime >= sendInterval) {
      lastDataSentTime = agora;

      // Imprimir leituras apenas quando enviar dados
      Serial.printf("Leitura sensores: T12=%d T13=%d T27=%d T32=%d T33=%d -> Nivel=%d%%\n",
                    touchRead(TOUCH_PIN_12), touchRead(TOUCH_PIN_13), 
                    touchRead(TOUCH_PIN_27), touchRead(TOUCH_PIN_32), 
                    touchRead(TOUCH_PIN_33), nivel);

      String dados = "D1N" + String(nivel);
      Serial.println("Enviando via LoRa para ibyt: " + dados);

      LoRa.beginPacket();
      LoRa.print(dados);
      LoRa.endPacket();
    }
  }
}