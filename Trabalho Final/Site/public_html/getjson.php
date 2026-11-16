<?php
/*
 * getjson.php - o ESP8266 BUSCA aqui o que deve exibir/acionar.
 *
 * Parâmetros (GET):
 *   mac     endereço MAC da placa (obrigatório)
 *   sensor  (opcional) só as faixas desse sensor
 *
 * Exemplo:  getjson.php?mac=5C:CF:7F:67:A2:49
 *
 * Resposta:
 *   {"ok":true,"mac":"..","ativo":1,"hora":"2026-11-13 15:00:00",
 *    "faixas":[{"sensor":1,"min":0,"max":10,"msg":"PERTO! PARE",
 *               "r":255,"g":0,"b":0,"tom":2000,"tempo":100,
 *               "pinr":13,"ping":15,"pinb":16}, ...]}
 *
 * Uma faixa vale para distâncias de min (inclusive) até max (exclusive), em cm.
 * Só vêm faixas dentro do período (datainicio/datafim) e de sensores ativos.
 * Se a placa estiver inativa, vem "ativo":0 e a lista de faixas vazia.
 */
require_once __DIR__ . '/conexao.php';

checa_token();

$mac    = strtoupper(trim((string)($_GET['mac'] ?? '')));
$sensor = (int)($_GET['sensor'] ?? 0);

if ($mac === '') {
    json_saida(['ok' => false, 'erro' => 'informe o mac'], 400);
}

try {
    $placa = linhas('SELECT idmac, ativo FROM mac WHERE idmac = ?', 's', [$mac]);
    if (!$placa) {
        json_saida(['ok' => false, 'erro' => 'MAC nao cadastrado'], 404);
    }

    $saida = [
        'ok'     => true,
        'mac'    => $placa[0]['idmac'],
        'ativo'  => (int)$placa[0]['ativo'],
        'hora'   => date('Y-m-d H:i:s'),
        'faixas' => [],
    ];

    if ($saida['ativo'] === 1) {
        $sql = "SELECT p.sensor_idsensor AS sensor, p.distmin, p.distmax, p.mensagem,
                       c.ledr, c.ledg, c.ledb, c.buzzertom, c.buzzertempo,
                       l.pinor, l.pinog, l.pinob
                  FROM combina c
                  JOIN painel p ON p.idpainel = c.painel_idpainel
                  JOIN led    l ON l.idled    = c.led_idled
                  JOIN sensor s ON s.idsensor = p.sensor_idsensor
                 WHERE c.mac_idmac = ?
                   AND s.ativo = 1
                   AND p.distmin IS NOT NULL AND p.distmax IS NOT NULL
                   AND NOW() BETWEEN COALESCE(p.datainicio, '1000-01-01') AND COALESCE(p.datafim, '9999-12-31')";
        $tipos  = 's';
        $params = [$mac];
        if ($sensor > 0) {
            $sql    .= ' AND p.sensor_idsensor = ?';
            $tipos  .= 'i';
            $params[] = $sensor;
        }
        $sql .= ' ORDER BY p.distmin';

        foreach (linhas($sql, $tipos, $params) as $r) {
            $saida['faixas'][] = [
                'sensor' => (int)$r['sensor'],
                'min'    => (float)$r['distmin'],
                'max'    => (float)$r['distmax'],
                'msg'    => (string)$r['mensagem'],
                'r'      => (int)$r['ledr'],
                'g'      => (int)$r['ledg'],
                'b'      => (int)$r['ledb'],
                'tom'    => (int)$r['buzzertom'],
                'tempo'  => (int)$r['buzzertempo'],
                'pinr'   => (int)$r['pinor'],
                'ping'   => (int)$r['pinog'],
                'pinb'   => (int)$r['pinob'],
            ];
        }
    }

    json_saida($saida);
} catch (mysqli_sql_exception $e) {
    json_saida(['ok' => false, 'erro' => 'erro no servidor'], 500);
}
