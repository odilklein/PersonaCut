<?php

declare(strict_types=1);

/** CRUD de filmes e séries do catálogo audiovisual. */

require __DIR__ . '/_guard.php';

use Core\Csrf;

$adminTitulo = 'Filmes e séries';
$adminAtivo = 'filmes.php';

$tipos = [
    'filme' => 'Filme',
    'serie' => 'Série',
];

// O catálogo ainda usa o vetor legado de 8 coordenadas.
$eixosLegado = ['Imagem', 'Emoção', 'Mundo', 'Ritmo', 'Ética', 'História', 'Tamanho', 'Época'];
$porPagina = 50;

$temCatalogo = false;

try {
    $temCatalogo = (bool) $pdo->query("SHOW TABLES LIKE 'catalogo_obras'")->fetchColumn();
} catch (Throwable $erro) {
    $temCatalogo = false;
}

if (!$temCatalogo) {
    require __DIR__ . '/partials/head.php';
    ?>
    <div class="page-header">
        <div>
            <h1><i class="icon-base ti tabler-movie"></i>Filmes e séries</h1>
            <span class="page-subtitle">Gerencie os títulos usados nas recomendações e no ranking de plataformas.</span>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="alert alert-warning mb-3" role="alert">
                <i class="icon-base ti tabler-alert-triangle me-2"></i>
                As tabelas do catálogo ainda não foram importadas neste banco. Importe <code>sql/009_catalogo.sql</code> e o snapshot do catálogo para habilitar este CRUD.
            </div>
            <a class="btn btn-outline-primary" href="<?= e(url('admin/')) ?>">
                <i class="icon-base ti tabler-arrow-left me-1"></i><?= e(t('admin.back')) ?>
            </a>
        </div>
    </div>
    <?php
    require __DIR__ . '/partials/foot.php';
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!Csrf::validarRequisicao()) {
        pc_admin_redirect(url('admin/filmes.php'), t('erros.csrf'), 'erro');
    }

    $id = pc_admin_inteiro('id');

    if (pc_admin_texto('acao') === 'excluir') {
        try {
            // Remove os filhos antes do título para funcionar mesmo em bancos
            // importados sem as FOREIGN KEYs da migração 009.
            $pdo->beginTransaction();

            foreach (['catalogo_creditos', 'catalogo_obra_plataformas', 'catalogo_obra_generos', 'catalogo_obra_paises'] as $tabelaFilha) {
                $stmtFilho = $pdo->prepare('DELETE FROM ' . $tabelaFilha . ' WHERE obra_id = :id');
                $stmtFilho->execute(['id' => $id]);
            }

            $stmt = $pdo->prepare('DELETE FROM catalogo_obras WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $pdo->commit();
        } catch (PDOException $erro) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            pc_admin_redirect(url('admin/filmes.php'), 'Erro ao excluir: ' . $erro->getMessage(), 'erro');
        }

        pc_admin_redirect(url('admin/filmes.php'), t('admin.deleted'));
    }

    $tipo = pc_admin_texto('tipo');
    $tmdbId = pc_admin_inteiro('tmdb_id');
    $titulo = pc_admin_texto('titulo');
    $tituloOriginal = pc_admin_texto('titulo_original');
    $anoTexto = pc_admin_texto('ano');
    $sinopse = pc_admin_texto('sinopse');
    $duracaoTexto = pc_admin_texto('duracao');
    $temporadasTexto = pc_admin_texto('temporadas');
    $notaTexto = pc_admin_texto('nota');
    $posterPath = pc_admin_texto('poster_path');
    $backdropPath = pc_admin_texto('backdrop_path');
    $idioma = pc_admin_texto('idioma_original');
    $confianca = pc_admin_inteiro('confianca', 0);
    $ativo = pc_admin_texto('ativo') === '1' ? 1 : 0;

    $erros = [];

    if (!isset($tipos[$tipo])) {
        $erros[] = 'Tipo inválido.';
    }

    if ($tmdbId <= 0 || $tmdbId > 4294967295) {
        $erros[] = 'Informe um ID TMDB válido e único para o tipo escolhido.';
    }

    if ($titulo === '' || mb_strlen($titulo) > 200) {
        $erros[] = 'Informe um título de até 200 caracteres.';
    }

    if (mb_strlen($tituloOriginal) > 200 || mb_strlen($sinopse) > 600) {
        $erros[] = 'Título original ou sinopse acima do limite permitido.';
    }

    $inteiroOpcional = static function (string $texto, int $min, int $max, string $rotulo, array &$erros): ?int {
        if ($texto === '') {
            return null;
        }

        if (!ctype_digit($texto) || (int) $texto < $min || (int) $texto > $max) {
            $erros[] = $rotulo . ' inválido.';

            return null;
        }

        return (int) $texto;
    };

    $ano = $inteiroOpcional($anoTexto, 1800, 2100, 'Ano', $erros);
    $duracao = $inteiroOpcional($duracaoTexto, 1, 1000, 'Duração', $erros);
    $temporadas = $inteiroOpcional($temporadasTexto, 1, 100, 'Temporadas', $erros);

    $nota = null;

    if ($notaTexto !== '') {
        if (!is_numeric($notaTexto) || (float) $notaTexto < 0 || (float) $notaTexto > 10) {
            $erros[] = 'Nota inválida (use de 0 a 10).';
        } else {
            $nota = round((float) $notaTexto, 1);
        }
    }

    if (mb_strlen($idioma) > 5 || mb_strlen($posterPath) > 160 || mb_strlen($backdropPath) > 160) {
        $erros[] = 'Idioma ou caminho de imagem acima do limite permitido.';
    }

    if ($confianca < 0 || $confianca > 100) {
        $erros[] = 'A confiança deve ficar entre 0 e 100.';
    }

    $vetor = [];

    for ($i = 1; $i <= 8; $i++) {
        $valor = pc_admin_inteiro('eixo_' . $i, 50);

        if ($valor < 0 || $valor > 100) {
            $erros[] = 'As notas dos eixos devem ficar entre 0 e 100.';
            break;
        }

        $vetor[$i] = $valor;
    }

    if ($erros !== []) {
        pc_admin_redirect(
            url('admin/filmes.php', ['editar' => $id > 0 ? $id : 'novo']),
            implode(' ', $erros),
            'erro'
        );
    }

    $dados = [
        'tipo' => $tipo,
        'tmdb_id' => $tmdbId,
        'titulo' => $titulo,
        'titulo_original' => $tituloOriginal !== '' ? $tituloOriginal : null,
        'ano' => $ano,
        'sinopse' => $sinopse !== '' ? $sinopse : null,
        'duracao' => $duracao,
        'temporadas' => $tipo === 'serie' ? $temporadas : null,
        'poster_path' => $posterPath !== '' ? $posterPath : null,
        'backdrop_path' => $backdropPath !== '' ? $backdropPath : null,
        'nota' => $nota,
        'idioma_original' => $idioma !== '' ? $idioma : null,
        'confianca' => $confianca,
        'ativo' => $ativo,
        'atualizado_em' => date('Y-m-d H:i:s'),
    ];

    for ($i = 1; $i <= 8; $i++) {
        $dados['eixo_' . $i] = $vetor[$i];
    }

    try {
        if ($id > 0) {
            $set = implode(', ', array_map(static fn (string $c): string => $c . ' = :' . $c, array_keys($dados)));
            $stmt = $pdo->prepare('UPDATE catalogo_obras SET ' . $set . ' WHERE id = :id');
            $stmt->execute($dados + ['id' => $id]);
        } else {
            $campos = implode(', ', array_keys($dados));
            $marcadores = implode(', ', array_map(static fn (string $c): string => ':' . $c, array_keys($dados)));
            $stmt = $pdo->prepare('INSERT INTO catalogo_obras (' . $campos . ') VALUES (' . $marcadores . ')');
            $stmt->execute($dados);
            $id = (int) $pdo->lastInsertId();
        }
    } catch (PDOException $erro) {
        $mensagem = ($erro->errorInfo[1] ?? 0) === 1062
            ? 'Já existe um título desse tipo com esse ID TMDB.'
            : 'Erro ao salvar: ' . $erro->getMessage();

        pc_admin_redirect(url('admin/filmes.php'), $mensagem, 'erro');
    }

    pc_admin_redirect(url('admin/filmes.php', ['editar' => $id]), t('admin.saved'));
}

$filtroTipo = is_string($_GET['tipo'] ?? null) && isset($tipos[$_GET['tipo']]) ? (string) $_GET['tipo'] : '';
$busca = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$pagina = is_numeric($_GET['pagina'] ?? null) ? max(1, (int) $_GET['pagina']) : 1;

$parametrosBase = [];

if ($filtroTipo !== '') {
    $parametrosBase['tipo'] = $filtroTipo;
}

if ($busca !== '') {
    $parametrosBase['q'] = $busca;
}

$link = static fn (array $extras = []): string => url('admin/filmes.php', $parametrosBase + $extras);

$condicoes = [];
$parametros = [];

if ($filtroTipo !== '') {
    $condicoes[] = 'tipo = :tipo';
    $parametros['tipo'] = $filtroTipo;
}

if ($busca !== '') {
    // Cada ocorrência precisa de um marcador próprio: com prepares nativos o
    // PDO não aceita reaproveitar o mesmo nome na mesma instrução.
    $condicoes[] = '(titulo LIKE :busca_titulo OR titulo_original LIKE :busca_original OR CAST(tmdb_id AS CHAR) LIKE :busca_id)';
    $parametros['busca_titulo'] = '%' . $busca . '%';
    $parametros['busca_original'] = '%' . $busca . '%';
    $parametros['busca_id'] = '%' . $busca . '%';
}

$onde = $condicoes !== [] ? ' WHERE ' . implode(' AND ', $condicoes) : '';

$stmtTotal = $pdo->prepare('SELECT COUNT(*) FROM catalogo_obras' . $onde);

foreach ($parametros as $chave => $valor) {
    $stmtTotal->bindValue($chave, $valor);
}

$stmtTotal->execute();
$total = (int) $stmtTotal->fetchColumn();
$paginas = max(1, (int) ceil($total / $porPagina));
$pagina = min($pagina, $paginas);
$offset = ($pagina - 1) * $porPagina;

$stmt = $pdo->prepare(
    'SELECT id, tmdb_id, tipo, titulo, titulo_original, ano, nota, ativo, atualizado_em
       FROM catalogo_obras' . $onde . '
      ORDER BY titulo, id
      LIMIT :limite OFFSET :deslocamento'
);

foreach ($parametros as $chave => $valor) {
    $stmt->bindValue($chave, $valor);
}

$stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':deslocamento', $offset, PDO::PARAM_INT);
$stmt->execute();
$obras = $stmt->fetchAll();

$editar = $_GET['editar'] ?? null;
$editando = null;

if ($editar === 'novo') {
    $editando = [
        'id' => 0,
        'tipo' => $filtroTipo !== '' ? $filtroTipo : 'filme',
        'tmdb_id' => '',
        'titulo' => '',
        'titulo_original' => '',
        'ano' => '',
        'sinopse' => '',
        'duracao' => '',
        'temporadas' => '',
        'poster_path' => '',
        'backdrop_path' => '',
        'nota' => '',
        'idioma_original' => '',
        'confianca' => 0,
        'ativo' => 1,
    ];

    for ($i = 1; $i <= 8; $i++) {
        $editando['eixo_' . $i] = 50;
    }
} elseif (is_numeric($editar)) {
    $stmtEdicao = $pdo->prepare('SELECT * FROM catalogo_obras WHERE id = :id');
    $stmtEdicao->execute(['id' => (int) $editar]);
    $linha = $stmtEdicao->fetch();

    if ($linha !== false) {
        $editando = $linha;
    }
}

require __DIR__ . '/partials/head.php';
?>

<div class="page-header">
    <div>
        <h1><i class="icon-base ti tabler-movie"></i>Filmes e séries</h1>
        <span class="page-subtitle">Cadastre, edite e exclua os títulos do catálogo usados nas recomendações. O ID TMDB deve ser único por tipo.</span>
    </div>
    <div class="page-header-actions">
        <a class="btn btn-primary" href="<?= e($link(['editar' => 'novo'])) ?>">
            <i class="icon-base ti tabler-plus"></i> <?= e(t('admin.new')) ?>
        </a>
    </div>
</div>

<?php if ($editando !== null): ?>
    <div class="card mb-4">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="card-title mb-1"><?= (int) $editando['id'] > 0 ? 'Editar título' : 'Novo título' ?></h5>
                <p class="card-subtitle mb-0">Dados do TMDB, metadados e vetor legado do catálogo</p>
            </div>
            <?php if ((int) $editando['id'] > 0): ?>
                <span class="badge bg-label-primary rounded-pill">#<?= (int) $editando['id'] ?></span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <form method="post" action="<?= e(url('admin/filmes.php')) ?>">
                <?= Csrf::campo() ?>
                <input type="hidden" name="acao" value="salvar">
                <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="tipo">Tipo</label>
                        <select class="form-select" id="tipo" name="tipo">
                            <?php foreach ($tipos as $chave => $rotulo): ?>
                                <option value="<?= e($chave) ?>" <?= $editando['tipo'] === $chave ? 'selected' : '' ?>>
                                    <?= e($rotulo) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="tmdb_id">ID TMDB</label>
                        <input class="form-control" id="tmdb_id" name="tmdb_id" type="number" min="1" max="4294967295"
                               value="<?= e((string) $editando['tmdb_id']) ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="ano">Ano (opcional)</label>
                        <input class="form-control" id="ano" name="ano" type="number" min="1800" max="2100"
                               value="<?= e((string) ($editando['ano'] ?? '')) ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="titulo">Título</label>
                        <input class="form-control" id="titulo" name="titulo" type="text" maxlength="200"
                               value="<?= e((string) $editando['titulo']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="titulo_original">Título original (opcional)</label>
                        <input class="form-control" id="titulo_original" name="titulo_original" type="text" maxlength="200"
                               value="<?= e((string) ($editando['titulo_original'] ?? '')) ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="sinopse">Sinopse (opcional)</label>
                        <textarea class="form-control" id="sinopse" name="sinopse" maxlength="600" rows="3"><?= e((string) ($editando['sinopse'] ?? '')) ?></textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="duracao">Duração em minutos (filmes)</label>
                        <input class="form-control" id="duracao" name="duracao" type="number" min="1" max="1000"
                               value="<?= e((string) ($editando['duracao'] ?? '')) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="temporadas">Temporadas (séries)</label>
                        <input class="form-control" id="temporadas" name="temporadas" type="number" min="1" max="100"
                               value="<?= e((string) ($editando['temporadas'] ?? '')) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="nota">Nota (0 a 10)</label>
                        <input class="form-control" id="nota" name="nota" type="number" min="0" max="10" step="0.1"
                               value="<?= e((string) ($editando['nota'] ?? '')) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="idioma_original">Idioma original (opcional)</label>
                        <input class="form-control" id="idioma_original" name="idioma_original" type="text" maxlength="5"
                               value="<?= e((string) ($editando['idioma_original'] ?? '')) ?>" placeholder="pt-BR">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="poster_path">Caminho do pôster (opcional)</label>
                        <input class="form-control" id="poster_path" name="poster_path" type="text" maxlength="160"
                               value="<?= e((string) ($editando['poster_path'] ?? '')) ?>" placeholder="/abc.jpg">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="backdrop_path">Caminho do backdrop (opcional)</label>
                        <input class="form-control" id="backdrop_path" name="backdrop_path" type="text" maxlength="160"
                               value="<?= e((string) ($editando['backdrop_path'] ?? '')) ?>" placeholder="/abc.jpg">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="confianca">Confiança do vetor (0 a 100)</label>
                        <input class="form-control" id="confianca" name="confianca" type="number" min="0" max="100"
                               value="<?= (int) ($editando['confianca'] ?? 0) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="ativo">Disponível no catálogo</label>
                        <input type="hidden" name="ativo" value="0">
                        <div class="form-check form-switch my-2">
                            <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1" <?= (int) ($editando['ativo'] ?? 1) === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ativo">Ativo</label>
                        </div>
                    </div>
                </div>

                <fieldset class="mt-4">
                    <legend class="h6 text-heading mb-1">
                        Vetor legado do catálogo
                        <small class="text-body-secondary fw-normal">(0 = polo esquerdo, 100 = direito)</small>
                    </legend>
                    <div class="row g-3">
                        <?php foreach ($eixosLegado as $indice => $nome): ?>
                            <?php $numero = $indice + 1; ?>
                            <div class="col-6 col-md-3">
                                <label class="form-label" for="eixo_<?= $numero ?>"><?= e($nome) ?></label>
                                <input class="form-control" id="eixo_<?= $numero ?>" name="eixo_<?= $numero ?>" type="number" min="0" max="100"
                                       value="<?= (int) ($editando['eixo_' . $numero] ?? 50) ?>" required>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button class="btn btn-primary" type="submit">
                        <i class="icon-base ti tabler-device-floppy me-1"></i><?= e(t('admin.save')) ?>
                    </button>
                    <a class="btn btn-outline-secondary" href="<?= e($link()) ?>">
                        <i class="icon-base ti tabler-arrow-left me-1"></i><?= e(t('admin.back')) ?>
                    </a>

                    <?php if ((int) $editando['id'] > 0): ?>
                        <button class="btn btn-outline-danger ms-auto" type="submit" form="form-excluir">
                            <i class="icon-base ti tabler-trash me-1"></i><?= e(t('admin.delete')) ?>
                        </button>
                    <?php endif; ?>
                </div>
            </form>

            <?php if ((int) $editando['id'] > 0): ?>
                <form id="form-excluir" method="post" action="<?= e(url('admin/filmes.php')) ?>" data-confirm="<?= e(t('admin.confirm_delete')) ?>">
                    <?= Csrf::campo() ?>
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex flex-wrap gap-1">
            <a class="btn btn-sm <?= $filtroTipo === '' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= e($link()) ?>">Todos</a>
            <?php foreach ($tipos as $chave => $rotulo): ?>
                <a class="btn btn-sm <?= $filtroTipo === $chave ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= e($link(['tipo' => $chave])) ?>">
                    <?= e($rotulo) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <form class="d-flex flex-wrap gap-2" method="get" action="<?= e(url('admin/filmes.php')) ?>">
            <?php if ($filtroTipo !== ''): ?>
                <input type="hidden" name="tipo" value="<?= e($filtroTipo) ?>">
            <?php endif; ?>
            <div class="input-group input-group-sm" style="width:min(320px,100%)">
                <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
                <input class="form-control" name="q" type="search" value="<?= e($busca) ?>" placeholder="Buscar título ou ID TMDB" aria-label="Buscar título ou ID TMDB">
            </div>
            <button class="btn btn-sm btn-primary" type="submit">
                <i class="icon-base ti tabler-search me-1"></i> Buscar
            </button>
        </form>
    </div>

    <h5 class="card-header text-md-start text-center border-bottom">
        <?= numero($total, 0) ?> <?= $total === 1 ? 'título' : 'títulos' ?>
    </h5>

    <div class="card-datatable table-responsive">
        <table class="table table-hover align-middle responsive-card mb-0">
            <thead class="border-top">
            <tr>
                <th>Título</th>
                <th>Tipo</th>
                <th>TMDB</th>
                <th>Ano</th>
                <th>Nota</th>
                <th>Status</th>
                <th class="text-end">Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($obras === []): ?>
                <tr class="row-empty"><td colspan="7" class="text-center text-body-secondary py-5"><?= e(t('admin.no_data')) ?></td></tr>
            <?php endif; ?>

            <?php foreach ($obras as $obra): ?>
                <tr>
                    <td data-label="Título">
                        <span class="text-heading fw-medium"><?= e($obra['titulo']) ?></span>
                        <?php if (!empty($obra['titulo_original']) && $obra['titulo_original'] !== $obra['titulo']): ?>
                            <small class="text-body-secondary d-block"><?= e($obra['titulo_original']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td data-label="Tipo"><span class="badge bg-label-secondary rounded-pill"><?= e($tipos[$obra['tipo']] ?? $obra['tipo']) ?></span></td>
                    <td data-label="TMDB"><small class="font-monospace text-body-secondary"><?= (int) $obra['tmdb_id'] ?></small></td>
                    <td data-label="Ano"><?= $obra['ano'] !== null ? (int) $obra['ano'] : '—' ?></td>
                    <td data-label="Nota"><?= $obra['nota'] !== null ? e(number_format((float) $obra['nota'], 1, ',', '.')) : '—' ?></td>
                    <td data-label="Status">
                        <span class="badge <?= (int) $obra['ativo'] === 1 ? 'bg-label-success' : 'bg-label-secondary' ?> rounded-pill">
                            <?= (int) $obra['ativo'] === 1 ? 'ativo' : 'inativo' ?>
                        </span>
                    </td>
                    <td data-label="Ações" class="text-end">
                        <a class="btn btn-sm btn-icon btn-text-secondary rounded-pill" href="<?= e($link(['editar' => $obra['id']])) ?>" title="<?= e(t('admin.edit')) ?>">
                            <i class="icon-base ti tabler-pencil"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($paginas > 1): ?>
        <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2">
            <small class="text-body-secondary">Página <?= $pagina ?> de <?= $paginas ?> · <?= numero($total, 0) ?> títulos</small>
            <nav aria-label="Paginação">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= e($link(['pagina' => max(1, $pagina - 1)])) ?>">Anterior</a>
                    </li>
                    <li class="page-item active">
                        <span class="page-link"><?= $pagina ?></span>
                    </li>
                    <li class="page-item <?= $pagina >= $paginas ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= e($link(['pagina' => min($paginas, $pagina + 1)])) ?>">Próxima</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/partials/foot.php'; ?>
