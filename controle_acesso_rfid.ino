/*
  Sistema de Controle de Acesso RFID
  Placa: Heltec WiFi LoRa 32 V3
  Biblioteca OLED: heltec_unofficial
  Comunicacao: HTTP em rede local

  Bibliotecas:
    - heltec_unofficial
    - MFRC522
    - WiFiManager
    - ArduinoJson (versao 7)

  IMPORTANTE:
    1. Preencher os GPIOs antes de utilizar.
    2. Configurar API_URL com o IP do computador que roda o XAMPP.
    3. Trocar a senha do hotspot (WIFI_AP_PASSWORD).
  
  MAIS IMPORTANTE AINDA:
  ESSE CÓDIGO FOI FEITO PENSANDO NO HELTEC ESP32 V3, DEMAIS PLACAS/MICROCONTROLADORES DEVEM TER O CÓDIGO REVISADO E VOLTADO PARA
  A ESPECIFICIDADE DE CADA UM!
*/

#include <heltec_unofficial.h>
#include <WiFi.h>
#include <WiFiManager.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <SPI.h>
#include <MFRC522.h>


// CONFIGURACAO DOS GPIOs


// RFID - SPI
int RFID_SS_PIN   = -1;
int RFID_RST_PIN  = -1;
int RFID_SCK_PIN  = -1;
int RFID_MISO_PIN = -1;
int RFID_MOSI_PIN = -1;

// Buzzer e rele
int BUZZER_PIN = -1;
int RELAY_PIN  = -1;

// CONFIGURACOES DE REDE E API


// Troque SEU_IP pelo IPv4 do computador
// O caminho precisa bater com a pasta onde o projeto esta no htdocs.
const char* API_URL = "http://SEU_IP/catraca-web/api/acesso.php";

// Nome e senha do hotspot de configuracao (senha com 8+ caracteres)
const char* WIFI_AP_NAME     = "Heltec-Acesso-Setup";
const char* WIFI_AP_PASSWORD = "TroqueEstaSenha123";

// Endereco do portal de configuracao
IPAddress portalIP(192, 168, 4, 1);
IPAddress portalGateway(192, 168, 4, 1);
IPAddress portalSubnet(255, 255, 255, 0);

// Tempo que o portal de configuracao fica aberto no boot
const unsigned long WIFI_PORTAL_TIMEOUT_MS = 180000;
const unsigned long WIFI_RETRY_INTERVAL    = 10000;
const unsigned long API_TIMEOUT_MS         = 8000;
const unsigned long RESULT_DISPLAY_MS      = 3000;
const unsigned long RELAY_DURATION_MS      = 1000;

const unsigned long BEEP_SHORT_MS = 100;
const unsigned long BEEP_LONG_MS  = 800;

// Maximo de caracteres que cabem em uma linha do OLED (fonte 10)
const size_t OLED_MAX_CHARS = 21;

// CONFIGURACOES DE HARDWARE

// true: rele liga com HIGH | false: rele liga com LOW
const bool RELAY_ACTIVE_HIGH = true;

// true: buzzer ativo acionado com HIGH | false: com LOW
const bool BUZZER_ACTIVE_HIGH = true;

// OBJETOS


MFRC522* rfid = nullptr;


// ESTADOS DO SISTEMA


enum AccessResult {
  ACCESS_AUTHORIZED,
  ACCESS_DENIED,
  ACCESS_COMMUNICATION_ERROR
};

enum CommunicationError {
  COMM_NONE,
  COMM_WIFI_DISCONNECTED,
  COMM_TIMEOUT,
  COMM_HTTP_ERROR,
  COMM_INVALID_JSON,
  COMM_INVALID_RESPONSE
};

struct ApiResult {
  AccessResult result;
  CommunicationError error;
  int httpCode;
  String message;
  String details;
};

bool relayActive = false;
unsigned long relayStartTime = 0;

bool waitingForCardRemoval = false;

unsigned long lastWiFiAttempt = 0;


//FUNCOES DE HARDWARE


void relayOff() {
  if (RELAY_PIN < 0) return;
  digitalWrite(RELAY_PIN, RELAY_ACTIVE_HIGH ? LOW : HIGH);
  relayActive = false;
}

void relayOn() {
  if (RELAY_PIN < 0) return;
  digitalWrite(RELAY_PIN, RELAY_ACTIVE_HIGH ? HIGH : LOW);
  relayActive = true;
  relayStartTime = millis();
}

void updateRelay() {
  if (!relayActive) return;
  if (millis() - relayStartTime >= RELAY_DURATION_MS) {
    relayOff();
  }
}

void buzzerOn() {
  if (BUZZER_PIN < 0) return;
  digitalWrite(BUZZER_PIN, BUZZER_ACTIVE_HIGH ? HIGH : LOW);
}

void buzzerOff() {
  if (BUZZER_PIN < 0) return;
  digitalWrite(BUZZER_PIN, BUZZER_ACTIVE_HIGH ? LOW : HIGH);
}

void beepShort() {
  buzzerOn();
  delay(BEEP_SHORT_MS);
  buzzerOff();
}

void beepLong() {
  buzzerOn();
  delay(BEEP_LONG_MS);
  buzzerOff();
}

void beepSuccess() {
  beepShort();
  delay(100);
  beepShort();
}

// Espera sem "congelar" o rele: continua atualizando o tempo dele
// (resolve o problema do rele ficar ligado por mais tempo que o configurado)
void waitWithRelay(unsigned long ms) {
  unsigned long start = millis();
  while (millis() - start < ms) {
    updateRelay();
    heltec_loop();
    delay(10);
  }
  updateRelay();
}


// 8. OLED

// Corta textos longos para nao estourar a largura da tela
String fit(const String& text) {
  if (text.length() <= OLED_MAX_CHARS) return text;
  return text.substring(0, OLED_MAX_CHARS - 1) + ".";
}

void displayMessage(
  const String& line1,
  const String& line2 = "",
  const String& line3 = ""
) {
  display.clear();

  display.setFont(ArialMT_Plain_10);
  display.setTextAlignment(TEXT_ALIGN_CENTER);

  display.drawString(64, 5,  fit(line1));
  display.drawString(64, 22, fit(line2));
  display.drawString(64, 39, fit(line3));

  display.display();
}

void showReady() {
  displayMessage(
    "Controle de acesso",
    "Aproxime sua chave",
    WiFi.isConnected() ? "Wi-Fi conectado" : "Sem Wi-Fi"
  );
}

void showAuthorized(const String& name) {
  displayMessage("ACESSO LIBERADO", name, "Bem-vindo!");
}

void showDenied(const String& message) {
  displayMessage("ACESSO NEGADO", message, "Procure a administracao");
}

void showCommunicationError(
  const String& title,
  const String& code,
  const String& detail
) {
  display.clear();

  display.setFont(ArialMT_Plain_10);
  display.setTextAlignment(TEXT_ALIGN_CENTER);

  display.drawString(64, 2, fit(title));
  display.drawString(64, 18, fit(code));
  display.drawStringMaxWidth(64, 34, 124, detail);

  display.display();
}


// WI-FI


// Usada SOMENTE no boot: abre o portal de configuracao se nao
// houver rede salva ou se a conexao falhar.
bool connectWiFi() {
  WiFiManager wifiManager;

  wifiManager.setConfigPortalTimeout(WIFI_PORTAL_TIMEOUT_MS / 1000);
  wifiManager.setAPStaticIPConfig(portalIP, portalGateway, portalSubnet);

  Serial.println();
  Serial.println("Iniciando configuracao Wi-Fi...");

  displayMessage(
    "Configurando Wi-Fi",
    "Conecte-se ao hotspot",
    WIFI_AP_NAME
  );

  bool connected = wifiManager.autoConnect(WIFI_AP_NAME, WIFI_AP_PASSWORD);

  if (connected && WiFi.isConnected()) {
    Serial.println("Wi-Fi conectado!");
    Serial.print("IP: ");
    Serial.println(WiFi.localIP());

    displayMessage(
      "Wi-Fi conectado",
      WiFi.SSID(),
      WiFi.localIP().toString()
    );

    delay(1500);
    return true;
  }

  Serial.println("Falha ao conectar ao Wi-Fi.");

  displayMessage(
    "Falha no Wi-Fi",
    "Verifique a rede",
    "Tentando novamente"
  );

  return false;
}

// Usada no loop: apenas tenta reconectar, sem abrir portal
// e sem travar a leitura de cartoes.
void maintainWiFi() {
  if (WiFi.isConnected()) return;

  if (millis() - lastWiFiAttempt >= WIFI_RETRY_INTERVAL) {
    lastWiFiAttempt = millis();
    Serial.println("Wi-Fi desconectado. Reconectando...");
    WiFi.reconnect();
  }
}


// VALIDACAO DOS GPIOs

bool validatePins() {
  if (
    RFID_SS_PIN < 0 ||
    RFID_RST_PIN < 0 ||
    RFID_SCK_PIN < 0 ||
    RFID_MISO_PIN < 0 ||
    RFID_MOSI_PIN < 0 ||
    BUZZER_PIN < 0 ||
    RELAY_PIN < 0
  ) {
    Serial.println("ERRO: Configure todos os GPIOs!");

    displayMessage(
      "ERRO DE CONFIGURACAO",
      "GPIO nao definido",
      "Verifique o codigo"
    );

    return false;
  }

  return true;
}


//  LEITURA DO UID


String getCardUID() {
  String uid = "";

  for (byte i = 0; i < rfid->uid.size; i++) {
    if (rfid->uid.uidByte[i] < 0x10) {
      uid += "0";
    }
    uid += String(rfid->uid.uidByte[i], HEX);
  }

  uid.toUpperCase();
  return uid;
}


// CONSULTA HTTP


ApiResult checkAccess(const String& uid) {
  ApiResult result;

  result.result = ACCESS_COMMUNICATION_ERROR;
  result.error = COMM_NONE;
  result.httpCode = 0;
  result.message = "";
  result.details = "";

  if (WiFi.status() != WL_CONNECTED) {
    result.error = COMM_WIFI_DISCONNECTED;
    result.details = "Wi-Fi desconectado";
    return result;
  }

  HTTPClient http;

  http.setConnectTimeout(API_TIMEOUT_MS);
  http.setTimeout(API_TIMEOUT_MS);

  Serial.println();
  Serial.println("Consultando API...");
  Serial.print("URL: ");
  Serial.println(API_URL);
  Serial.print("UID: ");
  Serial.println(uid);

  if (!http.begin(API_URL)) {
    result.error = COMM_HTTP_ERROR;
    result.details = "Falha ao iniciar HTTPClient";
    return result;
  }

  http.addHeader("Content-Type", "application/json");

  JsonDocument requestDoc;
  requestDoc["uid"] = uid;

  String requestBody;
  serializeJson(requestDoc, requestBody);

  Serial.print("POST: ");
  Serial.println(requestBody);

  int httpCode = http.POST(requestBody);
  result.httpCode = httpCode;

  // Codigos negativos sao erros locais da biblioteca
  if (httpCode < 0) {
    result.error = COMM_HTTP_ERROR;
    result.details = HTTPClient::errorToString(httpCode);

    if (
      httpCode == HTTPC_ERROR_READ_TIMEOUT ||
      httpCode == HTTPC_ERROR_CONNECTION_TIMEOUT
    ) {
      result.error = COMM_TIMEOUT;
    }

    Serial.print("Erro HTTPClient: ");
    Serial.println(httpCode);
    Serial.print("Descricao: ");
    Serial.println(result.details);

    http.end();
    return result;
  }

  Serial.print("HTTP Status: ");
  Serial.println(httpCode);

  String responseBody = http.getString();

  Serial.print("Resposta: ");
  Serial.println(responseBody);

  http.end();

  // Status HTTP fora da faixa de sucesso
  if (httpCode < 200 || httpCode >= 300) {
    result.error = COMM_HTTP_ERROR;
    result.details = "Servidor retornou HTTP " + String(httpCode);
    return result;
  }

  // Interpretar JSON
  JsonDocument responseDoc;
  DeserializationError jsonError = deserializeJson(responseDoc, responseBody);

  if (jsonError) {
    result.error = COMM_INVALID_JSON;
    result.details = jsonError.c_str();
    return result;
  }

  if (!responseDoc["authorized"].is<bool>()) {
    result.error = COMM_INVALID_RESPONSE;
    result.details = "Campo authorized ausente ou invalido";
    return result;
  }

  bool authorized = responseDoc["authorized"].as<bool>();

  result.message = responseDoc["message"] |
                   (authorized ? "Autorizado" : "Negado");

  result.result = authorized ? ACCESS_AUTHORIZED : ACCESS_DENIED;
  result.error = COMM_NONE;

  return result;
}


// REGISTRO DE ERRO DE COMUNICACAO


void handleCommunicationError(const ApiResult& result) {
  Serial.println();
  Serial.println("========== ERRO DE COMUNICACAO ==========");

  switch (result.error) {
    case COMM_WIFI_DISCONNECTED:
      Serial.println("Tipo: Wi-Fi desconectado");
      showCommunicationError("ERRO DE COMUNICACAO", "Wi-Fi desconectado", "Verifique a rede");
      break;

    case COMM_TIMEOUT:
      Serial.println("Tipo: Timeout");
      Serial.print("Codigo HTTPClient: ");
      Serial.println(result.httpCode);
      Serial.println(result.details);
      showCommunicationError("ERRO DE COMUNICACAO", "Timeout", result.details);
      break;

    case COMM_HTTP_ERROR:
      Serial.println("Tipo: Erro HTTP");
      Serial.print("Codigo: ");
      Serial.println(result.httpCode);
      Serial.println(result.details);
      showCommunicationError("ERRO HTTP", "Codigo: " + String(result.httpCode), result.details);
      break;

    case COMM_INVALID_JSON:
      Serial.println("Tipo: JSON invalido");
      Serial.println(result.details);
      showCommunicationError("ERRO DE RESPOSTA", "JSON invalido", result.details);
      break;

    case COMM_INVALID_RESPONSE:
      Serial.println("Tipo: Resposta invalida");
      Serial.println(result.details);
      showCommunicationError("ERRO DE RESPOSTA", "Formato invalido", result.details);
      break;

    default:
      Serial.println("Tipo: Erro desconhecido");
      showCommunicationError("ERRO", "Comunicacao", "Erro desconhecido");
      break;
  }

  Serial.println("=========================================");
}


// PROCESSAMENTO DA CHAVE


void processCard(const String& uid) {
  Serial.println();
  Serial.println("Nova chave detectada");
  Serial.print("UID: ");
  Serial.println(uid);

  beepShort();

  displayMessage("Chave detectada", uid, "Consultando servidor...");

  ApiResult result = checkAccess(uid);

  if (result.result == ACCESS_COMMUNICATION_ERROR) {
    relayOff();
    handleCommunicationError(result);
    waitWithRelay(RESULT_DISPLAY_MS);
    showReady();
    return;
  }

  if (result.result == ACCESS_AUTHORIZED) {
    Serial.println("ACESSO AUTORIZADO");

    // Rele primeiro: a catraca libera sem esperar o display/bipe
    relayOn();
    showAuthorized(result.message);
    beepSuccess();
  } else {
    Serial.println("ACESSO NEGADO");

    relayOff();
    showDenied(result.message);
    beepLong();
  }

  waitWithRelay(RESULT_DISPLAY_MS);
  showReady();
}


// SETUP

void setup() {
  Serial.begin(115200);
  delay(500);

  Serial.println();
  Serial.println("Inicializando controle de acesso...");

  // Inicializa a placa Heltec e o OLED
  heltec_setup();

  display.setFont(ArialMT_Plain_10);

  displayMessage("Controle de acesso", "Inicializando...", "");

  // Garantir rele e buzzer desligados o quanto antes
  if (RELAY_PIN >= 0) {
    pinMode(RELAY_PIN, OUTPUT);
    relayOff();
  }

  if (BUZZER_PIN >= 0) {
    pinMode(BUZZER_PIN, OUTPUT);
    buzzerOff();
  }

  // Conferir GPIOs
  if (!validatePins()) {
    while (true) {
      heltec_loop();
      delay(1000);
    }
  }

  // Inicializar SPI e RFID
  SPI.begin(RFID_SCK_PIN, RFID_MISO_PIN, RFID_MOSI_PIN, RFID_SS_PIN);

  rfid = new MFRC522(RFID_SS_PIN, RFID_RST_PIN);
  rfid->PCD_Init();

  Serial.println("Leitor RFID inicializado.");

  // Diagnostico: mostra a versao do chip no Monitor Serial.
  // Se aparecer 0x00 ou 0xFF, ha problema de fiacao no leitor.
  rfid->PCD_DumpVersionToSerial();

  // Wi-Fi (portal de configuracao so no boot)
  connectWiFi();

  showReady();
}

// LOOP

void loop() {
  heltec_loop();

  maintainWiFi();
  updateRelay();

  // Evitar multiplas consultas pela mesma aproximacao
  if (waitingForCardRemoval) {
    if (!rfid->PICC_IsNewCardPresent()) {
      waitingForCardRemoval = false;
    }

    delay(50);
    return;
  }

  if (!rfid->PICC_IsNewCardPresent()) {
    delay(20);
    return;
  }

  if (!rfid->PICC_ReadCardSerial()) {
    delay(20);
    return;
  }

  String uid = getCardUID();

  waitingForCardRemoval = true;

  processCard(uid);

  rfid->PICC_HaltA();
  rfid->PCD_StopCrypto1();
}
