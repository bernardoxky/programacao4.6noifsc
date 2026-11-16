<?php
/* crudsensor.php - CRUD da tabela `sensor` (sensor ultrassônico). */
require_once __DIR__ . '/crud_lib.php';

crud([
    'titulo'    => 'Sensores (sensor)',
    'descricao' => 'Sensor inativo não grava leituras e suas faixas não são enviadas ao ESP. O contador é atualizado pelo trigger.',
    'tabela'    => 'sensor',
    'pk'        => 'idsensor',
    'campos'    => [
        'nome'  => ['label' => 'Nome', 'tipo' => 'text', 'max' => 35, 'obrigatorio' => true],
        'ativo' => ['label' => 'Ativo', 'tipo' => 'bool', 'padrao' => 1],
    ],
]);
