<?php
/** Helpers compartilhados por form.php / save.php. */

function slugify($str) {
  $str = (string) $str;
  $translit = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
  if ($translit !== false) $str = $translit;
  $str = strtolower($str);
  $str = preg_replace('/[^a-z0-9]+/', '', $str);
  return $str;
}

/**
 * Nome de arquivo seguro para salvar em disco. Ao contrário de slugify()
 * (usada para código/slug do imóvel, sem separadores), aqui hífen e
 * underscore são preservados — é assim que as fotos já existentes são
 * nomeadas (ex: projeto-de-construcao-de-casa-f08-01.jpg).
 */
function safe_filename($originalName) {
  $originalName = basename($originalName);
  $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
  $base = pathinfo($originalName, PATHINFO_FILENAME);
  $translit = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $base);
  if ($translit !== false) $base = $translit;
  $base = strtolower($base);
  $base = preg_replace('/[^a-z0-9._-]+/', '-', $base);
  $base = trim($base, '-');
  if ($base === '') $base = 'foto';
  $ext = preg_replace('/[^a-z0-9]/', '', $ext);
  return $ext ? "{$base}.{$ext}" : $base;
}

function h($str) {
  return htmlspecialchars((string) ($str ?? ''), ENT_QUOTES, 'UTF-8');
}
