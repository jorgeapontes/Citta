<?php
require __DIR__ . '/auth.php';

if (is_logged_in()) {
  header('Location: index.php');
  exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = (string) ($_POST['password'] ?? '');

  $stmt = $pdo->prepare('SELECT id, username, password_hash FROM admin_users WHERE username = ? LIMIT 1');
  $stmt->execute([$username]);
  $user = $stmt->fetch();

  if ($user && password_verify($password, $user['password_hash'])) {
    session_regenerate_id(true);
    $_SESSION['admin_user'] = $user['username'];
    header('Location: index.php');
    exit;
  }
  $error = 'Usuário ou senha incorretos.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin | Città Empreendimentos</title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="/img/site/favicon-32.png" type="image/png" sizes="32x32">
<link rel="apple-touch-icon" href="/img/site/favicon-180.png">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/base/tokens.css">
<link rel="stylesheet" href="/css/base/reset.css">
<link rel="stylesheet" href="/css/base/base.css">
<link rel="stylesheet" href="/css/components.css">
<link rel="stylesheet" href="admin.css">
</head>
<body class="admin">

<div class="gate">
  <div class="gate__box">
    <img src="/img/logo.png" alt="Città Empreendimentos" class="gate__logo" onerror="this.style.display='none'">
    <h1>Painel Admin</h1>
    <p>Informe usuário e senha para acessar o painel e editar o catálogo de casas e terrenos.</p>
    <form method="post" class="login-form">
      <div class="form-field">
        <label for="username">Usuário</label>
        <input type="text" id="username" name="username" autocomplete="username" required autofocus>
      </div>
      <div class="form-field">
        <label for="password">Senha</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required>
      </div>
      <?php if ($error): ?>
        <p class="gate__warning"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>
      <button type="submit" class="btn btn--primary">Entrar</button>
    </form>
  </div>
</div>

</body>
</html>
