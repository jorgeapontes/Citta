# Painel Admin (`/admin/`)

Aplicação PHP com login e banco de dados MySQL (`citta`) para editar o
catálogo de casas e terrenos e gerar as páginas estáticas do site — sem
precisar editar HTML/JSON na mão.

## Arquitetura

- **Login real**, com sessão PHP (`admin/auth.php`) e senha com hash bcrypt
  (`password_hash`/`password_verify`) guardada na tabela `admin_users`.
- **Banco de dados só guarda texto** — os campos de cada casa/terreno
  (título, status, área, características etc.), nas tabelas `casas` e
  `lotes`. Ver `db/citta.sql` para a estrutura completa.
- **Fotos continuam sendo arquivos estáticos** em `img/casas/{CODIGO}/` e
  `img/lotes/{CODIGO}/`, servidos direto pelo Apache — o banco só guarda o
  nome dos arquivos (coluna `imagens_arquivos`, um JSON com a ordem; o
  primeiro é sempre a capa).
- O site público (`casas/`, `lotes/`, listagens, `sitemap.xml`) continua
  **100% HTML estático** — o admin só regenera esses arquivos a partir do
  banco (`admin/site-generator.php`) sempre que você salva, edita ou exclui
  um imóvel, ou clica em "Gerar site". Isso preserva SEO/Open Graph únicos
  por página sem exigir que o site público rode PHP a cada visita.

## Requisitos para rodar localmente

- **Apache + PHP + MySQL** (XAMPP cobre os três). O projeto precisa estar
  dentro de `htdocs/` (ou de um vhost apontando para a pasta) para o Apache
  processar os arquivos `.php` — abrir os arquivos direto com `file://` ou
  servir só HTML estático (`npx serve`, `python -m http.server`) não
  funciona mais para o admin, porque agora ele precisa de PHP+MySQL.
- Banco `citta` criado a partir de `db/citta.sql` (ver `docs/README.md` ou
  a seção "Publicar em produção" abaixo).

## Uso

1. Acesse `/admin/` — você verá a tela de login.
2. Entre com usuário e senha (peça as credenciais atuais a quem administra
   o site; o valor inicial de fábrica, criado durante a migração, era
   `admin` / `12345` — **troque assim que possível**, ver abaixo).
3. No dashboard, use as abas **Casas** / **Terrenos** para listar, criar,
   editar ou excluir imóveis.
4. No formulário de edição:
   - **Código**: não pode ser alterado depois de criado (evita desalinhar
     a pasta de fotos no disco).
   - **Fotos**: existentes aparecem com setas ← → para reordenar (a
     primeira é sempre a capa) e uma caixa "Remover" para excluir. Novas
     fotos entram pelo seletor de arquivo e são sempre adicionadas ao
     final, na ordem escolhida.
   - Campos sem informação real (quartos, suítes, banheiros, vagas) devem
     ficar em branco — o painel salva como `NULL`.
5. Ao salvar (ou excluir), o site já é regenerado automaticamente. O botão
   **"Gerar site"** no topo existe para regenerar tudo manualmente (por
   exemplo, depois de editar algo direto no banco via phpMyAdmin).
6. Publique os arquivos HTML gerados na hospedagem como sempre (FTP/painel).

## Trocar a senha do admin

Não há tela de "trocar senha" ainda. Gere um novo hash e atualize a tabela
direto (via phpMyAdmin ou linha de comando):

```
php -r "echo password_hash('sua-nova-senha', PASSWORD_DEFAULT);"
```

```sql
UPDATE admin_users SET password_hash = 'HASH_GERADO_ACIMA' WHERE username = 'admin';
```

## Publicar em produção

1. Suba os arquivos do projeto (exceto o que está no `.gitignore`) para a
   hospedagem — ela precisa suportar PHP e MySQL.
2. Importe `db/citta.sql` no banco da hospedagem (phpMyAdmin -> Importar,
   ou `mysql -u usuario -p < db/citta.sql`). **Atenção**: esse arquivo tem
   `DROP DATABASE IF EXISTS citta` — leia o aviso no topo do arquivo antes
   de rodar.
3. Crie `admin/config.local.php` no servidor (nunca vai pro Git — ver
   `.gitignore`) com as credenciais reais do banco da hospedagem:
   ```php
   <?php
   $DB_HOST = 'localhost';
   $DB_NAME = 'citta';
   $DB_USER = 'usuario_da_hospedagem';
   $DB_PASS = 'senha_da_hospedagem';
   ```
4. Troque a senha do admin (ver seção acima).

## Limitações conhecidas

- Sem "desfazer": cada "Salvar imóvel" já grava no banco. O controle de
  versão (Git) protege os arquivos HTML/imagens, mas não o conteúdo do
  banco — faça backups periódicos de `citta` em produção.
- Excluir um imóvel remove a linha do banco e a página HTML gerada
  (`casas/{slug}/` ou `lotes/{slug}/`), mas **não apaga** a pasta de fotos
  em `img/` — limpe manualmente se quiser liberar espaço.
- Sem histórico de quem editou o quê (só existe um usuário admin por
  enquanto).

## Lógica de geração compartilhada

`admin/render.php` tem as funções que geram o HTML de cada página de
imóvel/listagem/sitemap a partir dos dados do banco — é uma porta direta do
antigo `tools/render-core.js` (removido; ver histórico do Git se precisar
comparar). `admin/site-generator.php` busca os dados no MySQL e chama essas
funções para escrever os arquivos finais.
