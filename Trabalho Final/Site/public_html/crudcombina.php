<?php
/* crudcombina.php - CRUD da tabela `combina` (liga painel + LED + placa, com cor e buzzer). */
require_once __DIR__ . '/crud_lib.php';

crud([
    'titulo'    => 'Combinações (combina)',
    'descricao' => 'Liga um painel (faixa) a uma placa e a um LED, definindo a cor (0 a 255 em cada canal) e o buzzer (tom em Hz, 0 = sem som; tempo em ms).',
    'tabela'    => 'combina',
    'pk'        => 'idcombina',
    'campos'    => [
        'painel_idpainel' => ['label' => 'Painel', 'tipo' => 'select', 'obrigatorio' => true,
                              'fk' => ['painel', 'idpainel', "CONCAT(idpainel, ' - ', IFNULL(nome, ''))"]],
        'led_idled'       => ['label' => 'LED', 'tipo' => 'select', 'obrigatorio' => true,
                              'fk' => ['led', 'idled', "CONCAT(idled, ' - ', IFNULL(nome, ''))"]],
        'mac_idmac'       => ['label' => 'Placa (MAC)', 'tipo' => 'select', 'obrigatorio' => true,
                              'fk' => ['mac', 'idmac', "CONCAT(idmac, ' - ', IFNULL(nome, ''))"]],
        'ledr'            => ['label' => 'Vermelho (0-255)', 'tipo' => 'number', 'min' => 0, 'max' => 255],
        'ledg'            => ['label' => 'Verde (0-255)', 'tipo' => 'number', 'min' => 0, 'max' => 255],
        'ledb'            => ['label' => 'Azul (0-255)', 'tipo' => 'number', 'min' => 0, 'max' => 255],
        'buzzertom'       => ['label' => 'Buzzer - tom (Hz)', 'tipo' => 'number', 'min' => 0, 'obrigatorio' => true, 'padrao' => 0],
        'buzzertempo'     => ['label' => 'Buzzer - tempo (ms)', 'tipo' => 'number', 'min' => 0, 'obrigatorio' => true, 'padrao' => 0],
    ],
]);
