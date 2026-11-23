<?php

declare(strict_types=1);

/** CRUD de arquétipos (16 combinações eixo dominante + polo). */

require __DIR__ . '/_guard.php';

use Core\Csrf;
use Models\Arquetipo;
use Models\Eixo;

$adminTitulo = t('admin.archetypes');
$adminAtivo = 'arquetipos.php';
$eixos = Eixo::todos($pdo);
$eixosPorId = [];

foreach ($eixos as $eixo) {
    $eixosPorId[(int) $eixo['id']] = $eixo;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!Csrf::validarRequisicao()) {
        pc_admin_redirect(url('admin/arquetipos.php'), t('erros.csrf'), 'erro');
    }

    if (pc_admin_texto('acao') === 'excluir') {
        $stmt = $pdo->prepare('DELETE FROM arquetipos WHERE id = :id');
        $stmt->execute(['id' => pc_admin_inteiro('id')]);

        pc_admin_redirect(url('admin/arquetipos.php'), t('admin.deleted'));
    }

    $id = pc_admin_inteiro('id');
    $eixo1 = pc_admin_inteiro('eixo_dominante_1');
    $polo1 = pc_admin_texto('polo_1') === 'D' ? 'D' : 'E';
    $eixo2 = pc_admin_inteiro('eixo_dominante_2', 0);
    $polo2 = pc_admin_texto('polo_2') === 'D' ? 'D' : 'E';
    $titulo = pc_admin_texto('titulo');
    $descricao = pc_admin_texto('descricao');
    $ritual = pc_admin_texto('ritual_cinema');
    $vetor = [];

    for ($i = 1; $i <= 6; $i++) {
        $valor = pc_admin_inteiro('eixo_' . $i, 50);
        $vetor[$i] = $valor;
    }

    $erros = [];

    if (!isset($eixosPorId[$eixo1])) {
        $erros[] = 'Escolha o eixo dominante.';
    }

    if ($eixo2 > 0 && !isset($eixosPorId[$eixo2])) {
        $erros[] = 'Eixo secundário inválido.';
    }

    if ($titulo === '' || $descricao === '' || $ritual === '') {
        $erros[] = 'Preencha título, descrição e ritual.';
    }

    foreach ($vetor as $valor) {
        if ($valor < 0 || $valor > 100) {
            $erros[] = 'As notas do vetor devem ficar entre 0 e 100.';
            break;
        }
    }

    if ($erros !== []) {
        pc_admin_redirect(
            url('admin/arquetipos.php', ['editar' => $id > 0 ? $id : 'novo']),
            implode(' ', $erros),
            'erro'
        );
    }

    $slug = pc_admin_slug($titulo);
    $dados = [
        'eixo_dominante_1' => $eixo1,
        'polo_1' => $polo1,
        'eixo_dominante_2' => $eixo2 > 0 ? $eixo2 : null,
        'polo_2' => $eixo2 > 0 ? $polo2 : null,
        'titulo_key' => pc_admin_texto('titulo_key') ?: 'arquetipo.' . $slug,
        'descricao_key' => pc_admin_texto('descricao_key') ?: 'arquetipo.' . $slug . '.desc',
        'ritual_cinema_key' => pc_admin_texto('ritual_cinema_key') ?: 'arquetipo.' . $slug . '.ritual',
        'titulo' => $titulo,
        'descricao' => $descricao,
        'ritual_cinema' => $ritual,
    ];

    for ($i = 1; $i <= 6; $i++) {
        $dados['eixo_' . $i] = $vetor[$i];
    }

    try {
        if ($id > 0) {
            $set = implode(', ', array_map(static fn (string $c): string => $c . ' = :' . $c, array_keys($dados)));
            $stmt = $pdo->prepare('UPDATE arquetipos SET ' . $set . ' WHERE id = :id');
            $stmt->execute($dados + ['id' => $id]);
        } else {
            $campos = implode(', ', array_keys($dados));
            $marcadores = implode(', ', array_map(static fn (string $c): string => ':' . $c, array_keys($dados)));
            $stmt = $pdo->prepare('INSERT INTO arquetipos (' . $campos . ') VALUES (' . $marcadores . ')');
            $stmt->execute($dados);
        }
    } catch (PDOException $erro) {
        $mensagem = ($erro->errorInfo[1] ?? 0) === 1062
            ? 'Já existe um arquétipo para esse eixo dominante e polo (ou a chave de título está repetida).'
            : 'Erro ao salvar: ' . $erro->getMessage();

        pc_admin_redirect(url('admin/arquetipos.php'), $mensagem, 'erro');
    }

    pc_admin_redirect(url('admin/arquetipos.php'), t('admin.saved'));
}

$arquetipos = Arquetipo::todos($pdo);
$editar = $_GET['editar'] ?? null;
$editando = null;

if ($editar === 'novo') {
    $editando = [
        'id' => 0, 'eixo_dominante_1' => 1, 'polo_1' => 'E', 'eixo_dominante_2' => '',
        'polo_2' => 'E', 'titulo_key' => '', 'descricao_key' => '', 'ritual_cinema_key' => '',
        'titulo' => '', 'descricao' => '', 'ritual_cinema' => '',
    ];
} elseif ($editar !== null) {
    foreach ($arquetipos as $arquetipo) {
        if ((int) $arquetipo['id'] === (int) $editar) {
            $editando = $arquetipo;
        }
    }
}

require __DIR__ . '/partials/head.php';
?>

<div class="pc-admin__head">
    <div>
        <h1><?= e(t('admin.archetypes')) ?></h1>
        <p class="pc-text-muted" style="font-size:13px">
            <?= count($arquetipos) ?> arquétipos · um por eixo dominante + polo (o segundo eixo é decorativo).
        </p>
    </div>

    <a class="pc-btn pc-btn--sm" href="<?= url('admin/arquetipos.php', ['editar' => 'novo']) ?>">
        <?= icon('compass', 16) ?> <?= e(t('admin.new')) ?>
    </a>
</div>

<?php if ($editando !== null): ?>
    <form class="pc-form pc-mb-3" method="post" action="<?= url('admin/arquetipos.php') ?>">
        <?= Csrf::campo() ?>
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">

        <div class="pc-form__row">
            <div class="pc-field">
                <label class="pc-label" for="eixo_dominante_1">Eixo dominante</label>
                <select class="pc-select" id="eixo_dominante_1" name="eixo_dominante_1">
                    <?php foreach ($eixos as $eixo): ?>
                        <option value="<?= (int) $eixo['id'] ?>" <?= (int) $eixo['id'] === (int) $editando['eixo_dominante_1'] ? 'selected' : '' ?>>
                            <?= e($eixo['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="pc-field">
                <label class="pc-label" for="polo_1">Polo dominante</label>
                <select class="pc-select" id="polo_1" name="polo_1">
                    <option value="E" <?= $editando['polo_1'] === 'E' ? 'selected' : '' ?>>Esquerdo</option>
                    <option value="D" <?= $editando['polo_1'] === 'D' ? 'selected' : '' ?>>Direito</option>
                </select>
            </div>
        </div>

        <div class="pc-form__row">
            <div class="pc-field">
                <label class="pc-label" for="eixo_dominante_2">Eixo secundário (opcional)</label>
                <select class="pc-select" id="eixo_dominante_2" name="eixo_dominante_2">
                    <option value="0">Nenhum</option>
                    <?php foreach ($eixos as $eixo): ?>
                        <option value="<?= (int) $eixo['id'] ?>" <?= (int) $eixo['id'] === (int) ($editando['eixo_dominante_2'] ?? 0) ? 'selected' : '' ?>>
                            <?= e($eixo['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="pc-field">
                <label class="pc-label" for="polo_2">Polo secundário</label>
                <select class="pc-select" id="polo_2" name="polo_2">
                    <option value="E" <?= ($editando['polo_2'] ?? 'E') === 'E' ? 'selected' : '' ?>>Esquerdo</option>
                    <option value="D" <?= ($editando['polo_2'] ?? 'E') === 'D' ? 'selected' : '' ?>>Direito</option>
                </select>
            </div>
        </div>

        <div class="pc-field">
            <span class="pc-label">Vetor de referência (0 = polo esquerdo, 100 = direito)</span>
        </div>

        <div class="pc-form__row" style="grid-template-columns: repeat(4, minmax(0,1fr))">
            <?php foreach ($eixos as $indice => $eixo): ?>
                <div class="pc-field">
                    <label class="pc-label" for="arq_eixo_<?= $indice + 1 ?>"><?= e($eixo['nome']) ?></label>
                    <input class="pc-input" id="arq_eixo_<?= $indice + 1 ?>" name="eixo_<?= $indice + 1 ?>" type="number" min="0" max="100"
                           value="<?= (int) ($editando['vetor'][$indice] ?? 50) ?>">
                </div>
            <?php endforeach; ?>
        </div>

        <div class="pc-field">
            <label class="pc-label" for="titulo">Título do arquétipo</label>
            <input class="pc-input" id="titulo" name="titulo" type="text" maxlength="120" value="<?= e($editando['titulo']) ?>" required>
        </div>

        <div class="pc-field">
            <label class="pc-label" for="descricao">Descrição psicológica</label>
            <textarea class="pc-textarea" id="descricao" name="descricao" required><?= e($editando['descricao']) ?></textarea>
        </div>

        <div class="pc-field">
            <label class="pc-label" for="ritual_cinema">Ritual de cinema</label>
            <textarea class="pc-textarea" id="ritual_cinema" name="ritual_cinema" style="min-height:70px" required><?= e($editando['ritual_cinema']) ?></textarea>
        </div>

        <div class="pc-form__row pc-form__row--3">
            <div class="pc-field">
                <label class="pc-label" for="titulo_key">Chave do título</label>
                <input class="pc-input" id="titulo_key" name="titulo_key" type="text" value="<?= e($editando['titulo_key']) ?>" placeholder="auto">
            </div>
            <div class="pc-field">
                <label class="pc-label" for="descricao_key">Chave da descrição</label>
                <input class="pc-input" id="descricao_key" name="descricao_key" type="text" value="<?= e($editando['descricao_key']) ?>" placeholder="auto">
            </div>
            <div class="pc-field">
                <label class="pc-label" for="ritual_cinema_key">Chave do ritual</label>
                <input class="pc-input" id="ritual_cinema_key" name="ritual_cinema_key" type="text" value="<?= e($editando['ritual_cinema_key']) ?>" placeholder="auto">
            </div>
        </div>

        <div class="pc-form__actions">
            <button class="pc-btn" type="submit"><?= e(t('admin.save')) ?></button>
            <a class="pc-btn pc-btn--ghost" href="<?= url('admin/arquetipos.php') ?>"><?= e(t('admin.back')) ?></a>

            <?php if ((int) $editando['id'] > 0): ?>
                <button class="pc-btn pc-btn--light" type="submit" form="form-excluir"><?= e(t('admin.delete')) ?></button>
            <?php endif; ?>
        </div>
    </form>

    <?php if ((int) $editando['id'] > 0): ?>
        <form id="form-excluir" method="post" action="<?= url('admin/arquetipos.php') ?>" data-confirm="<?= e(t('admin.confirm_delete')) ?>">
            <?= Csrf::campo() ?>
            <input type="hidden" name="acao" value="excluir">
            <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">
        </form>
    <?php endif; ?>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
        <thead>
        <tr>
            <th>Arquétipo</th>
            <th>Eixo dominante</th>
            <th>Secundário</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($arquetipos as $arquetipo): ?>
            <?php
            $eixo1 = $eixosPorId[$arquetipo['eixo_dominante_1']] ?? null;
            $eixo2 = $arquetipo['eixo_dominante_2'] !== null
                ? ($eixosPorId[$arquetipo['eixo_dominante_2']] ?? null)
                : null;
            ?>
            <tr>
                <td>
                    <strong><?= e($arquetipo['titulo']) ?></strong>
                    <div class="pc-text-muted" style="font-size:11px"><?= e($arquetipo['titulo_key']) ?></div>
                </td>
                <td>
                    <?= e($eixo1['nome'] ?? '—') ?>
                    <span class="pc-badge-mini"><?= $arquetipo['polo_1'] === 'E' ? 'esquerdo' : 'direito' ?></span>
                </td>
                <td class="pc-text-muted">
                    <?= $eixo2 !== null ? e($eixo2['nome']) . ' · ' . ($arquetipo['polo_2'] === 'E' ? 'esquerdo' : 'direito') : '—' ?>
                </td>
                <td><a href="<?= url('admin/arquetipos.php', ['editar' => $arquetipo['id']]) ?>"><?= e(t('admin.edit')) ?></a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/partials/foot.php'; ?>
