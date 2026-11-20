<?php
require 'includes/verifica_login.php';
require 'includes/conexao.php';

$totalLivros     = (int) $pdo->query('SELECT COUNT(*) FROM livros')->fetchColumn();
$totalCategorias = (int) $pdo->query('SELECT COUNT(*) FROM categorias')->fetchColumn();

$titulo = 'Painel';
include 'includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <h2 class="mb-0"><i class="bi bi-speedometer2"></i> Painel</h2>
  <div class="d-flex gap-2">
    <a href="livros.php" class="btn btn-primary">
      <i class="bi bi-book"></i> Gerenciar Livros
    </a>
    <a href="categorias.php" class="btn btn-success">
      <i class="bi bi-tags"></i> Gerenciar Categorias
    </a>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-6">
    <div class="card text-bg-primary shadow-sm h-100">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <h5 class="card-title"><i class="bi bi-book"></i> Livros</h5>
          <p class="display-5 fw-bold mb-2"><?= $totalLivros ?></p>
          <a href="livros.php" class="btn btn-light btn-sm">
            Gerenciar Livros <i class="bi bi-arrow-right"></i>
          </a>
        </div>
        <i class="bi bi-journal-bookmark opacity-50" style="font-size: 3.5rem;"></i>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card text-bg-success shadow-sm h-100">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <h5 class="card-title"><i class="bi bi-tags"></i> Categorias</h5>
          <p class="display-5 fw-bold mb-2"><?= $totalCategorias ?></p>
          <a href="categorias.php" class="btn btn-light btn-sm">
            Gerenciar Categorias <i class="bi bi-arrow-right"></i>
          </a>
        </div>
        <i class="bi bi-collection opacity-50" style="font-size: 3.5rem;"></i>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
