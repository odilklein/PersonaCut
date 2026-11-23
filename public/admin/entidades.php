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

<div class="pc-admin__head">
    <div>
        <h1><?= e(t('admin.entities')) ?></h1>
        <p class="pc-text-muted" style="font-size:13px">O vetor editorial de 8 eixos é legado. O match usa a filmografia de diretores/escolas; personagens não entram no matching.</p>
    </div>

    <a class="pc-btn pc-btn--sm" href="<?= url('admin/entidades.php', ['editar' => 'novo', 'tipo' => $filtro]) ?>">
        <?= icon('users', 16) ?> <?= e(t('admin.new')) ?>
    </a>
</div>

<?php if ($editando !== null): ?>
    <form class="pc-form pc-mb-3" method="post" action="<?= url('admin/entidades.php') ?>">
        <?= Csrf::campo() ?>
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">

        <div class="pc-form__row pc-form__row--3">
            <div class="pc-field">
                <label class="pc-label" for="tipo">Tipo</label>
                <select class="pc-select" id="tipo" name="tipo">
                    <?php foreach ($tipos as $tipo): ?>
                        <option value="<?= e($tipo) ?>" <?= $editando['tipo'] === $tipo ? 'selected' : '' ?>>
                            <?= e(t('resultado.types.' . $tipo)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="pc-field">
                <label class="pc-label" for="nome">Nome</label>
                <input class="pc-input" id="nome" name="nome" type="text" maxlength="120" value="<?= e((string) $editando['nome']) ?>" required>
            </div>

            <div class="pc-field">
                <label class="pc-label" for="ano">Ano (opcional)</label>
                <input class="pc-input" id="ano" name="ano" type="number" min="1800" max="2100" value="<?= e((string) ($editando['ano'] ?? '')) ?>">
            </div>
        </div>

        <div class="pc-form__row">
            <div class="pc-field">
                <label class="pc-label" for="obra_origem">Obra de origem</label>
                <input class="pc-input" id="obra_origem" name="obra_origem" type="text" maxlength="160" value="<?= e((string) $editando['obra_origem']) ?>" required>
            </div>

            <div class="pc-field">
                <label class="pc-label" for="frase">Frase de assinatura</label>
                <input class="pc-input" id="frase" name="frase" type="text" maxlength="255" value="<?= e((string) $editando['frase']) ?>" required>
            </div>
        </div>

        <div class="pc-field">
            <label class="pc-label" for="bio">Biografia curta</label>
            <textarea class="pc-textarea" id="bio" name="bio" maxlength="1200"><?= e((string) ($editando['bio'] ?? '')) ?></textarea>
        </div>

        <div class="pc-form__row pc-form__row--3">
            <div class="pc-field">
                <label class="pc-label" for="ator_nome">Intérprete (personagens)</label>
                <input class="pc-input" id="ator_nome" name="ator_nome" type="text" maxlength="120" value="<?= e((string) ($editando['ator_nome'] ?? '')) ?>">
            </div>
            <div class="pc-field">
                <label class="pc-label" for="pais">Origem / país</label>
                <input class="pc-input" id="pais" name="pais" type="text" maxlength="80" value="<?= e((string) ($editando['pais'] ?? '')) ?>">
            </div>
            <div class="pc-field">
                <label class="pc-label" for="nascimento">Nascimento (ano)</label>
                <input class="pc-input" id="nascimento" name="nascimento" type="number" min="1800" max="2100" value="<?= e((string) ($editando['nascimento'] ?? '')) ?>">
            </div>
        </div>

        <div class="pc-form__row pc-form__row--3">
            <div class="pc-field">
                <label class="pc-label" for="imagem_url">URL da imagem (opcional)</label>
                <input class="pc-input" id="imagem_url" name="imagem_url" type="text" value="<?= e((string) ($editando['imagem_url'] ?? '')) ?>" placeholder="/assets/img/...">
            </div>
            <div class="pc-field">
                <label class="pc-label" for="tmdb_id">ID TMDB (opcional)</label>
                <input class="pc-input" id="tmdb_id" name="tmdb_id" type="number" value="<?= e((string) ($editando['tmdb_id'] ?? '')) ?>">
            </div>
            <div class="pc-field">
                <label class="pc-label" for="frase_key">Chave da frase (opcional)</label>
                <input class="pc-input" id="frase_key" name="frase_key" type="text" value="<?= e((string) ($editando['frase_key'] ?? '')) ?>">
            </div>
        </div>

        <div class="pc-field">
            <span class="pc-label">Vetor editorial legado (8 eixos; não usado no match)</span>
        </div>

        <div class="pc-form__row" style="grid-template-columns: repeat(4, minmax(0,1fr))">
            <?php foreach ($eixos as $indice => $eixo): ?>
                <div class="pc-field">
                    <label class="pc-label" for="eixo_<?= $indice + 1 ?>"><?= e($eixo['nome']) ?></label>
                    <input class="pc-input" id="eixo_<?= $indice + 1 ?>" name="eixo_<?= $indice + 1 ?>" type="number" min="0" max="100"
                           value="<?= (int) ($editando['vetor'][$indice] ?? 50) ?>" required>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="pc-form__actions">
            <button class="pc-btn" type="submit"><?= e(t('admin.save')) ?></button>
            <a class="pc-btn pc-btn--ghost" href="<?= url('admin/entidades.php', ['tipo' => $filtro]) ?>"><?= e(t('admin.back')) ?></a>

            <?php if ((int) $editando['id'] > 0): ?>
                <button class="pc-btn pc-btn--light" type="submit" form="form-excluir"><?= e(t('admin.delete')) ?></button>
            <?php endif; ?>
        </div>
    </form>

    <?php if ((int) $editando['id'] > 0): ?>
        <form id="form-excluir" method="post" action="<?= url('admin/entidades.php') ?>" data-confirm="<?= e(t('admin.confirm_delete')) ?>">
            <?= Csrf::campo() ?>
            <input type="hidden" name="acao" value="excluir">
            <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">
        </form>
    <?php endif; ?>
<?php endif; ?>

<div class="pc-admin__head" style="margin-bottom:12px">
    <div class="pc-admin__actions" style="margin-left:0">
        <a class="pc-chip <?= $filtro === '' ? 'pc-chip--green' : '' ?>" href="<?= url('admin/entidades.php') ?>">Todos</a>
        <?php foreach ($tipos as $tipo): ?>
            <a class="pc-chip <?= $filtro === $tipo ? 'pc-chip--green' : '' ?>" href="<?= url('admin/entidades.php', ['tipo' => $tipo]) ?>">
                <?= e(t('resultado.types.' . $tipo)) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
        <thead>
        <tr>
            <th>Nome</th>
            <th>Tipo</th>
            <th>Obra</th>
            <th>Vetor</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php if ($entidades === []): ?>
            <tr><td colspan="5" class="text-center text-muted py-3"><?= e(t('admin.no_data')) ?></td></tr>
        <?php endif; ?>

        <?php foreach ($entidades as $entidade): ?>
            <tr>
                <td><strong><?= e($entidade['nome']) ?></strong></td>
                <td><?= e(t('resultado.types.' . $entidade['tipo'])) ?></td>
                <td class="pc-text-muted" style="font-size:11.5px"><?= e($entidade['obra_origem']) ?></td>
                <td style="font-family:var(--pc-mono);font-size:10.5px;white-space:nowrap">
                    <?= e(implode(' · ', array_map(static fn (float $v): string => (string) (int) $v, $entidade['vetor']))) ?>
                </td>
                <td><a href="<?= url('admin/entidades.php', ['editar' => $entidade['id']]) ?>"><?= e(t('admin.edit')) ?></a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/partials/foot.php'; ?>
