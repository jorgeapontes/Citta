<?php
/**
 * Busca casas/lotes no banco e (re)gera os arquivos estáticos do site
 * público — equivalente ao antigo tools/generate.js, mas lendo do MySQL em
 * vez de data/*.json. Usado pelo botão "Gerar site" do dashboard e chamado
 * automaticamente depois de salvar/excluir um imóvel, para o site nunca
 * ficar desatualizado em relação ao banco.
 */
require_once __DIR__ . '/render.php';

function casa_row_to_array(array $row): array {
  return [
    'codigo' => $row['codigo'],
    'slug' => $row['slug'],
    'tipo' => 'casa',
    'status' => $row['status'],
    'destaque' => (bool) $row['destaque'],
    'ordem' => (int) $row['ordem'],
    'titulo' => $row['titulo'],
    'descricao' => $row['descricao'],
    'localizacao' => $row['localizacao'],
    'condominio' => $row['condominio'],
    'areaTerreno' => $row['area_terreno'] !== null ? (float) $row['area_terreno'] : null,
    'areaConstruida' => $row['area_construida'] !== null ? (float) $row['area_construida'] : null,
    'quartos' => $row['quartos'] !== null ? (int) $row['quartos'] : null,
    'suites' => $row['suites'] !== null ? (int) $row['suites'] : null,
    'banheiros' => $row['banheiros'] !== null ? (int) $row['banheiros'] : null,
    'vagas' => $row['vagas'] !== null ? (int) $row['vagas'] : null,
    'caracteristicas' => json_decode($row['caracteristicas'] ?? '[]', true) ?: [],
    'imagens' => [
      'pasta' => $row['imagens_pasta'],
      'largura' => (int) $row['imagens_largura'],
      'altura' => (int) $row['imagens_altura'],
      'arquivos' => json_decode($row['imagens_arquivos'], true) ?: [],
    ],
  ];
}

function lote_row_to_array(array $row): array {
  return [
    'codigo' => $row['codigo'],
    'slug' => $row['slug'],
    'tipo' => 'lote',
    'status' => $row['status'],
    'destaque' => (bool) $row['destaque'],
    'ordem' => (int) $row['ordem'],
    'titulo' => $row['titulo'],
    'descricao' => $row['descricao'],
    'localizacao' => $row['localizacao'],
    'condominio' => $row['condominio'],
    'area' => $row['area'] !== null ? (float) $row['area'] : null,
    'quadra' => $row['quadra'],
    'numeroLote' => $row['numero_lote'] !== null ? (int) $row['numero_lote'] : null,
    'topografia' => $row['topografia'],
    'imagens' => [
      'pasta' => $row['imagens_pasta'],
      'largura' => (int) $row['imagens_largura'],
      'altura' => (int) $row['imagens_altura'],
      'arquivos' => json_decode($row['imagens_arquivos'], true) ?: [],
    ],
  ];
}

function fetch_all_casas(PDO $pdo): array {
  $rows = $pdo->query('SELECT * FROM casas ORDER BY ordem ASC')->fetchAll();
  return array_map('casa_row_to_array', $rows);
}

function fetch_all_lotes(PDO $pdo): array {
  $rows = $pdo->query('SELECT * FROM lotes ORDER BY ordem ASC')->fetchAll();
  return array_map('lote_row_to_array', $rows);
}

function site_write_file(string $relPath, string $content): void {
  $full = dirname(__DIR__) . '/' . $relPath;
  @mkdir(dirname($full), 0775, true);
  file_put_contents($full, $content);
}

/**
 * Regenera todo o site estático (páginas de detalhe, listagens e sitemap)
 * a partir do estado atual do banco. Retorna contagem para log/mensagem.
 */
function regenerate_site(PDO $pdo): array {
  $casas = fetch_all_casas($pdo);
  $lotes = fetch_all_lotes($pdo);

  foreach ($casas as $casa) {
    site_write_file("casas/{$casa['slug']}/index.html", render_casa_detail($casa));
  }
  foreach ($lotes as $lote) {
    site_write_file("lotes/{$lote['slug']}/index.html", render_lote_detail($lote));
  }
  site_write_file('casas/index.html', casas_listing_html($casas));
  site_write_file('lotes/index.html', lotes_listing_html($lotes));
  site_write_file('sitemap.xml', build_sitemap($casas, $lotes));

  return ['casas' => count($casas), 'lotes' => count($lotes)];
}

/**
 * Remove a página de detalhe gerada para um imóvel (usado ao excluir). As
 * fotos em img/casas|lotes/{CODIGO}/ NÃO são apagadas — ficam no servidor
 * até uma limpeza manual, para evitar perda de arquivos por engano.
 */
function remove_detail_page(string $tipoPlural, string $slug): void {
  $dir = dirname(__DIR__) . "/{$tipoPlural}/{$slug}";
  $index = $dir . '/index.html';
  if (is_file($index)) @unlink($index);
  if (is_dir($dir)) @rmdir($dir); // só remove se ficou vazia
}
