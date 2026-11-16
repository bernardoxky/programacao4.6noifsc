<?php
/* index.php - página "Equipe e Projetos". Todo o conteúdo vem de dados.php. */
$dados = require __DIR__ . '/dados.php';

function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/** Ícones (traço, 24x24). */
function icone(string $nome): string
{
    $svg = [
        'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/>',
        'github'    => '<path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/>',
        'linkedin'  => '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>',
        'lattes'    => '<path d="M22 10 12 5 2 10l10 5 10-5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/><path d="M22 10v6"/>',
        'site'      => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
        'email'     => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
        'chip'      => '<rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><path d="M9 1v3M15 1v3M9 20v3M15 20v3M20 9h3M20 14h3M1 9h3M1 14h3"/>',
        'painel'    => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>',
        'seta'      => '<path d="M5 12h14M13 6l6 6-6 6"/>',
    ];
    return '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
         . ($svg[$nome] ?? '') . '</svg>';
}

$rotulos = ['instagram' => 'Instagram', 'github' => 'GitHub', 'linkedin' => 'LinkedIn', 'lattes' => 'Currículo Lattes', 'site' => 'Site', 'email' => 'E-mail'];

function iniciais(string $nome): string
{
    $p = preg_split('/\s+/', trim($nome));
    $a = mb_substr($p[0], 0, 1);
    $b = count($p) > 1 ? mb_substr($p[count($p) - 1], 0, 1) : '';
    return mb_strtoupper($a . $b);
}

/** Avatar: foto (se existir na pasta fotos/) ou círculo com as iniciais. */
function avatar(array $p, string $classe = ''): string
{
    $foto = $p['foto'] ?? '';
    if ($foto !== '' && is_file(__DIR__ . '/fotos/' . basename($foto))) {
        return '<img class="avatar ' . $classe . '" src="fotos/' . h(rawurlencode(basename($foto))) . '" alt="' . h($p['nome']) . '">';
    }
    return '<span class="avatar ' . $classe . '" style="background:' . h($p['cor'] ?? '#0f6b4f') . '" title="' . h($p['nome']) . '" aria-label="' . h($p['nome']) . '">' . h(iniciais($p['nome'])) . '</span>';
}

$porNome = [];
foreach ($dados['pessoas'] as $p) {
    $porNome[$p['nome']] = $p;
}
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Equipe e Projetos</title>
<meta name="description" content="Perfis da equipe e projetos desenvolvidos no IFSC Câmpus Chapecó.">
<link rel="stylesheet" href="estilo.css">
</head>
<body>

<header class="topo">
  <div class="topo-int">
    <a class="marca" href="#inicio">Equipe</a>
    <nav>
      <a href="#equipe">Perfis</a>
      <a href="#projetos">Projetos</a>
    </nav>
  </div>
</header>

<main>
  <section class="hero" id="inicio">
    <p class="sobre">IFSC Câmpus Chapecó · TDS · Módulo 4</p>
    <h1>Projetos do 6noIFSC</h1>
  </section>

  <section id="equipe" class="secao">
    <h2>Perfis</h2>
    <div class="grade-pessoas">
      <?php foreach ($dados['pessoas'] as $p): ?>
        <article class="pessoa">
          <?= avatar($p, 'grande') ?>
          <h3><?= h($p['nome']) ?></h3>
          <?php if (!empty($p['papel'])): ?><p class="papel"><?= h($p['papel']) ?></p><?php endif; ?>
          <p class="desc"><?= h($p['descricao'] ?? '') ?></p>
          <?php
            $links = [];
            foreach (($p['links'] ?? []) as $tipo => $url) {
                if (trim((string)$url) === '') { continue; }
                $href = $tipo === 'email' && strpos($url, 'mailto:') !== 0 ? 'mailto:' . $url : $url;
                $links[] = '<a class="rede" href="' . h($href) . '" target="' . ($tipo === 'email' ? '_self' : '_blank') . '" rel="noopener noreferrer" title="'
                         . h(($rotulos[$tipo] ?? $tipo) . ' de ' . $p['nome']) . '" aria-label="' . h(($rotulos[$tipo] ?? $tipo) . ' de ' . $p['nome']) . '">'
                         . icone($tipo) . '</a>';
            }
          ?>
          <?php if ($links): ?><div class="redes"><?= implode('', $links) ?></div><?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <section id="projetos" class="secao">
    <h2>Projetos</h2>
    <div class="grade-projetos">
      <?php foreach ($dados['projetos'] as $pr): ?>
        <article class="projeto">
          <div class="projeto-cab">
            <span class="projeto-icone"><?= icone($pr['icone'] ?? 'chip') ?></span>
            <div>
              <h3><?= h($pr['titulo']) ?></h3>
              <?php if (!empty($pr['disciplina'])): ?><p class="disc"><?= h($pr['disciplina']) ?></p><?php endif; ?>
            </div>
          </div>
          <p class="desc"><?= h($pr['descricao']) ?></p>

          <?php if (!empty($pr['tags'])): ?>
            <ul class="tags"><?php foreach ($pr['tags'] as $t): ?><li><?= h($t) ?></li><?php endforeach; ?></ul>
          <?php endif; ?>

          <?php if (!empty($pr['membros'])): ?>
            <div class="membros">
              <span class="rotulo">Equipe</span>
              <div class="pilha">
                <?php foreach ($pr['membros'] as $nome): ?>
                  <?= avatar($porNome[$nome] ?? ['nome' => $nome, 'cor' => '#667085'], 'pequeno') ?>
                <?php endforeach; ?>
              </div>
              <span class="nomes"><?= h(implode(', ', $pr['membros'])) ?></span>
            </div>
          <?php endif; ?>

          <div class="acoes">
            <?php foreach (($pr['links'] ?? []) as $l): ?>
              <?php if (trim((string)($l['url'] ?? '')) === '') { continue; } ?>
              <a class="botao<?= !empty($l['principal']) ? ' principal' : '' ?>" href="<?= h($l['url']) ?>"<?= strpos($l['url'], 'http') === 0 ? ' target="_blank" rel="noopener noreferrer"' : '' ?>>
                <?= icone($l['icone'] ?? 'site') ?><span><?= h($l['rotulo']) ?></span><?= !empty($l['principal']) ? icone('seta') : '' ?>
              </a>
            <?php endforeach; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
</main>

<footer class="rodape">
  <p>IFSC Câmpus Chapecó · <?= date('Y') ?></p>
</footer>

</body>
</html>
