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
    <div class="pc-admin__head">
        <div>
            <h1>Filmes e séries</h1>
            <p class="pc-text-muted" style="font-size:13px">Gerencie os títulos usados nas recomendações e no ranking de plataformas.</p>
        </div>
    </div>

    <div class="pc-panel">
        <p>As tabelas do catálogo ainda não foram importadas neste banco. Importe <code>sql/009_catalogo.sql</code> e o snapshot do catálogo para habilitar este CRUD.</p>
        <a class="pc-btn pc-btn--sm pc-mt-3" href="<?= url('admin/') ?>"><?= icon('arrow-left', 15) ?> <?= e(t('admin.back')) ?></a>
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

<div class="pc-admin__head">
    <div>
        <h1>Filmes e séries</h1>
        <p class="pc-text-muted" style="font-size:13px">
            Cadastre, edite e exclua os títulos do catálogo usados nas recomendações. O ID TMDB deve ser único por tipo.
        </p>
    </div>

    <a class="pc-btn pc-btn--sm" href="<?= e($link(['editar' => 'novo'])) ?>">
        <?= icon('film', 16) ?> <?= e(t('admin.new')) ?>
    </a>
</div>

<?php if ($editando !== null): ?>
    <form class="pc-form pc-mb-3" method="post" action="<?= url('admin/filmes.php') ?>">
        <?= Csrf::campo() ?>
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">

        <div class="pc-form__row pc-form__row--3">
            <div class="pc-field">
                <label class="pc-label" for="tipo">Tipo</label>
                <select class="pc-select" id="tipo" name="tipo">
                    <?php foreach ($tipos as $chave => $rotulo): ?>
                        <option value="<?= e($chave) ?>" <?= $editando['tipo'] === $chave ? 'selected' : '' ?>>
                            <?= e($rotulo) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="pc-field">
                <label class="pc-label" for="tmdb_id">ID TMDB</label>
                <input class="pc-input" id="tmdb_id" name="tmdb_id" type="number" min="1" max="4294967295"
                       value="<?= e((string) $editando['tmdb_id']) ?>" required>
            </div>

            <div class="pc-field">
                <label class="pc-label" for="ano">Ano (opcional)</label>
                <input class="pc-input" id="ano" name="ano" type="number" min="1800" max="2100"
                       value="<?= e((string) ($editando['ano'] ?? '')) ?>">
            </div>
        </div>

        <div class="pc-form__row">
            <div class="pc-field">
                <label class="pc-label" for="titulo">Título</label>
                <input class="pc-input" id="titulo" name="titulo" type="text" maxlength="200"
                       value="<?= e((string) $editando['titulo']) ?>" required>
            </div>

            <div class="pc-field">
                <label class="pc-label" for="titulo_original">Título original (opcional)</label>
                <input class="pc-input" id="titulo_original" name="titulo_original" type="text" maxlength="200"
                       value="<?= e((string) ($editando['titulo_original'] ?? '')) ?>">
            </div>
        </div>

        <div class="pc-field">
            <label class="pc-label" for="sinopse">Sinopse (opcional)</label>
            <textarea class="pc-textarea" id="sinopse" name="sinopse" maxlength="600" style="min-height:80px"><?= e((string) ($editando['sinopse'] ?? '')) ?></textarea>
        </div>

        <div class="pc-form__row pc-form__row--3">
            <div class="pc-field">
                <label class="pc-label" for="duracao">Duração em minutos (filmes)</label>
                <input class="pc-input" id="duracao" name="duracao" type="number" min="1" max="1000"
                       value="<?= e((string) ($editando['duracao'] ?? '')) ?>">
            </div>

            <div class="pc-field">
                <label class="pc-label" for="temporadas">Temporadas (séries)</label>
                <input class="pc-input" id="temporadas" name="temporadas" type="number" min="1" max="100"
                       value="<?= e((string) ($editando['temporadas'] ?? '')) ?>">
            </div>

            <div class="pc-field">
                <label class="pc-label" for="nota">Nota (0 a 10)</label>
                <input class="pc-input" id="nota" name="nota" type="number" min="0" max="10" step="0.1"
                       value="<?= e((string) ($editando['nota'] ?? '')) ?>">
            </div>
        </div>

        <div class="pc-form__row pc-form__row--3">
            <div class="pc-field">
                <label class="pc-label" for="idioma_original">Idioma original (opcional)</label>
                <input class="pc-input" id="idioma_original" name="idioma_original" type="text" maxlength="5"
                       value="<?= e((string) ($editando['idioma_original'] ?? '')) ?>" placeholder="pt-BR">
            </div>

            <div class="pc-field">
                <label class="pc-label" for="poster_path">Caminho do pôster (opcional)</label>
                <input class="pc-input" id="poster_path" name="poster_path" type="text" maxlength="160"
                       value="<?= e((string) ($editando['poster_path'] ?? '')) ?>" placeholder="/abc.jpg">
            </div>

            <div class="pc-field">
                <label class="pc-label" for="backdrop_path">Caminho do backdrop (opcional)</label>
                <input class="pc-input" id="backdrop_path" name="backdrop_path" type="text" maxlength="160"
                       value="<?= e((string) ($editando['backdrop_path'] ?? '')) ?>" placeholder="/abc.jpg">
            </div>
        </div>

        <div class="pc-form__row pc-form__row--3">
            <div class="pc-field">
                <label class="pc-label" for="confianca">Confiança do vetor (0 a 100)</label>
                <input class="pc-input" id="confianca" name="confianca" type="number" min="0" max="100"
                       value="<?= (int) ($editando['confianca'] ?? 0) ?>">
            </div>

            <div class="pc-field">
                <span class="pc-label">Disponível no catálogo</span>
                <input type="hidden" name="ativo" value="0">
                <label class="pc-checkbox" style="display:flex;align-items:center;gap:8px;margin-top:8px">
                    <input type="checkbox" name="ativo" value="1" <?= (int) ($editando['ativo'] ?? 1) === 1 ? 'checked' : '' ?>>
                    Ativo
                </label>
            </div>
        </div>

        <div class="pc-field">
            <span class="pc-label">Vetor legado do catálogo (0 = polo esquerdo, 100 = direito)</span>
        </div>

        <div class="pc-form__row" style="grid-template-columns: repeat(4, minmax(0,1fr))">
            <?php foreach ($eixosLegado as $indice => $nome): ?>
                <?php $numero = $indice + 1; ?>
                <div class="pc-field">
                    <label class="pc-label" for="eixo_<?= $numero ?>"><?= e($nome) ?></label>
                    <input class="pc-input" id="eixo_<?= $numero ?>" name="eixo_<?= $numero ?>" type="number" min="0" max="100"
                           value="<?= (int) ($editando['eixo_' . $numero] ?? 50) ?>" required>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="pc-form__actions">
            <button class="pc-btn" type="submit"><?= e(t('admin.save')) ?></button>
            <a class="pc-btn pc-btn--ghost" href="<?= e($link()) ?>"><?= e(t('admin.back')) ?></a>

            <?php if ((int) $editando['id'] > 0): ?>
                <button class="pc-btn pc-btn--light" type="submit" form="form-excluir"><?= e(t('admin.delete')) ?></button>
            <?php endif; ?>
        </div>
    </form>

    <?php if ((int) $editando['id'] > 0): ?>
        <form id="form-excluir" method="post" action="<?= url('admin/filmes.php') ?>" data-confirm="<?= e(t('admin.confirm_delete')) ?>">
            <?= Csrf::campo() ?>
            <input type="hidden" name="acao" value="excluir">
            <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">
        </form>
    <?php endif; ?>
<?php endif; ?>

<div class="pc-admin__head" style="margin-bottom:12px">
    <div class="pc-admin__actions" style="margin-left:0">
        <a class="pc-chip <?= $filtroTipo === '' ? 'pc-chip--green' : '' ?>" href="<?= e($link()) ?>">Todos</a>
        <?php foreach ($tipos as $chave => $rotulo): ?>
            <a class="pc-chip <?= $filtroTipo === $chave ? 'pc-chip--green' : '' ?>" href="<?= e($link(['tipo' => $chave])) ?>">
                <?= e($rotulo) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <form class="pc-admin__actions" method="get" action="<?= url('admin/filmes.php') ?>">
        <?php if ($filtroTipo !== ''): ?>
            <input type="hidden" name="tipo" value="<?= e($filtroTipo) ?>">
        <?php endif; ?>
        <input class="pc-input" name="q" type="search" value="<?= e($busca) ?>" placeholder="Buscar título ou ID TMDB" style="min-width:240px">
        <button class="pc-btn pc-btn--sm" type="submit">Buscar</button>
    </form>
</div>

<div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
        <thead>
        <tr>
            <th>Título</th>
            <th>Tipo</th>
            <th>TMDB</th>
            <th>Ano</th>
            <th>Nota</th>
            <th>Status</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php if ($obras === []): ?>
            <tr><td colspan="7" class="text-center text-muted py-3"><?= e(t('admin.no_data')) ?></td></tr>
        <?php endif; ?>

        <?php foreach ($obras as $obra): ?>
            <tr>
                <td>
                    <strong><?= e($obra['titulo']) ?></strong>
                    <?php if (!empty($obra['titulo_original']) && $obra['titulo_original'] !== $obra['titulo']): ?>
                        <div class="pc-text-muted" style="font-size:11px"><?= e($obra['titulo_original']) ?></div>
                    <?php endif; ?>
                </td>
                <td><?= e($tipos[$obra['tipo']] ?? $obra['tipo']) ?></td>
                <td style="font-family:var(--pc-mono);font-size:11px"><?= (int) $obra['tmdb_id'] ?></td>
                <td><?= $obra['ano'] !== null ? (int) $obra['ano'] : '—' ?></td>
                <td><?= $obra['nota'] !== null ? e(number_format((float) $obra['nota'], 1, ',', '.')) : '—' ?></td>
                <td>
                    <span class="pc-badge-mini"><?= (int) $obra['ativo'] === 1 ? 'ativo' : 'inativo' ?></span>
                </td>
                <td><a href="<?= e($link(['editar' => $obra['id']])) ?>"><?= e(t('admin.edit')) ?></a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($paginas > 1): ?>
    <div class="pc-admin__head" style="margin-bottom:12px">
        <span class="pc-text-muted" style="font-size:12px">Página <?= $pagina ?> de <?= $paginas ?> · <?= $total ?> títulos</span>
        <div class="pc-admin__actions">
            <?php if ($pagina > 1): ?>
                <a class="pc-btn pc-btn--sm pc-btn--ghost" href="<?= e($link(['pagina' => $pagina - 1])) ?>">Anterior</a>
            <?php endif; ?>
            <?php if ($pagina < $paginas): ?>
                <a class="pc-btn pc-btn--sm pc-btn--ghost" href="<?= e($link(['pagina' => $pagina + 1])) ?>">Próxima</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/partials/foot.php'; ?>
