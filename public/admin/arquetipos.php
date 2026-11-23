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

<div class="page-header">
    <div>
        <h1><i class="icon-base ti tabler-layers"></i><?= e(t('admin.archetypes')) ?></h1>
        <span class="page-subtitle"><?= count($arquetipos) ?> arquétipos · um por eixo dominante + polo</span>
    </div>
    <div class="page-header-actions">
        <a class="btn btn-primary" href="<?= e(url('admin/arquetipos.php', ['editar' => 'novo'])) ?>">
            <i class="icon-base ti tabler-plus"></i> <?= e(t('admin.new')) ?>
        </a>
    </div>
</div>

<?php if ($editando !== null): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-1"><?= (int) $editando['id'] > 0 ? 'Editar arquétipo' : 'Novo arquétipo' ?></h5>
            <p class="card-subtitle mb-0">Defina o vetor de referência e os textos exibidos no resultado</p>
        </div>
        <div class="card-body">
            <form method="post" action="<?= e(url('admin/arquetipos.php')) ?>">
                <?= Csrf::campo() ?>
                <input type="hidden" name="acao" value="salvar">
                <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="eixo_dominante_1">Eixo dominante</label>
                        <select class="form-select" id="eixo_dominante_1" name="eixo_dominante_1">
                            <?php foreach ($eixos as $eixo): ?>
                                <option value="<?= (int) $eixo['id'] ?>" <?= (int) $eixo['id'] === (int) $editando['eixo_dominante_1'] ? 'selected' : '' ?>>
                                    <?= e($eixo['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="polo_1">Polo dominante</label>
                        <select class="form-select" id="polo_1" name="polo_1">
                            <option value="E" <?= $editando['polo_1'] === 'E' ? 'selected' : '' ?>>Esquerdo</option>
                            <option value="D" <?= $editando['polo_1'] === 'D' ? 'selected' : '' ?>>Direito</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="eixo_dominante_2">Eixo secundário (opcional)</label>
                        <select class="form-select" id="eixo_dominante_2" name="eixo_dominante_2">
                            <option value="0">Nenhum</option>
                            <?php foreach ($eixos as $eixo): ?>
                                <option value="<?= (int) $eixo['id'] ?>" <?= (int) $eixo['id'] === (int) ($editando['eixo_dominante_2'] ?? 0) ? 'selected' : '' ?>>
                                    <?= e($eixo['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="polo_2">Polo secundário</label>
                        <select class="form-select" id="polo_2" name="polo_2">
                            <option value="E" <?= ($editando['polo_2'] ?? 'E') === 'E' ? 'selected' : '' ?>>Esquerdo</option>
                            <option value="D" <?= ($editando['polo_2'] ?? 'E') === 'D' ? 'selected' : '' ?>>Direito</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label mb-0">Vetor de referência <small class="text-body-secondary">(0 = polo esquerdo, 100 = direito)</small></label>
                    </div>
                    <?php foreach ($eixos as $indice => $eixo): ?>
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="arq_eixo_<?= $indice + 1 ?>"><?= e($eixo['nome']) ?></label>
                            <input class="form-control" id="arq_eixo_<?= $indice + 1 ?>" name="eixo_<?= $indice + 1 ?>" type="number" min="0" max="100" value="<?= (int) ($editando['vetor'][$indice] ?? 50) ?>">
                        </div>
                    <?php endforeach; ?>

                    <div class="col-12">
                        <label class="form-label" for="titulo">Título do arquétipo</label>
                        <input class="form-control" id="titulo" name="titulo" type="text" maxlength="120" value="<?= e($editando['titulo']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="descricao">Descrição psicológica</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="4" required><?= e($editando['descricao']) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="ritual_cinema">Ritual de cinema</label>
                        <textarea class="form-control" id="ritual_cinema" name="ritual_cinema" rows="4" required><?= e($editando['ritual_cinema']) ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="titulo_key">Chave do título</label>
                        <input class="form-control" id="titulo_key" name="titulo_key" type="text" value="<?= e($editando['titulo_key']) ?>" placeholder="auto">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="descricao_key">Chave da descrição</label>
                        <input class="form-control" id="descricao_key" name="descricao_key" type="text" value="<?= e($editando['descricao_key']) ?>" placeholder="auto">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="ritual_cinema_key">Chave do ritual</label>
                        <input class="form-control" id="ritual_cinema_key" name="ritual_cinema_key" type="text" value="<?= e($editando['ritual_cinema_key']) ?>" placeholder="auto">
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button class="btn btn-primary" type="submit">
                        <i class="icon-base ti tabler-device-floppy me-1"></i><?= e(t('admin.save')) ?>
                    </button>
                    <a class="btn btn-outline-secondary" href="<?= e(url('admin/arquetipos.php')) ?>"><?= e(t('admin.back')) ?></a>
                    <?php if ((int) $editando['id'] > 0): ?>
                        <button class="btn btn-outline-danger ms-auto" type="submit" form="form-excluir">
                            <i class="icon-base ti tabler-trash me-1"></i><?= e(t('admin.delete')) ?>
                        </button>
                    <?php endif; ?>
                </div>
            </form>

            <?php if ((int) $editando['id'] > 0): ?>
                <form id="form-excluir" method="post" action="<?= e(url('admin/arquetipos.php')) ?>" data-confirm="<?= e(t('admin.confirm_delete')) ?>">
                    <?= Csrf::campo() ?>
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-1"><?= e(t('admin.archetypes')) ?></h5>
        <p class="card-subtitle mb-0">Ordenados por eixo dominante</p>
    </div>
    <div class="card-datatable table-responsive">
        <table class="table table-hover align-middle responsive-card mb-0">
            <thead class="border-top">
            <tr>
                <th>Arquétipo</th>
                <th>Eixo dominante</th>
                <th>Secundário</th>
                <th class="text-end">Ações</th>
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
                    <td data-label="Arquétipo">
                        <span class="text-heading fw-medium"><?= e($arquetipo['titulo']) ?></span>
                        <small class="text-body-secondary d-block"><?= e($arquetipo['titulo_key']) ?></small>
                    </td>
                    <td data-label="Eixo dominante">
                        <?= e($eixo1['nome'] ?? '—') ?>
                        <span class="badge bg-label-primary rounded-pill ms-1"><?= $arquetipo['polo_1'] === 'E' ? 'esquerdo' : 'direito' ?></span>
                    </td>
                    <td data-label="Secundário">
                        <?= $eixo2 !== null
                            ? e($eixo2['nome']) . ' <span class="badge bg-label-secondary rounded-pill">' . ($arquetipo['polo_2'] === 'E' ? 'esquerdo' : 'direito') . '</span>'
                            : '<span class="text-body-secondary">—</span>' ?>
                    </td>
                    <td data-label="Ações" class="text-end">
                        <a class="btn btn-sm btn-icon btn-text-secondary rounded-pill" href="<?= e(url('admin/arquetipos.php', ['editar' => $arquetipo['id']])) ?>" title="<?= e(t('admin.edit')) ?>">
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
