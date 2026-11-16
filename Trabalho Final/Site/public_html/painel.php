<?php
/* painel.php - painel inicial: resumo, últimas leituras e faixas em vigor. Atualiza sozinho a cada 10 s. */
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
exigir_login();

$erro = '';
try {
    $placas   = linhas('SELECT COUNT(*) AS total, COALESCE(SUM(ativo = 1), 0) AS ativas FROM mac')[0];
    $sensores = linhas('SELECT COUNT(*) AS total, COALESCE(SUM(ativo = 1), 0) AS ativos FROM sensor')[0];
    $total    = (int)linhas('SELECT COUNT(*) AS n FROM leitura')[0]['n'];

    $ultimas = linhas("SELECT l.idleitura, l.datahora, l.valor, l.mac_idmac, m.nome AS placa, s.nome AS sensor
                         FROM leitura l
                         JOIN mac m ON m.idmac = l.mac_idmac
                         JOIN sensor s ON s.idsensor = l.sensor_idsensor
                        ORDER BY l.idleitura DESC LIMIT 15");

    $serie = array_reverse(array_map('intval', array_column(
        linhas('SELECT valor FROM leitura WHERE valor IS NOT NULL ORDER BY idleitura DESC LIMIT 40'), 'valor')));

    $vigentes = linhas("SELECT p.distmin, p.distmax, p.mensagem, c.ledr, c.ledg, c.ledb, c.buzzertom, c.mac_idmac
                          FROM combina c JOIN painel p ON p.idpainel = c.painel_idpainel
                          JOIN sensor s ON s.idsensor = p.sensor_idsensor
                         WHERE s.ativo = 1 AND p.distmin IS NOT NULL AND p.distmax IS NOT NULL
                           AND NOW() BETWEEN COALESCE(p.datainicio, '1000-01-01') AND COALESCE(p.datafim, '9999-12-31')
                         ORDER BY c.mac_idmac, p.distmin");
    $primeiroMac = linhas('SELECT idmac FROM mac ORDER BY idmac LIMIT 1');
} catch (Throwable $e) {
    $erro = 'Não foi possível ler o banco: ' . $e->getMessage();
    $placas = ['total' => 0, 'ativas' => 0];
    $sensores = ['total' => 0, 'ativos' => 0];
    $total = 0;
    $ultimas = $serie = $vigentes = $primeiroMac = [];
}

// gráfico simples (SVG) com as últimas leituras
function sparkline(array $v): string
{
    $w = 640; $h = 120; $n = count($v);
    if ($n < 2) {
        return '<p class="mut">Ainda não há leituras suficientes para o gráfico.</p>';
    }
    $max = max(max($v), 1);
    $pts = [];
    foreach ($v as $i => $y) {
        $pts[] = round($i * ($w / ($n - 1)), 1) . ',' . round($h - ($y / $max) * ($h - 8) - 4, 1);
    }
    return '<svg viewBox="0 0 ' . $w . ' ' . $h . '" class="grafico" role="img" aria-label="Últimas leituras">'
         . '<polyline fill="none" stroke="currentColor" stroke-width="2" points="' . implode(' ', $pts) . '"/></svg>'
         . '<p class="mut">Últimas ' . $n . ' leituras (máximo: ' . $max . ' cm)</p>';
}

topo('Painel', '<meta http-equiv="refresh" content="10">');
?>
<h1>Painel</h1>
<?php if ($erro): ?><p class="erro"><?= h($erro) ?></p><?php endif; ?>

<div class="cards">
  <div class="card num"><span class="mut">Placas ativas</span><strong><?= (int)$placas['ativas'] ?> <small>/ <?= (int)$placas['total'] ?></small></strong></div>
  <div class="card num"><span class="mut">Sensores ativos</span><strong><?= (int)$sensores['ativos'] ?> <small>/ <?= (int)$sensores['total'] ?></small></strong></div>
  <div class="card num"><span class="mut">Leituras gravadas</span><strong><?= $total ?></strong></div>
  <div class="card num"><span class="mut">Última distância</span><strong><?= $ultimas ? h($ultimas[0]['valor']) . ' <small>cm</small>' : '—' ?></strong></div>
</div>

<section class="card">
  <h2>Distância ao longo do tempo</h2>
  <?= sparkline($serie) ?>
</section>

<section class="card">
  <h2>Últimas leituras</h2>
  <div class="rolagem">
    <table>
      <thead><tr><th>#</th><th>Data e hora</th><th>Placa</th><th>Sensor</th><th>Distância (cm)</th></tr></thead>
      <tbody>
      <?php foreach ($ultimas as $l): ?>
        <tr><td><?= (int)$l['idleitura'] ?></td><td><?= h($l['datahora']) ?></td>
            <td><?= h($l['mac_idmac']) ?> <small class="mut"><?= h($l['placa']) ?></small></td>
            <td><?= h($l['sensor']) ?></td><td><?= h($l['valor']) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$ultimas): ?><tr><td colspan="5" class="mut">Nenhuma leitura ainda.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="card">
  <h2>Faixas em vigor agora</h2>
  <div class="rolagem">
    <table>
      <thead><tr><th>Placa</th><th>Faixa (cm)</th><th>Mensagem</th><th>Cor</th><th>Buzzer</th></tr></thead>
      <tbody>
      <?php foreach ($vigentes as $v): ?>
        <tr><td><?= h($v['mac_idmac']) ?></td><td><?= h($v['distmin']) ?> a <?= h($v['distmax']) ?></td>
            <td><code><?= h($v['mensagem']) ?></code></td>
            <td><span class="amostra" style="background:rgb(<?= (int)$v['ledr'] ?>,<?= (int)$v['ledg'] ?>,<?= (int)$v['ledb'] ?>)"></span></td>
            <td><?= (int)$v['buzzertom'] > 0 ? h($v['buzzertom']) . ' Hz' : 'sem som' ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$vigentes): ?><tr><td colspan="5" class="mut">Nenhuma faixa vigente. Cadastre em "Programar display".</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <p><a class="botao-sec" href="faixas.php">Programar display</a>
     <?php if ($primeiroMac): ?><a class="botao-sec" href="getjson.php?mac=<?= urlencode($primeiroMac[0]['idmac']) ?>" target="_blank">Ver o JSON que o ESP recebe</a><?php endif; ?></p>
</section>
<?php rodape(); ?>
