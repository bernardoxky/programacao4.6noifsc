<?php
/*
 * conexao.php - configuração e funções de apoio (usado por todos os outros arquivos)
 * EDITE as constantes abaixo antes de subir para o servidor.
 */

const DB_HOST = 'localhost';
const DB_USER = 'u616938032_6noifsc';
const DB_PASS = 'TrabalhoFinal2026';
const DB_NAME = 'u616938032_ProjetoFinal';

// Senha para entrar no painel e nos CRUDs (o ESP NÃO usa senha). TROQUE!
const PAINEL_SENHA = 'trabalhofinal';

// Opcional: se preenchido, o ESP precisa mandar ?token=... em gravaleitura.php e getjson.php
const TOKEN_ESP = '';

// Erros do MySQL viram exceções (mysqli_sql_exception), tratadas em cada página
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/** Conexão única com o banco. */
function db(): mysqli
{
    static $c = null;
    if ($c === null) {
        $c = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $c->set_charset('utf8mb4');
        $c->query("SET time_zone = '-03:00'"); // NOW() em horário de Brasília
    }
    return $c;
}

/** Executa uma consulta com parâmetros (prepared statement). Ex.: q('... WHERE id=?', 'i', [5]) */
function q(string $sql, string $tipos = '', array $params = []): mysqli_stmt
{
    $st = db()->prepare($sql);
    if ($tipos !== '') {
        $st->bind_param($tipos, ...$params);
    }
    $st->execute();
    return $st;
}

/** Executa um SELECT e devolve todas as linhas como array associativo. */
function linhas(string $sql, string $tipos = '', array $params = []): array
{
    return q($sql, $tipos, $params)->get_result()->fetch_all(MYSQLI_ASSOC);
}

/** Escapa texto para HTML. */
function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/** Responde em JSON e encerra (usado por gravaleitura.php e getjson.php). */
function json_saida(array $dados, int $http = 200): void
{
    http_response_code($http);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Confere o token do ESP, se TOKEN_ESP estiver configurado. */
function checa_token(): void
{
    if (TOKEN_ESP !== '' && !hash_equals(TOKEN_ESP, (string)($_REQUEST['token'] ?? ''))) {
        json_saida(['ok' => false, 'erro' => 'token invalido'], 401);
    }
}

/** Tira acentos (o display LCD 16x2 não mostra acentos). */
function sem_acentos(string $s): string
{
    return strtr($s, [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', 'ñ' => 'n',
        'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A',
        'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
        'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
        'Ç' => 'C', 'Ñ' => 'N',
    ]);
}
