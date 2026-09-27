<?php
require __DIR__ . '/auth.php';
require_login();
require __DIR__ . '/site-generator.php';
require __DIR__ . '/helpers.php';

$tipo = ($_GET['tipo'] ?? '') === 'lote' ? 'lote' : 'casa';
$tipoPlural = $tipo === 'casa' ? 'casas' : 'lotes';
$codigoParam = trim($_GET['codigo'] ?? '');

$record = null;
if ($codigoParam !== '') {
  $table = $tipo === 'casa' ? 'casas' : 'lotes';
  $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE codigo = ? LIMIT 1");
  $stmt->execute([$codigoParam]);
  $row = $stmt->fetch();
  if ($row) {
    $record = $tipo === 'casa' ? casa_row_to_array($row) : lote_row_to_array($row);
    $record['id'] = (int) $row['id'];
  }
}
$isEdit = $record !== null;

$defaults = [
  'status' => 'disponivel',
  'ordem' => ($tipo === 'casa' ? count(fetch_all_casas($pdo)) : count(fetch_all_lotes($pdo))) + 1,
  'destaque' => false,
  'descricao' => $tipo === 'casa' ? 'Projeto Residencial - Condomínio Terras de Gênova' : 'Loteamento fechado Terras de Gênova',
  'localizacao' => $tipo === 'casa' ? 'Condomínio Terras de Gênova, Jundiaí/SP' : 'Loteamento Terras de Gênova, Jundiaí/SP',
  'condominio' => 'Terras de Gênova',
  'topografia' => 'plano',
];

function val($record, $defaults, $key, $fallback = '') {
  if ($record !== null && array_key_exists($key, $record)) {
    return $record[$key] ?? $fallback;
  }
  return $defaults[$key] ?? $fallback;
}

$existingImages = $isEdit ? $record['imagens']['arquivos'] : [];
$imagensPasta = $isEdit ? $record['imagens']['pasta'] : "img/{$tipoPlural}/";
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
    </div>
    <div class="admin-topbar__actions">
      <a href="index.php" class="btn btn--ghost">← Voltar</a>
    </div>
  </header>

  <main class="admin-main">
    <form method="post" action="save.php" enctype="multipart/form-data" class="edit-form edit-form--page">
      <h2><?= $isEdit ? 'Editar imóvel — ' . h($record['codigo']) : 'Novo ' . ($tipo === 'casa' ? 'imóvel (casa)' : 'terreno') ?></h2>

      <input type="hidden" name="tipo" value="<?= h($tipo) ?>">
      <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int) $record['id'] ?>"><?php endif; ?>

      <div class="edit-form__grid">
        <div class="form-field">
          <label for="f-codigo">Código *</label>
          <input type="text" id="f-codigo" name="codigo" required placeholder="Ex: F08"
                 value="<?= $isEdit ? h($record['codigo']) : '' ?>" <?= $isEdit ? 'readonly' : '' ?>>
          <?php if ($isEdit): ?><p class="form-hint">O código não pode ser alterado depois de criado (evita desalinhar a pasta de fotos).</p><?php endif; ?>
        </div>
        <div class="form-field">
          <label for="f-slug">Slug (URL)</label>
          <input type="text" id="f-slug" name="slug" placeholder="gerado a partir do código"
                 value="<?= $isEdit ? h($record['slug']) : '' ?>">
        </div>
        <div class="form-field">
          <label for="f-status">Status</label>
          <select id="f-status" name="status">
            <?php foreach (['disponivel' => 'Disponível', 'reservada' => 'Reservada', 'vendida' => 'Vendida', 'indisponivel' => 'Indisponível'] as $v => $label): ?>
              <option value="<?= $v ?>" <?= val($record, $defaults, 'status') === $v ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-field">
          <label for="f-ordem">Ordem de exibição</label>
          <input type="number" id="f-ordem" name="ordem" min="1" value="<?= h(val($record, $defaults, 'ordem')) ?>">
        </div>
        <div class="form-field form-field--checkbox">
          <label><input type="checkbox" name="destaque" <?= val($record, $defaults, 'destaque') ? 'checked' : '' ?>> Destaque na home</label>
        </div>
      </div>

      <div class="form-field">
        <label for="f-titulo">Título *</label>
        <input type="text" id="f-titulo" name="titulo" required value="<?= $isEdit ? h($record['titulo']) : '' ?>">
      </div>

      <div class="form-field">
        <label for="f-descricao">Descrição curta</label>
        <input type="text" id="f-descricao" name="descricao" value="<?= h(val($record, $defaults, 'descricao')) ?>">
      </div>

      <div class="form-field">
        <label for="f-localizacao">Localização</label>
        <input type="text" id="f-localizacao" name="localizacao" value="<?= h(val($record, $defaults, 'localizacao')) ?>">
      </div>

      <div class="form-field">
        <label for="f-condominio">Condomínio</label>
        <input type="text" id="f-condominio" name="condominio" value="<?= h(val($record, $defaults, 'condominio')) ?>">
      </div>

      <?php if ($tipo === 'casa'): ?>
        <div class="edit-form__grid">
          <div class="form-field">
            <label for="f-areaTerreno">Área do terreno (m²)</label>
            <input type="number" id="f-areaTerreno" name="areaTerreno" step="0.01" value="<?= $isEdit ? h($record['areaTerreno']) : '' ?>">
          </div>
          <div class="form-field">
            <label for="f-areaConstruida">Área construída (m²)</label>
            <input type="number" id="f-areaConstruida" name="areaConstruida" step="0.01" value="<?= $isEdit ? h($record['areaConstruida']) : '' ?>">
          </div>
          <div class="form-field">
            <label for="f-quartos">Quartos</label>
            <input type="number" id="f-quartos" name="quartos" min="0" value="<?= $isEdit ? h($record['quartos']) : '' ?>">
          </div>
          <div class="form-field">
            <label for="f-suites">Suítes</label>
            <input type="number" id="f-suites" name="suites" min="0" value="<?= $isEdit ? h($record['suites']) : '' ?>">
          </div>
          <div class="form-field">
            <label for="f-banheiros">Banheiros</label>
            <input type="number" id="f-banheiros" name="banheiros" min="0" value="<?= $isEdit ? h($record['banheiros']) : '' ?>">
          </div>
          <div class="form-field">
            <label for="f-vagas">Vagas</label>
            <input type="number" id="f-vagas" name="vagas" min="0" value="<?= $isEdit ? h($record['vagas']) : '' ?>">
          </div>
        </div>
        <div class="form-field">
          <label for="f-caracteristicas">Características (uma por linha)</label>
          <textarea id="f-caracteristicas" name="caracteristicas" rows="5"><?= $isEdit ? h(implode("\n", $record['caracteristicas'])) : '' ?></textarea>
        </div>
      <?php else: ?>
        <div class="edit-form__grid">
          <div class="form-field">
            <label for="f-area">Área (m²)</label>
            <input type="number" id="f-area" name="area" step="0.01" value="<?= $isEdit ? h($record['area']) : '' ?>">
          </div>
          <div class="form-field">
            <label for="f-quadra">Quadra</label>
            <input type="text" id="f-quadra" name="quadra" value="<?= $isEdit ? h($record['quadra']) : '' ?>">
          </div>
          <div class="form-field">
            <label for="f-numeroLote">Número do lote</label>
            <input type="number" id="f-numeroLote" name="numeroLote" min="1" value="<?= $isEdit ? h($record['numeroLote']) : '' ?>">
          </div>
          <div class="form-field">
            <label for="f-topografia">Topografia</label>
            <select id="f-topografia" name="topografia">
              <?php foreach (['plano' => 'Plano', 'declive' => 'Declive', 'aclive' => 'Aclive'] as $v => $label): ?>
                <option value="<?= $v ?>" <?= val($record, $defaults, 'topografia') === $v ? 'selected' : '' ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      <?php endif; ?>

      <div class="form-field">
        <label>Fotos</label>
        <div id="image-list" class="image-list">
          <?php foreach ($existingImages as $file): ?>
            <div class="image-list__item" data-filename="<?= h($file) ?>">
              <div class="image-list__media">
                <img src="/<?= h($imagensPasta) ?>/<?= h($file) ?>" alt="" draggable="false">
              </div>
              <div class="image-list__toolbar">
                <button type="button" class="image-list__nav move-left" title="Mover para trás" aria-label="Mover foto para trás">
                  <svg class="icon" width="14" height="14" aria-hidden="true"><use href="/img/site/icons.svg#chevron-left"></use></svg>
                </button>
                <span class="image-list__drag-handle" draggable="true" title="Arrastar para reordenar" aria-hidden="true">⠿</span>
                <button type="button" class="image-list__nav move-right" title="Mover para frente" aria-label="Mover foto para frente">
                  <svg class="icon" width="14" height="14" aria-hidden="true"><use href="/img/site/icons.svg#chevron-right"></use></svg>
                </button>
              </div>
              <label class="image-list__remove"><input type="checkbox" name="remove_images[]" value="<?= h($file) ?>"> Remover</label>
            </div>
          <?php endforeach; ?>
        </div>
        <input type="hidden" name="images_order" id="images-order" value="<?= h(implode(',', $existingImages)) ?>">
        <input type="file" id="f-new-images" name="new_images[]" accept="image/*" multiple>
        <p class="form-hint">A primeira foto da lista é a capa usada nos cards e no compartilhamento. Use as setas ← → para reordenar as fotos já existentes. Fotos marcadas "Remover" são apagadas do servidor ao salvar. Novas fotos entram sempre no final, na ordem escolhida no seletor de arquivos.</p>
      </div>

      <div class="edit-form__actions">
        <a href="index.php" class="btn btn--ghost">Cancelar</a>
        <button type="submit" class="btn btn--primary">Salvar imóvel</button>
      </div>
    </form>
  </main>
</div>

<script>
(function () {
  var codigoInput = document.getElementById('f-codigo');
  var slugInput = document.getElementById('f-slug');
  var isEdit = <?= $isEdit ? 'true' : 'false' ?>;
  function slugify(str) {
    return String(str || '')
      .toLowerCase()
      .normalize('NFD').replace(/[̀-ͯ]/g, '')
      .replace(/[^a-z0-9]+/g, '')
      .trim();
  }
  if (!isEdit && codigoInput && slugInput) {
    slugInput.dataset.auto = 'true';
    codigoInput.addEventListener('input', function () {
      if (slugInput.dataset.auto !== 'false') {
        slugInput.value = slugify(codigoInput.value);
      }
    });
    slugInput.addEventListener('input', function () { slugInput.dataset.auto = 'false'; });
  }

  var list = document.getElementById('image-list');
  var orderInput = document.getElementById('images-order');
  if (list && orderInput) {
    function updateOrder() {
      var items = Array.from(list.querySelectorAll('.image-list__item'));
      orderInput.value = items.map(function (it) { return it.dataset.filename; }).join(',');
      items.forEach(function (it, i) {
        var badge = it.querySelector('.image-list__badge');
        if (i === 0 && !badge) {
          badge = document.createElement('span');
          badge.className = 'image-list__badge';
          badge.textContent = 'capa';
          it.prepend(badge);
        } else if (i !== 0 && badge) {
          badge.remove();
        }
        it.querySelector('.move-left').disabled = i === 0;
        it.querySelector('.move-right').disabled = i === items.length - 1;
      });
    }
    list.addEventListener('click', function (e) {
      var btn = e.target.closest('button');
      if (!btn) return;
      var item = btn.closest('.image-list__item');
      if (btn.classList.contains('move-left') && item.previousElementSibling) {
        list.insertBefore(item, item.previousElementSibling);
      } else if (btn.classList.contains('move-right') && item.nextElementSibling) {
        list.insertBefore(item.nextElementSibling, item);
      }
      updateOrder();
    });

    // Arrastar e soltar (além das setas) pra reordenar as fotos. A grade
    // quebra linha, então a posição de destino é decidida pelo item mais
    // próximo do ponteiro (distância até o centro), não só pelo eixo Y.
    var dragging = null;
    function closestItem(x, y) {
      var items = Array.from(list.querySelectorAll('.image-list__item')).filter(function (it) { return it !== dragging; });
      var best = null;
      var bestDist = Infinity;
      items.forEach(function (it) {
        var r = it.getBoundingClientRect();
        var cx = r.left + r.width / 2;
        var cy = r.top + r.height / 2;
        var dist = Math.hypot(x - cx, y - cy);
        if (dist < bestDist) { bestDist = dist; best = it; }
      });
      if (!best) return null;
      var r = best.getBoundingClientRect();
      var sameRow = y >= r.top && y <= r.bottom;
      var before = sameRow ? x < r.left + r.width / 2 : y < r.top + r.height / 2;
      return { el: best, before: before };
    }
    list.addEventListener('dragstart', function (e) {
      var handle = e.target.closest('.image-list__drag-handle');
      if (!handle) { e.preventDefault(); return; }
      dragging = handle.closest('.image-list__item');
      dragging.classList.add('is-dragging');
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', dragging.dataset.filename || '');
    });
    list.addEventListener('dragover', function (e) {
      if (!dragging) return;
      e.preventDefault();
      var drop = closestItem(e.clientX, e.clientY);
      if (!drop) return;
      list.insertBefore(dragging, drop.before ? drop.el : drop.el.nextSibling);
    });
    list.addEventListener('dragend', function () {
      if (!dragging) return;
      dragging.classList.remove('is-dragging');
      dragging = null;
      updateOrder();
    });

    updateOrder();
  }
})();
</script>

</body>
</html>
