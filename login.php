<?php
require 'includes/conexao.php';
session_start();

if (!empty($_SESSION['usuario_id'])) {
    header('Location: painel.php');
    exit;
}

$erro = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $stmt = $pdo->prepare('SELECT id, nome, senha FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        session_regenerate_id(true);
        $_SESSION['usuario_id']   = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        header('Location: painel.php');
        exit;
    }
    $erro = 'E-mail ou senha inválidos.';
}

$titulo = 'Login';
include 'includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-6 col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body p-4">
        <h3 class="mb-4"><i class="bi bi-box-arrow-in-right"></i> Entrar</h3>

        <?php if (($_GET['cadastro'] ?? '') === 'ok'): ?>
          <div class="alert alert-success">Cadastro realizado! Faça login para continuar.</div>
        <?php endif; ?>
        <?php if (($_GET['erro'] ?? '') === 'acesso'): ?>
          <div class="alert alert-warning">Faça login para acessar essa página.</div>
        <?php endif; ?>
        <?php if (($_GET['saiu'] ?? '') === '1'): ?>
          <div class="alert alert-info">Você saiu do sistema.</div>
        <?php endif; ?>
        <?php if ($erro): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="post">
          <div class="mb-3">
            <label class="form-label" for="email">E-mail</label>
            <input type="email" class="form-control" id="email" name="email" required
                   value="<?= htmlspecialchars($email) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label" for="senha">Senha</label>
            <input type="password" class="form-control" id="senha" name="senha" required>
          </div>
          <button class="btn btn-primary w-100" type="submit">Entrar</button>
        </form>

        <p class="text-center mt-3 mb-0">Não tem conta? <a href="cadastro.php">Cadastre-se</a></p>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>