<?php
/* crudpainel.php - CRUD da tabela `painel` (faixa de distância + mensagem do display). */
require_once __DIR__ . '/crud_lib.php';

crud([
    'titulo'    => 'Painéis (painel)',
    'descricao' => 'Cada painel é uma faixa de distância [mín, máx) em cm com a mensagem do LCD, válida entre início e fim. A cor e o buzzer ficam em "Combinações". Para cadastrar tudo de uma vez, use "Programar display".',
    'tabela'    => 'painel',
    'pk'        => 'idpainel',
    'campos'    => [
        'nome'       => ['label' => 'Nome', 'tipo' => 'text', 'max' => 50, 'obrigatorio' => true],
        'datainicio' => ['label' => 'Início da validade', 'tipo' => 'datetime', 'dica' => 'Vazio = sempre válido'],
        'datafim'    => ['label' => 'Fim da validade', 'tipo' => 'datetime', 'dica' => 'Vazio = sempre válido'],
        'sensor_idsensor' => ['label' => 'Sensor', 'tipo' => 'select',
                              'fk' => ['sensor', 'idsensor', "CONCAT(idsensor, ' - ', IFNULL(nome, ''))"]],
        'distmin'    => ['label' => 'Distância mínima (cm)', 'tipo' => 'float'],
        'distmax'    => ['label' => 'Distância máxima (cm)', 'tipo' => 'float'],
        'mensagem'   => ['label' => 'Mensagem do display', 'tipo' => 'text', 'max' => 16, 'sem_acento' => true,
                         'dica' => 'Até 16 caracteres; acentos são removidos (o LCD não mostra)'],
    ],
]);
