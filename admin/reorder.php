<?php
/**
 * Persiste a nova ordem de exibição (drag-and-drop na listagem do
 * dashboard). Recebe { tipo: 'casa'|'lote', order: [codigo, ...] } via
 * JSON e grava ordem = posição (1-based) para cada código.
 */
require __DIR__ . '/auth.php';
require_login();
require __DIR__ . '/site-generator.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$tipo = (is_array($input) && ($input['tipo'] ?? '') === 'lote') ? 'lote' : 'casa';
$tipoPlural = $tipo === 'casa' ? 'casas' : 'lotes';
$order = is_array($input['order'] ?? null) ? $input['order'] : [];

if (!$order) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'empty_order']);
  exit;
}

$stmt = $pdo->prepare("UPDATE {$tipoPlural} SET ordem = ? WHERE codigo = ?");
foreach ($order as $i => $codigo) {
  $stmt->execute([$i + 1, trim((string) $codigo)]);
}

regenerate_site($pdo);

echo json_encode(['ok' => true]);
