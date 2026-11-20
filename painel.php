<?php
require 'includes/verifica_login.php';
require 'includes/conexao.php';

$totalLivros = $pdo->query('SELECT COUNT(*) FROM livros')->fetchColumn();
$totalCategorias = $pdo->query('SELECT COUNT(*) FROM categorias')->fetchColumn();

$titulo = 'Painel';
include 'includes/header.php';
?>

<h2 class="mb-4">Painel</h2>
<div class="row g-3">
  <div class="col-md-6">
    <div class="card text-bg-primary"><div class="card-body">
      <h5><i class="bi bi-book"></i> Livros</h5>
      <p class="display-5 mb-0"><?= $totalLivros ?></p>
    </div></div>
  </div>
  <div class="col-md-6">
    <div class="card text-bg-success"><div class="card-body">
      <h5><i class="bi bi-tags"></i> Categorias</h5>
      <p class="display-5 mb-0"><?= $totalCategorias ?></p>
    </div></div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>