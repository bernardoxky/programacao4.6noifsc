<?php
include'../../conectabanco.php';

// Carrega PAINEL do banco
$sqlpainelbernardo = "SELECT idpainelbernardo, nome FROM painelbernardo ORDER BY nome";
$stmtpainelbernardo = $conn->query($sqlpainelbernardo);
$listapainelbernardo = $stmtpainelbernardo->fetchAll(PDO::FETCH_ASSOC); // Corrigido: fetchAll

// Carrega LED do banco
$sqlledbernardo = "SELECT idledbernardo, nome FROM ledbernardo ORDER BY nome";
$stmtledbernardo = $conn->query($sqlledbernardo);
$listaledbernardo = $stmtledbernardo->fetchAll(PDO::FETCH_ASSOC); // Corrigido: fetchAll

// Carrega MAC do banco
$sqlmacbernardo = "SELECT idmacbernardo, nome FROM macbernardo ORDER BY nome";
$stmtmacbernardo = $conn->query($sqlmacbernardo);
$listamacbernardo = $stmtmacbernardo->fetchAll(PDO::FETCH_ASSOC); // Corrigido: fetchAll

// DELETE
if (isset($_GET["delete"])) {
    $idtabela = $_GET["delete"];
    $sql = "DELETE FROM combinabernardo WHERE idcombinabernardo = :idcombinabernardo";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":idcombinabernardo", $idtabela); // Corrigido: :idtabela
    $stmt->execute();
    header("Location: ".$_SERVER['PHP_SELF']."?status=deletado");
    exit();
}

// UPDATE
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["acao"]) && $_POST["acao"] === "update") {
    $idaltera = $_POST["idaltera"];
    $novoLedr = $_POST["novoLedr"];
    $novoLedg = $_POST["novoLedg"];
    $novoLedb = $_POST["novoLedb"];

    $sql = "UPDATE combinabernardo SET ledr=:novoLedr, ledg=:novoLedg, ledb=:novoLedb  WHERE idcombinabernardo = :idaltera";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":idaltera", $idaltera);
    $stmt->bindParam(":novoLedr", $novoLedr);
     $stmt->bindParam(":novoLedg", $novoLedg);
      $stmt->bindParam(":novoLedb", $novoLedb);
    $stmt->execute();
    // MySQL devolve 0 linhas afetadas quando os valores enviados são iguais aos já salvos
    $resultadoUpdate = $stmt->rowCount() > 0 ? "atualizado" : "semalteracao";
    header("Location: ".$_SERVER['PHP_SELF']."?status=".$resultadoUpdate);
    exit();
}

// INSERT / Processa o formulário principal
if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST["acao"])) {
    $painelbernardo_idpainelbernardo = $_POST["painelbernardo_idpainelbernardo"] ?? "";
    $ledbernardo_idledbernardo = $_POST["ledbernardo_idledbernardo"] ?? "";
    $macbernardo_idmacbernardo = $_POST["macbernardo_idmacbernardo"] ?? "";
    $ledr = $_POST["ledr"] ?? "";
    $ledg = $_POST["ledg"] ?? "";
    $ledb = $_POST["ledb"] ?? "";

    if (!empty($painelbernardo_idpainelbernardo) && !empty($ledbernardo_idledbernardo) && !empty($macbernardo_idmacbernardo)) {
        $sql = "INSERT INTO combinabernardo (painelbernardo_idpainelbernardo, ledbernardo_idledbernardo, macbernardo_idmacbernardo, ledr, ledg, ledb)
                VALUES (:painelbernardo_idpainelbernardo, :ledbernardo_idledbernardo, :macbernardo_idmacbernardo, :ledr, :ledg, :ledb)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":painelbernardo_idpainelbernardo", $painelbernardo_idpainelbernardo);
        $stmt->bindParam(":ledbernardo_idledbernardo", $ledbernardo_idledbernardo);
        $stmt->bindParam(":macbernardo_idmacbernardo", $macbernardo_idmacbernardo);
        $stmt->bindParam(":ledr", $ledr);
        $stmt->bindParam(":ledg", $ledg);
        $stmt->bindParam(":ledb", $ledb);
        $stmt->execute();
        
        // Redireciona para evitar reinclusão com F5
        header("Location: ".$_SERVER['PHP_SELF']."?status=sucesso");
        exit();        
    } else {
        $erro = "Preencha todos os campos.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php 
    if (isset($_GET['status']) && $_GET['status'] === 'sucesso') {
        echo "<p style='color: green;'>Registro salvo com sucesso!</p>";
    } elseif (isset($_GET['status']) && $_GET['status'] === 'atualizado') {
        echo "<p style='color: green;'>Registro atualizado!</p>";
    } elseif (isset($_GET['status']) && $_GET['status'] === 'semalteracao') {
        echo "<p style='color: #b45309;'>Nada mudou: os valores enviados já eram os mesmos que estavam salvos.</p>";
    } elseif (isset($_GET['status']) && $_GET['status'] === 'deletado') {
        echo "<p style='color: green;'>Registro excluído!</p>";
    } elseif (isset($erro)) {
        echo "<p style='color: red;'>$erro</p>";
    }
    ?>

    <!-- Formulário -->
    <div class='container'>
        <form method="post" action="">

        <label>Painel:</label>
        <select name="painelbernardo_idpainelbernardo" required>
            <option value="">Selecione...</option>
            <?php foreach ($listapainelbernardo as $pai): ?>
                <option value="<?= $pai['idpainelbernardo'] ?>">
                <?= htmlspecialchars($pai['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select><br>

        <label>LED:</label>
        <select name="ledbernardo_idledbernardo" required>
            <option value="">Selecione...</option>
            <?php foreach ($listaledbernardo as $led): ?>
                <option value="<?= $led['idledbernardo'] ?>">
                <?= htmlspecialchars($led['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select><br>

        <label>MAC:</label>
        <select name="macbernardo_idmacbernardo" required>
            <option value="">Selecione...</option>
            <?php foreach ($listamacbernardo as $mac): ?>
                <option value="<?= $mac['idmacbernardo'] ?>">
                <?= htmlspecialchars($mac['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select><br>

        <label>LED R:</label>
        <input type="number" name="ledr" required>

        <label>LED G:</label>
        <input type="number" name="ledg" required>

        <label>LED B:</label>
        <input type="number" name="ledb" required>
        
        <button type="submit">Salvar</button>
        </form>
    </div>

    <div class='containerlista'>
        <h3>Lista combina</h3>
        <table border="1">
            <?php
            $sql = 'SELECT idcombinabernardo, ledr, ledg, ledb FROM combinabernardo';
            $resultado = $conn->query($sql);
            foreach ($resultado as $row) {
                echo "<tr>";
                echo "<td>
                        <!-- Formulário UPDATE -->
                        <form method='post' action='' style='display:inline;'>
                            <input type='hidden' name='acao' value='update'>
                            <input type='hidden' name='idaltera' value='{$row[0]}'>
                            <label>Led R:<input type='number' name='novoLedr' value='{$row[1]}'></label>
                            <label>Led G:<input type='number' name='novoLedg' value='{$row[2]}'></label>
                            <label>Led B:<input type='number' name='novoLedb' value='{$row[3]}'></label>
                            <button type='submit'>Atualizar</button>
                        </form>
                        <!-- Link DELETE -->
                        <a href='?delete=$row[0]' onclick=\"return confirm('Deseja excluir?')\">Excluir</a>
                      </td>";
                echo "</tr>";
            }
            ?>
        </table>
    </div>
</body>
</html>