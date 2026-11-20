<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$logado = !empty($_SESSION['usuario_id']);
$titulo = $titulo ?? 'Biblioteca';
$paginaAtual = basename($_SERVER['PHP_SELF'] ?? '');
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

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-semibold" href="index.html"><i class="bi bi-book"></i> Biblioteca</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu" aria-controls="menu" aria-expanded="false" aria-label="Alternar navegação">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="menu">
      <ul class="navbar-nav me-auto">
        <?php if ($logado): ?>
          <li class="nav-item">
            <a class="nav-link <?= $paginaAtual === 'painel.php' ? 'active fw-semibold' : '' ?>" href="painel.php">
              <i class="bi bi-speedometer2"></i> Painel
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $paginaAtual === 'livros.php' ? 'active fw-semibold' : '' ?>" href="livros.php">
              <i class="bi bi-book"></i> Livros
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $paginaAtual === 'categorias.php' ? 'active fw-semibold' : '' ?>" href="categorias.php">
              <i class="bi bi-tags"></i> Categorias
            </a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link" href="index.html"><i class="bi bi-house"></i> Início</a>
          </li>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav align-items-lg-center">
        <?php if ($logado): ?>
          <li class="nav-item">
            <span class="navbar-text text-white me-lg-3">
              <i class="bi bi-person-circle"></i> Olá, <?= htmlspecialchars($_SESSION['usuario_nome'] ?? 'Usuário') ?>
            </span>
          </li>
          <li class="nav-item mt-2 mt-lg-0">
            <a class="btn btn-outline-light btn-sm" href="logout.php">
              <i class="bi bi-box-arrow-right"></i> Sair
            </a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link <?= $paginaAtual === 'login.php' ? 'active fw-semibold' : '' ?>" href="login.php">Entrar</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $paginaAtual === 'cadastro.php' ? 'active fw-semibold' : '' ?>" href="cadastro.php">Cadastrar-se</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<main class="container py-4 flex-grow-1">
