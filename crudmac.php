<?php
include'../../conectabanco.php';

// Processa o formulário
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $idmacbernardo = $_POST["idmacbernardo"] ?? "";
    $nome = $_POST["nome"] ?? "";
    $ativo = $_POST["ativo"] ?? "";
    if (!empty($idmacbernardo) && !empty($nome)) {
        $sql = "INSERT INTO macbernardo (idmacbernardo, nome, ativo)
                VALUES (:idmacbernardo, :nome, :ativo)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":idmacbernardo", $idmacbernardo);
        $stmt->bindParam(":nome", $nome);
        $stmt->bindParam(":ativo", $ativo);
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
            <label>IdMAC:</label>
            <input type="text" name="idmacbernardo" required><br><br>

            <label>Nome:</label>
            <input type="text" name="nome" required><br><br>
            
            <label>Ativo:</label>
            <input type="checkbox" id="ativo" name="ativo" value="1"><br><br>

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
    $idmacbernardo = $_GET["delete"];
    $sql = "DELETE FROM macbernardo WHERE idmacbernardo = :idmacbernardo";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":idmacbernardo", $idmacbernardo);
    $stmt->execute();
}

// UPDATE
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["acao"]) &&
$_POST["acao"] === "update") {
    $idmacbernardo = $_POST["idmacbernardo"];
    $novoNome = $_POST["novoNome"];
    $novoAtivo = $_POST["novoAtivo"];

    $sql = "UPDATE macbernardo SET nome=:novoNome, ativo=:novoAtivo WHERE idmacbernardo = :idmacbernardo";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":idmacbernardo", $idmacbernardo);
    $stmt->bindParam(":novoNome", $novoNome);
    $stmt->bindParam(":novoAtivo", $novoAtivo);
    $stmt->execute();
}
$sql = 'SELECT idmacbernardo, nome, ativo FROM macbernardo';
$resultado = $conn->query($sql);
foreach ($resultado as $row) {
    echo "<tr>";

    echo "<td>
            <!-- Formulário UPDATE -->
            <form method='post' action='' style='display:inline;'>
                <input type='hidden' name='acao' value='update'>
                <input type='hidden' name='idmacbernardo' value='$row[0]'>
                <label>Nome:<input type='text' name='novoNome' value='$row[1]'>
                <label>Ativo:<input type='text' name='novoAtivo' value='$row[2]'>
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
