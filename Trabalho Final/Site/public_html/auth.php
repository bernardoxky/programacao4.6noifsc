<?php
/*
 * auth.php - login simples por senha (sessão). O painel e os CRUDs exigem login;
 * gravaleitura.php e getjson.php NÃO exigem (o ESP não faz login).
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

function exigir_login(): void
{
    if (empty($_SESSION['painel_ok'])) {
        header('Location: login.php');
        exit;
    }
}
