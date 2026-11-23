<?php

declare(strict_types=1);

/** CRUD dos 6 eixos (ordem, nomes, polos, cores e mapeamento TMDB). */

require __DIR__ . '/_guard.php';

use Core\Csrf;
use Models\Eixo;

$adminTitulo = t('admin.axes');
$adminAtivo = 'eixos.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!Csrf::validarRequisicao()) {
        pc_admin_redirect(url('admin/eixos.php'), t('erros.csrf'), 'erro');
    }

    $id = pc_admin_inteiro('id');
    $ordem = pc_admin_inteiro('ordem', 1);
    $slug = pc_admin_slug(pc_admin_texto('slug'));
    $nome = pc_admin_texto('nome');
    $descricao = pc_admin_texto('descricao');
    $poloEsq = pc_admin_texto('polo_esq_nome');
    $poloDir = pc_admin_texto('polo_dir_nome');
    $corEsq = pc_admin_texto('cor_esq', '#0c3b2e');
    $corDir = pc_admin_texto('cor_dir', '#a61b12');
    $iconeEsq = pc_admin_texto('icone_esq', 'sparkles');
    $iconeDir = pc_admin_texto('icone_dir', 'sparkles');

    $erros = [];

    if ($id <= 0) {
        $erros[] = 'Eixo inválido.';
    }

    if ($nome === '') {
        $erros[] = 'Informe o nome do eixo.';
    }

    if ($poloEsq === '' || $poloDir === '') {
        $erros[] = 'Informe os dois polos.';
    }

    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $corEsq) || !preg_match('/^#[0-9a-fA-F]{6}$/', $corDir)) {
        $erros[] = 'Cores precisam estar no formato #RRGGBB.';
    }

    if ($erros !== []) {
        pc_admin_redirect(url('admin/eixos.php', ['editar' => $id]), implode(' ', $erros), 'erro');
    }

    $stmt = $pdo->prepare(
        'UPDATE eixos
            SET ordem = :ordem, slug = :slug, nome = :nome, descricao = :descricao,
                polo_esq_nome = :polo_esq, polo_dir_nome = :polo_dir,
                cor_esq = :cor_esq, cor_dir = :cor_dir,
                icone_esq = :icone_esq, icone_dir = :icone_dir,
                tmdb_genres_esq = :gen_esq, tmdb_genres_dir = :gen_dir,
                tmdb_keywords_esq = :kw_esq, tmdb_keywords_dir = :kw_dir
          WHERE id = :id'
    );

    $stmt->execute([
        'ordem' => $ordem,
        'slug' => $slug,
        'nome' => $nome,
        'descricao' => $descricao,
        'polo_esq' => $poloEsq,
        'polo_dir' => $poloDir,
        'cor_esq' => $corEsq,
        'cor_dir' => $corDir,
        'icone_esq' => $iconeEsq,
        'icone_dir' => $iconeDir,
        'gen_esq' => pc_admin_texto('tmdb_genres_esq'),
        'gen_dir' => pc_admin_texto('tmdb_genres_dir'),
        'kw_esq' => pc_admin_texto('tmdb_keywords_esq'),
        'kw_dir' => pc_admin_texto('tmdb_keywords_dir'),
        'id' => $id,
    ]);

    pc_admin_redirect(url('admin/eixos.php'), t('admin.saved'));
}

$eixos = Eixo::todos($pdo);
$editar = isset($_GET['editar']) ? (int) $_GET['editar'] : 0;
$editando = null;

foreach ($eixos as $eixo) {
    if ((int) $eixo['id'] === $editar) {
        $editando = $eixo;
    }
}

require __DIR__ . '/partials/head.php';
?>

<div class="page-header">
    <div>
        <h1><i class="icon-base ti tabler-compass"></i><?= e(t('admin.axes')) ?></h1>
        <span class="page-subtitle">Os eixos são fixos: edite nomes, polos, cores e o mapeamento TMDB.</span>
    </div>
</div>

<?php if ($editando !== null): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-1">Editar eixo</h5>
            <p class="card-subtitle mb-0">Campos usados no cálculo do vetor e na curadoria do catálogo</p>
        </div>
        <div class="card-body">
            <form method="post" action="<?= e(url('admin/eixos.php')) ?>">
                <?= Csrf::campo() ?>
                <input type="hidden" name="acao" value="salvar">
                <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">

                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label" for="ordem">Ordem</label>
                        <input class="form-control" id="ordem" name="ordem" type="number" min="1" max="8" value="<?= (int) $editando['ordem'] ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="slug">Slug</label>
                        <input class="form-control" id="slug" name="slug" type="text" value="<?= e($editando['slug']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="nome">Nome</label>
                        <input class="form-control" id="nome" name="nome" type="text" maxlength="60" value="<?= e($editando['nome']) ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="descricao">Descrição do eixo</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="2"><?= e((string) $editando['descricao']) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="polo_esq_nome">Polo esquerdo</label>
                        <input class="form-control" id="polo_esq_nome" name="polo_esq_nome" type="text" maxlength="60" value="<?= e($editando['polo_esq_nome']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="polo_dir_nome">Polo direito</label>
                        <input class="form-control" id="polo_dir_nome" name="polo_dir_nome" type="text" maxlength="60" value="<?= e($editando['polo_dir_nome']) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="cor_esq">Cor esquerda</label>
                        <input class="form-control form-control-color w-100" id="cor_esq" name="cor_esq" type="color" value="<?= e($editando['cor_esq']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="cor_dir">Cor direita</label>
                        <input class="form-control form-control-color w-100" id="cor_dir" name="cor_dir" type="color" value="<?= e($editando['cor_dir']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="icone_esq">Ícone (chave)</label>
                        <input class="form-control" id="icone_esq" name="icone_esq" type="text" value="<?= e($editando['icone_esq']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="icone_dir">Ícone direito</label>
                        <input class="form-control" id="icone_dir" name="icone_dir" type="text" value="<?= e($editando['icone_dir']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="tmdb_genres_esq">Gêneros TMDB (esquerda)</label>
                        <input class="form-control" id="tmdb_genres_esq" name="tmdb_genres_esq" type="text" value="<?= e($editando['tmdb_genres_esq']) ?>" placeholder="18,36,10749">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="tmdb_genres_dir">Gêneros TMDB (direita)</label>
                        <input class="form-control" id="tmdb_genres_dir" name="tmdb_genres_dir" type="text" value="<?= e($editando['tmdb_genres_dir']) ?>" placeholder="14,16,9648">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="tmdb_keywords_esq">Keywords TMDB (esquerda)</label>
                        <input class="form-control" id="tmdb_keywords_esq" name="tmdb_keywords_esq" type="text" value="<?= e($editando['tmdb_keywords_esq']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="tmdb_keywords_dir">Keywords TMDB (direita)</label>
                        <input class="form-control" id="tmdb_keywords_dir" name="tmdb_keywords_dir" type="text" value="<?= e($editando['tmdb_keywords_dir']) ?>">
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button class="btn btn-primary" type="submit">
                        <i class="icon-base ti tabler-device-floppy me-1"></i><?= e(t('admin.save')) ?>
                    </button>
                    <a class="btn btn-outline-secondary" href="<?= e(url('admin/eixos.php')) ?>"><?= e(t('admin.back')) ?></a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h5 class="card-title mb-1"><?= e(t('admin.axes')) ?></h5>
            <p class="card-subtitle mb-0"><?= count($eixos) ?> eixos cadastrados</p>
        </div>
    </div>
    <div class="card-datatable table-responsive">
        <table class="table table-hover align-middle responsive-card mb-0">
            <thead class="border-top">
            <tr>
                <th>#</th>
                <th>Eixo</th>
                <th>Polos</th>
                <th>Gêneros TMDB</th>
                <th class="text-end">Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($eixos as $eixo): ?>
                <tr>
                    <td data-label="#"><span class="badge bg-label-secondary rounded-pill"><?= (int) $eixo['ordem'] ?></span></td>
                    <td data-label="Eixo">
                        <span class="text-heading fw-medium"><?= e($eixo['nome']) ?></span>
                        <small class="text-body-secondary d-block"><?= e($eixo['slug']) ?></small>
                    </td>
                    <td data-label="Polos">
                        <span class="badge rounded-pill me-1" style="border:1px solid <?= e($eixo['cor_esq']) ?>;color:<?= e($eixo['cor_esq']) ?>">
                            <i class="icon-base ti tabler-circle-filled me-1" style="color:<?= e($eixo['cor_esq']) ?>"></i><?= e($eixo['polo_esq_nome']) ?>
                        </span>
                        <span class="badge rounded-pill" style="border:1px solid <?= e($eixo['cor_dir']) ?>;color:<?= e($eixo['cor_dir']) ?>">
                            <i class="icon-base ti tabler-circle-filled me-1" style="color:<?= e($eixo['cor_dir']) ?>"></i><?= e($eixo['polo_dir_nome']) ?>
                        </span>
                    </td>
                    <td data-label="Gêneros TMDB"><small class="text-body-secondary"><?= e($eixo['tmdb_genres_esq']) ?> / <?= e($eixo['tmdb_genres_dir']) ?></small></td>
                    <td data-label="Ações" class="text-end">
                        <a class="btn btn-sm btn-icon btn-text-secondary rounded-pill" href="<?= e(url('admin/eixos.php', ['editar' => $eixo['id']])) ?>" title="<?= e(t('admin.edit')) ?>">
                            <i class="icon-base ti tabler-pencil"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/partials/foot.php'; ?>
