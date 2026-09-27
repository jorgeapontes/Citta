<?php
/**
 * Núcleo de renderização das páginas de imóveis — porta para PHP de
 * tools/render-core.js (removido; ver histórico do Git). Gera exatamente o
 * mesmo HTML que o site sempre teve; a diferença é que agora os dados vêm do
 * banco (via admin/site-generator.php) em vez de data/*.json.
 *
 * Todo valor interpolado é resolvido para uma variável simples ANTES de cada
 * heredoc — heredoc em PHP só interpola variáveis, não chamadas de função.
 */

const SITE_URL = 'https://condominiosemjundiai.com.br'; // TODO: confirmar domínio final de produção

const STATUS_LABEL = [
  'disponivel' => 'Disponível',
  'vendida' => 'Vendida',
  'reservada' => 'Reservada',
  'indisponivel' => 'Indisponível',
];

const TOPO_LABEL = [
  'plano' => 'Terreno plano',
  'declive' => 'Terreno com declive',
  'aclive' => 'Terreno com aclive',
];

function esc($str) {
  return str_replace(
    ['&', '<', '>', '"', "'"],
    ['&amp;', '&lt;', '&gt;', '&quot;', '&#39;'],
    (string) ($str ?? '')
  );
}

function fmt_area($n) {
  if ($n === null || $n === '') return null;
  $f = (float) $n;
  $s = rtrim(rtrim(sprintf('%.10f', $f), '0'), '.');
  if ($s === '' || $s === '-0') $s = '0';
  return str_replace('.', ',', $s) . 'm²';
}

/** Espelha o encodeURIComponent do JS (que não escapa ! ' ( ) *), diferente do rawurlencode do PHP (RFC 3986). */
function js_encode_uri_component($str) {
  $encoded = rawurlencode($str);
  return strtr($encoded, ['%21' => '!', '%27' => "'", '%28' => '(', '%29' => ')', '%2A' => '*']);
}

function status_badge($status) {
  $label = STATUS_LABEL[$status] ?? $status;
  $s = esc($status);
  $l = esc($label);
  return "<span class=\"status-badge status-badge--{$s}\">{$l}</span>";
}

/* -------------------------------------------------------------------------- */
/* Shell de página — head + header/footer includes compartilhados            */
/* -------------------------------------------------------------------------- */
function page_shell($title, $description, $canonicalPath, $ogImage, $ogType, $bodyContent, $jsonLd = null, $extraHead = '') {
  $canonical = SITE_URL . $canonicalPath;
  $image = $ogImage ? SITE_URL . $ogImage : SITE_URL . '/img/site/og-default.jpg';
  $titleEsc = esc($title);
  $descEsc = esc($description);
  $jsonLdScript = $jsonLd !== null
    ? '<script type="application/ld+json">' . json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</script>\n"
    : '';

  return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$titleEsc}</title>
<meta name="description" content="{$descEsc}">
<link rel="canonical" href="{$canonical}">
<meta property="og:type" content="{$ogType}">
<meta property="og:title" content="{$titleEsc}">
<meta property="og:description" content="{$descEsc}">
<meta property="og:url" content="{$canonical}">
<meta property="og:image" content="{$image}">
<meta property="og:locale" content="pt_BR">
<meta property="og:site_name" content="Città Empreendimentos Imobiliários">
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#025490">
<link rel="icon" href="/img/site/favicon-32.png" type="image/png" sizes="32x32">
<link rel="apple-touch-icon" href="/img/site/favicon-180.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/base/tokens.css">
<link rel="stylesheet" href="/css/base/reset.css">
<link rel="stylesheet" href="/css/base/base.css">
<link rel="stylesheet" href="/css/components.css">
<link rel="stylesheet" href="/css/animations.css">
{$extraHead}{$jsonLdScript}</head>
<body>
<a class="skip-link" href="#main">Pular para o conteúdo</a>
<div data-include="/components/header.html"></div>
<main id="main">
{$bodyContent}
</main>
<div data-include="/components/footer.html"></div>
<script src="/js/core/include.js" defer></script>
<script src="/js/core/menu.js" defer></script>
<script src="/js/core/reveal.js" defer></script>
<script src="/js/core/gallery.js" defer></script>
<script src="/js/core/forms.js" defer></script>
</body>
</html>

HTML;
}

/* -------------------------------------------------------------------------- */
/* Galeria (markup estático, progressive enhancement via gallery.js)         */
/* -------------------------------------------------------------------------- */
function gallery_markup($imagens, $altBase) {
  $pasta = $imagens['pasta'];
  $largura = $imagens['largura'];
  $altura = $imagens['altura'];
  $arquivos = $imagens['arquivos'];
  $total = count($arquivos);

  $thumbs = '';
  foreach ($arquivos as $i => $file) {
    $url = "/{$pasta}/{$file}";
    $alt = "{$altBase} — foto " . ($i + 1) . " de {$total}";
    $urlEsc = esc($url);
    $altEsc = esc($alt);
    $current = $i === 0 ? 'true' : 'false';
    $loading = $i === 0 ? 'eager' : 'lazy';
    $thumbs .= <<<HTML
<button type="button" class="gallery__thumb" data-index="{$i}" data-full="{$urlEsc}" data-alt="{$altEsc}" aria-current="{$current}">
          <img src="{$urlEsc}" width="{$largura}" height="{$altura}" alt="" loading="{$loading}">
        </button>

HTML;
  }
  $thumbs = rtrim($thumbs);

  $first = "/{$pasta}/{$arquivos[0]}";
  $firstEsc = esc($first);
  $firstAltEsc = esc($altBase . ' — foto 1');
  $hasMultiple = $total > 1;

  $navButtons = '';
  $lightboxNav = '';
  $lightboxNavNext = '';
  if ($hasMultiple) {
    $navButtons = <<<HTML
<button type="button" class="gallery__nav gallery__nav--prev" data-gallery-prev aria-label="Foto anterior">
        <svg class="icon" width="20" height="20" aria-hidden="true"><use href="/img/site/icons.svg#chevron-left"></use></svg>
      </button>
      <button type="button" class="gallery__nav gallery__nav--next" data-gallery-next aria-label="Próxima foto">
        <svg class="icon" width="20" height="20" aria-hidden="true"><use href="/img/site/icons.svg#chevron-right"></use></svg>
      </button>
      <span class="gallery__counter" data-gallery-counter>1 / {$total}</span>
HTML;
    $lightboxNav = <<<HTML
<button type="button" class="gallery__nav gallery__nav--prev" data-lightbox-prev aria-label="Foto anterior">
      <svg class="icon" width="22" height="22" aria-hidden="true"><use href="/img/site/icons.svg#chevron-left"></use></svg>
    </button>
HTML;
    $lightboxNavNext = <<<HTML
<button type="button" class="gallery__nav gallery__nav--next" data-lightbox-next aria-label="Próxima foto">
      <svg class="icon" width="22" height="22" aria-hidden="true"><use href="/img/site/icons.svg#chevron-right"></use></svg>
    </button>
HTML;
  }

  $thumbsBlock = $hasMultiple ? "<div class=\"gallery__thumbs\" data-gallery-thumbs>\n        {$thumbs}\n    </div>" : '';

  return <<<HTML
<div class="gallery" data-gallery>
    <div class="gallery__main">
      <img data-gallery-main src="{$firstEsc}" width="{$largura}" height="{$altura}" alt="{$firstAltEsc}" loading="eager" fetchpriority="high">
      {$navButtons}
    </div>
    {$thumbsBlock}
  </div>
  <div class="lightbox" data-lightbox hidden>
    <button type="button" class="lightbox__close" data-lightbox-close aria-label="Fechar galeria">
      <svg class="icon" width="24" height="24" aria-hidden="true"><use href="/img/site/icons.svg#close"></use></svg>
    </button>
    {$lightboxNav}
    <img class="lightbox__img" data-lightbox-img src="{$firstEsc}" alt="">
    {$lightboxNavNext}
    <span class="lightbox__counter" data-lightbox-counter></span>
  </div>
HTML;
}

function spec_item($iconId, $label, $value) {
  if ($value === null) return '';
  $valueEsc = esc($value);
  $labelEsc = esc($label);
  return "<li><svg class=\"icon\" width=\"18\" height=\"18\" aria-hidden=\"true\"><use href=\"/img/site/icons.svg#{$iconId}\"></use></svg> <strong>{$valueEsc}</strong> {$labelEsc}</li>";
}

function breadcrumb_json_ld($items) {
  $list = [];
  foreach ($items as $i => $it) {
    $list[] = [
      '@type' => 'ListItem',
      'position' => $i + 1,
      'name' => $it['name'],
      'item' => SITE_URL . $it['path'],
    ];
  }
  return [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => $list,
  ];
}

/* -------------------------------------------------------------------------- */
/* Página de detalhe — Casa                                                   */
/* -------------------------------------------------------------------------- */
function render_casa_detail($casa) {
  $urlPath = "/casas/{$casa['slug']}/";
  $title = "{$casa['titulo']} | Città Empreendimentos";
  $description = "{$casa['descricao']} Terreno de " . fmt_area($casa['areaTerreno']) . ', área construída de ' . fmt_area($casa['areaConstruida']) . '. Localizado em Jundiaí/SP.';
  $altBase = $casa['titulo'];

  $specParts = [
    spec_item('ruler', 'de terreno', fmt_area($casa['areaTerreno'])),
    spec_item('ruler', 'construídos', fmt_area($casa['areaConstruida'])),
    spec_item('bed', $casa['quartos'] === 1 ? 'quarto' : 'quartos', $casa['quartos']),
    spec_item('key', $casa['suites'] === 1 ? 'suíte' : 'suítes', $casa['suites']),
    spec_item('bath', $casa['banheiros'] === 1 ? 'banheiro' : 'banheiros', $casa['banheiros']),
    spec_item('car', $casa['vagas'] === 1 ? 'vaga' : 'vagas', $casa['vagas']),
  ];
  $specs = implode("\n            ", array_filter($specParts, fn($s) => $s !== ''));

  if (!empty($casa['caracteristicas'])) {
    $items = '';
    foreach ($casa['caracteristicas'] as $c) {
      $cEsc = esc($c);
      $items .= "<li><svg class=\"icon\" width=\"16\" height=\"16\" aria-hidden=\"true\"><use href=\"/img/site/icons.svg#check\"></use></svg> {$cEsc}</li>\n        ";
    }
    $items = rtrim($items);
    $caracteristicas = <<<HTML
<section class="section" data-reveal>
      <h2>Características do projeto</h2>
      <ul class="feature-list">
        {$items}
      </ul>
    </section>
HTML;
  } else {
    $caracteristicas = '<!-- Características ainda não cadastradas para este imóvel -->';
  }

  $statusBadge = status_badge($casa['status']);
  $codigoEsc = esc($casa['codigo']);
  $tituloEsc = esc($casa['titulo']);
  $localizacaoEsc = esc($casa['localizacao']);
  $galeria = gallery_markup($casa['imagens'], $altBase);
  $descricaoEsc = esc($casa['descricao']);
  $whatsappMsg = js_encode_uri_component('Olá! Tenho interesse na Casa ' . $casa['codigo'] . ' (Terras de Gênova).');

  $body = <<<HTML

  <nav class="breadcrumb container" aria-label="Você está aqui">
    <ol style="display:flex;gap:.5rem;flex-wrap:wrap">
      <li><a href="/">Início</a></li>
      <li><a href="/casas/">Casas</a></li>
      <li aria-current="page">Lote {$codigoEsc}</li>
    </ol>
  </nav>

  <section class="container">
    <header style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem">
      {$statusBadge}
      <span class="property-card__code">Lote {$codigoEsc}</span>
    </header>
    <h1>{$tituloEsc}</h1>
    <p class="section__header" style="margin:.75rem 0 2rem"><svg class="icon" width="16" height="16" aria-hidden="true"><use href="/img/site/icons.svg#map-pin"></use></svg> {$localizacaoEsc}</p>

    {$galeria}

    <div class="grid-2" style="margin-top:3rem;align-items:start">
      <div>
        <h2>Sobre o imóvel</h2>
        <p>{$descricaoEsc}</p>
        <ul class="property-card__meta" style="margin-top:1.5rem;border:none;padding:0;font-size:1rem">
          {$specs}
        </ul>
      </div>
      <aside class="cta-box" data-reveal>
        <h3>Interessou pelo Lote {$codigoEsc}?</h3>
        <p>Fale agora com nossa equipe e agende uma visita.</p>
        <a class="btn btn--whatsapp btn--block" target="_blank" rel="noopener noreferrer"
           href="https://wa.me/5511997844233?text={$whatsappMsg}">
          Falar no WhatsApp
        </a>
        <a class="btn btn--outline btn--block" style="margin-top:.75rem" href="/contato/?imovel={$codigoEsc}">Enviar mensagem</a>
      </aside>
    </div>
  </section>

  {$caracteristicas}

  <section class="section section--alt" data-reveal>
    <div class="container" style="text-align:center">
      <h2>Veja outras opções</h2>
      <p class="section__header" style="margin:0 auto 2rem">Conheça outras casas disponíveis no Terras de Gênova.</p>
      <a class="btn btn--primary" href="/casas/">Ver todas as casas</a>
    </div>
  </section>

HTML;

  $jsonLd = [
    breadcrumb_json_ld([
      ['name' => 'Início', 'path' => '/'],
      ['name' => 'Casas', 'path' => '/casas/'],
      ['name' => "Lote {$casa['codigo']}", 'path' => $urlPath],
    ]),
  ];

  $ogImage = "/{$casa['imagens']['pasta']}/{$casa['imagens']['arquivos'][0]}";

  return page_shell($title, $description, $urlPath, $ogImage, 'website', $body, $jsonLd);
}

/* -------------------------------------------------------------------------- */
/* Página de detalhe — Lote                                                   */
/* -------------------------------------------------------------------------- */
function render_lote_detail($lote) {
  $urlPath = "/lotes/{$lote['slug']}/";
  $title = "{$lote['titulo']} | Città Empreendimentos";
  $topoLabel = TOPO_LABEL[$lote['topografia']] ?? $lote['topografia'];
  $description = 'Terreno de ' . fmt_area($lote['area']) . " na Quadra {$lote['quadra']}, Lote {$lote['numeroLote']}, no loteamento Terras de Gênova, Jundiaí/SP. {$topoLabel}.";
  $altBase = $lote['titulo'];

  $statusBadge = status_badge($lote['status']);
  $quadraEsc = esc($lote['quadra']);
  $numeroLoteEsc = esc($lote['numeroLote']);
  $tituloEsc = esc($lote['titulo']);
  $localizacaoEsc = esc($lote['localizacao']);
  $galeria = gallery_markup($lote['imagens'], $altBase);
  $areaFmt = fmt_area($lote['area']);
  $codigoEsc = esc($lote['codigo']);
  $whatsappMsg = js_encode_uri_component('Olá! Tenho interesse no terreno Quadra ' . $lote['quadra'] . ', Lote ' . $lote['numeroLote'] . ' (Terras de Gênova).');

  $body = <<<HTML

  <nav class="breadcrumb container" aria-label="Você está aqui">
    <ol style="display:flex;gap:.5rem;flex-wrap:wrap">
      <li><a href="/">Início</a></li>
      <li><a href="/lotes/">Terrenos</a></li>
      <li aria-current="page">Quadra {$quadraEsc}, Lote {$numeroLoteEsc}</li>
    </ol>
  </nav>

  <section class="container">
    <header style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem">
      {$statusBadge}
      <span class="property-card__code">Quadra {$quadraEsc} · Lote {$numeroLoteEsc}</span>
    </header>
    <h1>{$tituloEsc}</h1>
    <p class="section__header" style="margin:.75rem 0 2rem"><svg class="icon" width="16" height="16" aria-hidden="true"><use href="/img/site/icons.svg#map-pin"></use></svg> {$localizacaoEsc}</p>

    {$galeria}

    <div class="grid-2" style="margin-top:3rem;align-items:start">
      <div>
        <h2>Sobre o terreno</h2>
        <ul class="property-card__meta" style="margin-top:.5rem;border:none;padding:0;font-size:1rem">
          <li><svg class="icon" width="18" height="18" aria-hidden="true"><use href="/img/site/icons.svg#ruler"></use></svg> <strong>{$areaFmt}</strong> de área</li>
          <li><svg class="icon" width="18" height="18" aria-hidden="true"><use href="/img/site/icons.svg#tree"></use></svg> {$topoLabel}</li>
        </ul>
        <p style="margin-top:1.5rem">Imagens meramente ilustrativas.</p>
      </div>
      <aside class="cta-box" data-reveal>
        <h3>Interessou pelo terreno?</h3>
        <p>Fale agora com nossa equipe e saiba mais sobre este lote.</p>
        <a class="btn btn--whatsapp btn--block" target="_blank" rel="noopener noreferrer"
           href="https://wa.me/5511997844233?text={$whatsappMsg}">
          Falar no WhatsApp
        </a>
        <a class="btn btn--outline btn--block" style="margin-top:.75rem" href="/contato/?imovel={$codigoEsc}">Enviar mensagem</a>
      </aside>
    </div>
  </section>

  <section class="section section--alt" data-reveal>
    <div class="container" style="text-align:center">
      <h2>Veja outros terrenos</h2>
      <p class="section__header" style="margin:0 auto 2rem">Confira as demais opções disponíveis no Terras de Gênova.</p>
      <a class="btn btn--primary" href="/lotes/">Ver todos os terrenos</a>
    </div>
  </section>

HTML;

  $jsonLd = [
    breadcrumb_json_ld([
      ['name' => 'Início', 'path' => '/'],
      ['name' => 'Terrenos', 'path' => '/lotes/'],
      ['name' => "Quadra {$lote['quadra']}, Lote {$lote['numeroLote']}", 'path' => $urlPath],
    ]),
  ];

  $ogImage = "/{$lote['imagens']['pasta']}/{$lote['imagens']['arquivos'][0]}";

  return page_shell($title, $description, $urlPath, $ogImage, 'website', $body, $jsonLd);
}

/* -------------------------------------------------------------------------- */
/* Listagens                                                                   */
/* -------------------------------------------------------------------------- */
function casa_card($casa) {
  $cover = esc("/{$casa['imagens']['pasta']}/{$casa['imagens']['arquivos'][0]}");
  $tituloEsc = esc($casa['titulo']);
  $codigoEsc = esc($casa['codigo']);
  $slugEsc = esc($casa['slug']);
  $largura = $casa['imagens']['largura'];
  $altura = $casa['imagens']['altura'];
  $statusBadge = status_badge($casa['status']);
  $statusAttr = esc($casa['status']);
  $areaTerrenoFmt = fmt_area($casa['areaTerreno']);
  $areaConstruidaFmt = fmt_area($casa['areaConstruida']);

  return <<<HTML
<article class="property-card" data-status="{$statusAttr}">
    <div class="property-card__media">
      <img src="{$cover}" width="{$largura}" height="{$altura}" alt="{$tituloEsc}" loading="lazy">
      <span class="property-card__status">{$statusBadge}</span>
    </div>
    <div class="property-card__body">
      <span class="property-card__code">Lote {$codigoEsc}</span>
      <h3 class="property-card__title">{$tituloEsc}</h3>
      <ul class="property-card__meta">
        <li>{$areaTerrenoFmt} terreno</li>
        <li>{$areaConstruidaFmt} construídos</li>
      </ul>
      <a class="property-card__link" href="/casas/{$slugEsc}/" aria-label="Ver detalhes do Lote {$codigoEsc}"></a>
    </div>
  </article>
HTML;
}

function lote_card($lote) {
  $cover = esc("/{$lote['imagens']['pasta']}/{$lote['imagens']['arquivos'][0]}");
  $tituloEsc = esc($lote['titulo']);
  $quadraEsc = esc($lote['quadra']);
  $numeroLoteEsc = esc($lote['numeroLote']);
  $slugEsc = esc($lote['slug']);
  $largura = $lote['imagens']['largura'];
  $altura = $lote['imagens']['altura'];
  $statusBadge = status_badge($lote['status']);
  $statusAttr = esc($lote['status']);
  $areaFmt = fmt_area($lote['area']);
  $topoLabel = TOPO_LABEL[$lote['topografia']] ?? $lote['topografia'];

  return <<<HTML
<article class="property-card" data-status="{$statusAttr}">
    <div class="property-card__media">
      <img src="{$cover}" width="{$largura}" height="{$altura}" alt="{$tituloEsc}" loading="lazy">
      <span class="property-card__status">{$statusBadge}</span>
    </div>
    <div class="property-card__body">
      <span class="property-card__code">Quadra {$quadraEsc} · Lote {$numeroLoteEsc}</span>
      <h3 class="property-card__title">{$tituloEsc}</h3>
      <ul class="property-card__meta">
        <li>{$areaFmt}</li>
        <li>{$topoLabel}</li>
      </ul>
      <a class="property-card__link" href="/lotes/{$slugEsc}/" aria-label="Ver detalhes da Quadra {$quadraEsc}, Lote {$numeroLoteEsc}"></a>
    </div>
  </article>
HTML;
}

function render_listing_page($items, $cardFn, $urlPath, $title, $description, $heading, $lead) {
  $statuses = [];
  foreach ($items as $i) {
    if (!in_array($i['status'], $statuses, true)) $statuses[] = $i['status'];
  }
  $allStatuses = array_merge(['todos'], $statuses);
  $chips = '';
  foreach ($allStatuses as $s) {
    $label = $s === 'todos' ? 'Todos' : (STATUS_LABEL[$s] ?? $s);
    $pressed = $s === 'todos' ? 'true' : 'false';
    $sEsc = esc($s);
    $labelEsc = esc($label);
    $chips .= "<button type=\"button\" class=\"filter-chip\" data-filter=\"{$sEsc}\" aria-pressed=\"{$pressed}\">{$labelEsc}</button>\n      ";
  }
  $chips = rtrim($chips);

  $sorted = $items;
  usort($sorted, fn($a, $b) => $a['ordem'] <=> $b['ordem']);
  $cardsParts = array_map($cardFn, $sorted);
  $cards = implode("\n    ", $cardsParts);

  $headingEsc = esc($heading);
  $leadEsc = esc($lead);

  $body = <<<HTML

  <div id="topo2" class="page-banner">
    <div class="container">
      <h1>{$headingEsc}</h1>
      <p>{$leadEsc}</p>
    </div>
  </div>

  <section class="container" data-reveal>
    <div class="filter-bar" data-filter-bar role="group" aria-label="Filtrar por status">
      {$chips}
    </div>
    <div class="property-grid" data-property-grid>
    {$cards}
    </div>
    <p class="filter-empty" data-filter-empty hidden>Nenhum imóvel encontrado com esse filtro.</p>
  </section>

  <script>
  (function(){
    var bar = document.querySelector('[data-filter-bar]');
    var cards = Array.from(document.querySelectorAll('[data-property-grid] .property-card'));
    var empty = document.querySelector('[data-filter-empty]');
    if (!bar) return;
    bar.addEventListener('click', function(e){
      var btn = e.target.closest('[data-filter]');
      if (!btn) return;
      bar.querySelectorAll('[data-filter]').forEach(function(b){ b.setAttribute('aria-pressed', String(b === btn)); });
      var filter = btn.getAttribute('data-filter');
      var visible = 0;
      cards.forEach(function(card){
        var show = filter === 'todos' || card.getAttribute('data-status') === filter;
        card.hidden = !show;
        if (show) visible++;
      });
      empty.hidden = visible !== 0;
    });
  })();
  </script>

HTML;

  return page_shell($title, $description, $urlPath, null, 'website', $body);
}

function casas_listing_html($casas) {
  return render_listing_page(
    $casas,
    'casa_card',
    '/casas/',
    'Casas à venda em Jundiaí/SP | Città Empreendimentos',
    'Casas à venda no condomínio Terras de Gênova, em Jundiaí/SP. Confira disponibilidade, área e fotos de cada projeto residencial.',
    'Casas à venda',
    'Projetos residenciais no condomínio Terras de Gênova, Jundiaí/SP.'
  );
}

function lotes_listing_html($lotes) {
  return render_listing_page(
    $lotes,
    'lote_card',
    '/lotes/',
    'Terrenos à venda em Jundiaí/SP | Città Empreendimentos',
    'Terrenos à venda no loteamento fechado Terras de Gênova, em Jundiaí/SP, a partir de 250m².',
    'Terrenos à venda',
    'Lotes no loteamento fechado Terras de Gênova, Jundiaí/SP.'
  );
}

function build_sitemap($casas, $lotes) {
  $urls = ['/', '/casas/', '/lotes/', '/quem-somos/', '/contato/', '/terras-de-genova/'];
  foreach ($casas as $c) $urls[] = "/casas/{$c['slug']}/";
  foreach ($lotes as $l) $urls[] = "/lotes/{$l['slug']}/";

  $lines = array_map(fn($u) => "  <url><loc>" . SITE_URL . $u . "</loc></url>", $urls);
  $body = implode("\n", $lines);

  return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
{$body}
</urlset>

XML;
}
