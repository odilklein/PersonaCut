<?php

declare(strict_types=1);

/** CRUD das entidades do matcher (personagens, diretores, estúdios e filmes). */

require __DIR__ . '/_guard.php';

use Core\Csrf;
use Core\Vetor;
use Models\Eixo;
use Models\EntidadeMatch;

$adminTitulo = t('admin.entities');
$adminAtivo = 'entidades.php';
$eixos = Eixo::todos($pdo);
$tipos = ['personagem', 'diretor', 'estudio'];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!Csrf::validarRequisicao()) {
        pc_admin_redirect(url('admin/entidades.php'), t('erros.csrf'), 'erro');
    }

    if (pc_admin_texto('acao') === 'excluir') {
        $stmt = $pdo->prepare('DELETE FROM entidades_match WHERE id = :id');
        $stmt->execute(['id' => pc_admin_inteiro('id')]);

        pc_admin_redirect(url('admin/entidades.php'), t('admin.deleted'));
    }

    $id = pc_admin_inteiro('id');
    $tipo = pc_admin_texto('tipo');
    $nome = pc_admin_texto('nome');
    $obra = pc_admin_texto('obra_origem');
    $ano = pc_admin_texto('ano');
    $frase = pc_admin_texto('frase');
    $fraseKey = pc_admin_texto('frase_key');
    $imagem = pc_admin_texto('imagem_url');
    $tmdbId = pc_admin_texto('tmdb_id');
    $bio = pc_admin_texto('bio');
    $atorNome = pc_admin_texto('ator_nome');
    $pais = pc_admin_texto('pais');
    $nascimento = pc_admin_texto('nascimento');

    $erros = [];

    if (!in_array($tipo, $tipos, true)) {
        $erros[] = 'Tipo inválido.';
    }

    if ($nome === '' || mb_strlen($nome) > 120) {
        $erros[] = 'Informe um nome de até 120 caracteres.';
    }

    if ($obra === '') {
        $erros[] = 'Informe a obra de origem.';
    }

    if ($frase === '') {
        $erros[] = 'Informe a frase de assinatura.';
    }

    if ($ano !== '' && (!is_numeric($ano) || (int) $ano < 1800 || (int) $ano > 2100)) {
        $erros[] = 'Ano inválido.';
    }

    if ($nascimento !== '' && (!is_numeric($nascimento) || (int) $nascimento < 1800 || (int) $nascimento > 2100)) {
        $erros[] = 'Ano de nascimento inválido.';
    }

    $vetor = [];

    for ($i = 1; $i <= Vetor::EIXOS; $i++) {
        $valor = pc_admin_inteiro('eixo_' . $i, 50);

        if ($valor < 0 || $valor > 100) {
            $erros[] = 'As notas dos eixos devem ficar entre 0 e 100.';
            break;
        }

        $vetor[] = $valor;
    }

    if ($erros !== []) {
        pc_admin_redirect(
            url('admin/entidades.php', ['editar' => $id > 0 ? $id : 'novo', 'tipo' => $tipo]),
            implode(' ', $erros),
            'erro'
        );
    }

    if ($fraseKey === '') {
        $fraseKey = 'frase.' . pc_admin_slug($nome) . '.' . substr(bin2hex(random_bytes(2)), 0, 4);
    }

    $colunas = Vetor::paraColunasLegado($vetor);

    $dados = [
        'tipo' => $tipo,
        'nome' => $nome,
        'obra_origem' => $obra,
        'ano' => $ano === '' ? null : (int) $ano,
        'frase_key' => $fraseKey,
        'frase' => $frase,
        'imagem_url' => $imagem === '' ? null : $imagem,
        'tmdb_id' => $tmdbId === '' ? null : (int) $tmdbId,
    ] + $colunas;

    // Campos opcionais só entram no UPDATE se vierem do formulário, para não
    // apagar dados enriquecidos pela TMDB em integrações antigas.
    if (array_key_exists('bio', $_POST)) {
        $dados['bio'] = $bio === '' ? null : $bio;
    }

    if (array_key_exists('ator_nome', $_POST)) {
        $dados['ator_nome'] = $atorNome === '' ? null : $atorNome;
    }

    if (array_key_exists('pais', $_POST)) {
        $dados['pais'] = $pais === '' ? null : $pais;
    }

    if (array_key_exists('nascimento', $_POST)) {
        $dados['nascimento'] = $nascimento === '' ? null : (int) $nascimento;
    }

    try {
        if ($id > 0) {
            $set = implode(', ', array_map(static fn (string $c): string => $c . ' = :' . $c, array_keys($dados)));
            $stmt = $pdo->prepare('UPDATE entidades_match SET ' . $set . ' WHERE id = :id');
            $stmt->execute($dados + ['id' => $id]);
        } else {
            $campos = implode(', ', array_keys($dados));
            $marcadores = implode(', ', array_map(static fn (string $c): string => ':' . $c, array_keys($dados)));
            $stmt = $pdo->prepare('INSERT INTO entidades_match (' . $campos . ') VALUES (' . $marcadores . ')');
            $stmt->execute($dados);
        }
    } catch (PDOException $erro) {
        $mensagem = ($erro->errorInfo[1] ?? 0) === 1062
            ? 'Já existe uma entidade desse tipo com esse nome.'
            : 'Erro ao salvar: ' . $erro->getMessage();

        pc_admin_redirect(url('admin/entidades.php'), $mensagem, 'erro');
    }

    pc_admin_redirect(url('admin/entidades.php', ['tipo' => $tipo]), t('admin.saved'));
}

$filtro = is_string($_GET['tipo'] ?? null) && in_array($_GET['tipo'], $tipos, true) ? (string) $_GET['tipo'] : '';
$entidades = EntidadeMatch::todas($pdo);

if ($filtro !== '') {
    $entidades = array_values(array_filter(
        $entidades,
        static fn (array $e): bool => $e['tipo'] === $filtro
    ));
}

$editar = $_GET['editar'] ?? null;
$editando = null;

if ($editar === 'novo') {
    $editando = [
        'id' => 0, 'tipo' => $filtro !== '' ? $filtro : 'personagem', 'nome' => '', 'obra_origem' => '',
        'ano' => '', 'frase_key' => '', 'frase' => '', 'imagem_url' => '', 'tmdb_id' => '',
        'vetor' => array_fill(0, Vetor::EIXOS, 50),
    ];
} elseif ($editar !== null) {
    foreach (EntidadeMatch::todas($pdo) as $entidade) {
        if ((int) $entidade['id'] === (int) $editar) {
            $editando = $entidade;
        }
    }
}

require __DIR__ . '/partials/head.php';
?>

<div class="page-header">
    <div>
        <h1><i class="icon-base ti tabler-users"></i><?= e(t('admin.entities')) ?></h1>
        <span class="page-subtitle">Filmografia de diretores/escolas alimenta o match; personagens entram apenas no retrato.</span>
    </div>
    <div class="page-header-actions">
        <a class="btn btn-primary" href="<?= e(url('admin/entidades.php', ['editar' => 'novo', 'tipo' => $filtro])) ?>">
            <i class="icon-base ti tabler-plus"></i> <?= e(t('admin.new')) ?>
        </a>
    </div>
</div>

<?php if ($editando !== null): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-1"><?= (int) $editando['id'] > 0 ? 'Editar entidade' : 'Nova entidade' ?></h5>
            <p class="card-subtitle mb-0">Vetor editorial legado de 8 eixos (não usado no match)</p>
        </div>
        <div class="card-body">
            <form method="post" action="<?= e(url('admin/entidades.php')) ?>">
                <?= Csrf::campo() ?>
                <input type="hidden" name="acao" value="salvar">
                <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="tipo">Tipo</label>
                        <select class="form-select" id="tipo" name="tipo">
                            <?php foreach ($tipos as $tipo): ?>
                                <option value="<?= e($tipo) ?>" <?= $editando['tipo'] === $tipo ? 'selected' : '' ?>>
                                    <?= e(t('resultado.types.' . $tipo)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="nome">Nome</label>
                        <input class="form-control" id="nome" name="nome" type="text" maxlength="120" value="<?= e((string) $editando['nome']) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="ano">Ano (opcional)</label>
                        <input class="form-control" id="ano" name="ano" type="number" min="1800" max="2100" value="<?= e((string) ($editando['ano'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="obra_origem">Obra de origem</label>
                        <input class="form-control" id="obra_origem" name="obra_origem" type="text" maxlength="160" value="<?= e((string) $editando['obra_origem']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="frase">Frase de assinatura</label>
                        <input class="form-control" id="frase" name="frase" type="text" maxlength="255" value="<?= e((string) $editando['frase']) ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="bio">Biografia curta</label>
                        <textarea class="form-control" id="bio" name="bio" rows="3" maxlength="1200"><?= e((string) ($editando['bio'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="ator_nome">Intérprete (personagens)</label>
                        <input class="form-control" id="ator_nome" name="ator_nome" type="text" maxlength="120" value="<?= e((string) ($editando['ator_nome'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="pais">Origem / país</label>
                        <input class="form-control" id="pais" name="pais" type="text" maxlength="80" value="<?= e((string) ($editando['pais'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="nascimento">Nascimento (ano)</label>
                        <input class="form-control" id="nascimento" name="nascimento" type="number" min="1800" max="2100" value="<?= e((string) ($editando['nascimento'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="imagem_url">URL da imagem (opcional)</label>
                        <input class="form-control" id="imagem_url" name="imagem_url" type="text" value="<?= e((string) ($editando['imagem_url'] ?? '')) ?>" placeholder="/assets/img/...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="tmdb_id">ID TMDB (opcional)</label>
                        <input class="form-control" id="tmdb_id" name="tmdb_id" type="number" value="<?= e((string) ($editando['tmdb_id'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="frase_key">Chave da frase (opcional)</label>
                        <input class="form-control" id="frase_key" name="frase_key" type="text" value="<?= e((string) ($editando['frase_key'] ?? '')) ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label mb-0">Vetor editorial legado (8 eixos)</label>
                    </div>
                    <?php foreach ($eixos as $indice => $eixo): ?>
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="eixo_<?= $indice + 1 ?>"><?= e($eixo['nome']) ?></label>
                            <input class="form-control" id="eixo_<?= $indice + 1 ?>" name="eixo_<?= $indice + 1 ?>" type="number" min="0" max="100" value="<?= (int) ($editando['vetor'][$indice] ?? 50) ?>" required>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button class="btn btn-primary" type="submit">
                        <i class="icon-base ti tabler-device-floppy me-1"></i><?= e(t('admin.save')) ?>
                    </button>
                    <a class="btn btn-outline-secondary" href="<?= e(url('admin/entidades.php', ['tipo' => $filtro])) ?>"><?= e(t('admin.back')) ?></a>
                    <?php if ((int) $editando['id'] > 0): ?>
                        <button class="btn btn-outline-danger ms-auto" type="submit" form="form-excluir">
                            <i class="icon-base ti tabler-trash me-1"></i><?= e(t('admin.delete')) ?>
                        </button>
                    <?php endif; ?>
                </div>
            </form>

            <?php if ((int) $editando['id'] > 0): ?>
                <form id="form-excluir" method="post" action="<?= e(url('admin/entidades.php')) ?>" data-confirm="<?= e(t('admin.confirm_delete')) ?>">
                    <?= Csrf::campo() ?>
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h5 class="card-title mb-1"><?= e(t('admin.entities')) ?></h5>
            <p class="card-subtitle mb-0"><?= count($entidades) ?> registros</p>
        </div>
        <div class="d-flex flex-wrap gap-1">
            <a class="btn btn-sm <?= $filtro === '' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= e(url('admin/entidades.php')) ?>">Todos</a>
            <?php foreach ($tipos as $tipo): ?>
                <a class="btn btn-sm <?= $filtro === $tipo ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= e(url('admin/entidades.php', ['tipo' => $tipo])) ?>">
                    <?= e(t('resultado.types.' . $tipo)) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card-datatable table-responsive">
        <table class="table table-hover align-middle responsive-card mb-0">
            <thead class="border-top">
            <tr>
                <th>Nome</th>
                <th>Tipo</th>
                <th>Obra</th>
                <th>Vetor (8 eixos)</th>
                <th class="text-end">Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($entidades === []): ?>
                <tr><td colspan="5" class="text-center text-body-secondary py-5"><?= e(t('admin.no_data')) ?></td></tr>
            <?php endif; ?>

            <?php foreach ($entidades as $entidade): ?>
                <tr>
                    <td data-label="Nome"><span class="text-heading fw-medium"><?= e($entidade['nome']) ?></span></td>
                    <td data-label="Tipo"><span class="badge bg-label-primary rounded-pill"><?= e(t('resultado.types.' . $entidade['tipo'])) ?></span></td>
                    <td data-label="Obra"><small class="text-body-secondary"><?= e($entidade['obra_origem']) ?></small></td>
                    <td data-label="Vetor"><small class="font-monospace text-body-secondary"><?= e(implode(' · ', array_map(static fn (float $v): string => (string) (int) $v, $entidade['vetor']))) ?></small></td>
                    <td data-label="Ações" class="text-end">
                        <a class="btn btn-sm btn-icon btn-text-secondary rounded-pill" href="<?= e(url('admin/entidades.php', ['editar' => $entidade['id'], 'tipo' => $entidade['tipo']])) ?>" title="<?= e(t('admin.edit')) ?>">
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
