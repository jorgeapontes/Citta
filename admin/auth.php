<?php
/**
 * Sessão do painel admin. Incluído por toda página administrativa antes de
 * qualquer output (login.php, logout.php, index.php, form.php, save.php,
 * delete.php, generate.php).
 */
define('CITTA_ADMIN', true);
require_once __DIR__ . '/config.php';

session_start();

function is_logged_in(): bool {
  return !empty($_SESSION['admin_user']);
}

function require_login(): void {
  if (!is_logged_in()) {
    header('Location: login.php');
    exit;
  }
}
