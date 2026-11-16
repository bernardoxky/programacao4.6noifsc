<?php
/*
 * faixas.php - "Programar display": cadastra de uma vez só uma faixa de alerta
 * (grava em `painel` + `combina`): distância, mensagem do LCD, cor do LED e buzzer.
 * É a tela que o professor chama de "painel para programar as mensagens do display".
 */
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
exigir_login();

function hex_para_rgb(string $hex): array
{
    $hex = ltrim($hex, '#');
    if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
        $hex = '00ff00';
    }
    return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
}

function rgb_para_hex($r, $g, $b): string
{
    return sprintf('#%02x%02x%02x', max(0, min(255, (int)$r)), max(0, min(255, (int)$g)), max(0, min(255, (int)$b)));
}

$c      = db();
$erro   = '';
$flash  = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$acao     = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['acao'] ?? '') : '';
$editando = $acao === 'salvar' ? (int)($_POST['editando'] ?? 0) : 0; // idcombina em edição
$f = [
    'nome' => '', 'sensor' => 0, 'distmin' => '', 'distmax' => '', 'mensagem' => '',
    'inicio' => '', 'fim' => '', 'mac' => '', 'led' => 0, 'cor' => '#00ff00', 'tom' => 0, 'tempo' => 0,
];

// ---------- excluir ----------
if ($acao === 'excluir') {
    try {
        $id = (int)($_POST['id'] ?? 0);
        $c->begin_transaction();
        $r = linhas('SELECT painel_idpainel FROM combina WHERE idcombina = ?', 'i', [$id]);
        if ($r) {
            $pid = (int)$r[0]['painel_idpainel'];
            q('DELETE FROM combina WHERE idcombina = ?', 'i', [$id]);
            $n = (int)linhas('SELECT COUNT(*) AS n FROM combina WHERE painel_idpainel = ?', 'i', [$pid])[0]['n'];
            if ($n === 0) {
                q('DELETE FROM painel WHERE idpainel = ?', 'i', [$pid]); // só apaga o painel se ninguém mais usa
            }
        }
        $c->commit();
        $_SESSION['flash'] = 'Faixa excluída.';
        header('Location: faixas.php');
        exit;
    } catch (Throwable $e) {
        $c->rollback();
        $erro = 'Não foi possível excluir: ' . $e->getMessage();
    }
}

// ---------- salvar ----------
if ($acao === 'salvar') {
    $f = [
        'nome'     => trim((string)($_POST['nome'] ?? '')),
        'sensor'   => (int)($_POST['sensor'] ?? 0),
        'distmin'  => trim((string)($_POST['distmin'] ?? '')),
        'distmax'  => trim((string)($_POST['distmax'] ?? '')),
        'mensagem' => sem_acentos(trim((string)($_POST['mensagem'] ?? ''))),
        'inicio'   => str_replace('T', ' ', trim((string)($_POST['inicio'] ?? ''))),
        'fim'      => str_replace('T', ' ', trim((string)($_POST['fim'] ?? ''))),
        'mac'      => strtoupper(trim((string)($_POST['mac'] ?? ''))),
        'led'      => (int)($_POST['led'] ?? 0),
        'cor'      => (string)($_POST['cor'] ?? '#00ff00'),
        'tom'      => max(0, (int)($_POST['tom'] ?? 0)),
        'tempo'    => max(0, (int)($_POST['tempo'] ?? 0)),
    ];
    try {
        if ($f['sensor'] <= 0 || $f['mac'] === '' || $f['led'] <= 0) {
            throw new Exception('Escolha o sensor, a placa e o LED.');
        }
        if (!is_numeric($f['distmin']) || !is_numeric($f['distmax'])) {
            throw new Exception('Informe as distâncias mínima e máxima em cm.');
        }
        if ((float)$f['distmin'] >= (float)$f['distmax']) {
            throw new Exception('A distância mínima precisa ser menor que a máxima.');
        }
        if ($f['mensagem'] === '' || mb_strlen($f['mensagem']) > 16) {
            throw new Exception('A mensagem do display precisa ter de 1 a 16 caracteres.');
        }
        if ($f['nome'] === '') {
            $f['nome'] = 'Faixa ' . $f['distmin'] . '-' . $f['distmax'] . ' cm';
        }
        $inicio = $f['inicio'] !== '' ? $f['inicio'] : null;
        $fim    = $f['fim'] !== '' ? $f['fim'] : null;
        $dmin   = (float)$f['distmin'];
        $dmax   = (float)$f['distmax'];
        [$r, $g, $b] = hex_para_rgb($f['cor']);

        $c->begin_transaction();
        if ($editando > 0) {
            $pid = linhas('SELECT painel_idpainel FROM combina WHERE idcombina = ?', 'i', [$editando]);
            if (!$pid) {
                throw new Exception('Faixa não encontrada.');
            }
            q('UPDATE painel SET nome = ?, sensor_idsensor = ?, distmin = ?, distmax = ?, mensagem = ?, datainicio = ?, datafim = ?
                WHERE idpainel = ?',
              'siddsssi', [$f['nome'], $f['sensor'], $dmin, $dmax, $f['mensagem'], $inicio, $fim, (int)$pid[0]['painel_idpainel']]);
            q('UPDATE combina SET mac_idmac = ?, led_idled = ?, ledr = ?, ledg = ?, ledb = ?, buzzertom = ?, buzzertempo = ?
                WHERE idcombina = ?',
              'siiiiiii', [$f['mac'], $f['led'], $r, $g, $b, $f['tom'], $f['tempo'], $editando]);
        } else {
            $st = q('INSERT INTO painel (nome, sensor_idsensor, distmin, distmax, mensagem, datainicio, datafim)
                     VALUES (?, ?, ?, ?, ?, ?, ?)',
                    'siddsss', [$f['nome'], $f['sensor'], $dmin, $dmax, $f['mensagem'], $inicio, $fim]);
            $pid = $st->insert_id;
            q('INSERT INTO combina (painel_idpainel, led_idled, mac_idmac, ledr, ledg, ledb, buzzertom, buzzertempo)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
              'iisiiiii', [$pid, $f['led'], $f['mac'], $r, $g, $b, $f['tom'], $f['tempo']]);
        }
        $c->commit();
        $_SESSION['flash'] = 'Faixa salva.';
        header('Location: faixas.php');
        exit;
    } catch (Throwable $e) {
        try { $c->rollback(); } catch (Throwable $ignorado) {}
        $erro = $e instanceof mysqli_sql_exception ? 'Erro no banco: ' . $e->getMessage() : $e->getMessage();
    }
}

// ---------- carregar para edição ----------
if ($editando === 0 && !$erro && isset($_GET['editar'])) {
    $r = linhas('SELECT c.idcombina, c.mac_idmac, c.led_idled, c.ledr, c.ledg, c.ledb, c.buzzertom, c.buzzertempo,
                        p.nome, p.sensor_idsensor, p.distmin, p.distmax, p.mensagem, p.datainicio, p.datafim
                   FROM combina c JOIN painel p ON p.idpainel = c.painel_idpainel
                  WHERE c.idcombina = ?', 'i', [(int)$_GET['editar']]);
    if ($r) {
        $x = $r[0];
        $editando = (int)$x['idcombina'];
        $f = [
            'nome' => (string)$x['nome'], 'sensor' => (int)$x['sensor_idsensor'],
            'distmin' => (string)$x['distmin'], 'distmax' => (string)$x['distmax'], 'mensagem' => (string)$x['mensagem'],
            'inicio' => (string)$x['datainicio'], 'fim' => (string)$x['datafim'],
            'mac' => (string)$x['mac_idmac'], 'led' => (int)$x['led_idled'],
            'cor' => rgb_para_hex($x['ledr'], $x['ledg'], $x['ledb']),
            'tom' => (int)$x['buzzertom'], 'tempo' => (int)$x['buzzertempo'],
        ];
    }
}

$sensores = linhas('SELECT idsensor, nome FROM sensor ORDER BY idsensor');
$placas   = linhas('SELECT idmac, nome FROM mac ORDER BY idmac');
$leds     = linhas('SELECT idled, nome FROM led ORDER BY idled');

$lista = linhas("SELECT c.idcombina, c.mac_idmac, c.ledr, c.ledg, c.ledb, c.buzzertom, c.buzzertempo,
                        p.nome, p.distmin, p.distmax, p.mensagem, p.datainicio, p.datafim,
                        s.nome AS sensornome, m.nome AS macnome,
                        (NOW() BETWEEN COALESCE(p.datainicio, '1000-01-01') AND COALESCE(p.datafim, '9999-12-31')) AS vigente
                   FROM combina c
                   JOIN painel p ON p.idpainel = c.painel_idpainel
                   JOIN mac m ON m.idmac = c.mac_idmac
                   LEFT JOIN sensor s ON s.idsensor = p.sensor_idsensor
                  WHERE p.distmin IS NOT NULL AND p.distmax IS NOT NULL
                  ORDER BY c.mac_idmac, p.distmin");

$pagina_titulo = 'Programar display';
topo($pagina_titulo);
?>
<h1>Programar display</h1>
<p class="mut">Cada faixa diz o que o ESP faz quando a distância medida cai entre o mínimo (inclusive) e o máximo (exclusive): mensagem no LCD, cor do LED RGB e buzzer. As alterações chegam ao ESP na próxima consulta ao <code>getjson.php</code>.</p>

<?php if ($flash): ?><p class="ok"><?= h($flash) ?></p><?php endif; ?>
<?php if ($erro): ?><p class="erro"><?= h($erro) ?></p><?php endif; ?>

<section class="card">
  <h2><?= $editando ? 'Editar faixa' : 'Nova faixa' ?></h2>
  <form method="post" action="faixas.php" class="grade">
    <input type="hidden" name="acao" value="salvar">
    <input type="hidden" name="editando" value="<?= (int)$editando ?>">

    <label>Placa (MAC) *
      <select name="mac" required>
        <option value="">— escolha —</option>
        <?php foreach ($placas as $p): ?>
          <option value="<?= h($p['idmac']) ?>"<?= $p['idmac'] === $f['mac'] ? ' selected' : '' ?>><?= h($p['idmac'] . ' - ' . $p['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Sensor *
      <select name="sensor" required>
        <option value="">— escolha —</option>
        <?php foreach ($sensores as $s): ?>
          <option value="<?= (int)$s['idsensor'] ?>"<?= (int)$s['idsensor'] === $f['sensor'] ? ' selected' : '' ?>><?= h($s['idsensor'] . ' - ' . $s['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>LED RGB *
      <select name="led" required>
        <option value="">— escolha —</option>
        <?php foreach ($leds as $l): ?>
          <option value="<?= (int)$l['idled'] ?>"<?= (int)$l['idled'] === $f['led'] ? ' selected' : '' ?>><?= h($l['idled'] . ' - ' . $l['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>Distância mínima (cm) *
      <input type="number" step="any" min="0" name="distmin" value="<?= h($f['distmin']) ?>" required>
    </label>
    <label>Distância máxima (cm) *
      <input type="number" step="any" min="0" name="distmax" value="<?= h($f['distmax']) ?>" required>
    </label>

    <label>Mensagem do display * <small>(até 16 caracteres, sem acentos)</small>
      <input type="text" name="mensagem" id="mensagem" maxlength="16" value="<?= h($f['mensagem']) ?>" required>
    </label>
    <div class="lcd-bloco">
      <span class="mut">Prévia do LCD 16x2</span>
      <div class="lcd" id="lcd"><span id="lcd1"></span><span id="lcd2">Dist: -- cm</span></div>
    </div>

    <label>Cor do LED
      <input type="color" name="cor" id="cor" value="<?= h($f['cor']) ?>">
    </label>
    <label>Buzzer - tom (Hz) <small>(0 = sem som)</small>
      <input type="number" min="0" step="1" name="tom" value="<?= (int)$f['tom'] ?>">
    </label>
    <label>Buzzer - tempo (ms)
      <input type="number" min="0" step="1" name="tempo" value="<?= (int)$f['tempo'] ?>">
    </label>

    <label>Nome da faixa <small>(opcional)</small>
      <input type="text" name="nome" maxlength="50" value="<?= h($f['nome']) ?>">
    </label>
    <label>Válida a partir de <small>(vazio = sempre)</small>
      <input type="datetime-local" name="inicio" value="<?= h($f['inicio'] ? str_replace(' ', 'T', substr($f['inicio'], 0, 16)) : '') ?>">
    </label>
    <label>Válida até <small>(vazio = sempre)</small>
      <input type="datetime-local" name="fim" value="<?= h($f['fim'] ? str_replace(' ', 'T', substr($f['fim'], 0, 16)) : '') ?>">
    </label>

    <div class="acoes">
      <button type="submit"><?= $editando ? 'Salvar alterações' : 'Adicionar faixa' ?></button>
      <?php if ($editando): ?><a class="botao-sec" href="faixas.php">Cancelar</a><?php endif; ?>
    </div>
  </form>
</section>

<section class="card">
  <h2>Faixas programadas</h2>
  <div class="rolagem">
    <table>
      <thead><tr><th>Placa</th><th>Sensor</th><th>Faixa (cm)</th><th>Mensagem</th><th>Cor</th><th>Buzzer</th><th>Validade</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($lista as $x): ?>
        <tr>
          <td><?= h($x['mac_idmac']) ?><br><small class="mut"><?= h($x['macnome']) ?></small></td>
          <td><?= h($x['sensornome']) ?></td>
          <td><?= h($x['distmin']) ?> a <?= h($x['distmax']) ?></td>
          <td><code><?= h($x['mensagem']) ?></code></td>
          <td><span class="amostra" style="background:rgb(<?= (int)$x['ledr'] ?>,<?= (int)$x['ledg'] ?>,<?= (int)$x['ledb'] ?>)"></span></td>
          <td><?= (int)$x['buzzertom'] > 0 ? h($x['buzzertom']) . ' Hz / ' . h($x['buzzertempo']) . ' ms' : 'sem som' ?></td>
          <td>
            <?= $x['vigente'] ? '<span class="tag ok-tag">vigente</span>' : '<span class="tag">fora do período</span>' ?>
            <br><small class="mut"><?= h($x['datainicio'] ?: 'sempre') ?> → <?= h($x['datafim'] ?: 'sempre') ?></small>
          </td>
          <td class="acoes-linha">
            <a href="faixas.php?editar=<?= (int)$x['idcombina'] ?>">Editar</a>
            <form method="post" action="faixas.php" onsubmit="return confirm('Excluir esta faixa?')">
              <input type="hidden" name="acao" value="excluir">
              <input type="hidden" name="id" value="<?= (int)$x['idcombina'] ?>">
              <button type="submit" class="perigo">Excluir</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$lista): ?><tr><td colspan="8" class="mut">Nenhuma faixa programada ainda.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<script>
  // prévia do LCD: a primeira linha mostra a mensagem digitada
  var msg = document.getElementById('mensagem');
  var l1 = document.getElementById('lcd1');
  function atualiza() { l1.textContent = msg.value.padEnd(16, ' '); }
  msg.addEventListener('input', atualiza);
  atualiza();
</script>
<?php rodape(); ?>
