#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClient.h>
#include <ArduinoJson.h>

// ===== CONFIGURACAO WIFI =====
const char* ssid = "IFSC";
const char* senha = "campuschapeco";

// ===== ENDERECO DO PHP =====
// Substitua pelo IP ou dominio do seu servidor.
const char* url;

// Intervalo entre consultas
const unsigned long intervalo = 10000;
unsigned long ultimaConsulta = 0;

// Dados recebidos
int idCombinacao = 0;
int ledR = 0;int ledG = 0;int ledB = 0;
int pinoR = -1;int pinoG = -1;int pinoB = -1;
String dataInicio;String dataFim;String nomeMac; String dataHora;

// Pinos GPIO comuns e utilizaveis como saida
bool pinoValido(int pino) {
  return pino == 4  || pino == 5  ||
         pino == 12 || pino == 13 ||
         pino == 14 || pino == 16;
}
// Lista de servidores de origem
int indiceServidor=0;
char* servidores[] = {
  "http://projeto4.migueldebarba.com.br/grupoe/bernardo/getjson.php",
  "http://projeto4.migueldebarba.com.br/grupoe/pedro/getjson.php"
};

void conectarWiFi() {
  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, senha);

  Serial.print("Conectando ao Wi-Fi");

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println();  Serial.println("Wi-Fi conectado!");  Serial.print("IP: ");  Serial.println(WiFi.localIP());
  Serial.print("MAC do ESP8266: ");  Serial.println(WiFi.macAddress());
}

void consultarPHP() {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("Wi-Fi desconectado.");
    return;
  }

  WiFiClient client;
  HTTPClient http;
  // Obtem o MAC real do ESP8266
  String maclocal = WiFi.macAddress();
  maclocal.toUpperCase();
  
  Serial.println("\nConsultando PHP...");
 // Envia o MAC como parametro GET
  String endereco = String(url) + "?idmac=" + maclocal + "&dataesp=" + dataHora;
  endereco.replace(" ", "%20");

  Serial.println("Consultando: " + endereco);
    if (!http.begin(client, endereco)) {
      Serial.println("Falha ao iniciar HTTP.");
    return;
  }

  http.setTimeout(10000);
  int codigoHTTP = http.GET();
  if (codigoHTTP != HTTP_CODE_OK) {
    Serial.printf("Erro HTTP: %d\n", codigoHTTP);
    http.end();
    return;
  }

  // Buffer para desserializar o JSON
  DynamicJsonDocument doc(4096);

  DeserializationError erro =
      deserializeJson(doc, http.getStream());

  http.end();

  if (erro) {
    Serial.print("Erro ao ler JSON: ");
    Serial.println(erro.c_str());
    return;
  }

  if (!doc.is<JsonArray>()) {
    Serial.println("O JSON recebido nao e um array.");
    return;
  }

  // MAC real do ESP8266
  String macLocal = WiFi.macAddress();
  macLocal.toUpperCase();
  bool encontrado = false;
  JsonArray registros = doc.as<JsonArray>();

  for (JsonObject item : registros) {
    const char* macJSON =item["mac_idmac"] | "";
    String macRegistro = String(macJSON);
    macRegistro.trim();
    macRegistro.toUpperCase();

    Serial.print("MAC do registro: ");
    Serial.println(macRegistro);

    // Seleciona apenas o registro deste ESP8266
    if (macRegistro != macLocal) {continue;}

    // Le os dados do registro correspondente
    idCombinacao = item["combina_idcombina"] | 0;
    ledR = item["ledr"] | 0;    ledG = item["ledg"] | 0;    ledB = item["ledb"] | 0;
    pinoR = item["pinor"] | -1;    pinoG = item["pinog"] | -1;    pinoB = item["pinob"] | -1;
    dataInicio = item["datainicio"] | "";    dataFim = item["datafim"] | "";    nomeMac = item["nome"] | "";
    encontrado = true;
    break;
  }

  if (!encontrado) {
    Serial.println("Nenhum registro encontrado para este MAC.");
    return;
  }

  // Mostra os dados selecionados
  Serial.println("\n=== REGISTRO ENCONTRADO ===");

  Serial.printf("ID: %d\n", idCombinacao);
  Serial.printf("Nome: %s\n", nomeMac.c_str());
  Serial.printf("MAC: %s\n", macLocal.c_str());

  Serial.printf("RGB: %d, %d, %d\n",ledR, ledG, ledB);
  Serial.printf("Pinos: %d, %d, %d\n",pinoR, pinoG, pinoB);

  Serial.printf("Inicio: %s\n", dataInicio.c_str());
  Serial.printf("Fim: %s\n", dataFim.c_str());

  // Impede uso de GPIOs inadequados
  if (!pinoValido(pinoR) || !pinoValido(pinoG) || !pinoValido(pinoB)) {
    Serial.println("ERRO: pinos invalidos para saida RGB.");
    Serial.println("Configure GPIOs validos no banco de dados.");
    return;
  }

  // Configura os pinos de saida
  pinMode(pinoR, OUTPUT);  pinMode(pinoG, OUTPUT);  pinMode(pinoB, OUTPUT);

  // PWM do ESP8266: escala de 0 a 255
  analogWriteRange(255);
  analogWrite(pinoR, ledR);  analogWrite(pinoG, ledG);  analogWrite(pinoB, ledB);
  Serial.println("LED RGB atualizado!");
}

void setup() {
  Serial.begin(115200);
  delay(500);
  conectarWiFi();
  ultimaConsulta = millis();
}

void loop() {
    if (millis() - ultimaConsulta >= intervalo){
        ultimaConsulta = millis();
        url = servidores[indiceServidor];        
        consultarPHP();
        indiceServidor++;
        if (indiceServidor >= 2) {indiceServidor = 0;}
    }
}