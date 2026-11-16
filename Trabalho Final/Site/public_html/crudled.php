<?php
/* crudled.php - CRUD da tabela `led` (pinos do LED RGB). */
require_once __DIR__ . '/crud_lib.php';

crud([
    'titulo'    => 'LEDs RGB (led)',
    'descricao' => 'Pinos (GPIO) de cada cor do LED RGB. Ex.: D7 = 13, D8 = 15, D0 = 16.',
    'tabela'    => 'led',
    'pk'        => 'idled',
    'campos'    => [
        'nome'  => ['label' => 'Nome', 'tipo' => 'text', 'max' => 35, 'obrigatorio' => true],
        'pinor' => ['label' => 'Pino vermelho (R)', 'tipo' => 'number', 'min' => 0, 'max' => 40],
        'pinog' => ['label' => 'Pino verde (G)', 'tipo' => 'number', 'min' => 0, 'max' => 40],
        'pinob' => ['label' => 'Pino azul (B)', 'tipo' => 'number', 'min' => 0, 'max' => 40],
    ],
]);
