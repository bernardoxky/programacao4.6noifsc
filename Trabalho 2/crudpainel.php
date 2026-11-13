<?php
include'../../conectabanco.php';

// Processa o formulário
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $idpainelbernardo = $_POST["idpainelbernardo"] ?? "";
    $nome = $_POST["nome"] ?? "";
    $datainicio = $_POST["datainicio"] ?? "";
    $datafim = $_POST["datafim"] ?? "";
    if (!empty($nome)) {
        $sql = "INSERT INTO painelbernardo (idpainelbernardo, nome, datainicio, datafim)
                VALUES (:idpainelbernardo, :nome, :datainicio, :datafim)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":idpainelbernardo", $idpainelbernardo);
        $stmt->bindParam(":nome", $nome);
        $stmt->bindParam(":datainicio", $datainicio);
                $stmt->bindParam(":datafim", $datafim);

        $stmt->execute();
        // Redireciona para evitar reinclusão com F5
            header("Location: ".$_SERVER['PHP_SELF']."?status=sucesso");
            exit();        
    } else {
        echo "<p>Preencha todos os campos.</p>";
    }
}
?>
<html>
    <link rel="stylesheet" href="style.css">
    <div class="container">        
        <!-- Formulário -->
        <form method="post" action="">
            <label>Nome:</label>
            <input type="text" name="nome" required><br><br>
            <label>data início:</label>
            <input type="datetime-local" name="datainicio" required><br><br>
            <label>data fim:</label>
            <input type="datetime-local" name="datafim" required><br><br>

            <button type='submit'>Atualizar</button>
        </form>
    </div>
<div class="container">
<h3>Placas MAC</h3>
<table border="1">
<tr>
</tr>

<?php

// DELETE
if (isset($_GET["delete"])) {
    $idpainelbernardo = $_GET["delete"];
    $sql = "DELETE FROM painelbernardo WHERE idpainelbernardo = :idpainelbernardo";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":idpainelbernardo", $idpainelbernardo);
    $stmt->execute();
}

// UPDATE
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["acao"]) &&
$_POST["acao"] === "update") {
    $idpainelbernardo = $_POST["idpainelbernardo"];
    $novoNome = $_POST["novoNome"]; 
    $novoDatainicio = $_POST["novoDatainicio"];
    $novoDatafim = $_POST["novoDatafim"];

    $sql = "UPDATE painelbernardo SET nome=:novoNome, datainicio=:novoDatainicio, datafim=:novoDatafim WHERE idpainelbernardo = :idpainelbernardo";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":idpainelbernardo", $idpainelbernardo);
    $stmt->bindParam(":novoNome", $novoNome);
    $stmt->bindParam(":novoDatainicio", $novoDatainicio);
    $stmt->bindParam(":novoDatafim", $novoDatafim);
    $stmt->execute();
}
$sql = 'SELECT idpainelbernardo, nome, datainicio, datafim FROM painelbernardo';
$resultado = $conn->query($sql);
foreach ($resultado as $row) {
    echo "<tr>";

    echo "<td>
            <!-- Formulário UPDATE -->
            <form method='post' action='' style='display:inline;'>
                <input type='hidden' name='acao' value='update'>
                <input type='hidden' name='idmacbernardo' value='$row[0]'>
                <label>Nome:<input type='text' name='novoNome' value='$row[1]'>
                <label>Data início:<input type='datetime' name='novoDatainicio' value='$row[2]'>
                <label>Data fim:<input type='datetime' name='novoDatafim' value='$row[3]'>
                <button type='submit'>Atualizar</button>
            </form>
            <!-- Link DELETE -->
            <a  href='?delete=$row[0]' onclick=\"return confirm('Deseja excluir?')\">Excluir</a>
          </td>
          </tr></div>";
}
?>  
</table>
</html>
