<?php
include '../../conectabanco.php';

// Faz o PDO lançar exceção em erro de SQL (senão ele falha em silêncio)
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$erro = "";

try {
    // ===== DELETE =====
    if (isset($_GET["delete"])) {
        $sql = "DELETE FROM combinabernardo WHERE idcombinabernardo = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(":id", $_GET["delete"]);
        $stmt->execute();

        header("Location: " . $_SERVER['PHP_SELF'] . "?status=deletado");
        exit();
    }

    // ===== UPDATE =====
    if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["acao"] ?? "") === "update") {
        $id     = $_POST["idaltera"] ?? "";
        $painel = $_POST["novopainel"] ?? "";
        $mac    = $_POST["novomac"] ?? "";
        $led    = $_POST["novoled"] ?? "";
        $ledr   = $_POST["novoledr"] ?? "";
        $ledg   = $_POST["novoledg"] ?? "";
        $ledb   = $_POST["novoledb"] ?? "";

        if ($id !== "" && $painel !== "" && $mac !== "" && $led !== "" && $ledr !== "" && $ledg !== "" && $ledb !== "") {
            $sql = "UPDATE combinabernardo
                    SET painelbernardo_idpainelbernardo = :painel,
                        macbernardo_idmacbernardo = :mac,
                        ledbernardo_idledbernardo = :led,
                        ledr = :ledr,
                        ledg = :ledg,
                        ledb = :ledb
                    WHERE idcombinabernardo = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(":painel", $painel);
            $stmt->bindValue(":mac", $mac);
            $stmt->bindValue(":led", $led);
            $stmt->bindValue(":ledr", $ledr);
            $stmt->bindValue(":ledg", $ledg);
            $stmt->bindValue(":ledb", $ledb);
            $stmt->bindValue(":id", $id);
            $stmt->execute();

            // MySQL devolve 0 linhas afetadas quando os valores enviados são iguais aos já salvos
            $resultadoUpdate = $stmt->rowCount() > 0 ? "atualizado" : "semalteracao";

            header("Location: " . $_SERVER['PHP_SELF'] . "?status=" . $resultadoUpdate);
            exit();
        } else {
            $erro = "Preencha todos os campos para atualizar.";
        }
    }

    // ===== INSERT =====
    if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST["acao"])) {
        $painel = $_POST["painelbernardo_idpainelbernardo"] ?? "";
        $mac    = $_POST["macbernardo_idmacbernardo"] ?? "";
        $led    = $_POST["ledbernardo_idledbernardo"] ?? "";
        $ledr   = $_POST["ledr"] ?? "";
        $ledg   = $_POST["ledg"] ?? "";
        $ledb   = $_POST["ledb"] ?? "";

        if ($painel !== "" && $mac !== "" && $led !== "" && $ledr !== "" && $ledg !== "" && $ledb !== "") {
            $sql = "INSERT INTO combinabernardo
                        (painelbernardo_idpainelbernardo, macbernardo_idmacbernardo, ledbernardo_idledbernardo, ledr, ledg, ledb)
                    VALUES
                        (:painel, :mac, :led, :ledr, :ledg, :ledb)";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(":painel", $painel);
            $stmt->bindValue(":mac", $mac);
            $stmt->bindValue(":led", $led);
            $stmt->bindValue(":ledr", $ledr);
            $stmt->bindValue(":ledg", $ledg);
            $stmt->bindValue(":ledb", $ledb);
            $stmt->execute();

            // Redireciona para evitar reinclusão com F5
            header("Location: " . $_SERVER['PHP_SELF'] . "?status=sucesso");
            exit();
        } else {
            $erro = "Preencha todos os campos.";
        }
    }
} catch (PDOException $e) {
    $erro = "Erro no banco: " . $e->getMessage();
}

// Monta as <option> de um select, marcando a selecionada
function opcoes(array $lista, string $coluna, $selecionado = null): string
{
    $html = "";
    foreach ($lista as $item) {
        $valor = (string)$item[$coluna];
        $marcado = ($selecionado !== null && $valor === (string)$selecionado) ? " selected" : "";
        $html .= '<option value="' . htmlspecialchars($valor) . '"' . $marcado . '>'
               . htmlspecialchars($item['nome']) . '</option>';
    }
    return $html;
}

// ===== Listas para os selects =====
$listapainel = $conn->query("SELECT idpainelbernardo, nome FROM painelbernardo ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$listamac    = $conn->query("SELECT idmacbernardo, nome FROM macbernardo ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$listaled    = $conn->query("SELECT idledbernardo, nome FROM ledbernardo ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

// ===== Lista de combinações =====
$sql = "SELECT combinabernardo.idcombinabernardo AS id,
               combinabernardo.painelbernardo_idpainelbernardo AS painel_id,
               combinabernardo.macbernardo_idmacbernardo AS mac_id,
               combinabernardo.ledbernardo_idledbernardo AS led_id,
               combinabernardo.ledr, combinabernardo.ledg, combinabernardo.ledb,
               painelbernardo.datainicio, painelbernardo.datafim
        FROM combinabernardo
        JOIN painelbernardo ON combinabernardo.painelbernardo_idpainelbernardo = painelbernardo.idpainelbernardo
        JOIN macbernardo    ON combinabernardo.macbernardo_idmacbernardo = macbernardo.idmacbernardo
        JOIN ledbernardo    ON combinabernardo.ledbernardo_idledbernardo = ledbernardo.idledbernardo
        ORDER BY combinabernardo.idcombinabernardo DESC";
$combinacoes = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$status = $_GET["status"] ?? "";
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Combinações</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php if ($status === "sucesso"): ?>
    <p style="color: green;">Registro salvo com sucesso!</p>
<?php elseif ($status === "atualizado"): ?>
    <p style="color: green;">Registro atualizado!</p>
<?php elseif ($status === "semalteracao"): ?>
    <p style="color: #b45309;">Nada mudou: os valores enviados já eram os mesmos que estavam salvos.</p>
<?php elseif ($status === "deletado"): ?>
    <p style="color: green;">Registro excluído!</p>
<?php endif; ?>

<?php if ($erro !== ""): ?>
    <p style="color: red;"><?= htmlspecialchars($erro) ?></p>
<?php endif; ?>

<!-- Formulário -->
<div class="container">
    <form method="post" action="">
        <label>Painel:</label>
        <select name="painelbernardo_idpainelbernardo" required>
            <option value="">Selecione...</option>
            <?= opcoes($listapainel, 'idpainelbernardo') ?>
        </select>

        <label>MAC:</label>
        <select name="macbernardo_idmacbernardo" required>
            <option value="">Selecione...</option>
            <?= opcoes($listamac, 'idmacbernardo') ?>
        </select>

        <label>LED:</label>
        <select name="ledbernardo_idledbernardo" required>
            <option value="">Selecione...</option>
            <?= opcoes($listaled, 'idledbernardo') ?>
        </select>

        <label>LED R:</label>
        <input type="number" name="ledr" min="0" required>

        <label>LED G:</label>
        <input type="number" name="ledg" min="0" required>

        <label>LED B:</label>
        <input type="number" name="ledb" min="0" required>

        <button type="submit">Salvar</button>
    </form>
</div>

<!-- Lista -->
<div class="containerlista">
    <h3>Lista combina</h3>
    <table>
        <?php foreach ($combinacoes as $row): ?>
            <tr>
                <td>
                    <form method="post" action="">
                        <input type="hidden" name="acao" value="update">
                        <input type="hidden" name="idaltera" value="<?= htmlspecialchars($row['id']) ?>">

                        <label>Painel
                            <select name="novopainel" required>
                                <?= opcoes($listapainel, 'idpainelbernardo', $row['painel_id']) ?>
                            </select>
                        </label>
                        <label>MAC
                            <select name="novomac" required>
                                <?= opcoes($listamac, 'idmacbernardo', $row['mac_id']) ?>
                            </select>
                        </label>
                        <label>LED
                            <select name="novoled" required>
                                <?= opcoes($listaled, 'idledbernardo', $row['led_id']) ?>
                            </select>
                        </label>
                        <label>Red
                            <input type="number" name="novoledr" min="0" required value="<?= htmlspecialchars($row['ledr']) ?>">
                        </label>
                        <label>Green
                            <input type="number" name="novoledg" min="0" required value="<?= htmlspecialchars($row['ledg']) ?>">
                        </label>
                        <label>Blue
                            <input type="number" name="novoledb" min="0" required value="<?= htmlspecialchars($row['ledb']) ?>">
                        </label>
                        <label>Início (do painel)
                            <input type="text" value="<?= htmlspecialchars($row['datainicio']) ?>" disabled>
                        </label>
                        <label>Fim (do painel)
                            <input type="text" value="<?= htmlspecialchars($row['datafim']) ?>" disabled>
                        </label>

                        <button type="submit">Atualizar</button>
                    </form>

                    <a href="?delete=<?= htmlspecialchars($row['id']) ?>" onclick="return confirm('Deseja excluir?')">Excluir</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

</body>
</html>