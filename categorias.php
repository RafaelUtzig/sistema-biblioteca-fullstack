<?php
require 'includes/verifica_login.php';
require 'includes/conexao.php';

$erros = [];
$sucesso = '';
$modoEdicao = false;

$form = [
    'id'        => '',
    'nome'      => '',
    'descricao' => '',
];

$mensagens = [
    'criada'     => 'Categoria cadastrada com sucesso!',
    'atualizada' => 'Categoria atualizada com sucesso!',
    'excluida'   => 'Categoria excluída com sucesso!',
];

if (isset($_GET['msg'], $mensagens[$_GET['msg']])) {
    $sucesso = $mensagens[$_GET['msg']];
}

// Carrega categoria para edição via ?editar=ID
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['editar'])) {
    $editarId = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);
    if ($editarId) {
        $stmt = $pdo->prepare('SELECT id, nome, descricao FROM categorias WHERE id = ?');
        $stmt->execute([$editarId]);
        $catEdit = $stmt->fetch();

        if ($catEdit) {
            $modoEdicao = true;
            $form = [
                'id'        => (string) $catEdit['id'],
                'nome'      => $catEdit['nome'],
                'descricao' => $catEdit['descricao'] ?? '',
            ];
        } else {
            $erros[] = 'Categoria não encontrada para edição.';
        }
    } else {
        $erros[] = 'ID de categoria inválido.';
    }
}

// Processa ações POST (Criar, Atualizar, Excluir)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'excluir') {
        $excluirId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$excluirId) {
            $erros[] = 'ID inválido para exclusão.';
        } else {
            try {
                $stmt = $pdo->prepare('DELETE FROM categorias WHERE id = ?');
                $stmt->execute([$excluirId]);
                if ($stmt->rowCount() > 0) {
                    header('Location: categorias.php?msg=excluida');
                    exit;
                }
                $erros[] = 'Categoria não encontrada para exclusão.';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $erros[] = 'Não é possível excluir uma categoria que possui livros cadastrados. Remova ou altere a categoria dos livros vinculados antes de excluí-la.';
                } else {
                    $erros[] = 'Erro ao excluir a categoria. Tente novamente.';
                }
            }
        }
    } elseif ($acao === 'salvar') {
        $form['id']        = trim($_POST['id'] ?? '');
        $form['nome']      = trim($_POST['nome'] ?? '');
        $form['descricao'] = trim($_POST['descricao'] ?? '');

        $modoEdicao = $form['id'] !== '';

        if (mb_strlen($form['nome']) < 2 || mb_strlen($form['nome']) > 80) {
            $erros[] = 'O nome da categoria deve ter entre 2 e 80 caracteres.';
        }

        if (mb_strlen($form['descricao']) > 255) {
            $erros[] = 'A descrição deve ter no máximo 255 caracteres.';
        }

        if ($modoEdicao && !ctype_digit($form['id'])) {
            $erros[] = 'ID de categoria inválido.';
        }

        if (!$erros) {
            $descricaoVal = $form['descricao'] !== '' ? $form['descricao'] : null;
            try {
                if ($modoEdicao) {
                    $stmt = $pdo->prepare('UPDATE categorias SET nome = ?, descricao = ? WHERE id = ?');
                    $stmt->execute([$form['nome'], $descricaoVal, (int) $form['id']]);
                    header('Location: categorias.php?msg=atualizada');
                    exit;
                } else {
                    $stmt = $pdo->prepare('INSERT INTO categorias (nome, descricao) VALUES (?, ?)');
                    $stmt->execute([$form['nome'], $descricaoVal]);
                    header('Location: categorias.php?msg=criada');
                    exit;
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $erros[] = 'Já existe uma categoria cadastrada com este nome.';
                } else {
                    $erros[] = 'Erro ao salvar a categoria no banco de dados.';
                }
            }
        }
    }
}

// READ: Lista todas as categorias com contagem de livros vinculados
$categorias = $pdo->query(
    'SELECT c.id, c.nome, c.descricao, COUNT(l.id) AS total_livros
     FROM categorias c
     LEFT JOIN livros l ON l.categoria_id = c.id
     GROUP BY c.id, c.nome, c.descricao
     ORDER BY c.nome ASC'
)->fetchAll();

$titulo = 'Categorias';
include 'includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <h2 class="mb-0"><i class="bi bi-tags"></i> Gerenciar Categorias</h2>
  <a href="livros.php" class="btn btn-outline-primary btn-sm">
    <i class="bi bi-book"></i> Gerenciar Livros
  </a>
</div>

<?php if ($sucesso): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle"></i> <?= htmlspecialchars($sucesso) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
  </div>
<?php endif; ?>

<?php foreach ($erros as $erro): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($erro) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
  </div>
<?php endforeach; ?>

<div class="card shadow-sm mb-4">
  <div class="card-header bg-white d-flex justify-content-between align-items-center">
    <h5 class="mb-0">
      <?php if ($modoEdicao): ?>
        <i class="bi bi-pencil-square text-success"></i> Editar Categoria #<?= htmlspecialchars($form['id']) ?>
      <?php else: ?>
        <i class="bi bi-plus-circle text-success"></i> Cadastrar Nova Categoria
      <?php endif; ?>
    </h5>
    <?php if ($modoEdicao): ?>
      <a href="categorias.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-x-lg"></i> Cancelar edição
      </a>
    <?php endif; ?>
  </div>
  <div class="card-body">
    <form method="post" id="formCategoria" novalidate>
      <input type="hidden" name="acao" value="salvar">
      <input type="hidden" name="id" value="<?= htmlspecialchars($form['id']) ?>">

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label" for="nome">Nome <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="nome" name="nome" minlength="2" maxlength="80" required
                 value="<?= htmlspecialchars($form['nome']) ?>">
          <div class="invalid-feedback">Informe o nome da categoria (2 a 80 caracteres).</div>
        </div>

        <div class="col-md-6">
          <label class="form-label" for="descricao">Descrição</label>
          <input type="text" class="form-control" id="descricao" name="descricao" maxlength="255"
                 placeholder="Breve descrição da categoria" value="<?= htmlspecialchars($form['descricao']) ?>">
          <div class="invalid-feedback">A descrição deve ter no máximo 255 caracteres.</div>
        </div>

        <div class="col-md-2 d-flex align-items-end">
          <button type="submit" class="btn btn-success w-100">
            <i class="bi bi-check-lg"></i> <?= $modoEdicao ? 'Salvar' : 'Cadastrar' ?>
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Categorias Cadastradas (<?= count($categorias) ?>)</h5>
    <div class="input-group" style="max-width: 280px;">
      <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
      <input type="search" class="form-control form-control-sm" id="buscaCategorias" placeholder="Buscar categoria...">
    </div>
  </div>
  <div class="card-body p-0">
    <?php if (!$categorias): ?>
      <p class="text-muted text-center py-4 mb-0">Nenhuma categoria cadastrada até o momento.</p>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tabelaCategorias">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Nome</th>
              <th>Descrição</th>
              <th>Livros Vinculados</th>
              <th class="text-end">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($categorias as $cat): ?>
              <tr>
                <td><?= (int) $cat['id'] ?></td>
                <td class="fw-semibold"><?= htmlspecialchars($cat['nome']) ?></td>
                <td><?= $cat['descricao'] !== null && $cat['descricao'] !== '' ? htmlspecialchars($cat['descricao']) : '-' ?></td>
                <td>
                  <span class="badge text-bg-info"><?= (int) $cat['total_livros'] ?></span>
                </td>
                <td class="text-end text-nowrap">
                  <a href="categorias.php?editar=<?= (int) $cat['id'] ?>" class="btn btn-outline-primary btn-sm" title="Editar">
                    <i class="bi bi-pencil"></i> Editar
                  </a>
                  <button type="button"
                          class="btn btn-outline-danger btn-sm btn-excluir-categoria"
                          data-id="<?= (int) $cat['id'] ?>"
                          data-nome="<?= htmlspecialchars($cat['nome']) ?>"
                          data-bs-toggle="modal"
                          data-bs-target="#modalExcluirCategoria"
                          title="Excluir">
                    <i class="bi bi-trash"></i> Excluir
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
            <tr id="semResultadosCategorias" class="d-none">
              <td colspan="5" class="text-center text-muted py-4">Nenhuma categoria encontrada para a busca informada.</td>
            </tr>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Modal de Confirmação de Exclusão -->
<div class="modal fade" id="modalExcluirCategoria" tabindex="-1" aria-labelledby="modalExcluirCategoriaLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="acao" value="excluir">
        <input type="hidden" name="id" id="excluirCategoriaId">
        <div class="modal-header">
          <h5 class="modal-title" id="modalExcluirCategoriaLabel"><i class="bi bi-exclamation-triangle text-danger"></i> Confirmar Exclusão</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          Deseja realmente excluir a categoria <strong id="excluirCategoriaNome"></strong>?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Excluir</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function () {
  const form = document.getElementById('formCategoria');
  form.addEventListener('submit', function (e) {
    if (!this.checkValidity()) {
      e.preventDefault();
      e.stopPropagation();
    }
    this.classList.add('was-validated');
  });

  const buscaInput = document.getElementById('buscaCategorias');
  const tabela = document.getElementById('tabelaCategorias');
  const semResultados = document.getElementById('semResultadosCategorias');

  if (buscaInput && tabela) {
    buscaInput.addEventListener('input', function () {
      const termo = this.value.trim().toLowerCase();
      const linhas = tabela.querySelectorAll('tbody tr:not(#semResultadosCategorias)');
      let visiveis = 0;

      linhas.forEach(function (linha) {
        const texto = linha.textContent.toLowerCase();
        const corresponde = texto.includes(termo);
        linha.classList.toggle('d-none', !corresponde);
        if (corresponde) visiveis++;
      });

      if (semResultados) {
        semResultados.classList.toggle('d-none', visiveis > 0);
      }
    });
  }

  document.querySelectorAll('.btn-excluir-categoria').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.getElementById('excluirCategoriaId').value = this.dataset.id;
      document.getElementById('excluirCategoriaNome').textContent = this.dataset.nome;
    });
  });
})();
</script>

<?php include 'includes/footer.php'; ?>
