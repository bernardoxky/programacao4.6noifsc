/*
 * ============================================================
 *  SENSOR DE PROXIMIDADE  -  Trabalho Final de Programação IV
 *  IFSC Câmpus Chapecó
 *
 *  Placa: NodeMCU ESP8266 (ESP-12E)
 *  Peças: sensor ultrassônico HC-SR04, display LCD 16x2 com módulo I2C,
 *         LED RGB, buzzer passivo e push button.
 *
 *  O que ele faz:
 *   1. Mede a distância (cm) várias vezes por segundo.
 *   2. Escolhe a "faixa" da distância (mensagem do LCD, cor do LED, buzzer).
 *      As faixas vêm do site (getjson.php) e são programadas pelo painel.
 *   3. Envia as leituras para o banco de dados (gravaleitura.php).
 *   4. Botão: clique curto = silenciar/ativar o buzzer.
 *             segurar 1 s  = alterna a tela (distância <-> status da rede).
 *
 *  Bibliotecas (Gerenciador de Bibliotecas da IDE):
 *    - ArduinoJson (Benoit Blanchon), versão 7.x
 *    - LiquidCrystal I2C (Frank de Brabander)
 *  Placa na IDE: "NodeMCU 1.0 (ESP-12E Module)"  (pacote esp8266 da ESP8266 Community)
 * ============================================================
 */

#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClientSecure.h>
#include <ArduinoJson.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>

// ============================================================
//  1) CONFIGURE AQUI
// ============================================================
const char* WIFI_SSID  = "IFSC";
const char* WIFI_SENHA = "campuschapeco";

const char* SERVIDOR   = "6noifsc.com.br";  // só o domínio, sem http:// e sem barra
const bool  USAR_HTTPS = true;              // a Hostinger costuma exigir HTTPS
const char* TOKEN      = "";                // só se você preencheu TOKEN_ESP no conexao.php
const int   SENSOR_ID  = 1;                 // id do sensor na tabela `sensor`

const bool  LED_ANODO_COMUM = false;        // true se o seu LED RGB for de ânodo comum (+)

// ============================================================
//  2) PINOS (números GPIO; entre parênteses o nome impresso na placa)
// ============================================================
const uint8_t PIN_TRIG   = 14;  // D5  -> TRIG do HC-SR04
const uint8_t PIN_ECHO   = 12;  // D6  <- ECHO do HC-SR04 (passando pelo divisor de tensão!)
const uint8_t PIN_LED_R  = 13;  // D7  -> LED vermelho (com resistor de 220 ohm)
const uint8_t PIN_LED_G  = 15;  // D8  -> LED verde    (com resistor de 220 ohm)
const uint8_t PIN_LED_B  = 16;  // D0  -> LED azul     (com resistor de 220 ohm) - sem PWM, só liga/desliga
const uint8_t PIN_BUZZER = 2;   // D4  -> buzzer passivo
const uint8_t PIN_BOTAO  = 0;   // D3  -> push button (outro lado no GND)
const uint8_t PIN_SDA    = 4;   // D2  -> SDA do módulo I2C do LCD
const uint8_t PIN_SCL    = 5;   // D1  -> SCL do módulo I2C do LCD

// ============================================================
//  3) TEMPOS
// ============================================================
const unsigned long INTERVALO_MEDIDA = 150;     // mede a cada 150 ms
const unsigned long INTERVALO_FAIXAS = 20000;   // busca as faixas no site a cada 20 s
const unsigned long INTERVALO_INATIVA = 10000;  // placa inativa: pergunta de novo a cada 10 s
const unsigned long ENVIO_MIN = 2000;           // envia se a distância mudou, no mínimo a cada 2 s
const unsigned long ENVIO_MAX = 10000;          // e sempre a cada 10 s, mesmo sem mudar
const int           VARIACAO_ENVIO = 3;         // "mudou" = variou 3 cm ou mais
const unsigned long PRESS_LONGO = 1000;         // segurar o botão por 1 s

// ============================================================
//  Faixas (o que fazer em cada intervalo de distância)
// ============================================================
struct Faixa {
  float    mn;       // distância mínima (cm), inclusive
  float    mx;       // distância máxima (cm), exclusive
  char     msg[17];  // mensagem do LCD (16 colunas)
  uint8_t  r, g, b;  // cor do LED (0 a 255)
  uint16_t tom;      // buzzer: frequência em Hz (0 = sem som)
  uint16_t tempo;    // buzzer: duração de cada bip em ms
};

const int MAX_FAIXAS = 12;
Faixa faixas[MAX_FAIXAS];
int   nFaixas = 0;
bool  faixasDoServidor = false;

// ============================================================
//  Estado do programa
// ============================================================
LiquidCrystal_I2C* lcd = nullptr;
char lcdCache[2][17] = {"", ""};

WiFiClientSecure clienteTls;
WiFiClient       clienteTcp;
String meuMac;
String statusServidor = "---";

bool placaAtiva = true;     // vem do servidor (tabela mac, campo ativo)
bool mudo = false;          // botão: clique curto
bool telaStatus = false;    // botão: segurar 1 s

const Faixa* faixaAtual = nullptr;
float distanciaAtual = -1;

unsigned long ultimaMedida = 0;
unsigned long ultimaSync = 0;
unsigned long ultimoEnvio = 0;
unsigned long ultimoBip = 0;
int ultimoEnviado = -1000;

bool botaoAnterior = HIGH;
unsigned long botaoDesde = 0;
bool longoTratado = false;

// ============================================================
//  LCD
// ============================================================
uint8_t acharLcd() {                    // procura o módulo I2C em 0x27 e 0x3F
  const uint8_t enderecos[] = {0x27, 0x3F};
  for (uint8_t e : enderecos) {
    Wire.beginTransmission(e);
    if (Wire.endTransmission() == 0) return e;
  }
  return 0x27;
}

// Escreve uma linha só se o texto mudou (evita piscar a tela)
void lcdLinha(uint8_t linha, const char* texto) {
  char buf[17];
  snprintf(buf, sizeof(buf), "%-16s", texto);
  if (strcmp(buf, lcdCache[linha]) != 0) {
    lcd->setCursor(0, linha);
    lcd->print(buf);
    strcpy(lcdCache[linha], buf);
  }
}

// ============================================================
//  LED RGB e buzzer
// ============================================================
void canalLed(uint8_t pino, uint8_t valor) {
  if (LED_ANODO_COMUM) valor = 255 - valor;
  if (pino == 16) {                                   // GPIO16 (D0) não tem PWM
    digitalWrite(pino, valor > 127 ? HIGH : LOW);
  } else {
    analogWrite(pino, map(valor, 0, 255, 0, 1023));
  }
}

void ledCor(uint8_t r, uint8_t g, uint8_t b) {
  canalLed(PIN_LED_R, r);
  canalLed(PIN_LED_G, g);
  canalLed(PIN_LED_B, b);
}

// Faz os bips da faixa atual (chamado o tempo todo, sem travar o programa)
void tocarBuzzer(const Faixa* f) {
  if (mudo || f == nullptr || f->tom == 0) {
    noTone(PIN_BUZZER);
    return;
  }
  unsigned long duracao = f->tempo > 0 ? f->tempo : 200;
  unsigned long agora = millis();
  if (agora - ultimoBip >= duracao * 2) {             // bip + silêncio de mesma duração
    tone(PIN_BUZZER, f->tom, duracao);
    ultimoBip = agora;
  }
}

// ============================================================
//  Sensor ultrassônico HC-SR04
// ============================================================
// Mede 3 vezes e usa a do meio (mediana) para ignorar leituras erradas.
// Retorna a distância em cm, ou -1 se não houve eco.
float medirCm() {
  float v[3];
  int n = 0;
  for (int i = 0; i < 3; i++) {
    digitalWrite(PIN_TRIG, LOW);
    delayMicroseconds(3);
    digitalWrite(PIN_TRIG, HIGH);                     // pulso de 10 microssegundos
    delayMicroseconds(10);
    digitalWrite(PIN_TRIG, LOW);
    unsigned long duracao = pulseIn(PIN_ECHO, HIGH, 25000UL);   // espera até 25 ms (~4 m)
    if (duracao > 0) v[n++] = duracao * 0.0343f / 2.0f;         // som: 343 m/s, ida e volta
    delay(8);
  }
  if (n == 0) return -1;
  for (int i = 0; i < n - 1; i++)                     // ordena (n é no máximo 3)
    for (int j = 0; j < n - 1 - i; j++)
      if (v[j] > v[j + 1]) { float t = v[j]; v[j] = v[j + 1]; v[j + 1] = t; }
  float cm = v[n / 2];
  return (cm < 2.0f) ? -1 : cm;
}

const Faixa* faixaPara(float cm) {
  for (int i = 0; i < nFaixas; i++)
    if (cm >= faixas[i].mn && cm < faixas[i].mx) return &faixas[i];
  return nullptr;
}

// Faixas de reserva (usadas até o site responder, ou se estiver sem internet)
void faixasPadrao() {
  nFaixas = 3;
  faixas[0] = {0, 10, "PERTO! PARE", 255, 0, 0, 2000, 100};
  faixas[1] = {10, 30, "ATENCAO", 255, 255, 0, 1000, 300};
  faixas[2] = {30, 400, "DISTANCIA OK", 0, 255, 0, 0, 0};
  faixasDoServidor = false;
}

// ============================================================
//  Rede: conversa com o site
// ============================================================
String urlBase() {
  return String(USAR_HTTPS ? "https://" : "http://") + SERVIDOR;
}

bool iniciarHttp(HTTPClient& http, const String& url) {
  http.setTimeout(5000);
  http.setFollowRedirects(HTTPC_STRICT_FOLLOW_REDIRECTS);
  if (USAR_HTTPS) return http.begin(clienteTls, url);
  return http.begin(clienteTcp, url);
}

// GET getjson.php -> atualiza as faixas e se a placa está ativa
bool buscarFaixas() {
  if (WiFi.status() != WL_CONNECTED) { statusServidor = "SEM REDE"; return false; }

  HTTPClient http;
  String url = urlBase() + "/getjson.php?mac=" + meuMac + "&sensor=" + String(SENSOR_ID);
  if (strlen(TOKEN) > 0) url += String("&token=") + TOKEN;
  if (!iniciarHttp(http, url)) { statusServidor = "ERRO URL"; return false; }

  int codigo = http.GET();
  if (codigo != HTTP_CODE_OK) {
    statusServidor = codigo == 404 ? String("MAC NAO CAD.")
                   : codigo > 0 ? String("ERRO ") + codigo : String("SEM RESP.");
    http.end();
    return false;
  }
  String corpo = http.getString();
  http.end();

  JsonDocument doc;
  if (deserializeJson(doc, corpo)) { statusServidor = "JSON INVAL."; return false; }

  placaAtiva = (doc["ativo"] | 1) == 1;

  int n = 0;
  for (JsonObject f : doc["faixas"].as<JsonArray>()) {
    if (n >= MAX_FAIXAS) break;
    Faixa& x = faixas[n++];
    x.mn    = f["min"] | 0.0f;
    x.mx    = f["max"] | 0.0f;
    x.r     = f["r"] | 0;
    x.g     = f["g"] | 0;
    x.b     = f["b"] | 0;
    x.tom   = f["tom"] | 0;
    x.tempo = f["tempo"] | 0;
    strlcpy(x.msg, f["msg"] | "", sizeof(x.msg));
  }
  nFaixas = n;
  faixasDoServidor = true;
  statusServidor = "OK";
  return true;
}

// POST gravaleitura.php -> grava a distância no banco (os triggers conferem e contam)
void enviarLeitura(int cm) {
  if (WiFi.status() != WL_CONNECTED) { statusServidor = "SEM REDE"; return; }

  HTTPClient http;
  if (!iniciarHttp(http, urlBase() + "/gravaleitura.php")) { statusServidor = "ERRO URL"; return; }
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");

  String corpo = "mac=" + meuMac + "&sensor=" + String(SENSOR_ID) + "&valor=" + String(cm);
  if (strlen(TOKEN) > 0) corpo += String("&token=") + TOKEN;

  int codigo = http.POST(corpo);
  if (codigo == HTTP_CODE_OK)      statusServidor = "OK";
  else if (codigo == 403)          statusServidor = "INATIVO";
  else if (codigo == 404)          statusServidor = "MAC NAO CAD.";
  else statusServidor = codigo > 0 ? String("ERRO ") + codigo : String("SEM RESP.");
  http.end();
}

// ============================================================
//  Botão (INPUT_PULLUP: apertado = LOW)
// ============================================================
void tratarBotao() {
  bool atual = digitalRead(PIN_BOTAO);
  unsigned long agora = millis();

  if (atual == LOW && botaoAnterior == HIGH) {        // começou a apertar
    botaoDesde = agora;
    longoTratado = false;
  }
  if (atual == LOW && !longoTratado && agora - botaoDesde >= PRESS_LONGO) {
    telaStatus = !telaStatus;                         // segurou: troca a tela
    longoTratado = true;
  }
  if (atual == HIGH && botaoAnterior == LOW) {        // soltou
    if (!longoTratado && agora - botaoDesde >= 40) {  // clique curto (ignora ruído < 40 ms)
      mudo = !mudo;
      if (mudo) noTone(PIN_BUZZER);
    }
  }
  botaoAnterior = atual;
}

// ============================================================
//  Atualiza LCD e LED
// ============================================================
void mostrarSaidas() {
  char linha[24];

  if (!placaAtiva) {
    ledCor(0, 0, 0);
    lcdLinha(0, "PLACA INATIVA");
    lcdLinha(1, "Ative no painel");
    return;
  }

  if (telaStatus) {                                   // tela 2: status da rede
    lcdLinha(0, WiFi.status() == WL_CONNECTED ? WiFi.localIP().toString().c_str() : "Sem Wi-Fi");
    snprintf(linha, sizeof(linha), "Srv:%s", statusServidor.c_str());
    lcdLinha(1, linha);
  } else {                                            // tela 1: mensagem da faixa + distância
    if (faixaAtual != nullptr)       lcdLinha(0, faixaAtual->msg);
    else if (distanciaAtual < 0)     lcdLinha(0, "SEM LEITURA");
    else                             lcdLinha(0, "SEM FAIXA");

    if (distanciaAtual < 0) snprintf(linha, sizeof(linha), "Dist: --- cm");
    else snprintf(linha, sizeof(linha), "Dist:%3d cm%s", (int)(distanciaAtual + 0.5f), mudo ? " MUDO" : "");
    lcdLinha(1, linha);
  }

  if (faixaAtual != nullptr) ledCor(faixaAtual->r, faixaAtual->g, faixaAtual->b);
  else ledCor(0, 0, 0);
}

// ============================================================
//  setup() e loop()
// ============================================================
void setup() {
  Serial.begin(115200);
  Serial.println();
  Serial.println("Sensor de Proximidade - iniciando");

  pinMode(PIN_TRIG, OUTPUT);
  pinMode(PIN_ECHO, INPUT);
  pinMode(PIN_BOTAO, INPUT_PULLUP);
  pinMode(PIN_LED_R, OUTPUT);
  pinMode(PIN_LED_G, OUTPUT);
  pinMode(PIN_LED_B, OUTPUT);
  pinMode(PIN_BUZZER, OUTPUT);
  analogWriteRange(1023);
  ledCor(0, 0, 0);

  Wire.begin(PIN_SDA, PIN_SCL);
  lcd = new LiquidCrystal_I2C(acharLcd(), 16, 2);
  lcd->init();
  lcd->backlight();
  lcdLinha(0, "Sensor Proximid.");
  lcdLinha(1, "Iniciando...");

  faixasPadrao();                                     // funciona mesmo sem internet

  clienteTls.setInsecure();                           // não valida o certificado (projeto escolar)
  clienteTls.setBufferSizes(1024, 512);               // economiza memória no TLS

  WiFi.mode(WIFI_STA);
  WiFi.persistent(false);
  WiFi.setAutoReconnect(true);
  WiFi.begin(WIFI_SSID, WIFI_SENHA);
  lcdLinha(1, "Conectando WiFi");
  unsigned long inicio = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - inicio < 20000) {
    delay(300);
    Serial.print('.');
  }
  Serial.println();

  meuMac = WiFi.macAddress();                         // é o MAC que você cadastra em "Placas"
  Serial.print("MAC desta placa: ");
  Serial.println(meuMac);
  if (WiFi.status() == WL_CONNECTED) {
    Serial.print("IP: ");
    Serial.println(WiFi.localIP());
    // mostra o MAC por 3 s (o LCD tem só 16 colunas, então ele vai em duas linhas)
    String parte1 = "MAC " + meuMac.substring(0, 11) + ":";   // MAC 5C:CF:7F:67:
    String parte2 = "    " + meuMac.substring(12);             //     A2:49
    lcdLinha(0, parte1.c_str());
    lcdLinha(1, parte2.c_str());
    delay(3000);
  }

  buscarFaixas();
  ultimaSync = millis();
  lcdLinha(0, "");
  lcdLinha(1, "");
}

void loop() {
  unsigned long agora = millis();

  tratarBotao();

  // 1) Busca as faixas no site de tempos em tempos
  unsigned long intervalo = placaAtiva ? INTERVALO_FAIXAS : INTERVALO_INATIVA;
  if (agora - ultimaSync >= intervalo) {
    buscarFaixas();
    ultimaSync = millis();
  }

  // 2) Mede a distância e decide a faixa (tudo local: o alerta é imediato)
  if (placaAtiva && agora - ultimaMedida >= INTERVALO_MEDIDA) {
    ultimaMedida = agora;
    distanciaAtual = medirCm();
    faixaAtual = distanciaAtual < 0 ? nullptr : faixaPara(distanciaAtual);

    // 3) Envia ao banco quando a distância mudou ou de tempos em tempos
    if (distanciaAtual >= 0) {
      int cm = (int)(distanciaAtual + 0.5f);
      unsigned long desde = millis() - ultimoEnvio;
      bool mudou = abs(cm - ultimoEnviado) >= VARIACAO_ENVIO;
      if ((mudou && desde >= ENVIO_MIN) || desde >= ENVIO_MAX) {
        enviarLeitura(cm);
        ultimoEnviado = cm;
        ultimoEnvio = millis();
      }
    }
  }

  // 4) Atualiza LCD, LED e buzzer
  mostrarSaidas();
  if (placaAtiva) tocarBuzzer(faixaAtual);
  else noTone(PIN_BUZZER);
}
