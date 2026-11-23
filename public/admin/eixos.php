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

<div class="pc-admin__head">
    <div>
        <h1><?= e(t('admin.axes')) ?></h1>
        <p class="pc-text-muted" style="font-size:13px">Os 6 eixos são fixos: edite nomes, polos, cores e o mapeamento TMDB.</p>
    </div>
</div>

<?php if ($editando !== null): ?>
    <form class="pc-form pc-mb-3" method="post" action="<?= url('admin/eixos.php') ?>">
        <?= Csrf::campo() ?>
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">

        <div class="pc-form__row pc-form__row--3">
            <div class="pc-field">
                <label class="pc-label" for="ordem">Ordem</label>
                <input class="pc-input" id="ordem" name="ordem" type="number" min="1" max="8" value="<?= (int) $editando['ordem'] ?>">
            </div>
            <div class="pc-field">
                <label class="pc-label" for="slug">Slug</label>
                <input class="pc-input" id="slug" name="slug" type="text" value="<?= e($editando['slug']) ?>">
            </div>
            <div class="pc-field">
                <label class="pc-label" for="nome">Nome</label>
                <input class="pc-input" id="nome" name="nome" type="text" maxlength="60" value="<?= e($editando['nome']) ?>" required>
            </div>
        </div>

        <div class="pc-field">
            <label class="pc-label" for="descricao">Descrição do eixo</label>
            <textarea class="pc-textarea" id="descricao" name="descricao"><?= e((string) $editando['descricao']) ?></textarea>
        </div>

        <div class="pc-form__row">
            <div class="pc-field">
                <label class="pc-label" for="polo_esq_nome">Polo esquerdo</label>
                <input class="pc-input" id="polo_esq_nome" name="polo_esq_nome" type="text" maxlength="60" value="<?= e($editando['polo_esq_nome']) ?>" required>
            </div>
            <div class="pc-field">
                <label class="pc-label" for="polo_dir_nome">Polo direito</label>
                <input class="pc-input" id="polo_dir_nome" name="polo_dir_nome" type="text" maxlength="60" value="<?= e($editando['polo_dir_nome']) ?>" required>
            </div>
        </div>

        <div class="pc-form__row" style="grid-template-columns: repeat(4, minmax(0,1fr))">
            <div class="pc-field">
                <label class="pc-label" for="cor_esq">Cor esquerda</label>
                <input class="pc-input" id="cor_esq" name="cor_esq" type="color" value="<?= e($editando['cor_esq']) ?>" style="padding:4px;height:44px">
            </div>
            <div class="pc-field">
                <label class="pc-label" for="cor_dir">Cor direita</label>
                <input class="pc-input" id="cor_dir" name="cor_dir" type="color" value="<?= e($editando['cor_dir']) ?>" style="padding:4px;height:44px">
            </div>
            <div class="pc-field">
                <label class="pc-label" for="icone_esq">Ícone (chave)</label>
                <input class="pc-input" id="icone_esq" name="icone_esq" type="text" value="<?= e($editando['icone_esq']) ?>">
            </div>
            <div class="pc-field">
                <label class="pc-label" for="icone_dir">Ícone direito</label>
                <input class="pc-input" id="icone_dir" name="icone_dir" type="text" value="<?= e($editando['icone_dir']) ?>">
            </div>
        </div>

        <div class="pc-form__row">
            <div class="pc-field">
                <label class="pc-label" for="tmdb_genres_esq">Gêneros TMDB (esquerda)</label>
                <input class="pc-input" id="tmdb_genres_esq" name="tmdb_genres_esq" type="text" value="<?= e($editando['tmdb_genres_esq']) ?>" placeholder="18,36,10749">
            </div>
            <div class="pc-field">
                <label class="pc-label" for="tmdb_genres_dir">Gêneros TMDB (direita)</label>
                <input class="pc-input" id="tmdb_genres_dir" name="tmdb_genres_dir" type="text" value="<?= e($editando['tmdb_genres_dir']) ?>" placeholder="14,16,9648">
            </div>
        </div>

        <div class="pc-form__row">
            <div class="pc-field">
                <label class="pc-label" for="tmdb_keywords_esq">Keywords TMDB (esquerda)</label>
                <input class="pc-input" id="tmdb_keywords_esq" name="tmdb_keywords_esq" type="text" value="<?= e($editando['tmdb_keywords_esq']) ?>">
            </div>
            <div class="pc-field">
                <label class="pc-label" for="tmdb_keywords_dir">Keywords TMDB (direita)</label>
                <input class="pc-input" id="tmdb_keywords_dir" name="tmdb_keywords_dir" type="text" value="<?= e($editando['tmdb_keywords_dir']) ?>">
            </div>
        </div>

        <div class="pc-form__actions">
            <button class="pc-btn" type="submit"><?= e(t('admin.save')) ?></button>
            <a class="pc-btn pc-btn--ghost" href="<?= url('admin/eixos.php') ?>"><?= e(t('admin.back')) ?></a>
        </div>
    </form>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
        <thead>
        <tr>
            <th>#</th>
            <th>Eixo</th>
            <th>Polos</th>
            <th>Gêneros TMDB</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($eixos as $eixo): ?>
            <tr>
                <td><?= (int) $eixo['ordem'] ?></td>
                <td>
                    <strong><?= e($eixo['nome']) ?></strong>
                    <div class="pc-text-muted" style="font-size:11px"><?= e($eixo['slug']) ?></div>
                </td>
                <td>
                    <span class="pc-chip" style="border-color: <?= e($eixo['cor_esq']) ?>">
                        <i class="pc-chip__dot" style="background: <?= e($eixo['cor_esq']) ?>"></i><?= e($eixo['polo_esq_nome']) ?>
                    </span>
                    <span class="pc-chip" style="border-color: <?= e($eixo['cor_dir']) ?>">
                        <i class="pc-chip__dot" style="background: <?= e($eixo['cor_dir']) ?>"></i><?= e($eixo['polo_dir_nome']) ?>
                    </span>
                </td>
                <td class="pc-text-muted" style="font-size:11.5px">
                    <?= e($eixo['tmdb_genres_esq']) ?> / <?= e($eixo['tmdb_genres_dir']) ?>
                </td>
                <td><a href="<?= url('admin/eixos.php', ['editar' => $eixo['id']]) ?>"><?= e(t('admin.edit')) ?></a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/partials/foot.php'; ?>
