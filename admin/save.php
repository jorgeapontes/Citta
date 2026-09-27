<?php
require __DIR__ . '/auth.php';
require_login();
require __DIR__ . '/site-generator.php';
require __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: index.php');
  exit;
}

function fail(string $message): void {
  http_response_code(400);
  echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>Erro</title>'
    . '<link rel="stylesheet" href="/css/base/tokens.css"><link rel="stylesheet" href="/css/base/reset.css">'
    . '<link rel="stylesheet" href="/css/base/base.css"><link rel="stylesheet" href="/css/components.css">'
    . '<link rel="stylesheet" href="admin.css"></head><body class="admin"><div class="gate"><div class="gate__box">'
    . '<h1>Não foi possível salvar</h1><p class="gate__warning">' . h($message) . '</p>'
    . '<p><a class="btn btn--primary" href="javascript:history.back()">Voltar</a></p>'
    . '</div></div></body></html>';
  exit;
}

function num_or_null($v) {
  if ($v === null || $v === '') return null;
  return is_numeric($v) ? $v + 0 : null;
}

$tipo = ($_POST['tipo'] ?? '') === 'lote' ? 'lote' : 'casa';
$tipoPlural = $tipo === 'casa' ? 'casas' : 'lotes';
$table = $tipoPlural;
$id = isset($_POST['id']) && $_POST['id'] !== '' ? (int) $_POST['id'] : null;

$original = null;
if ($id !== null) {
  $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE id = ? LIMIT 1");
  $stmt->execute([$id]);
  $original = $stmt->fetch();
  if (!$original) fail('Imóvel não encontrado (id inválido).');
}

// Código nunca muda numa edição (o campo é somente leitura no formulário) —
// usar sempre o valor já salvo evita desalinhar a pasta de fotos no disco.
$codigo = $original ? $original['codigo'] : strtoupper(trim($_POST['codigo'] ?? ''));
if ($codigo === '') fail('Código é obrigatório.');

$titulo = trim($_POST['titulo'] ?? '');
if ($titulo === '') fail('Título é obrigatório.');

$slug = trim($_POST['slug'] ?? '');
if ($slug === '') $slug = slugify($codigo);

// Duplicidade de código só importa ao criar (na edição o código é fixo).
if (!$original) {
  $stmt = $pdo->prepare("SELECT id FROM {$table} WHERE codigo = ? LIMIT 1");
  $stmt->execute([$codigo]);
  if ($stmt->fetch()) fail("Já existe um imóvel com o código {$codigo}.");
}
// Slug também precisa ser único (gera a URL da página).
$stmt = $pdo->prepare("SELECT id FROM {$table} WHERE slug = ? AND id <> ? LIMIT 1");
$stmt->execute([$slug, $id ?? 0]);
if ($stmt->fetch()) fail("Já existe um imóvel com o slug \"{$slug}\".");

$pasta = "img/{$tipoPlural}/{$codigo}";
$pastaFull = dirname(__DIR__) . '/' . $pasta;

/* -------------------------------------------------------------------------- */
/* Fotos: ordem/remoção das existentes + upload das novas                     */
/* -------------------------------------------------------------------------- */
$orderList = array_values(array_filter(array_map('trim', explode(',', $_POST['images_order'] ?? ''))));
$removeSet = array_flip($_POST['remove_images'] ?? []);
$keptExisting = array_values(array_filter($orderList, fn($f) => !isset($removeSet[$f])));

foreach (array_keys($removeSet) as $filename) {
  $path = $pastaFull . '/' . basename($filename);
  if (is_file($path)) @unlink($path);
}

$newFilenames = [];
if (!empty($_FILES['new_images']) && is_array($_FILES['new_images']['tmp_name'])) {
  if (!is_dir($pastaFull)) @mkdir($pastaFull, 0775, true);
  $count = count($_FILES['new_images']['tmp_name']);
  for ($i = 0; $i < $count; $i++) {
    if ($_FILES['new_images']['error'][$i] !== UPLOAD_ERR_OK) continue;
    $filename = safe_filename($_FILES['new_images']['name'][$i]);
    $dest = $pastaFull . '/' . $filename;
    $attempt = 1;
    $base = pathinfo($filename, PATHINFO_FILENAME);
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    while (is_file($dest) || in_array($filename, $keptExisting, true) || in_array($filename, $newFilenames, true)) {
      $attempt++;
      $filename = $ext ? "{$base}-{$attempt}.{$ext}" : "{$base}-{$attempt}";
      $dest = $pastaFull . '/' . $filename;
    }
    if (move_uploaded_file($_FILES['new_images']['tmp_name'][$i], $dest)) {
      $newFilenames[] = $filename;
    }
  }
}

$finalImages = array_merge($keptExisting, $newFilenames);
if (!$finalImages) fail('Adicione pelo menos uma foto.');

$dims = @getimagesize($pastaFull . '/' . $finalImages[0]);
$defaultW = $tipo === 'casa' ? 640 : 480;
$defaultH = $tipo === 'casa' ? 480 : 270;
$largura = $dims ? $dims[0] : $defaultW;
$altura = $dims ? $dims[1] : $defaultH;

/* -------------------------------------------------------------------------- */
/* Campos comuns                                                              */
/* -------------------------------------------------------------------------- */
$status = in_array($_POST['status'] ?? '', ['disponivel', 'reservada', 'vendida', 'indisponivel'], true)
  ? $_POST['status'] : 'disponivel';
$destaque = isset($_POST['destaque']) ? 1 : 0;
$ordem = (int) ($_POST['ordem'] ?? 1);
if ($ordem < 1) $ordem = 1;
$descricao = trim($_POST['descricao'] ?? '');
$localizacao = trim($_POST['localizacao'] ?? '');
$condominio = trim($_POST['condominio'] ?? '');
$imagensArquivosJson = json_encode($finalImages, JSON_UNESCAPED_UNICODE);

if ($tipo === 'casa') {
  $areaTerreno = num_or_null($_POST['areaTerreno'] ?? null);
  $areaConstruida = num_or_null($_POST['areaConstruida'] ?? null);
  $quartos = num_or_null($_POST['quartos'] ?? null);
  $suites = num_or_null($_POST['suites'] ?? null);
  $banheiros = num_or_null($_POST['banheiros'] ?? null);
  $vagas = num_or_null($_POST['vagas'] ?? null);
  $caracteristicas = array_values(array_filter(array_map('trim', explode("\n", $_POST['caracteristicas'] ?? ''))));
  $caracteristicasJson = json_encode($caracteristicas, JSON_UNESCAPED_UNICODE);

  if ($original) {
    $stmt = $pdo->prepare("
      UPDATE casas SET slug=:slug, status=:status, destaque=:destaque, ordem=:ordem, titulo=:titulo,
        descricao=:descricao, localizacao=:localizacao, condominio=:condominio,
        area_terreno=:area_terreno, area_construida=:area_construida, quartos=:quartos, suites=:suites,
        banheiros=:banheiros, vagas=:vagas, caracteristicas=:caracteristicas,
        imagens_pasta=:imagens_pasta, imagens_largura=:imagens_largura, imagens_altura=:imagens_altura,
        imagens_arquivos=:imagens_arquivos
      WHERE id=:id
    ");
    $params = [':id' => $id];
  } else {
    $stmt = $pdo->prepare("
      INSERT INTO casas
        (codigo, slug, status, destaque, ordem, titulo, descricao, localizacao, condominio,
         area_terreno, area_construida, quartos, suites, banheiros, vagas, caracteristicas,
         imagens_pasta, imagens_largura, imagens_altura, imagens_arquivos)
      VALUES
        (:codigo, :slug, :status, :destaque, :ordem, :titulo, :descricao, :localizacao, :condominio,
         :area_terreno, :area_construida, :quartos, :suites, :banheiros, :vagas, :caracteristicas,
         :imagens_pasta, :imagens_largura, :imagens_altura, :imagens_arquivos)
    ");
    $params = [':codigo' => $codigo];
  }
  $params += [
    ':slug' => $slug, ':status' => $status, ':destaque' => $destaque, ':ordem' => $ordem,
    ':titulo' => $titulo, ':descricao' => $descricao, ':localizacao' => $localizacao, ':condominio' => $condominio,
    ':area_terreno' => $areaTerreno, ':area_construida' => $areaConstruida, ':quartos' => $quartos,
    ':suites' => $suites, ':banheiros' => $banheiros, ':vagas' => $vagas, ':caracteristicas' => $caracteristicasJson,
    ':imagens_pasta' => $pasta, ':imagens_largura' => $largura, ':imagens_altura' => $altura,
    ':imagens_arquivos' => $imagensArquivosJson,
  ];
  $stmt->execute($params);
} else {
  $area = num_or_null($_POST['area'] ?? null);
  $quadra = trim($_POST['quadra'] ?? '');
  $numeroLote = num_or_null($_POST['numeroLote'] ?? null);
  $topografia = in_array($_POST['topografia'] ?? '', ['plano', 'declive', 'aclive'], true)
    ? $_POST['topografia'] : 'plano';

  if ($original) {
    $stmt = $pdo->prepare("
      UPDATE lotes SET slug=:slug, status=:status, destaque=:destaque, ordem=:ordem, titulo=:titulo,
        descricao=:descricao, localizacao=:localizacao, condominio=:condominio,
        area=:area, quadra=:quadra, numero_lote=:numero_lote, topografia=:topografia,
        imagens_pasta=:imagens_pasta, imagens_largura=:imagens_largura, imagens_altura=:imagens_altura,
        imagens_arquivos=:imagens_arquivos
      WHERE id=:id
    ");
    $params = [':id' => $id];
  } else {
    $stmt = $pdo->prepare("
      INSERT INTO lotes
        (codigo, slug, status, destaque, ordem, titulo, descricao, localizacao, condominio,
         area, quadra, numero_lote, topografia,
         imagens_pasta, imagens_largura, imagens_altura, imagens_arquivos)
      VALUES
        (:codigo, :slug, :status, :destaque, :ordem, :titulo, :descricao, :localizacao, :condominio,
         :area, :quadra, :numero_lote, :topografia,
         :imagens_pasta, :imagens_largura, :imagens_altura, :imagens_arquivos)
    ");
    $params = [':codigo' => $codigo];
  }
  $params += [
    ':slug' => $slug, ':status' => $status, ':destaque' => $destaque, ':ordem' => $ordem,
    ':titulo' => $titulo, ':descricao' => $descricao, ':localizacao' => $localizacao, ':condominio' => $condominio,
    ':area' => $area, ':quadra' => $quadra, ':numero_lote' => $numeroLote, ':topografia' => $topografia,
    ':imagens_pasta' => $pasta, ':imagens_largura' => $largura, ':imagens_altura' => $altura,
    ':imagens_arquivos' => $imagensArquivosJson,
  ];
  $stmt->execute($params);
}

regenerate_site($pdo);

header('Location: index.php');
exit;
