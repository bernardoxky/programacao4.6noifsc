<?php
/*
 * crud_lib.php - função que monta um CRUD completo (listar, inserir, editar, excluir)
 * a partir de uma configuração. Cada crudTabela.php só descreve a sua tabela e chama crud().
 *
 * Configuração ($cfg):
 *   titulo, descricao   textos da página
 *   tabela, pk          nome da tabela e da chave primária
 *   pk_manual           true quando a PK é digitada (ex.: MAC); false = AUTO_INCREMENT
 *   pk_label, pk_max, pk_maiusculo, pk_regex, pk_erro   (só para PK manual)
 *   campos              coluna => [label, tipo, opções]
 *       tipo: text | number | float | datetime | bool | select
 *       opções: obrigatorio, max, min, padrao, sem_acento (tira acentos),
 *               agora (vazio = NOW()), fk => [tabela, coluna_id, 'expressão SQL do rótulo']
 *   ordem               (opcional) ORDER BY da listagem
 * Colunas que não estão em "campos" (ex.: contador) aparecem na lista, mas não no formulário.
 */
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';

function crud_erro(Throwable $e): string
{
    if ($e instanceof mysqli_sql_exception) {
        switch ($e->getCode()) {
            case 1451: return 'Não dá para excluir: existem registros de outras tabelas ligados a este.';
            case 1452: return 'Referência inválida: o registro ligado não existe.';
            case 1062: return 'Já existe um registro com esse valor.';
            case 1644: return $e->getMessage(); // mensagens dos triggers (MAC/sensor inativo)
            default:   return 'Erro no banco: ' . $e->getMessage();
        }
    }
    return $e->getMessage();
}

function crud_input(string $col, array $f, $valor): string
{
    $n   = h($col);
    $req = !empty($f['obrigatorio']) ? ' required' : '';
    switch ($f['tipo']) {
        case 'bool':
            return '<input type="checkbox" name="' . $n . '" value="1"' . ($valor ? ' checked' : '') . '>';
        case 'select':
            [$tab, $idc, $rot] = $f['fk'];
            $o = '<option value="">— escolha —</option>';
            foreach (db()->query("SELECT `$idc` AS v, $rot AS r FROM `$tab` ORDER BY 1") as $r) {
                $sel = ((string)$r['v'] === (string)$valor) ? ' selected' : '';
                $o  .= '<option value="' . h($r['v']) . '"' . $sel . '>' . h($r['r']) . '</option>';
            }
            return '<select name="' . $n . '"' . $req . '>' . $o . '</select>';
        case 'datetime':
            $v = $valor ? str_replace(' ', 'T', substr((string)$valor, 0, 16)) : '';
            return '<input type="datetime-local" name="' . $n . '" value="' . h($v) . '"' . $req . '>';
        case 'number':
            $min = isset($f['min']) ? ' min="' . (int)$f['min'] . '"' : '';
            $max = isset($f['max']) ? ' max="' . (int)$f['max'] . '"' : '';
            return '<input type="number" step="1" name="' . $n . '" value="' . h($valor) . '"' . $min . $max . $req . '>';
        case 'float':
            return '<input type="number" step="any" name="' . $n . '" value="' . h($valor) . '"' . $req . '>';
        default:
            $max = isset($f['max']) ? ' maxlength="' . (int)$f['max'] . '"' : '';
            return '<input type="text" name="' . $n . '" value="' . h($valor) . '"' . $max . $req . '>';
    }
}

function crud(array $cfg): void
{
    exigir_login();

    $t      = $cfg['tabela'];
    $pk     = $cfg['pk'];
    $manual = !empty($cfg['pk_manual']);
    $campos = $cfg['campos'];
    $arq    = basename($_SERVER['PHP_SELF']);
    $erro   = '';
    $dados  = [];       // valores que preenchem o formulário
    $flash  = $_SESSION['flash'] ?? '';
    unset($_SESSION['flash']);

    $acao     = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['acao'] ?? '') : '';
    $editando = $acao === 'salvar' ? (string)($_POST['editando'] ?? '') : ''; // PK do registro em edição

    // ---------- gravar / excluir ----------
    if ($acao !== '') {
        $vals = [];
        try {
            if ($acao === 'excluir') {
                $st = q("DELETE FROM `$t` WHERE `$pk` = ?", 's', [(string)($_POST['id'] ?? '')]);
                $_SESSION['flash'] = $st->affected_rows ? 'Registro excluído.' : 'Nada foi excluído.';
                header("Location: $arq");
                exit;
            }

            if ($acao === 'salvar') {
                $cols = $ph = $sets = $params = [];

                $vpk = '';
                if ($manual) {
                    $vpk = trim((string)($_POST[$pk] ?? ''));
                    if (!empty($cfg['pk_maiusculo'])) {
                        $vpk = strtoupper($vpk);
                    }
                    $vals[$pk] = $vpk;
                    if ($vpk === '') {
                        throw new Exception('Preencha ' . ($cfg['pk_label'] ?? $pk) . '.');
                    }
                    if (!empty($cfg['pk_regex']) && !preg_match($cfg['pk_regex'], $vpk)) {
                        throw new Exception($cfg['pk_erro'] ?? 'Valor inválido na chave.');
                    }
                }

                foreach ($campos as $col => $f) {
                    if ($f['tipo'] === 'bool') {
                        $v = isset($_POST[$col]) ? 1 : 0;
                    } else {
                        $v = trim((string)($_POST[$col] ?? ''));
                        if ($f['tipo'] === 'datetime') {
                            $v = str_replace('T', ' ', $v);
                        }
                        if (!empty($f['sem_acento'])) {
                            $v = sem_acentos($v);
                        }
                        if ($v === '') {
                            $v = null;
                        }
                    }
                    $vals[$col] = $v;

                    if ($v === null && !empty($f['obrigatorio']) && empty($f['agora'])) {
                        throw new Exception('Preencha o campo "' . $f['label'] . '".');
                    }
                    if ($v !== null && in_array($f['tipo'], ['number', 'float'], true)) {
                        if (!is_numeric($v)) {
                            throw new Exception('O campo "' . $f['label'] . '" precisa ser um número.');
                        }
                        if ($f['tipo'] === 'number') {
                            $v = (string)(int)round((float)$v);
                            $vals[$col] = $v;
                        }
                        if (isset($f['min']) && $v < $f['min']) {
                            throw new Exception('"' . $f['label'] . '" deve ser no mínimo ' . $f['min'] . '.');
                        }
                        if (isset($f['max']) && $v > $f['max']) {
                            throw new Exception('"' . $f['label'] . '" deve ser no máximo ' . $f['max'] . '.');
                        }
                    }
                    if ($v !== null && $f['tipo'] === 'text' && isset($f['max']) && mb_strlen((string)$v) > $f['max']) {
                        throw new Exception('"' . $f['label'] . '" aceita no máximo ' . $f['max'] . ' caracteres.');
                    }

                    $cols[]   = $col;
                    $ph[]     = !empty($f['agora']) ? 'COALESCE(?, NOW())' : '?';
                    $sets[]   = !empty($f['agora']) ? "`$col` = COALESCE(?, `$col`)" : "`$col` = ?";
                    $params[] = $v;
                }

                if ($editando !== '') {
                    $sql      = "UPDATE `$t` SET " . implode(', ', $sets) . " WHERE `$pk` = ?";
                    $params[] = $editando;
                } else {
                    if ($manual) {
                        array_unshift($cols, $pk);
                        array_unshift($ph, '?');
                        array_unshift($params, $vpk);
                    }
                    $sql = "INSERT INTO `$t` (`" . implode('`, `', $cols) . '`) VALUES (' . implode(', ', $ph) . ')';
                }
                q($sql, str_repeat('s', count($params)), $params);

                $_SESSION['flash'] = 'Registro salvo.';
                header("Location: $arq");
                exit;
            }
        } catch (Throwable $e) {
            $erro  = crud_erro($e);
            $dados = $vals; // mantém o que foi digitado
        }
    }

    // ---------- carregar registro para edição ----------
    if ($editando === '' && !$erro && isset($_GET['editar'])) {
        $r = linhas("SELECT * FROM `$t` WHERE `$pk` = ?", 's', [(string)$_GET['editar']]);
        if ($r) {
            $dados    = $r[0];
            $editando = (string)$r[0][$pk];
        }
    }

    // ---------- página ----------
    topo($cfg['titulo']);
    echo '<h1>' . h($cfg['titulo']) . '</h1>';
    if (!empty($cfg['descricao'])) {
        echo '<p class="mut">' . h($cfg['descricao']) . '</p>';
    }
    if ($flash) {
        echo '<p class="ok">' . h($flash) . '</p>';
    }
    if ($erro) {
        echo '<p class="erro">' . h($erro) . '</p>';
    }

    echo '<section class="card"><h2>' . ($editando !== '' ? 'Editar registro' : 'Novo registro') . '</h2>';
    echo '<form method="post" action="' . h($arq) . '" class="grade">';
    echo '<input type="hidden" name="acao" value="salvar">';
    echo '<input type="hidden" name="editando" value="' . h($editando) . '">';

    if ($manual) {
        $max = (int)($cfg['pk_max'] ?? 50);
        $ro  = $editando !== '' ? ' readonly' : '';
        echo '<label>' . h($cfg['pk_label'] ?? $pk) . ' *<input type="text" name="' . h($pk) . '" maxlength="' . $max
           . '" value="' . h($dados[$pk] ?? '') . '" required' . $ro . '></label>';
    }
    foreach ($campos as $col => $f) {
        $valor = array_key_exists($col, $dados) ? $dados[$col] : ($f['padrao'] ?? '');
        $rot   = h($f['label']) . (!empty($f['obrigatorio']) ? ' *' : '');
        $dica  = !empty($f['dica']) ? '<small>' . h($f['dica']) . '</small>' : '';
        $cls   = $f['tipo'] === 'bool' ? ' class="marca-check"' : '';
        echo '<label' . $cls . '>' . $rot . crud_input($col, $f, $valor) . $dica . '</label>';
    }
    echo '<div class="acoes"><button type="submit">' . ($editando !== '' ? 'Salvar alterações' : 'Adicionar') . '</button>';
    if ($editando !== '') {
        echo ' <a class="botao-sec" href="' . h($arq) . '">Cancelar</a>';
    }
    echo '</div></form></section>';

    // ---------- listagem ----------
    $ordem = $cfg['ordem'] ?? ("ORDER BY `$pk` " . ($manual ? 'ASC' : 'DESC'));
    $res   = db()->query("SELECT * FROM `$t` $ordem LIMIT 300");
    $nomes = array_map(fn($f) => $f->name, $res->fetch_fields());

    echo '<section class="card"><h2>Registros</h2><div class="rolagem"><table><thead><tr>';
    foreach ($nomes as $n) {
        echo '<th>' . h($n) . '</th>';
    }
    echo '<th></th></tr></thead><tbody>';
    $qtd = 0;
    foreach ($res as $row) {
        $qtd++;
        echo '<tr>';
        foreach ($nomes as $n) {
            echo '<td>' . h($row[$n]) . '</td>';
        }
        $id = (string)$row[$pk];
        echo '<td class="acoes-linha"><a href="' . h($arq) . '?editar=' . urlencode($id) . '">Editar</a>'
           . '<form method="post" action="' . h($arq) . '" onsubmit="return confirm(\'Excluir este registro?\')">'
           . '<input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="' . h($id) . '">'
           . '<button type="submit" class="perigo">Excluir</button></form></td></tr>';
    }
    if ($qtd === 0) {
        echo '<tr><td colspan="' . (count($nomes) + 1) . '" class="mut">Nenhum registro ainda.</td></tr>';
    }
    echo '</tbody></table></div></section>';
    rodape();
}
