<?php
require 'includes/conexao.php';
session_start();

$erros = [];
$nome = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome      = trim($_POST['nome'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $senha     = $_POST['senha'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';

    if (mb_strlen($nome) < 3)                       $erros[] = 'O nome deve ter pelo menos 3 caracteres.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'Informe um e-mail válido.';
    if (strlen($senha) < 6)                         $erros[] = 'A senha deve ter pelo menos 6 caracteres.';
    if ($senha !== $confirmar)                      $erros[] = 'As senhas não conferem.';

    if (!$erros) {
        try {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)');
            $stmt->execute([$nome, $email, $hash]);

            header('Location: login.php?cadastro=ok');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $erros[] = 'Este e-mail já está cadastrado.';
            } else {
                $erros[] = 'Erro ao cadastrar. Tente novamente.';
            }
        }
    }
}

$titulo = 'Cadastro';
include 'includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="card shadow-sm">
      <div class="card-body p-4">
        <h3 class="mb-4"><i class="bi bi-person-plus"></i> Criar conta</h3>

        <?php foreach ($erros as $erro): ?>
          <div class="alert alert-danger py-2"><?= htmlspecialchars($erro) ?></div>
        <?php endforeach; ?>

        <form method="post" id="formCadastro" novalidate>
          <div class="mb-3">
            <label class="form-label" for="nome">Nome</label>
            <input type="text" class="form-control" id="nome" name="nome" minlength="3" required
                   value="<?= htmlspecialchars($nome) ?>">
            <div class="invalid-feedback">Informe seu nome (mínimo 3 caracteres).</div>
          </div>
          <div class="mb-3">
            <label class="form-label" for="email">E-mail</label>
            <input type="email" class="form-control" id="email" name="email" required
                   value="<?= htmlspecialchars($email) ?>">
            <div class="invalid-feedback">Informe um e-mail válido.</div>
          </div>
          <div class="mb-3">
            <label class="form-label" for="senha">Senha</label>
            <input type="password" class="form-control" id="senha" name="senha" minlength="6" required>
            <div class="invalid-feedback">A senha deve ter no mínimo 6 caracteres.</div>
          </div>
          <div class="mb-3">
            <label class="form-label" for="confirmar">Confirmar senha</label>
            <input type="password" class="form-control" id="confirmar" name="confirmar" required>
            <div class="invalid-feedback">As senhas não conferem.</div>
          </div>
          <button class="btn btn-primary w-100" type="submit">Cadastrar</button>
        </form>

        <p class="text-center mt-3 mb-0">Já tem conta? <a href="login.php">Entrar</a></p>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('formCadastro').addEventListener('submit', function (e) {
  const senha = document.getElementById('senha');
  const confirmar = document.getElementById('confirmar');

  confirmar.setCustomValidity(senha.value !== confirmar.value ? 'diferente' : '');

  if (!this.checkValidity()) {
    e.preventDefault();
    e.stopPropagation();
  }
  this.classList.add('was-validated');
});
</script>

<?php include 'includes/footer.php'; ?>