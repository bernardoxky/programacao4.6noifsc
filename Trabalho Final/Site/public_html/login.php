<?php
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/auth.php';

if (!empty($_SESSION['painel_ok'])) {
    header('Location: painel.php');
    exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (hash_equals(PAINEL_SENHA, (string)($_POST['senha'] ?? ''))) {
        session_regenerate_id(true);
        $_SESSION['painel_ok'] = true;
        header('Location: painel.php');
        exit;
    }
    sleep(1); // atrasa tentativas erradas
    $erro = 'Senha incorreta.';
}
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Entrar - Sensor de Proximidade</title>
<link rel="stylesheet" href="painel.css">
</head>
<body class="login">
<main class="caixa-login">
  <h1>Sensor de Proximidade</h1>
  <p class="mut">Controle do projeto de Programação IV</p>
  <?php if ($erro): ?><p class="erro"><?= h($erro) ?></p><?php endif; ?>
  <form method="post">
    <label>Senha
      <input type="password" name="senha" autofocus required>
    </label>
    <button type="submit">Entrar</button>
  </form>
  <p class="mut"><a href="/">← Voltar ao site</a></p>
</main>
</body>
</html>
