<?php
/*
 * gravaleitura.php - o ESP8266 ENVIA a leitura do sensor para cá.
 *
 * Parâmetros (POST; GET também funciona para testar no navegador):
 *   mac    endereço MAC da placa, ex.: 5C:CF:7F:67:A2:49
 *   sensor id do sensor na tabela `sensor` (padrão 1)
 *   valor  distância em cm
 *
 * Exemplo de teste:  gravaleitura.php?mac=5C:CF:7F:67:A2:49&sensor=1&valor=25
 *
 * Os triggers do banco recusam a leitura se o MAC ou o sensor estiver inativo
 * (resposta 403) e atualizam os contadores quando a leitura é gravada.
 */
require_once __DIR__ . '/conexao.php';

checa_token();

$mac    = strtoupper(trim((string)($_REQUEST['mac'] ?? '')));
$sensor = (int)($_REQUEST['sensor'] ?? 1);
$valor  = $_REQUEST['valor'] ?? '';

if ($mac === '' || $sensor <= 0 || !is_numeric($valor) || $valor < 0) {
    json_saida(['ok' => false, 'erro' => 'parametros invalidos (mac, sensor, valor)'], 400);
}
$valor = (int)round((float)$valor);

try {
    $st = q('INSERT INTO leitura (mac_idmac, sensor_idsensor, valor) VALUES (?, ?, ?)', 'sii', [$mac, $sensor, $valor]);
    json_saida(['ok' => true, 'id' => $st->insert_id]);
} catch (mysqli_sql_exception $e) {
    switch ($e->getCode()) {
        case 1644: // SIGNAL dos triggers: MAC ou sensor inativo
            json_saida(['ok' => false, 'erro' => $e->getMessage()], 403);
        case 1452: // chave estrangeira: MAC ou sensor não cadastrado
            json_saida(['ok' => false, 'erro' => 'MAC ou sensor nao cadastrado'], 404);
        default:
            json_saida(['ok' => false, 'erro' => 'erro no servidor'], 500);
    }
}
