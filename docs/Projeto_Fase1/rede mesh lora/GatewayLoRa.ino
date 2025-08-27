#include <WiFi.h>
#include <BluetoothSerial.h>
#include <FirebaseESP32.h>
#include <LoRa.h>
#include <EEPROM.h>

// Configurações do Firebase
#define FIREBASE_HOST "https://nivelcerto-65461-default-rtdb.firebaseio.com/"
#define FIREBASE_AUTH "hy1LKPz8Ui4aY9uVpuEaleiEOClnyE3awD64iTbM"
FirebaseData firebaseData;

BluetoothSerial SerialBT;
#define EEPROM_SIZE 512

String ssid = "";
String password = "";
String selectedSSID = "";

// Configurações do medidor
String cliente = "";
String caixa = "";
String descricao = "";

#define LORA_NETWORK_ID 0x01
#define SECRET_KEY 0x5A

void setup() {
  Serial.begin(115200);
  SerialBT.begin("ESP32_Receptor");

  EEPROM.begin(EEPROM_SIZE);
  loadWiFiCredentials();
  loadDeviceConfig();

  if (!LoRa.begin(915E6)) {
    Serial.println("Falha ao inicializar o LoRa");
    while (1);
  }
  LoRa.setSyncWord(0xF3);

  // Tenta conectar ao Wi-Fi usando credenciais salvas
  if (ssid != "" && password != "") {
    connectToWiFi();
  }
}

void loop() {
  // Configurações via Bluetooth
  if (SerialBT.available()) {
    String incoming = SerialBT.readStringUntil('\n');
    parseBluetoothCommand(incoming);
  }

  // Recebe dados via LoRa e envia ao Firebase
  if (WiFi.status() == WL_CONNECTED) {
    receiveLoRaDataAndSendToFirebase();
  }
}

// Função para processar comandos Bluetooth
void parseBluetoothCommand(String command) {
  if (command == "LIST") {
    listWiFiNetworks();
  } else if (command.startsWith("SSID:")) {
    selectedSSID = command.substring(5);
    SerialBT.println("Digite a senha para a rede " + selectedSSID);
  } else if (command.startsWith("PASSWORD:")) {
    password = command.substring(9);
    ssid = selectedSSID;
    saveWiFiCredentials();
    connectToWiFi();
  } else if (command.startsWith("CLIENTE:")) {
    cliente = command.substring(8);
    saveDeviceConfig();
    SerialBT.println("Cliente atualizado: " + cliente);
  } else if (command.startsWith("CAIXA:")) {
    caixa = command.substring(6);
    saveDeviceConfig();
    SerialBT.println("Caixa atualizada: " + caixa);
  } else if (command.startsWith("DESCRICAO:")) {
    descricao = command.substring(10);
    saveDeviceConfig();
    SerialBT.println("Descricao atualizada: " + descricao);
  } else {
    SerialBT.println("Comando desconhecido");
  }
}

// Função para listar redes Wi-Fi disponíveis
void listWiFiNetworks() {
  SerialBT.println("Buscando redes Wi-Fi...");
  int n = WiFi.scanNetworks();
  if (n == 0) {
    SerialBT.println("Nenhuma rede encontrada");
  } else {
    SerialBT.println("Redes encontradas:");
    for (int i = 0; i < n; i++) {
      SerialBT.print(i + 1);
      SerialBT.print(": ");
      SerialBT.println(WiFi.SSID(i));
    }
    SerialBT.println("Envie o comando 'SSID:<nome_da_rede>' para selecionar");
  }
  WiFi.scanDelete();
}

// Função para salvar SSID e senha na EEPROM
void saveWiFiCredentials() {
  EEPROM.writeString(0, ssid);
  EEPROM.writeString(100, password);
  EEPROM.commit();
}

// Função para carregar SSID e senha da EEPROM
void loadWiFiCredentials() {
  ssid = EEPROM.readString(0);
  password = EEPROM.readString(100);
}

// Função para salvar configurações do dispositivo na EEPROM
void saveDeviceConfig() {
  EEPROM.writeString(200, cliente);
  EEPROM.writeString(300, caixa);
  EEPROM.writeString(400, descricao);
  EEPROM.commit();
}

// Função para carregar configurações do dispositivo da EEPROM
void loadDeviceConfig() {
  cliente = EEPROM.readString(200);
  caixa = EEPROM.readString(300);
  descricao = EEPROM.readString(400);
}

// Função para conectar ao Wi-Fi
void connectToWiFi() {
  SerialBT.print("Conectando ao Wi-Fi: ");
  SerialBT.println(ssid);
  WiFi.begin(ssid.c_str(), password.c_str());

  int retries = 0;
  while (WiFi.status() != WL_CONNECTED && retries < 10) {
    delay(1000);
    SerialBT.print(".");
    retries++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    SerialBT.println("\nConectado ao Wi-Fi com sucesso!");
    Firebase.begin(FIREBASE_HOST, FIREBASE_AUTH);
    Firebase.reconnectWiFi(true);
  } else {
    SerialBT.println("\nFalha ao conectar ao Wi-Fi");
  }
}

// Função para receber dados via LoRa e enviar ao Firebase
void receiveLoRaDataAndSendToFirebase() {
  int packetSize = LoRa.parsePacket();
  if (packetSize) {
    int networkId = LoRa.read();
    if (networkId != LORA_NETWORK_ID) return;

    int sender = LoRa.read();
    int recipient = LoRa.read();
    String data = LoRa.readString();

    SerialBT.print("Dados recebidos de ");
    SerialBT.print(sender);
    SerialBT.print(": ");
    SerialBT.println(data);

    // Simulação de dados dos sensores
    String nivel = "80";        // Valor simulado, substitua pelo valor real do sensor
    String ph = "18";           // Valor simulado
    String temperatura = "22";  // Valor simulado

    // Caminho no Firebase
    String path = "/" + cliente + "/caixadeagua/" + caixa + "/medicoes";

    // Envia os dados para o Firebase
    if (Firebase.setString(firebaseData, path + "/nivel", nivel) &&
        Firebase.setString(firebaseData, path + "/ph", ph) &&
        Firebase.setString(firebaseData, path + "/temperatura", temperatura)) {
      SerialBT.println("Dados enviados ao Firebase com sucesso");
    } else {
      SerialBT.print("Erro ao enviar dados: ");
      SerialBT.println(firebaseData.errorReason());
    }
  }
}
