<?php
require __DIR__ . '/auth.php';
require_login();
require __DIR__ . '/site-generator.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: index.php');
  exit;
}

$tipo = ($_POST['tipo'] ?? '') === 'lote' ? 'lote' : 'casa';
$tipoPlural = $tipo === 'casa' ? 'casas' : 'lotes';
$codigo = trim($_POST['codigo'] ?? '');

if ($codigo !== '') {
  $stmt = $pdo->prepare("SELECT slug FROM {$tipoPlural} WHERE codigo = ? LIMIT 1");
  $stmt->execute([$codigo]);
  $row = $stmt->fetch();
  if ($row) {
    $del = $pdo->prepare("DELETE FROM {$tipoPlural} WHERE codigo = ?");
    $del->execute([$codigo]);
    remove_detail_page($tipoPlural, $row['slug']);
    regenerate_site($pdo);
  }
}

header('Location: index.php?deleted=1');
exit;
