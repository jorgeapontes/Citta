<?php
/**
 * Conexão com o banco "citta". Só pode ser incluído por outro script PHP do
 * projeto (nunca acessado direto pelo navegador — ver guarda CITTA_ADMIN
 * logo abaixo e o bloqueio equivalente no .htaccess).
 *
 * Em produção: NÃO edite os valores abaixo. Em vez disso, crie
 * admin/config.local.php (nunca versionado — ver .gitignore) definindo as
 * mesmas variáveis com as credenciais reais da hospedagem; esse arquivo,
 * se existir, sobrescreve os valores padrão (pensados para o XAMPP local).
 */

if (!defined('CITTA_ADMIN')) {
  http_response_code(403);
  exit('Acesso negado');
}

$DB_HOST = 'localhost';
$DB_NAME = 'citta';
$DB_USER = 'root';
$DB_PASS = '';

$localConfig = __DIR__ . '/config.local.php';
if (is_file($localConfig)) {
  require $localConfig;
}

try {
  $pdo = new PDO(
    "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
    $DB_USER,
    $DB_PASS,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
  );
} catch (PDOException $e) {
  http_response_code(500);
  exit('Erro ao conectar ao banco de dados. Verifique admin/config.php (ou admin/config.local.php em produção).');
}
