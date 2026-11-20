<?php
require 'includes/verifica_login.php';
require 'includes/conexao.php';

$erros = [];
$sucesso = '';
$modoEdicao = false;

$form = [
    'id'           => '',
    'titulo'       => '',
    'autor'        => '',
    'categoria_id' => '',
    'ano'          => '',
    'isbn'         => '',
    'preco'        => '',
    'status'       => 'disponivel',
];

$mensagens = [
    'criado'     => 'Livro cadastrado com sucesso!',
    'atualizado' => 'Livro atualizado com sucesso!',
    'excluido'   => 'Livro excluído com sucesso!',
];

if (isset($_GET['msg'], $mensagens[$_GET['msg']])) {
    $sucesso = $mensagens[$_GET['msg']];
}

// Carrega categorias para o <select> e validação
$categorias = $pdo->query('SELECT id, nome FROM categorias ORDER BY nome ASC')->fetchAll();
$categoriasIds = array_map(static fn($c) => (int) $c['id'], $categorias);

// Carrega dados do livro para edição via ?editar=ID
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['editar'])) {
    $editarId = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);
    if ($editarId) {
        $stmt = $pdo->prepare('SELECT * FROM livros WHERE id = ?');
        $stmt->execute([$editarId]);
        $livroEdit = $stmt->fetch();

        if ($livroEdit) {
            $modoEdicao = true;
            $form = [
                'id'           => (string) $livroEdit['id'],
                'titulo'       => $livroEdit['titulo'],
                'autor'        => $livroEdit['autor'],
                'categoria_id' => (string) $livroEdit['categoria_id'],
                'ano'          => $livroEdit['ano'] !== null ? (string) $livroEdit['ano'] : '',
                'isbn'         => $livroEdit['isbn'] ?? '',
                'preco'        => $livroEdit['preco'] !== null ? number_format((float) $livroEdit['preco'], 2, '.', '') : '',
                'status'       => $livroEdit['status'],
            ];
        } else {
            $erros[] = 'Livro não encontrado para edição.';
        }
    } else {
        $erros[] = 'ID de livro inválido.';
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
                $stmt = $pdo->prepare('DELETE FROM livros WHERE id = ?');
                $stmt->execute([$excluirId]);
                if ($stmt->rowCount() > 0) {
                    header('Location: livros.php?msg=excluido');
                    exit;
                }
                $erros[] = 'Livro não encontrado para exclusão.';
            } catch (PDOException $e) {
                $erros[] = 'Erro ao excluir o livro. Tente novamente.';
            }
        }
    } elseif ($acao === 'salvar') {
        $form['id']           = trim($_POST['id'] ?? '');
        $form['titulo']       = trim($_POST['titulo'] ?? '');
        $form['autor']        = trim($_POST['autor'] ?? '');
        $form['categoria_id'] = trim($_POST['categoria_id'] ?? '');
        $form['ano']          = trim($_POST['ano'] ?? '');
        $form['isbn']         = trim($_POST['isbn'] ?? '');
        $form['preco']        = trim($_POST['preco'] ?? '');
        $form['status']       = trim($_POST['status'] ?? 'disponivel');

        $modoEdicao = $form['id'] !== '';

        // Validações obrigatórias
        if ($form['titulo'] === '' || mb_strlen($form['titulo']) > 150) {
            $erros[] = 'O título é obrigatório e deve ter no máximo 150 caracteres.';
        }

        if ($form['autor'] === '' || mb_strlen($form['autor']) > 100) {
            $erros[] = 'O autor é obrigatório e deve ter no máximo 100 caracteres.';
        }

        if (!ctype_digit($form['categoria_id']) || !in_array((int) $form['categoria_id'], $categoriasIds, true)) {
            $erros[] = 'Selecione uma categoria válida.';
        }

        // Validação numérica de Ano
        $anoVal = null;
        if ($form['ano'] !== '') {
            $anoMax = (int) date('Y') + 1;
            if (!preg_match('/^\d{4}$/', $form['ano']) || (int) $form['ano'] < 1000 || (int) $form['ano'] > $anoMax) {
                $erros[] = "Informe um ano válido com 4 dígitos (entre 1000 e $anoMax).";
            } else {
                $anoVal = (int) $form['ano'];
            }
        }

        // Validação Regex de ISBN (ISBN-10 ou ISBN-13, com ou sem hífens, até 17 caracteres)
        $isbnVal = null;
        if ($form['isbn'] !== '') {
            $isbnPadrao = '/^(?:97[89](?:-?\d){10}|(?:\d-?){9}[\dXx])$/';
            if (
                strlen($form['isbn']) > 17
                || !preg_match($isbnPadrao, $form['isbn'])
                || str_contains($form['isbn'], '--')
                || str_ends_with($form['isbn'], '-')
            ) {
                $erros[] = 'Informe um ISBN válido (ex: 978-85-359-0277-5 ou 853590277X).';
            } else {
                $isbnVal = strtoupper($form['isbn']);
            }
        }

        // Validação numérica de Preço
        $precoVal = null;
        if ($form['preco'] !== '') {
            if (!preg_match('/^\d{1,6}([.,]\d{1,2})?$/', $form['preco'])) {
                $erros[] = 'Informe um preço numérico válido (ex: 49.90 ou 49,90).';
            } else {
                $precoNorm = str_replace(',', '.', $form['preco']);
                if ((float) $precoNorm < 0 || (float) $precoNorm > 999999.99) {
                    $erros[] = 'O preço deve estar entre R$ 0,00 e R$ 999.999,99.';
                } else {
                    $precoVal = number_format((float) $precoNorm, 2, '.', '');
                }
            }
        }

        // Validação de Status
        if (!in_array($form['status'], ['disponivel', 'emprestado'], true)) {
            $erros[] = 'Status inválido.';
        }

        if ($modoEdicao && !ctype_digit($form['id'])) {
            $erros[] = 'ID de livro inválido.';
        }

        if (!$erros) {
            try {
                if ($modoEdicao) {
                    $stmt = $pdo->prepare(
                        'UPDATE livros
                         SET titulo = ?, autor = ?, ano = ?, isbn = ?, preco = ?, status = ?, categoria_id = ?
                         WHERE id = ?'
                    );
                    $stmt->execute([
                        $form['titulo'],
                        $form['autor'],
                        $anoVal,
                        $isbnVal,
                        $precoVal,
                        $form['status'],
                        (int) $form['categoria_id'],
                        (int) $form['id'],
                    ]);
                    header('Location: livros.php?msg=atualizado');
                    exit;
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO livros (titulo, autor, ano, isbn, preco, status, categoria_id)
                         VALUES (?, ?, ?, ?, ?, ?, ?)'
                    );
                    $stmt->execute([
                        $form['titulo'],
                        $form['autor'],
                        $anoVal,
                        $isbnVal,
                        $precoVal,
                        $form['status'],
                        (int) $form['categoria_id'],
                    ]);
                    header('Location: livros.php?msg=criado');
                    exit;
                }
            } catch (PDOException $e) {
                $erros[] = 'Erro ao salvar o livro no banco de dados.';
            }
        }
    }
}

// READ: Lista todos os livros com INNER JOIN categorias
$livros = $pdo->query(
    'SELECT l.id, l.titulo, l.autor, l.ano, l.isbn, l.preco, l.status, l.categoria_id, c.nome AS categoria_nome
     FROM livros l
     INNER JOIN categorias c ON c.id = l.categoria_id
     ORDER BY l.id DESC'
)->fetchAll();

$titulo = 'Livros';
include 'includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <h2 class="mb-0"><i class="bi bi-book"></i> Gerenciar Livros</h2>
  <a href="categorias.php" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-tags"></i> Gerenciar Categorias
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

<?php if (!$categorias): ?>
  <div class="alert alert-warning">
    <i class="bi bi-info-circle"></i> Cadastre ao menos uma <a href="categorias.php" class="alert-link">categoria</a> antes de adicionar livros.
  </div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
  <div class="card-header bg-white d-flex justify-content-between align-items-center">
    <h5 class="mb-0">
      <?php if ($modoEdicao): ?>
        <i class="bi bi-pencil-square text-primary"></i> Editar Livro #<?= htmlspecialchars($form['id']) ?>
      <?php else: ?>
        <i class="bi bi-plus-circle text-primary"></i> Cadastrar Novo Livro
      <?php endif; ?>
    </h5>
    <?php if ($modoEdicao): ?>
      <a href="livros.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-x-lg"></i> Cancelar edição
      </a>
    <?php endif; ?>
  </div>
  <div class="card-body">
    <form method="post" id="formLivro" novalidate>
      <input type="hidden" name="acao" value="salvar">
      <input type="hidden" name="id" value="<?= htmlspecialchars($form['id']) ?>">

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label" for="titulo">Título <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="titulo" name="titulo" maxlength="150" required
                 value="<?= htmlspecialchars($form['titulo']) ?>">
          <div class="invalid-feedback">Informe o título do livro (máx. 150 caracteres).</div>
        </div>

        <div class="col-md-6">
          <label class="form-label" for="autor">Autor <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="autor" name="autor" maxlength="100" required
                 value="<?= htmlspecialchars($form['autor']) ?>">
          <div class="invalid-feedback">Informe o autor do livro (máx. 100 caracteres).</div>
        </div>

        <div class="col-md-4">
          <label class="form-label" for="categoria_id">Categoria <span class="text-danger">*</span></label>
          <select class="form-select" id="categoria_id" name="categoria_id" required>
            <option value="">Selecione...</option>
            <?php foreach ($categorias as $cat): ?>
              <option value="<?= (int) $cat['id'] ?>" <?= (string) $cat['id'] === $form['categoria_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['nome']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="invalid-feedback">Selecione uma categoria.</div>
        </div>

        <div class="col-md-2">
          <label class="form-label" for="ano">Ano</label>
          <input type="text" inputmode="numeric" class="form-control" id="ano" name="ano" maxlength="4"
                 placeholder="Ex: 2024" value="<?= htmlspecialchars($form['ano']) ?>">
          <div class="invalid-feedback">Ano inválido (4 dígitos).</div>
        </div>

        <div class="col-md-3">
          <label class="form-label" for="isbn">ISBN</label>
          <input type="text" class="form-control" id="isbn" name="isbn" maxlength="17"
                 placeholder="978-85-359-0277-5" value="<?= htmlspecialchars($form['isbn']) ?>">
          <div class="invalid-feedback">ISBN inválido (10 ou 13 dígitos).</div>
        </div>

        <div class="col-md-3">
          <label class="form-label" for="preco">Preço (R$)</label>
          <input type="text" inputmode="decimal" class="form-control" id="preco" name="preco"
                 placeholder="Ex: 49.90" value="<?= htmlspecialchars($form['preco']) ?>">
          <div class="invalid-feedback">Informe um valor numérico válido.</div>
        </div>

        <div class="col-md-3">
          <label class="form-label" for="status">Status</label>
          <select class="form-select" id="status" name="status" required>
            <option value="disponivel" <?= $form['status'] === 'disponivel' ? 'selected' : '' ?>>Disponível</option>
            <option value="emprestado" <?= $form['status'] === 'emprestado' ? 'selected' : '' ?>>Emprestado</option>
          </select>
        </div>

        <div class="col-md-9 d-flex align-items-end justify-content-md-end gap-2">
          <?php if ($modoEdicao): ?>
            <a href="livros.php" class="btn btn-outline-secondary">Cancelar</a>
          <?php endif; ?>
          <button type="submit" class="btn btn-primary" <?= !$categorias ? 'disabled' : '' ?>>
            <i class="bi bi-check-lg"></i> <?= $modoEdicao ? 'Salvar Alterações' : 'Cadastrar Livro' ?>
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Acervo de Livros (<?= count($livros) ?>)</h5>
    <div class="input-group" style="max-width: 320px;">
      <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
      <input type="search" class="form-control form-control-sm" id="buscaLivros"
             placeholder="Buscar por título, autor, categoria...">
    </div>
  </div>
  <div class="card-body p-0">
    <?php if (!$livros): ?>
      <p class="text-muted text-center py-4 mb-0">Nenhum livro cadastrado até o momento.</p>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tabelaLivros">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Título</th>
              <th>Autor</th>
              <th>Categoria</th>
              <th>Ano</th>
              <th>ISBN</th>
              <th>Preço</th>
              <th>Status</th>
              <th class="text-end">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($livros as $livro): ?>
              <tr>
                <td><?= (int) $livro['id'] ?></td>
                <td class="fw-semibold"><?= htmlspecialchars($livro['titulo']) ?></td>
                <td><?= htmlspecialchars($livro['autor']) ?></td>
                <td><span class="badge text-bg-secondary"><?= htmlspecialchars($livro['categoria_nome']) ?></span></td>
                <td><?= $livro['ano'] !== null ? (int) $livro['ano'] : '-' ?></td>
                <td><code><?= $livro['isbn'] !== null && $livro['isbn'] !== '' ? htmlspecialchars($livro['isbn']) : '-' ?></code></td>
                <td>
                  <?= $livro['preco'] !== null ? 'R$ ' . number_format((float) $livro['preco'], 2, ',', '.') : '-' ?>
                </td>
                <td>
                  <?php if ($livro['status'] === 'disponivel'): ?>
                    <span class="badge bg-success">Disponível</span>
                  <?php else: ?>
                    <span class="badge bg-warning text-dark">Emprestado</span>
                  <?php endif; ?>
                </td>
                <td class="text-end text-nowrap">
                  <a href="livros.php?editar=<?= (int) $livro['id'] ?>" class="btn btn-outline-primary btn-sm" title="Editar">
                    <i class="bi bi-pencil"></i> Editar
                  </a>
                  <button type="button"
                          class="btn btn-outline-danger btn-sm btn-excluir-livro"
                          data-id="<?= (int) $livro['id'] ?>"
                          data-titulo="<?= htmlspecialchars($livro['titulo']) ?>"
                          data-bs-toggle="modal"
                          data-bs-target="#modalExcluirLivro"
                          title="Excluir">
                    <i class="bi bi-trash"></i> Excluir
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
            <tr id="semResultadosLivros" class="d-none">
              <td colspan="9" class="text-center text-muted py-4">Nenhum livro encontrado para a busca informada.</td>
            </tr>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Modal de Confirmação de Exclusão -->
<div class="modal fade" id="modalExcluirLivro" tabindex="-1" aria-labelledby="modalExcluirLivroLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="acao" value="excluir">
        <input type="hidden" name="id" id="excluirLivroId">
        <div class="modal-header">
          <h5 class="modal-title" id="modalExcluirLivroLabel"><i class="bi bi-exclamation-triangle text-danger"></i> Confirmar Exclusão</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          Deseja realmente excluir o livro <strong id="excluirLivroTitulo"></strong>? Esta ação não pode ser desfeita.
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
  const form = document.getElementById('formLivro');
  const anoInput = document.getElementById('ano');
  const isbnInput = document.getElementById('isbn');
  const precoInput = document.getElementById('preco');

  function validarCamposExtras() {
    // Validação do Ano (opcional, mas se preenchido deve ser 4 dígitos entre 1000 e ano atual + 1)
    const anoVal = anoInput.value.trim();
    const anoMax = new Date().getFullYear() + 1;
    if (anoVal !== '' && (!/^\d{4}$/.test(anoVal) || Number(anoVal) < 1000 || Number(anoVal) > anoMax)) {
      anoInput.setCustomValidity('Ano inválido.');
    } else {
      anoInput.setCustomValidity('');
    }

    // Validação do ISBN (opcional, mas se preenchido deve seguir ISBN-10 ou ISBN-13)
    const isbnVal = isbnInput.value.trim();
    const isbnRegex = /^(?:97[89](?:-?\d){10}|(?:\d-?){9}[\dXx])$/;
    if (
      isbnVal !== '' &&
      (isbnVal.length > 17 || !isbnRegex.test(isbnVal) || isbnVal.includes('--') || isbnVal.endsWith('-'))
    ) {
      isbnInput.setCustomValidity('ISBN inválido.');
    } else {
      isbnInput.setCustomValidity('');
    }

    // Validação do Preço (opcional, formato decimal com ponto ou vírgula)
    const precoVal = precoInput.value.trim();
    if (precoVal !== '' && !/^\d{1,6}([.,]\d{1,2})?$/.test(precoVal)) {
      precoInput.setCustomValidity('Preço inválido.');
    } else {
      precoInput.setCustomValidity('');
    }
  }

  [anoInput, isbnInput, precoInput].forEach(function (el) {
    el.addEventListener('input', validarCamposExtras);
  });

  form.addEventListener('submit', function (e) {
    validarCamposExtras();
    if (!this.checkValidity()) {
      e.preventDefault();
      e.stopPropagation();
    }
    this.classList.add('was-validated');
  });

  // Filtro rápido na tabela via JavaScript
  const buscaInput = document.getElementById('buscaLivros');
  const tabela = document.getElementById('tabelaLivros');
  const semResultados = document.getElementById('semResultadosLivros');

  if (buscaInput && tabela) {
    buscaInput.addEventListener('input', function () {
      const termo = this.value.trim().toLowerCase();
      const linhas = tabela.querySelectorAll('tbody tr:not(#semResultadosLivros)');
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

  // Preenchimento do modal de exclusão
  document.querySelectorAll('.btn-excluir-livro').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.getElementById('excluirLivroId').value = this.dataset.id;
      document.getElementById('excluirLivroTitulo').textContent = this.dataset.titulo;
    });
  });
})();
</script>

<?php include 'includes/footer.php'; ?>
