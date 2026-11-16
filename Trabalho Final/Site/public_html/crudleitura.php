<?php
/* crudleitura.php - CRUD da tabela `leitura`. Inserir aqui também passa pelos triggers. */
require_once __DIR__ . '/crud_lib.php';

crud([
    'titulo'    => 'Leituras (leitura)',
    'descricao' => 'Medições do sensor (valor = distância em cm). Os triggers recusam leituras de placa/sensor inativo e atualizam os contadores. Mostra as 300 mais recentes.',
    'tabela'    => 'leitura',
    'pk'        => 'idleitura',
    'campos'    => [
        'mac_idmac'       => ['label' => 'Placa (MAC)', 'tipo' => 'select', 'obrigatorio' => true,
                              'fk' => ['mac', 'idmac', "CONCAT(idmac, ' - ', IFNULL(nome, ''))"]],
        'sensor_idsensor' => ['label' => 'Sensor', 'tipo' => 'select', 'obrigatorio' => true,
                              'fk' => ['sensor', 'idsensor', "CONCAT(idsensor, ' - ', IFNULL(nome, ''))"]],
        'datahora'        => ['label' => 'Data e hora', 'tipo' => 'datetime', 'agora' => true, 'dica' => 'Vazio = agora'],
        'valor'           => ['label' => 'Distância (cm)', 'tipo' => 'number', 'min' => 0],
    ],
]);
