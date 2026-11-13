<?php
include '../../conectabanco.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST["acao"])) {
    $nome = $_POST["nome"] ?? "";
    $pinor = $_POST["pinor"] ?? "";
    $pinog = $_POST["pinog"] ?? "";
    $pinob = $_POST["pinob"] ?? "";
    
    if ($nome !== "" && $pinor !== "" && $pinog !== "" && $pinob !== "") {
        
        $sql = "INSERT INTO ledbernardo (nome, pinor, pinog, pinob)
                VALUES (:nome, :pinor, :pinog, :pinob)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":nome", $nome);
        $stmt->bindParam(":pinor", $pinor);
        $stmt->bindParam(":pinog", $pinog);
        $stmt->bindParam(":pinob", $pinob);
        $stmt->execute();
        
        header("Location: ".$_SERVER['PHP_SELF']."?status=sucesso");
        exit();        
    } else {
        echo "<p>Preencha todos os campos.</p>";
    }
}

// DELETE
if (isset($_GET["delete"])) {
    $idledbernardo = $_GET["delete"];
    $sql = "DELETE FROM ledbernardo WHERE idledbernardo = :idledbernardo";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":idledbernardo", $idledbernardo);
    $stmt->execute();
    
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// UPDATE
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["acao"]) && $_POST["acao"] === "update") {
    $idledbernardo = $_POST["idledbernardo"];
    $novoNome = $_POST["novoNome"];
    $novopinor = $_POST["novopinor"];
    $novopinog = $_POST["novopinog"];
    $novopinob = $_POST["novopinob"];

    $sql = "UPDATE ledbernardo SET nome=:novoNome, pinor=:novopinor, pinog=:novopinog, pinob=:novopinob WHERE idledbernardo = :idledbernardo";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":novoNome", $novoNome);
    $stmt->bindParam(":novopinor", $novopinor);
    $stmt->bindParam(":novopinog", $novopinog);
    $stmt->bindParam(":novopinob", $novopinob);
    $stmt->bindParam(":idledbernardo", $idledbernardo); 
    $stmt->execute();

    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}
?>
<html>
<head>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">  
        <form method="post" action="">
            <label>Nome:</label>
            <input type="text" name="nome" required><br><br>
            
            <label>pinor:</label>
            <input type="text" name="pinor" required><br><br>

            <label>pinog:</label>
            <input type="text" name="pinog" required><br><br>
            
            <label>pinob:</label>
            <input type="text" name="pinob" required><br><br>

            <button type="submit">Salvar</button>
        </form>
    </div>

    <div class="container">
        <h3>Leds</h3>
        <table border="1">
        <?php
        $sql = 'SELECT idledbernardo, nome, pinor, pinog, pinob FROM ledbernardo';
        $resultado = $conn->query($sql);
        foreach ($resultado as $row) {
            echo "<tr>";
            echo "<td>
                    <!-- Formulário UPDATE -->
                    <form method='post' action='' style='display:inline;'>
                        <input type='hidden' name='acao' value='update'>
                        <input type='hidden' name='idledbernardo' value='{$row['idledbernardo']}'>
                        <label>Nome:<input type='text' name='novoNome' value='{$row['nome']}'></label>
                        <label>pinor:<input type='text' name='novopinor' value='{$row['pinor']}'></label><br>
                        <label>pinog:<input type='text' name='novopinog' value='{$row['pinog']}'></label>
                        <label>pinob:<input type='text' name='novopinob' value='{$row['pinob']}'></label>
                        <br><button type='submit'>Atualizar</button>
                    </form>
                    <!-- Link DELETE -->
                    <a href='?delete={$row['idledbernardo']}' onclick=\"return confirm('Deseja excluir?')\">Excluir</a>
                  </td>";
            echo "</tr>";
        }
        ?>  
        </table>
    </div>
</body>
</html>
