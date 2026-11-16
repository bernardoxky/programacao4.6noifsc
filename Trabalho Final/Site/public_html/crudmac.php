<?php
/* crudmac.php - CRUD da tabela `mac` (placas ESP8266). A chave é o próprio endereço MAC. */
require_once __DIR__ . '/crud_lib.php';

crud([
    'titulo'    => 'Placas (mac)',
    'descricao' => 'Cada ESP8266 é identificado pelo MAC (Serial: WiFi.macAddress()). Placa inativa não grava leituras e não recebe faixas. O contador é atualizado pelo trigger.',
    'tabela'    => 'mac',
    'pk'        => 'idmac',
    'pk_manual' => true,
    'pk_label'  => 'MAC',
    'pk_max'    => 17,
    'pk_maiusculo' => true,
    'pk_regex'  => '/^([0-9A-F]{2}:){5}[0-9A-F]{2}$/',
    'pk_erro'   => 'MAC no formato AA:BB:CC:DD:EE:FF.',
    'campos'    => [
        'nome'  => ['label' => 'Nome', 'tipo' => 'text', 'max' => 35],
        'ativo' => ['label' => 'Ativa', 'tipo' => 'bool', 'padrao' => 1],
    ],
]);
