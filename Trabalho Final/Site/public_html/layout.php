<?php
/* layout.php - cabeçalho e rodapé comuns das páginas do painel. */

function topo(string $titulo, string $head = ''): void
{
    $atual = basename($_SERVER['PHP_SELF']);
    $menu = [
        'painel.php'       => 'Painel',
        'faixas.php'       => 'Programar display',
        'crudmac.php'      => 'Placas',
        'crudsensor.php'   => 'Sensores',
        'crudled.php'      => 'LEDs',
        'crudpainel.php'   => 'Painéis',
        'crudcombina.php'  => 'Combinações',
        'crudleitura.php'  => 'Leituras',
    ];
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="utf-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<title>' . h($titulo) . ' - Sensor de Proximidade</title>'
       . '<link rel="stylesheet" href="painel.css">' . $head . '</head><body>';
    echo '<header class="topo"><div class="topo-int"><a class="marca" href="painel.php">Sensor de Proximidade</a><nav>';
    foreach ($menu as $arq => $rotulo) {
        echo '<a href="' . h($arq) . '"' . ($arq === $atual ? ' class="ativo"' : '') . '>' . h($rotulo) . '</a>';
    }
    echo '<a href="/">Site</a><a href="logout.php">Sair</a></nav></div></header><main class="conteudo">';
}

function rodape(): void
{
    echo '</main></body></html>';
}
