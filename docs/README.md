# Città Empreendimentos — novo site

Site institucional/imobiliário: as páginas públicas (`index.html`, `P/`,
`casas/`, `lotes/`) são HTML estático puro — o que está no repositório já é
o que sobe para a hospedagem, sem build step. O catálogo de imóveis (casas
e terrenos) mora num banco MySQL (`citta`) e é administrado por uma
aplicação PHP em `/admin/` com login; salvar/editar/excluir um imóvel ali
regenera automaticamente os arquivos HTML estáticos correspondentes. Ver
`docs/admin.md` para detalhes do painel.

## Estrutura

```
/
├── index.html              Home (autoria manual)
├── P/                       Páginas institucionais (autoria manual), organizadas
│   │                        aqui só para arrumar a raiz do repositório — as URLs
│   │                        públicas continuam as de sempre, veja .htaccess
│   ├── quem-somos/            → servida em /quem-somos/
│   ├── contato/                → servida em /contato/
│   └── terras-de-genova/       → servida em /terras-de-genova/
├── casas/, lotes/           Listagens + páginas de detalhe (GERADAS a partir do banco, ver abaixo)
├── admin/                   Painel admin em PHP (login + CRUD de casas/lotes, ver docs/admin.md)
├── db/citta.sql              Schema + dados do banco "citta" (importar em produção)
├── components/              header.html / footer.html — injetados via js/core/include.js
├── css/
│   ├── base/                 tokens.css (paleta/tipografia), reset.css, base.css
│   ├── components.css        Todos os componentes reutilizáveis (header, cards, galeria, forms...)
│   ├── animations.css        Scroll-reveal e transições
│   └── pages/                CSS específico de páginas (home.css etc.)
├── js/core/                  include.js, menu.js, reveal.js, gallery.js, forms.js
├── img/casas/{CODIGO}/       Fotos/renders de cada casa
├── img/lotes/{CODIGO}/       Fotos de cada terreno
├── .htaccess, robots.txt, sitemap.xml
```

## Como adicionar/editar um imóvel

Use o painel em `/admin/` (login necessário) — ver `docs/admin.md` para o
passo a passo completo. Resumindo: criar/editar um imóvel no dashboard já
grava no banco e regenera as páginas estáticas (`casas/{slug}/index.html`,
`lotes/{slug}/index.html`, as listagens e `sitemap.xml`) automaticamente;
basta publicar os arquivos alterados na hospedagem depois.

**Por que gerar HTML estático, e não servir tudo via PHP direto do banco?**
Cada página de imóvel precisa de `<title>`, meta description, canonical e
Open Graph ÚNICOS no HTML entregue ao navegador — crawlers de rede social e
boa parte do SEO não executam JavaScript para obter isso, mas também não
precisam de PHP: servir HTML já pronto é mais rápido e simples de hospedar.
O banco (só acessado pelo admin) resolve a edição sem exigir editar HTML na
mão; a geração estática resolve a entrega ao visitante.

## Identidade visual

Paleta e tipografia extraídas do CSS real do site atual (não estimadas) —
ver `css/base/tokens.css` e os comentários `ajuste` ali para os pequenos
acertos de contraste feitos.

## Limitações conhecidas (para revisar depois)

- As fotos de casas em `img/casas/` são páginas de um material de
  apresentação do arquiteto (T2R): misturam plantas técnicas, cortes,
  elevações e renders 3D, todas com legenda/marca d'água no rodapé da
  imagem. A tabela `casas` já aponta a melhor imagem disponível como capa
  de cada imóvel (primeiro item de `imagens_arquivos`), e o CSS usa leve
  zoom nos cards para reduzir a faixa de legenda — mas o ideal, a médio
  prazo, é substituir por fotos/renders de marketing sem essa marcação.
- `admin/render.php` tem `SITE_URL` fixo como
  `https://condominiosemjundiai.com.br` (domínio atual) — atualizar se o
  domínio de produção mudar.
- A página `/contato/` não tem mais formulário — só canais diretos
  (telefone, WhatsApp, Instagram, e-mail, endereço/mapa). `js/core/forms.js`
  continua no projeto (validação genérica de formulário, com `TODO` de
  backend de envio) caso algum formulário volte a ser necessário no futuro.
- **E-mail do rodapé/contato ainda é placeholder**
  (`contato@condominiosemjundiai.com.br`, marcado com `TODO` no HTML de
  `P/contato/index.html`) — atualizar assim que o endereço oficial for
  confirmado.
