<?php
require __DIR__ . '/auth.php';
require_login();
require __DIR__ . '/site-generator.php';

const STATUS_LABEL_VIEW = [
  'disponivel' => 'Disponível',
  'vendida' => 'Vendida',
  'reservada' => 'Reservada',
  'indisponivel' => 'Indisponível',
];

$casas = fetch_all_casas($pdo);
$lotes = fetch_all_lotes($pdo);

function render_row(array $item, string $tipo): string {
  $tipoPlural = $tipo === 'casa' ? 'casas' : 'lotes';
  $cover = htmlspecialchars('/' . $item['imagens']['pasta'] . '/' . $item['imagens']['arquivos'][0], ENT_QUOTES, 'UTF-8');
  $codigo = htmlspecialchars($item['codigo'], ENT_QUOTES, 'UTF-8');
  $titulo = htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8');
  $statusLabel = htmlspecialchars(STATUS_LABEL_VIEW[$item['status']] ?? $item['status'], ENT_QUOTES, 'UTF-8');
  $statusClass = htmlspecialchars($item['status'], ENT_QUOTES, 'UTF-8');
  $destaque = $item['destaque'] ? 'Sim' : 'Não';
  $ordem = (int) $item['ordem'];
  // Atributo data-*, não onsubmit inline: um título com aspas simples
  // quebraria (ou poderia escapar de) um confirm() com o texto interpolado
  // direto no JS. htmlspecialchars() aqui protege só o atributo HTML.
  $confirmMsg = htmlspecialchars(
    "Excluir \"{$item['titulo']}\" ({$item['codigo']})? Isso remove o imóvel do banco e apaga a página gerada em {$tipoPlural}/. As fotos NÃO são apagadas.",
    ENT_QUOTES, 'UTF-8'
  );

  return <<<HTML
<tr data-codigo="{$codigo}">
  <td class="col-drag"><span class="drag-handle" draggable="true" title="Arrastar para reordenar">⠿</span></td>
  <td><img class="thumb" src="{$cover}" alt=""></td>
  <td>{$codigo}</td>
  <td class="col-title">{$titulo}</td>
  <td><span class="status-badge status-badge--{$statusClass}">{$statusLabel}</span></td>
  <td>{$destaque}</td>
  <td class="col-ordem">{$ordem}</td>
  <td class="admin-table__actions">
    <a class="btn-link" href="form.php?tipo={$tipo}&codigo={$codigo}">Editar</a>
    <form method="post" action="delete.php" class="delete-form" data-confirm="{$confirmMsg}">
      <input type="hidden" name="tipo" value="{$tipo}">
      <input type="hidden" name="codigo" value="{$codigo}">
      <button type="submit" class="danger">Excluir</button>
    </form>
  </td>
</tr>
HTML;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin | Città Empreendimentos</title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="/img/site/favicon-32.png" type="image/png" sizes="32x32">
<link rel="apple-touch-icon" href="/img/site/favicon-180.png">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/base/tokens.css">
<link rel="stylesheet" href="/css/base/reset.css">
<link rel="stylesheet" href="/css/base/base.css">
<link rel="stylesheet" href="/css/components.css">
<link rel="stylesheet" href="admin.css">
</head>
<body class="admin">

<div class="dashboard">
  <header class="admin-topbar">
    <div class="admin-topbar__brand">
      <img src="/img/logo.png" alt="" height="28" onerror="this.remove()">
      <strong>Admin Città</strong>
      <span class="admin-topbar__folder">— <?= htmlspecialchars($_SESSION['admin_user'], ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="admin-topbar__actions">
      <a href="logout.php" class="btn btn--ghost">Sair</a>
    </div>
  </header>

  <?php if (isset($_GET['deleted'])): ?>
    <p class="flash-message">Imóvel excluído e site regenerado.</p>
  <?php endif; ?>

  <nav class="admin-tabs">
    <button type="button" class="admin-tabs__btn is-active" data-tab="casas">Casas <span class="admin-tabs__count"><?= count($casas) ?></span></button>
    <button type="button" class="admin-tabs__btn" data-tab="lotes">Terrenos <span class="admin-tabs__count"><?= count($lotes) ?></span></button>
  </nav>

  <main class="admin-main">
    <div class="admin-toolbar">
      <a href="form.php?tipo=casa" class="btn btn--primary" data-new-casa>+ Nova casa</a>
      <a href="form.php?tipo=lote" class="btn btn--primary" data-new-lote hidden>+ Novo terreno</a>
    </div>

    <div class="admin-table-wrap">
      <table class="admin-table" id="table-casas" data-tipo="casa">
        <thead>
          <tr><th></th><th>Capa</th><th>Código</th><th>Título</th><th>Status</th><th>Destaque</th><th>Ordem</th><th></th></tr>
        </thead>
        <tbody>
          <?php if (!$casas): ?>
            <tr><td colspan="8" class="admin-empty">Nenhuma casa cadastrada ainda.</td></tr>
          <?php else: foreach ($casas as $c): echo render_row($c, 'casa'); endforeach; endif; ?>
        </tbody>
      </table>
      <table class="admin-table" id="table-lotes" data-tipo="lote" hidden>
        <thead>
          <tr><th></th><th>Capa</th><th>Código</th><th>Título</th><th>Status</th><th>Destaque</th><th>Ordem</th><th></th></tr>
        </thead>
        <tbody>
          <?php if (!$lotes): ?>
            <tr><td colspan="8" class="admin-empty">Nenhum terreno cadastrado ainda.</td></tr>
          <?php else: foreach ($lotes as $l): echo render_row($l, 'lote'); endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>

<script>
(function () {
  var buttons = document.querySelectorAll('.admin-tabs__btn');
  var tables = { casas: document.getElementById('table-casas'), lotes: document.getElementById('table-lotes') };
  var newCasa = document.querySelector('[data-new-casa]');
  var newLote = document.querySelector('[data-new-lote]');
  buttons.forEach(function (btn) {
    btn.addEventListener('click', function () {
      var tab = btn.dataset.tab;
      buttons.forEach(function (b) { b.classList.toggle('is-active', b === btn); });
      tables.casas.hidden = tab !== 'casas';
      tables.lotes.hidden = tab !== 'lotes';
      newCasa.hidden = tab !== 'casas';
      newLote.hidden = tab !== 'lotes';
    });
  });

  document.querySelectorAll('.delete-form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!confirm(form.dataset.confirm)) e.preventDefault();
    });
  });

  // ---- Arrastar para reordenar ----
  function persistOrder(table) {
    var rows = Array.from(table.querySelectorAll('tbody tr[data-codigo]'));
    var order = rows.map(function (r) { return r.dataset.codigo; });
    rows.forEach(function (r, i) {
      var cell = r.querySelector('.col-ordem');
      if (cell) cell.textContent = i + 1;
    });
    fetch('reorder.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ tipo: table.dataset.tipo, order: order }),
    }).catch(function () {});
  }

  document.querySelectorAll('.admin-table[data-tipo]').forEach(function (table) {
    var tbody = table.querySelector('tbody');
    var dragging = null;

    tbody.addEventListener('dragstart', function (e) {
      var handle = e.target.closest('.drag-handle');
      if (!handle) { e.preventDefault(); return; }
      dragging = handle.closest('tr');
      dragging.classList.add('is-dragging');
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', dragging.dataset.codigo || '');
    });

    tbody.addEventListener('dragover', function (e) {
      if (!dragging) return;
      e.preventDefault();
      var target = e.target.closest('tr');
      if (!target || target === dragging || target.parentNode !== tbody) return;
      var rect = target.getBoundingClientRect();
      var before = (e.clientY - rect.top) < rect.height / 2;
      tbody.insertBefore(dragging, before ? target : target.nextSibling);
    });

    tbody.addEventListener('dragend', function () {
      if (!dragging) return;
      dragging.classList.remove('is-dragging');
      var moved = dragging;
      dragging = null;
      persistOrder(moved.closest('table'));
    });
  });
})();
</script>

</body>
</html>
