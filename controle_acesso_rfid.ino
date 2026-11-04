
#include <heltec_unofficial.h>
#include <SPI.h>
#include <MFRC522.h>
#include <Preferences.h>
#include <WiFi.h>
#include <WebServer.h>
#include <DNSServer.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>


// Hotspot de configuracao (altere a senha para algo forte, minimo 8 caracteres).
const char* CONFIG_AP_SSID = "Heltec-Acesso-Setup";
const char* CONFIG_AP_PASSWORD = "XXXXXXX";

// Edite os enderecos para apontar ao seu servidor PHP.
const char* API_URL_ACESSO = "http://192.168.15.100/api/acesso";
const char* API_URL_CADASTRO = "http://192.168.15.100/api/chaves/pendente";

// Pinos do MFRC522: exemplos; confirme a pinagem da Heltec e suas ligacoes.
constexpr int RFID_SCK_PIN  = 9;
constexpr int RFID_MISO_PIN = 11;
constexpr int RFID_MOSI_PIN = 10;
constexpr int RFID_SS_PIN   = 8;
constexpr int RFID_RST_PIN  = 12;

// Altere para GPIOs livres e adequados da placa (-1 = desativado).
constexpr int BOTAO_RESET_MESTRE_PIN = -1; 
constexpr int RELE_PIN = -1;               

// Buzzer e LED de sinalizacao (-1 = desativado).
constexpr int BUZZER_SINAL_PIN = 45;
constexpr int LED_SINAL_PIN    = 46;
constexpr bool BUZZER_ACTIVE_HIGH = true;  
constexpr bool LED_ACTIVE_HIGH    = true;  

constexpr bool RELE_ATIVO_HIGH = true;

// Rele inteligente local (Tasmota) acionado por HTTP
constexpr bool RELE_TASMOTA_HABILITADO = true;
const char* RELE_TASMOTA_URL_ON  = "http://192.168.15.213/cm?cmnd=Power%20on";
const char* RELE_TASMOTA_URL_OFF = "http://192.168.15.213/cm?cmnd=Power%20off";
constexpr uint16_t RELE_HTTP_TIMEOUT_MS = 1500;
constexpr uint8_t  RELE_TENTATIVAS_DESLIGAR = 3;

// Tempos
constexpr unsigned long TEMPO_PULSO_RELE_MS = 2000;        // rele ligado por 2 s
constexpr unsigned long TEMPO_MODO_CADASTRO_MS = 30000;
constexpr unsigned long TEMPO_PRESSIONADO_MESTRE_MS = 10000;
constexpr unsigned long TEMPO_LIMITE_HTTP_MS = 5000;
constexpr unsigned long INTERVALO_ANTI_REPETICAO_MS = 1500;
constexpr unsigned long TEMPO_PORTAL_AUTO_CONECTAR_MS = 60000;

// Sinais e telas
constexpr unsigned long BEEP_LONGO_MS      = 800;   // acesso liberado
constexpr unsigned long LED_FASE_MS        = 100;   // 100 ms aceso / 100 ms apagado
constexpr unsigned long BEEP_NEGADO_MS     = 200;   // cada beep do acesso negado
constexpr unsigned long INTERVALO_BEEP_MS  = 150;   // pausa entre os 2 beeps
constexpr unsigned long TEMPO_RESULTADO_MS = 2500;  // tela de resultado
constexpr unsigned long TEMPO_INICIANDO_MS = 2500;
constexpr unsigned long TEMPO_LEITURA_MS   = 1000;  // tela "chave lida"
constexpr uint8_t CONTRASTE_OLED = 255;             // 0 a 255
constexpr bool OLED_GIRAR_180 = false;              // true se a tela aparecer de cabeca para baixo

const byte DNS_PORT = 53;
IPAddress portalIP(192, 168, 4, 1);
IPAddress portalMask(255, 255, 255, 0);

// ======================== IMAGENS ========================

#define WiFi_Logo_width 60
#define WiFi_Logo_height 36
const uint8_t WiFi_Logo_bits[] PROGMEM = {
  0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0xF8,
  0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x80, 0xFF, 0x07, 0x00, 0x00, 0x00,
  0x00, 0x00, 0xE0, 0xFF, 0x1F, 0x00, 0x00, 0x00, 0x00, 0x00, 0xF8, 0xFF,
  0x7F, 0x00, 0x00, 0x00, 0x00, 0x00, 0xFC, 0xFF, 0xFF, 0x00, 0x00, 0x00,
  0x00, 0x00, 0xFE, 0xFF, 0xFF, 0x01, 0x00, 0x00, 0x00, 0x00, 0xFF, 0xFF,
  0xFF, 0x03, 0x00, 0x00, 0x00, 0xFC, 0xFF, 0xFF, 0xFF, 0xFF, 0x00, 0x00,
  0x00, 0xFF, 0xFF, 0xFF, 0x07, 0xC0, 0x83, 0x01, 0x80, 0xFF, 0xFF, 0xFF,
  0x01, 0x00, 0x07, 0x00, 0xC0, 0xFF, 0xFF, 0xFF, 0x00, 0x00, 0x0C, 0x00,
  0xC0, 0xFF, 0xFF, 0x7C, 0x00, 0x60, 0x0C, 0x00, 0xC0, 0x31, 0x46, 0x7C,
  0xFC, 0x77, 0x08, 0x00, 0xE0, 0x23, 0xC6, 0x3C, 0xFC, 0x67, 0x18, 0x00,
  0xE0, 0x23, 0xE4, 0x3F, 0x1C, 0x00, 0x18, 0x00, 0xE0, 0x23, 0x60, 0x3C,
  0x1C, 0x70, 0x18, 0x00, 0xE0, 0x03, 0x60, 0x3C, 0x1C, 0x70, 0x18, 0x00,
  0xE0, 0x07, 0x60, 0x3C, 0xFC, 0x73, 0x18, 0x00, 0xE0, 0x87, 0x70, 0x3C,
  0xFC, 0x73, 0x18, 0x00, 0xE0, 0x87, 0x70, 0x3C, 0x1C, 0x70, 0x18, 0x00,
  0xE0, 0x87, 0x70, 0x3C, 0x1C, 0x70, 0x18, 0x00, 0xE0, 0x8F, 0x71, 0x3C,
  0x1C, 0x70, 0x18, 0x00, 0xC0, 0xFF, 0xFF, 0x3F, 0x00, 0x00, 0x08, 0x00,
  0xC0, 0xFF, 0xFF, 0x1F, 0x00, 0x00, 0x0C, 0x00, 0x80, 0xFF, 0xFF, 0x1F,
  0x00, 0x00, 0x06, 0x00, 0x80, 0xFF, 0xFF, 0x0F, 0x00, 0x00, 0x07, 0x00,
  0x00, 0xFE, 0xFF, 0xFF, 0xFF, 0xFF, 0x01, 0x00, 0x00, 0xF8, 0xFF, 0xFF,
  0xFF, 0x7F, 0x00, 0x00, 0x00, 0x00, 0xFE, 0xFF, 0xFF, 0x01, 0x00, 0x00,
  0x00, 0x00, 0xFC, 0xFF, 0xFF, 0x00, 0x00, 0x00, 0x00, 0x00, 0xF8, 0xFF,
  0x7F, 0x00, 0x00, 0x00, 0x00, 0x00, 0xE0, 0xFF, 0x1F, 0x00, 0x00, 0x00,
  0x00, 0x00, 0x80, 0xFF, 0x07, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0xFC,
  0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00,
};

// ======================== OBJETOS / ESTADO ========================
MFRC522 leitor(RFID_SS_PIN, RFID_RST_PIN);
Preferences preferencias;
WebServer servidorWeb(80);
DNSServer servidorDNS;

String uidMestre = "";
bool modoCadastro = false;
unsigned long inicioModoCadastro = 0;
String ultimoUid = "";
unsigned long ultimaLeituraMs = 0;
bool portalAtivo = false;
unsigned long inicioPortalMs = 0;
bool tentativaAutomaticaFeita = false;

// Rele
bool releLigado = false;
unsigned long releInicio = 0;
uint8_t releTentativasOff = 0;

// Telas
enum Tela {
  T_INICIANDO = 0,
  T_WIFI,            // hotspot de configuracao ativo
  T_TESTANDO_WIFI,
  T_SEM_MESTRE,
  T_MESTRE,
  T_MODO_CADASTRO,
  T_CHAVE_LIDA,
  T_ENVIANDO,
  T_CONCEDIDO,
  T_NEGADO,
  T_AGUARDANDO,
  T_TEXTO            // 3 linhas simples
};

int telaAtual = -1;
unsigned long inicioTela = 0;
String uidAtual = "";
String linhaA = "", linhaB = "", linhaC = "";
bool botaoMostrando = false;
bool erroFatal = false;

// ======================== AUXILIARES DE TELA ========================

String cortar(const String& s, unsigned int n) {
  return s.length() > n ? s.substring(0, n) : s;
}

void setLinhas(const String& a, const String& b = "", const String& c = "") {
  linhaA = a; linhaB = b; linhaC = c;
}

void texto(int16_t x, int16_t y, const uint8_t* fonte, const String& s, bool negrito = false) {
  display.setColor(WHITE);
  display.setFont(fonte);
  display.setTextAlignment(TEXT_ALIGN_LEFT);
  display.drawString(x, y, s);
  if (negrito) display.drawString(x + 1, y, s);  // "negrito" = brilho extra
}

void barra(int16_t x, int16_t y, int16_t w, int16_t h, int pct) {
  if (pct > 100) pct = 100;
  display.setColor(WHITE);
  display.drawRect(x, y, w, h);
  int preenchido = (w - 4) * pct / 100;
  if (preenchido > 0) display.fillRect(x + 2, y + 2, preenchido, h - 4);
}

int pctDesde(unsigned long totalMs) {
  unsigned long dt = millis() - inicioTela;
  if (dt >= totalMs) return 100;
  return (int)(dt * 100UL / totalMs);
}

String pontosAnimados() {
  return String("...").substring(0, (millis() / 350) % 4);
}

// ======================== ICONES ========================

void iconePower(int16_t cx, int16_t cy) {
  display.setColor(WHITE);
  for (int r = 15; r <= 17; r++) {
    for (int a = 40; a <= 320; a += 2) {
      float rad = a * 0.0174533f;
      float sx = sinf(rad);
      float cxr = cosf(rad);
      int px = cx + (int)(r * sx + (sx >= 0 ? 0.5f : -0.5f));
      int py = cy - (int)(r * cxr + (cxr >= 0 ? 0.5f : -0.5f));
      display.setPixel(px, py);
    }
  }
  display.fillRect(cx - 1, cy - 19, 3, 17);
}

void iconeCartaoMestre(int16_t cx, int16_t cy) {
  display.setColor(WHITE);
  display.drawRect(cx - 19, cy - 13, 38, 26);
  display.drawRect(cx - 18, cy - 12, 36, 24);
  display.fillRect(cx - 19, cy - 8, 38, 5);       // tarja
  display.drawRect(cx - 14, cy + 1, 10, 8);       // chip
  display.drawHorizontalLine(cx - 14, cy + 5, 10);
  texto(cx + 4, cy - 1, ArialMT_Plain_10, "M", true);
}

void iconeUsuarioMais(int16_t cx, int16_t cy) {
  display.setColor(WHITE);
  display.fillCircle(cx - 4, cy - 8, 7);          // cabeca
  for (int yy = cy + 3; yy <= cy + 14; yy++) {    // ombros
    int dy = yy - (cy + 19);
    int hw = (int)sqrtf(256.0f - (float)(dy * dy));
    display.drawHorizontalLine(cx - 4 - hw, yy, 2 * hw + 1);
  }
  display.fillRect(cx + 10, cy - 8, 11, 3);       // sinal de +
  display.fillRect(cx + 14, cy - 12, 3, 11);
}

void iconeChave(int16_t cx, int16_t cy) {
  display.setColor(WHITE);
  display.drawCircle(cx - 9, cy, 9);
  display.drawCircle(cx - 9, cy, 8);
  display.drawCircle(cx - 9, cy, 3);
  if ((millis() / 300) % 2 == 0) display.fillCircle(cx - 9, cy, 2);  // pisca
  display.fillRect(cx, cy - 1, 20, 3);            // haste
  display.fillRect(cx + 13, cy + 2, 3, 7);        // dentes
  display.fillRect(cx + 18, cy + 2, 3, 5);
}

void iconeNuvem(int16_t cx, int16_t cy) {
  display.setColor(WHITE);
  display.fillCircle(cx - 10, cy + 4, 8);
  display.fillCircle(cx, cy - 3, 11);
  display.fillCircle(cx + 11, cy + 4, 8);
  display.fillRect(cx - 10, cy + 4, 22, 8);

  // seta preta subindo
  int off = -(int)((millis() / 110) % 6);
  display.setColor(BLACK);
  for (int i = 0; i <= 6; i++) {
    display.drawHorizontalLine(cx - i, cy - 9 + off + i, 2 * i + 1);
  }
  display.fillRect(cx - 1, cy - 2 + off, 3, 9);
  display.setColor(WHITE);
}

void iconeCheck(int16_t cx, int16_t cy) {
  display.setColor(WHITE);
  display.fillCircle(cx, cy, 18);
  display.setColor(BLACK);
  for (int k = -1; k <= 1; k++) {
    display.drawLine(cx - 9, cy + k, cx - 3, cy + 7 + k);
    display.drawLine(cx - 3, cy + 7 + k, cx + 9, cy - 7 + k);
  }
  display.setColor(WHITE);
}

void iconeX(int16_t cx, int16_t cy) {
  display.setColor(WHITE);
  display.fillCircle(cx, cy, 18);
  display.setColor(BLACK);
  for (int k = -1; k <= 1; k++) {
    display.drawLine(cx - 8 + k, cy - 8, cx + 8 + k, cy + 8);
    display.drawLine(cx + 8 + k, cy - 8, cx - 8 + k, cy + 8);
  }
  display.setColor(WHITE);
}

void iconeAguardando(int16_t cx, int16_t cy) {
  display.setColor(WHITE);

  // cartao/chave
  display.drawRect(cx - 19, cy - 9, 18, 18);
  display.drawRect(cx - 18, cy - 8, 16, 16);
  display.drawRect(cx - 14, cy - 3, 8, 6);

  // ondas de leitura aparecendo uma a uma
  int ondas = 1 + (millis() / 350) % 3;
  for (int w = 0; w < ondas; w++) {
    int r = 8 + w * 6;  // 8, 14, 20
    for (int rr = r; rr <= r + 1; rr++) {
      for (int a = -45; a <= 45; a += 2) {
        float rad = a * 0.0174533f;
        int px = cx + (int)lroundf(rr * cosf(rad));
        int py = cy - (int)lroundf(rr * sinf(rad));
        display.setPixel(px, py);
      }
    }
  }
}

// ======================== TELAS ========================

void desenhaTela(int t, int16_t x) {
  const int16_t y  = 0;
  const int16_t cx = x + 21;
  const int16_t cy = y + 27;
  const int16_t tx = x + 48;

  switch (t) {
    case T_INICIANDO:
      iconePower(cx, cy);
      texto(tx, y + 4, ArialMT_Plain_16, "Iniciando");
      texto(tx, y + 26, ArialMT_Plain_10, "Aguarde" + pontosAnimados());
      barra(tx, y + 42, 76, 8, pctDesde(TEMPO_INICIANDO_MS));
      break;

    case T_WIFI: {
      display.setColor(WHITE);
      display.drawXbm(x + 34, y + 0, WiFi_Logo_width, WiFi_Logo_height, WiFi_Logo_bits);
      display.setFont(ArialMT_Plain_10);
      String base = "Configurando Wi-Fi";
      int16_t w = display.getStringWidth(base);
      texto(x + (128 - w) / 2, y + 38, ArialMT_Plain_10, base + pontosAnimados(), true);
      // alterna entre nome do hotspot e IP
      String info = ((millis() / 2500) % 2 == 0) ? String(CONFIG_AP_SSID) : portalIP.toString();
      int16_t w2 = display.getStringWidth(info);
      texto(x + (128 - w2) / 2, y + 51, ArialMT_Plain_10, info);
      break;
    }

    case T_TESTANDO_WIFI:
      iconeNuvem(cx, cy);
      texto(tx, y + 3, ArialMT_Plain_16, "Testando");
      texto(tx, y + 25, ArialMT_Plain_10, "Wi-Fi" + pontosAnimados(), true);
      texto(tx, y + 40, ArialMT_Plain_10, cortar(linhaA, 14));
      break;

    case T_SEM_MESTRE:
      iconeCartaoMestre(cx, cy);
      texto(tx, y + 6, ArialMT_Plain_10, "Aproxime o", true);
      texto(tx, y + 19, ArialMT_Plain_10, "cartao mestre", true);
      texto(tx, y + 36, ArialMT_Plain_10, "p/ cadastrar");
      break;

    case T_MESTRE:
      iconeCartaoMestre(cx, cy);
      texto(tx, y + 6, ArialMT_Plain_10, "Cartao mestre", true);
      texto(tx, y + 19, ArialMT_Plain_10, "cadastrado", true);
      texto(tx, y + 36, ArialMT_Plain_10, cortar(uidAtual, 14));
      break;

    case T_MODO_CADASTRO: {
      iconeUsuarioMais(cx, cy);
      long decorrido = (long)(millis() - inicioModoCadastro);
      long restante = ((long)TEMPO_MODO_CADASTRO_MS - decorrido) / 1000 + 1;
      if (restante < 0) restante = 0;
      texto(tx, y + 6, ArialMT_Plain_10, "Modo cadastro", true);
      texto(tx, y + 19, ArialMT_Plain_10, "Passe a chave", true);
      texto(tx, y + 36, ArialMT_Plain_10, "Expira em " + String(restante) + "s");
      break;
    }

    case T_CHAVE_LIDA:
      iconeChave(cx, cy);
      texto(tx, y + 6, ArialMT_Plain_10, "Chave lida", true);
      texto(tx, y + 36, ArialMT_Plain_10, cortar(uidAtual, 14));
      break;

    case T_ENVIANDO:
      iconeNuvem(cx, cy);
      texto(tx, y + 3, ArialMT_Plain_16, "Enviando");
      texto(tx, y + 25, ArialMT_Plain_10, "via API...", true);
      barra(tx, y + 40, 76, 8, pctDesde(TEMPO_LIMITE_HTTP_MS));
      break;

    case T_CONCEDIDO:
      iconeCheck(cx, cy);
      texto(tx, y + 2, ArialMT_Plain_16, cortar(linhaA, 9));
      texto(tx, y + 20, ArialMT_Plain_16, cortar(linhaB, 9));
      texto(tx, y + 40, ArialMT_Plain_10, cortar(linhaC, 14));
      break;

    case T_NEGADO:
      iconeX(cx, cy);
      texto(tx, y + 2, ArialMT_Plain_16, cortar(linhaA, 9));
      texto(tx, y + 20, ArialMT_Plain_16, cortar(linhaB, 9));
      texto(tx, y + 40, ArialMT_Plain_10, cortar(linhaC, 14));
      break;

    case T_AGUARDANDO:
      iconeAguardando(cx, cy);
      texto(tx, y + 6, ArialMT_Plain_10, "Aguardando" + pontosAnimados(), true);
      texto(tx, y + 19, ArialMT_Plain_10, "identificacao", true);
      texto(tx, y + 38, ArialMT_Plain_10, "Passe a chave");
      break;

    case T_TEXTO:
      texto(x, y + 0, ArialMT_Plain_10, linhaA);
      if (linhaB.length()) texto(x, y + 16, ArialMT_Plain_10, linhaB);
      if (linhaC.length()) texto(x, y + 32, ArialMT_Plain_10, linhaC);
      break;
  }
}

void renderFixa() {
  if (telaAtual < 0) return;
  display.clear();
  desenhaTela(telaAtual, 0);
  display.display();
}

// Redesenha no maximo a cada 60 ms (para o loop principal)
void renderTela() {
  static unsigned long ultimo = 0;
  if (millis() - ultimo >= 60) {
    ultimo = millis();
    renderFixa();
  }
}

// Troca imediata, sem animacao
void telaDireta(int nova) {
  telaAtual = nova;
  inicioTela = millis();
  renderFixa();
}

// Troca de tela com slide para a esquerda
void trocaTela(int nova) {
  if (telaAtual < 0) {
    telaDireta(nova);
    return;
  }

  const unsigned long DURACAO = 300;
  unsigned long ini = millis();

  while (true) {
    unsigned long dt = millis() - ini;
    if (dt >= DURACAO) break;
    float p = (float)dt / (float)DURACAO;
    p = 1.0f - (1.0f - p) * (1.0f - p);  // desacelera no final
    int off = (int)(128.0f * p);

    display.clear();
    desenhaTela(telaAtual, -off);
    desenhaTela(nova, 128 - off);
    display.display();
  }
  telaDireta(nova);
}

// Compatibilidade com o codigo original: 3 linhas de texto
void mostrarOLED(const String& linha1,
                 const String& linha2 = "",
                 const String& linha3 = "") {
  setLinhas(linha1, linha2, linha3);
  telaDireta(T_TEXTO);
}

// Volta para a tela de repouso correta
void voltarOcioso() {
  if (portalAtivo)              trocaTela(T_WIFI);
  else if (modoCadastro)        trocaTela(T_MODO_CADASTRO);
  else if (uidMestre.isEmpty()) trocaTela(T_SEM_MESTRE);
  else                          trocaTela(T_AGUARDANDO);
}

// ======================== BUZZER / LED ========================

void buzzerSet(bool ligado) {
  if (BUZZER_SINAL_PIN < 0) return;
  digitalWrite(BUZZER_SINAL_PIN, (ligado == BUZZER_ACTIVE_HIGH) ? HIGH : LOW);
}

void ledSet(bool ligado) {
  if (LED_SINAL_PIN < 0) return;
  digitalWrite(LED_SINAL_PIN, (ligado == LED_ACTIVE_HIGH) ? HIGH : LOW);
}

// ======================== RELE ========================
void definirRele(bool ligado) {
  if (RELE_PIN < 0) return;
  bool nivelAlto = ligado ? RELE_ATIVO_HIGH : !RELE_ATIVO_HIGH;
  digitalWrite(RELE_PIN, nivelAlto ? HIGH : LOW);
}

// Comando HTTP simples para o rele Tasmota
bool releHttp(const char* url) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("Rele: sem Wi-Fi.");
    return false;
  }
  WiFiClient cliente;
  HTTPClient http;
  http.setConnectTimeout(RELE_HTTP_TIMEOUT_MS);
  http.setTimeout(RELE_HTTP_TIMEOUT_MS);
  if (!http.begin(cliente, url)) {
    Serial.println("Rele: nao foi possivel iniciar HTTP.");
    return false;
  }
  int codigo = http.GET();
  Serial.printf("Rele GET %s -> %d\n", url, codigo);
  http.end();
  return codigo == 200;
}

// Liga o rele e agenda o desligamento. Retorna false se o comando de ligar falhou.
bool acionarRele() {
  bool ok = true;
  bool algum = false;

  if (RELE_PIN >= 0) {
    definirRele(true);
    algum = true;
    Serial.println("Rele GPIO ligado.");
  }
  if (RELE_TASMOTA_HABILITADO) {
    algum = true;
    Serial.println("Acionando rele Tasmota...");
    if (!releHttp(RELE_TASMOTA_URL_ON)) {
      ok = false;
      Serial.println("ERRO: rele Tasmota nao respondeu ao comando ON.");
    }
  }
  if (!algum) {
    Serial.println("AVISO: nenhum rele configurado (RELE_PIN / Tasmota); nada acionado.");
    return true;
  }

  // Mesmo se o ON falhou, garante a tentativa de desligar.
  releLigado = true;
  releInicio = millis();
  releTentativasOff = 0;
  return ok;
}

// Chamar com frequencia: desliga o rele apos o tempo, sem travar a tela
void releAtualizar() {
  if (!releLigado) return;
  if (millis() - releInicio < TEMPO_PULSO_RELE_MS) return;

  bool tudoOk = true;
  if (RELE_PIN >= 0) definirRele(false);
  if (RELE_TASMOTA_HABILITADO) tudoOk = releHttp(RELE_TASMOTA_URL_OFF);

  if (tudoOk) {
    releLigado = false;
    Serial.println("Rele desligado.");
  } else if (++releTentativasOff >= RELE_TENTATIVAS_DESLIGAR) {
    releLigado = false;
    Serial.println("ERRO: nao foi possivel confirmar o desligamento do rele! Verifique o dispositivo.");
  } else {
    Serial.printf("Falha ao desligar rele; nova tentativa (%u/%u).\n",
                  releTentativasOff, RELE_TENTATIVAS_DESLIGAR);
    releInicio = millis() - TEMPO_PULSO_RELE_MS + 500;  // tenta de novo em 500 ms
  }
}

// ======================== BOTAO: APAGAR MESTRE ========================
void apagarCartaoMestre();  // declaracao antecipada

void verificarBotaoResetMestre() {
  if (BOTAO_RESET_MESTRE_PIN < 0) return;

  static unsigned long inicioPressao = 0;
  static bool acionadoNestaPressao = false;
  bool pressionado = digitalRead(BOTAO_RESET_MESTRE_PIN) == LOW;

  if (!pressionado) {
    inicioPressao = 0;
    acionadoNestaPressao = false;
    if (botaoMostrando) {
      botaoMostrando = false;
      if (!erroFatal) voltarOcioso();
    }
    return;
  }

  if (inicioPressao == 0) {
    inicioPressao = millis();
    botaoMostrando = true;
    Serial.println("Botao pressionado; segure 10 s para apagar o mestre.");
    mostrarOLED("Botao pressionado", "Segure 10 segundos");
  }

  if (!acionadoNestaPressao &&
      millis() - inicioPressao >= TEMPO_PRESSIONADO_MESTRE_MS) {
    acionadoNestaPressao = true;
    apagarCartaoMestre();
  }
}

// Tarefas de fundo enquanto o codigo "espera" algo
void servicos() {
  heltec_loop();
  releAtualizar();
  verificarBotaoResetMestre();
}

// Espera mantendo animacoes, rele e botao rodando
void espera(unsigned long ms) {
  unsigned long ini = millis();
  while (millis() - ini < ms) {
    servicos();
    renderFixa();
    delay(5);
  }
}

// ======================== SINAIS (BUZZER + LED) ========================

// Acesso liberado: 1 beep longo + LED piscando 3x
void sinalAcessoConcedido() {
  unsigned long ini = millis();
  buzzerSet(true);
  while (true) {
    unsigned long dt = millis() - ini;
    if (dt >= BEEP_LONGO_MS) break;
    bool ledLigado = false;
    if (dt < 6 * LED_FASE_MS) {                 // 3 piscadas = 6 fases
      ledLigado = ((dt / LED_FASE_MS) % 2) == 0;
    }
    ledSet(ledLigado);
    servicos();
    renderFixa();
  }
  buzzerSet(false);
  ledSet(false);
}

// Acesso negado: 2 beeps, LED apagado
void sinalAcessoNegado() {
  ledSet(false);
  for (int i = 0; i < 2; i++) {
    buzzerSet(true);
    espera(BEEP_NEGADO_MS);
    buzzerSet(false);
    if (i == 0) espera(INTERVALO_BEEP_MS);
  }
}

// ======================== UID RFID ========================
String lerUID() {
  String uid;
  for (byte i = 0; i < leitor.uid.size; i++) {
    if (leitor.uid.uidByte[i] < 0x10) uid += "0";
    uid += String(leitor.uid.uidByte[i], HEX);
    if (i + 1 < leitor.uid.size) uid += ":";
  }
  uid.toUpperCase();
  return uid;
}

bool lerCartao(String& uid) {
  if (!leitor.PICC_IsNewCardPresent()) return false;
  if (!leitor.PICC_ReadCardSerial()) return false;
  uid = lerUID();
  leitor.PICC_HaltA();
  leitor.PCD_StopCrypto1();
  return true;
}

// ======================== CARTAO MESTRE NA NVS ========================
void salvarCartaoMestre(const String& uid) {
  preferencias.begin("controle", false);
  preferencias.putString("uidMestre", uid);
  preferencias.end();
  uidMestre = uid;
}

void apagarCartaoMestre() {
  preferencias.begin("controle", false);
  preferencias.remove("uidMestre");
  preferencias.end();
  uidMestre = "";
  modoCadastro = false;
  mostrarOLED("Mestre apagado", "Aproxime novo", "cartao mestre");
  Serial.println("Cartao mestre removido. Aguardando novo mestre.");
}

void carregarCartaoMestre() {
  preferencias.begin("controle", true);
  uidMestre = preferencias.getString("uidMestre", "");
  preferencias.end();
}

// ======================== PAGINA DE CONFIGURACAO WI-FI ========================
String paginaConfiguracao(const String& aviso = "") {
  String html = R"HTML(
<!doctype html><html lang="pt-br"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Configurar Wi-Fi</title>
<style>
body{font-family:Arial,sans-serif;max-width:520px;margin:28px auto;padding:0 16px;color:#222}
main{border:1px solid #ddd;border-radius:12px;padding:20px}
h1{font-size:1.4rem}label{display:block;margin-top:16px;font-weight:bold}
select,input,button{box-sizing:border-box;width:100%;padding:12px;margin-top:7px;font-size:1rem}
button{border:0;border-radius:8px;cursor:pointer;background:#1f6feb;color:white}
.note{color:#555;font-size:.9rem}.msg{padding:10px;background:#f3f3f3;border-radius:8px}
</style></head><body><main>
<h1>Configuração Wi-Fi</h1>
<p class="note">Selecione uma rede encontrada pelo ESP32 e informe a senha. A configuração será salva somente se a conexão funcionar.</p>
<div class="msg">)HTML";
  html += aviso;
  html += R"HTML(</div>
<form action="/connect" method="post">
<label for="ssid">Rede Wi-Fi</label>
<select id="ssid" name="ssid" required><option value="">Buscando redes...</option></select>
<label for="manual">Ou informe outro SSID (opcional)</label>
<input id="manual" name="manual" placeholder="Nome de rede oculta ou outro SSID">
<label for="password">Senha</label>
<input id="password" name="password" type="password" autocomplete="current-password" placeholder="Senha da rede">
<button type="submit">Testar e salvar</button>
</form>
<p class="note">Hotspot: Heltec-Acesso-Setup · Endereço: 192.168.4.1</p>
<button type="button" onclick="scan()">Atualizar lista de redes</button>
</main>
<script>
async function scan(){
 const s=document.getElementById('ssid'); s.innerHTML='<option value="">Procurando...</option>';
 try{
  const r=await fetch('/scan'); const d=await r.json(); s.innerHTML='';
  if(!d.length){s.innerHTML='<option value="">Nenhuma rede encontrada</option>';return;}
  d.forEach(n=>{const o=document.createElement('option');o.value=n.ssid;o.textContent=n.ssid+' ('+n.rssi+' dBm)'+(n.secure?' 🔒':'');s.appendChild(o);});
 }catch(e){s.innerHTML='<option value="">Falha ao buscar redes</option>';}
}
scan();
</script></body></html>)HTML";
  return html;
}

// Wi-Fi conectado: mostra resultado, fecha o portal e volta ao modo normal
void wifiConectadoOK() {
  String ip = WiFi.localIP().toString();
  Serial.print("Wi-Fi conectado. IP: ");
  Serial.println(ip);
  setLinhas("Wi-Fi", "conectado", ip);
  telaDireta(T_CONCEDIDO);
  espera(1800);

  servidorDNS.stop();
  servidorWeb.stop();
  WiFi.softAPdisconnect(true);
  portalAtivo = false;
  WiFi.mode(WIFI_STA);
  Serial.println("Hotspot de configuracao encerrado.");

  // Seguranca: se o ESP reiniciou com o rele Tasmota ligado, desliga agora.
  if (RELE_TASMOTA_HABILITADO) {
    delay(500);
    releHttp(RELE_TASMOTA_URL_OFF);
  }

  if (uidMestre.isEmpty()) {
    Serial.println("Sem mestre cadastrado; aproxime o cartao que sera o mestre.");
  } else {
    Serial.println("Sistema pronto. Aproxime a chave.");
  }
  voltarOcioso();
}

void iniciarPortalConfiguracao() {
  WiFi.mode(WIFI_AP_STA);
  WiFi.softAPConfig(portalIP, portalIP, portalMask);
  bool apOk = WiFi.softAP(CONFIG_AP_SSID, CONFIG_AP_PASSWORD);
  if (!apOk) {
    Serial.println("Falha ao iniciar hotspot de configuracao.");
    erroFatal = true;
    mostrarOLED("Erro no hotspot", "Reinicie dispositivo");
    return;
  }

  servidorDNS.start(DNS_PORT, "*", portalIP);
  servidorWeb.on("/", HTTP_GET, []() {
    servidorWeb.send(200, "text/html; charset=utf-8", paginaConfiguracao("Pronto para configurar."));
  });

  servidorWeb.on("/scan", HTTP_GET, []() {
    Serial.println("Pagina pediu busca de redes Wi-Fi.");
    int quantidade = WiFi.scanNetworks(false, true);
    DynamicJsonDocument doc(4096);
    JsonArray arr = doc.to<JsonArray>();
    for (int i = 0; i < quantidade; i++) {
      String ssid = WiFi.SSID(i);
      if (ssid.isEmpty()) continue;
      JsonObject item = arr.createNestedObject();
      item["ssid"] = ssid;
      item["rssi"] = WiFi.RSSI(i);
      item["secure"] = WiFi.encryptionType(i) != WIFI_AUTH_OPEN;
    }
    String json;
    serializeJson(doc, json);
    WiFi.scanDelete();
    Serial.printf("Redes encontradas: %d\n", quantidade);
    servidorWeb.send(200, "application/json; charset=utf-8", json);
  });

  servidorWeb.on("/connect", HTTP_POST, []() {
    String ssid = servidorWeb.arg("manual");
    ssid.trim();
    if (ssid.isEmpty()) ssid = servidorWeb.arg("ssid");
    String senha = servidorWeb.arg("password");

    if (ssid.isEmpty()) {
      servidorWeb.send(400, "text/html; charset=utf-8", paginaConfiguracao("Selecione ou informe uma rede."));
      return;
    }

    servidorWeb.send(200, "text/html; charset=utf-8",
      "<!doctype html><html lang='pt-br'><meta charset='utf-8'><meta name='viewport' content='width=device-width,initial-scale=1'><body style='font-family:Arial;padding:24px'><h2>Testando conexão...</h2><p>Aguarde. O ESP32 tentará conectar à rede selecionada.</p></body></html>");

    Serial.println("Testando rede Wi-Fi: " + ssid);
    linhaA = ssid;
    telaDireta(T_TESTANDO_WIFI);
    WiFi.disconnect(false, false);
    WiFi.begin(ssid.c_str(), senha.c_str());

    unsigned long inicio = millis();
    while (WiFi.status() != WL_CONNECTED && millis() - inicio < 15000) {
      servidorDNS.processNextRequest();
      heltec_loop();
      renderFixa();
      delay(50);
    }

    if (WiFi.status() == WL_CONNECTED) {
      preferencias.begin("wifi", false);
      preferencias.putString("ssid", ssid);
      preferencias.putString("password", senha);
      preferencias.end();
      Serial.println("Credenciais Wi-Fi salvas.");
      wifiConectadoOK();
    } else {
      WiFi.disconnect(false, false);
      WiFi.mode(WIFI_AP_STA);
      Serial.println("Falha ao conectar; credenciais nao foram salvas.");
      setLinhas("Falha no", "Wi-Fi", "Verifique senha");
      telaDireta(T_NEGADO);
      // Hotspot e página continuam ativos; mostra mensagem na próxima visita.
      servidorWeb.on("/", HTTP_GET, []() {
        servidorWeb.send(200, "text/html; charset=utf-8", paginaConfiguracao("Falha na conexão. Confira a rede e a senha."));
      });
      espera(1800);
      voltarOcioso();
    }
  });

  servidorWeb.onNotFound([]() {
    servidorWeb.sendHeader("Location", String("http://") + portalIP.toString() + "/", true);
    servidorWeb.send(302, "text/plain", "");
  });

  servidorWeb.begin();
  portalAtivo = true;
  inicioPortalMs = millis();
  tentativaAutomaticaFeita = false;
  trocaTela(T_WIFI);
  Serial.println("Hotspot de configuracao iniciado.");
  Serial.print("SSID: "); Serial.println(CONFIG_AP_SSID);
  Serial.print("IP: "); Serial.println(WiFi.softAPIP());
}

bool conectarCredenciaisSalvas() {
  preferencias.begin("wifi", true);
  String ssid = preferencias.getString("ssid", "");
  String senha = preferencias.getString("password", "");
  preferencias.end();

  if (ssid.isEmpty()) {
    Serial.println("Nenhuma rede Wi-Fi salva; hotspot permanece ativo.");
    return false;
  }

  Serial.println("Tentando credenciais Wi-Fi salvas: " + ssid);
  linhaA = ssid;
  trocaTela(T_TESTANDO_WIFI);
  WiFi.begin(ssid.c_str(), senha.c_str());

  unsigned long inicio = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - inicio < 15000) {
    servidorDNS.processNextRequest();
    heltec_loop();
    renderFixa();
    delay(50);
  }

  if (WiFi.status() == WL_CONNECTED) {
    wifiConectadoOK();
    return true;
  }

  WiFi.disconnect(false, false);
  Serial.println("Credenciais salvas nao conectaram; portal continua disponivel.");
  setLinhas("Wi-Fi", "indisponivel", "Configure a rede");
  telaDireta(T_NEGADO);
  espera(1800);
  voltarOcioso();
  return false;
}

void processarPortalWiFi() {
  if (!portalAtivo) return;
  servidorDNS.processNextRequest();
  servidorWeb.handleClient();

  // A cada boot o hotspot fica disponível para configuração.
  // Se já há credenciais salvas, tenta usá-las após 60 s sem configuração manual.
  if (!tentativaAutomaticaFeita &&
      millis() - inicioPortalMs >= TEMPO_PORTAL_AUTO_CONECTAR_MS &&
      WiFi.softAPgetStationNum() == 0) {
    tentativaAutomaticaFeita = true;
    conectarCredenciaisSalvas();
  }
}

// ======================== API ========================
bool enviarPostJson(const char* url, const String& corpo,
                    String& resposta, int& codigoHttp) {
  if (portalAtivo || WiFi.status() != WL_CONNECTED) {
    codigoHttp = -1000;
    resposta = "";
    return false;
  }

  WiFiClient cliente;
  HTTPClient http;
  http.setTimeout(TEMPO_LIMITE_HTTP_MS);

  if (!http.begin(cliente, url)) {
    codigoHttp = -1001;
    Serial.println("Nao foi possivel iniciar HTTP.");
    return false;
  }

  http.addHeader("Content-Type", "application/json");
  codigoHttp = http.POST(corpo);
  if (codigoHttp > 0) resposta = http.getString();
  else {
    resposta = "";
    Serial.printf("Falha HTTP: %d\n", codigoHttp);
  }
  http.end();
  return codigoHttp >= 200 && codigoHttp < 300;
}

bool consultarAcesso(const String& uid, bool& autorizado, String& mensagem) {
  autorizado = false;
  mensagem = "Falha de comunicacao";

  StaticJsonDocument<192> pedido;
  pedido["uid"] = uid;
  String corpo;
  serializeJson(pedido, corpo);

  Serial.println("POST acesso: " + corpo);

  String resposta;
  int codigoHttp = 0;
  bool sucessoHttp = enviarPostJson(API_URL_ACESSO, corpo, resposta, codigoHttp);
  if (!sucessoHttp) {
    mensagem = (codigoHttp == -1000) ? "Sem Wi-Fi" : "Erro HTTP/API";
    Serial.printf("Consulta falhou. HTTP/codigo: %d\n", codigoHttp);
    return false;
  }

  Serial.println("Resposta API: " + resposta);
  StaticJsonDocument<512> retorno;
  DeserializationError erro = deserializeJson(retorno, resposta);
  if (erro || !retorno["authorized"].is<bool>()) {
    mensagem = "Resposta invalida";
    Serial.println("JSON invalido ou campo authorized ausente.");
    return false;
  }
  autorizado = retorno["authorized"].as<bool>();
  mensagem = retorno["message"] | (autorizado ? "Acesso permitido" : "Acesso negado");
  return true;
}

bool registrarChavePendente(const String& uid, String& mensagem) {
  StaticJsonDocument<192> pedido;
  pedido["uid"] = uid;
  String corpo;
  serializeJson(pedido, corpo);

  Serial.println("POST cadastro pendente: " + corpo);

  String resposta;
  int codigoHttp = 0;
  bool sucessoHttp = enviarPostJson(API_URL_CADASTRO, corpo, resposta, codigoHttp);
  if (!sucessoHttp) {
    mensagem = (codigoHttp == -1000) ? "Sem Wi-Fi" : "Erro HTTP/API";
    Serial.printf("Cadastro pendente falhou. HTTP/codigo: %d\n", codigoHttp);
    return false;
  }

  Serial.println("Resposta cadastro: " + resposta);
  StaticJsonDocument<512> retorno;
  DeserializationError erro = deserializeJson(retorno, resposta);
  if (erro) {
    mensagem = "Resposta invalida";
    return false;
  }
  mensagem = retorno["message"] | "Chave enviada";
  return true;
}

// ======================== PROCESSAMENTO RFID ========================
void processarCartao(const String& uid) {
  Serial.println("UID lido: " + uid);
  uidAtual = uid;

  // 1) Sem mestre: o primeiro cartao vira mestre
  if (uidMestre.isEmpty()) {
    salvarCartaoMestre(uid);
    Serial.println("Novo cartao mestre salvo.");
    trocaTela(T_MESTRE);
    espera(2000);
    voltarOcioso();
    return;
  }

  // 2) Cartao mestre: alterna modo de cadastro
  if (uid == uidMestre) {
    modoCadastro = !modoCadastro;
    if (modoCadastro) {
      inicioModoCadastro = millis();
      Serial.println("Modo cadastro ativado.");
      trocaTela(T_MODO_CADASTRO);
    } else {
      Serial.println("Modo cadastro desativado.");
      setLinhas("Cadastro", "encerrado", "Acesso normal");
      trocaTela(T_TEXTO);
      espera(1000);
      voltarOcioso();
    }
    return;
  }

  // 3) Modo cadastro: envia a chave como pendente
  if (modoCadastro) {
    trocaTela(T_CHAVE_LIDA);
    espera(TEMPO_LEITURA_MS);
    trocaTela(T_ENVIANDO);
    renderFixa();

    String mensagem;
    bool enviado = registrarChavePendente(uid, mensagem);
    modoCadastro = false;

    if (enviado) {
      Serial.println("Chave enviada para cadastro.");
      setLinhas("Chave", "enviada", mensagem);
      trocaTela(T_CONCEDIDO);
      sinalAcessoConcedido();   // somente beep + LED, nao aciona o rele
    } else {
      Serial.println("Falha no cadastro da chave: " + mensagem);
      setLinhas("Falha no", "cadastro", mensagem);
      trocaTela(T_NEGADO);
      sinalAcessoNegado();
    }
    espera(TEMPO_RESULTADO_MS);
    voltarOcioso();
    return;
  }

  // 4) Acesso normal: consulta a API
  trocaTela(T_CHAVE_LIDA);
  espera(TEMPO_LEITURA_MS);
  trocaTela(T_ENVIANDO);
  renderFixa();

  bool autorizado = false;
  String mensagem;
  bool consultaOk = consultarAcesso(uid, autorizado, mensagem);

  if (!consultaOk) {
    definirRele(false);
    Serial.println("Acesso negado por falha de comunicacao/validacao.");
    setLinhas("Acesso", "negado", mensagem);
    trocaTela(T_NEGADO);
    sinalAcessoNegado();
  } else if (autorizado) {
    Serial.println("Acesso autorizado pela API.");
    bool releOk = acionarRele();
    if (releOk) {
      setLinhas("Acesso", "concedido", uid);
      trocaTela(T_CONCEDIDO);
      sinalAcessoConcedido();
    } else {
      Serial.println("Acesso autorizado, mas o rele nao confirmou o acionamento.");
      setLinhas("Acesso", "negado", "Falha no rele");
      trocaTela(T_NEGADO);
      sinalAcessoNegado();
    }
  } else {
    definirRele(false);
    Serial.println("Acesso negado pela API.");
    setLinhas("Acesso", "negado", mensagem);
    trocaTela(T_NEGADO);
    sinalAcessoNegado();
  }

  espera(TEMPO_RESULTADO_MS);
  voltarOcioso();
}

// ======================== SETUP / LOOP ========================
void setup() {
  heltec_setup();
  if (OLED_GIRAR_180) display.flipScreenVertically();
  else                display.resetOrientation();
  display.setContrast(CONTRASTE_OLED);
  display.clear();
  display.display();

  Serial.println();
  Serial.println("=== Controle de acesso RFID ===");

  // Buzzer e LED desligados desde o inicio
  if (BUZZER_SINAL_PIN >= 0) pinMode(BUZZER_SINAL_PIN, OUTPUT);
  if (LED_SINAL_PIN >= 0) pinMode(LED_SINAL_PIN, OUTPUT);
  buzzerSet(false);
  ledSet(false);

  trocaTela(T_INICIANDO);
  Serial.println("Iniciando sistema...");

  if (RELE_PIN >= 0) {
    pinMode(RELE_PIN, OUTPUT);
    definirRele(false);
  }
  if (BOTAO_RESET_MESTRE_PIN >= 0) {
    pinMode(BOTAO_RESET_MESTRE_PIN, INPUT_PULLUP);
  }

  SPI.begin(RFID_SCK_PIN, RFID_MISO_PIN, RFID_MOSI_PIN, RFID_SS_PIN);
  leitor.PCD_Init();
  delay(50);

  byte versao = leitor.PCD_ReadRegister(leitor.VersionReg);
  if (versao == 0x00 || versao == 0xFF) {
    erroFatal = true;
    mostrarOLED("Erro no MFRC522", "Verifique ligacoes");
    Serial.println("Falha de comunicacao com MFRC522. Confira pinos e alimentacao.");
    while (true) {
      heltec_loop();
      verificarBotaoResetMestre();
      delay(10);
    }
  }
  Serial.printf("MFRC522 OK (versao 0x%02X).\n", versao);

  carregarCartaoMestre();
  if (uidMestre.isEmpty()) {
    Serial.println("Sem mestre cadastrado; primeiro cartao apresentado apos configuracao sera o mestre.");
  } else {
    Serial.println("Cartao mestre carregado: " + uidMestre);
  }

  espera(TEMPO_INICIANDO_MS);
  iniciarPortalConfiguracao();
}

void loop() {
  heltec_loop();
  releAtualizar();
  verificarBotaoResetMestre();

  if (portalAtivo) {
    processarPortalWiFi();
    renderTela();
    delay(5);
    return; // RFID bloqueado enquanto o portal Wi-Fi estiver ativo
  }

  if (modoCadastro &&
      millis() - inicioModoCadastro >= TEMPO_MODO_CADASTRO_MS) {
    modoCadastro = false;
    Serial.println("Tempo do modo cadastro expirou.");
    setLinhas("Cadastro", "expirou", "Acesso normal");
    trocaTela(T_TEXTO);
    espera(1000);
    voltarOcioso();
  }

  renderTela();  // mantem as animacoes da tela de repouso

  String uid;
  if (!lerCartao(uid)) {
    delay(10);
    return;
  }

  unsigned long agora = millis();
  if (uid == ultimoUid &&
      agora - ultimaLeituraMs < INTERVALO_ANTI_REPETICAO_MS) return;

  ultimoUid = uid;
  ultimaLeituraMs = agora;
  processarCartao(uid);
  ultimaLeituraMs = millis();  // o processamento e longo; reinicia a janela anti-repeticao
}
