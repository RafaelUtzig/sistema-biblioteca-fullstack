<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$logado = !empty($_SESSION['usuario_id']);
$titulo = $titulo ?? 'Biblioteca';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($titulo) ?> | Biblioteca</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand" href="/biblioteca/index.html"><i class="bi bi-book"></i> Biblioteca</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="menu">
      <ul class="navbar-nav me-auto">
        <?php if ($logado): ?>
          <li class="nav-item"><a class="nav-link" href="/biblioteca/painel.php">Painel</a></li>
          <li class="nav-item"><a class="nav-link" href="/biblioteca/livros/">Livros</a></li>
          <li class="nav-item"><a class="nav-link" href="/biblioteca/categorias/">Categorias</a></li>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav">
        <?php if ($logado): ?>
          <li class="nav-item"><span class="navbar-text me-3">Olá, <?= htmlspecialchars($_SESSION['usuario_nome']) ?></span></li>
          <li class="nav-item"><a class="btn btn-outline-light btn-sm" href="/biblioteca/logout.php">Sair</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="/biblioteca/login.php">Entrar</a></li>
          <li class="nav-item"><a class="nav-link" href="/biblioteca/cadastro.php">Cadastrar-se</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<main class="container py-4 flex-grow-1">