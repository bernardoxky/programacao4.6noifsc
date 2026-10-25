<?php
include '../../conectabanco.php';

$dataini=$_GET["dataini"] ?? "";
$datafim=$_GET["datafim"] ?? "";
$idmac=$_GET["idmac"] ?? "";
$dataesp=$_GET["dataesp"] ?? "";


try {
 $sql = "SELECT combinabernardo.idcombinabernardo as combina_idcombina, ledr, ledg, ledb, 
                combinabernardo.macbernardo_idmacbernardo as mac_idmac, 
                painelbernardo.nome, painelbernardo.datainicio, painelbernardo.datafim, 
                macbernardo.nome, pinor, pinog, pinob
        FROM combinabernardo, painelbernardo, macbernardo, ledbernardo
        WHERE 1=1 AND combinabernardo.painelbernardo_idpainelbernardo = painelbernardo.idpainelbernardo 
        AND combinabernardo.macbernardo_idmacbernardo = macbernardo.idmacbernardo 
        AND combinabernardo.ledbernardo_idledbernardo = ledbernardo.idledbernardo ";

 if (!empty($dataini) && !empty($datafim)) {
 $sql .= " AND painelbernardo.datainicio BETWEEN :dataini AND :datafim";
 }

 if (!empty($dataesp)) 
       { $sql .= " AND :dataesp>= painelbernardo.datainicio and :datafim painelbernardo.datainicio";}

 if (!empty($idmac)) {
        $sql .= " AND combinabernardo.macbernardo_idmacbernardo>= :idmac";
 }
 
 $stmt = $conn->prepare($sql);
 if (!empty($dataini) && !empty($datafim) && !empty($idmac)) {
 $stmt->bindParam(':dataini', $dataini);
 $stmt->bindParam(':datafim', $datafim);
 
 }
 if (!empty($idmac)) {
        $stmt->bindParam(':idmac', $idmac);
 }

 $stmt->execute();
 $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
 header('Content-Type: application/json');
 echo json_encode($resultado);
} catch (PDOException $e) {
 echo json_encode([
 'erro' => 'Erro conn: ' . $e->getMessage()
 ]);
}
?>
