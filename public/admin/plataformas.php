<?php

declare(strict_types=1);

/** Preços e disponibilidade das plataformas de streaming. */

require __DIR__ . '/_guard.php';

use Core\Csrf;
use Models\Plataforma;

$adminTitulo = t('admin.platforms');
$adminAtivo = 'plataformas.php';

$temCatalogo = false;

try {
    $temCatalogo = (bool) $pdo->query("SHOW TABLES LIKE 'catalogo_plataformas'")->fetchColumn();
} catch (Throwable $erro) {
    $temCatalogo = false;
}

if (!$temCatalogo) {
    require __DIR__ . '/partials/head.php';
    ?>
    <div class="pc-admin__head">
        <div>
            <h1><?= e(t('admin.platforms')) ?></h1>
            <p class="pc-text-muted" style="font-size:13px"><?= e(t('admin.platforms_help')) ?></p>
        </div>
    </div>

    <div class="pc-panel">
        <p><?= e(t('admin.platforms_missing')) ?></p>
        <a class="pc-btn pc-btn--sm pc-mt-3" href="<?= url('admin/') ?>">
            <?= icon('arrow-left', 15) ?>
            <?= e(t('admin.back')) ?>
        </a>
    </div>
    <?php
    require __DIR__ . '/partials/foot.php';
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!Csrf::validarRequisicao()) {
        pc_admin_redirect(url('admin/plataformas.php'), t('erros.csrf'), 'erro');
    }

    $ids = (array) ($_POST['id'] ?? []);
    $nomes = (array) ($_POST['nome'] ?? []);
    $precos = (array) ($_POST['preco_mensal'] ?? []);
    $datas = (array) ($_POST['preco_atualizado_em'] ?? []);
    $prioridades = (array) ($_POST['prioridade'] ?? []);
    $ativas = (array) ($_POST['ativa'] ?? []);
    $urls = (array) ($_POST['url'] ?? []);
    $erros = [];

    foreach ($ids as $idBruto) {
        $id = (int) $idBruto;
        $nome = trim((string) ($nomes[$id] ?? ''));
        $preco = trim((string) ($precos[$id] ?? ''));
        $data = trim((string) ($datas[$id] ?? ''));
        $prioridade = (int) ($prioridades[$id] ?? 0);
        $urlAssinatura = trim((string) ($urls[$id] ?? ''));
        $ativa = isset($ativas[$id]) ? 1 : 0;

        if ($id <= 0) {
            continue;
        }

        if ($nome === '' || mb_strlen($nome) > 80) {
            $erros[] = 'Nome inválido.';
        }

        if ($preco !== '' && (!is_numeric($preco) || (float) $preco < 0 || (float) $preco > 9999)) {
            $erros[] = 'Preço inválido em ' . $nome . '.';
        }

        if ($data !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) !== 1) {
            $erros[] = 'Data inválida em ' . $nome . ' (use AAAA-MM-DD).';
        }

        if ($urlAssinatura !== '' && filter_var($urlAssinatura, FILTER_VALIDATE_URL) === false) {
            $erros[] = 'URL inválida em ' . $nome . '.';
        }

        if ($prioridade < 0 || $prioridade > 255) {
            $erros[] = 'Prioridade inválida em ' . $nome . '.';
        }

        if ($erros !== []) {
            continue;
        }

        Plataforma::atualizar($pdo, $id, [
            'nome'                => $nome,
            'preco_mensal'        => $preco === '' ? null : round((float) $preco, 2),
            'preco_atualizado_em' => $data === '' ? null : $data,
            'url'                 => $urlAssinatura === '' ? null : $urlAssinatura,
            'prioridade'          => $prioridade,
            'ativa'               => $ativa,
        ]);
    }

    if ($erros !== []) {
        pc_admin_redirect(url('admin/plataformas.php'), implode(' ', $erros), 'erro');
    }

    pc_admin_redirect(url('admin/plataformas.php'), t('admin.saved'));
}

$plataformas = Plataforma::todas($pdo);
$totalCatalogo = (int) $pdo->query('SELECT COUNT(*) FROM catalogo_obras WHERE ativo = 1')->fetchColumn();
$totalDisponivel = (int) $pdo->query('SELECT COUNT(DISTINCT obra_id) FROM catalogo_obra_plataformas')->fetchColumn();
$catalogoAtualizado = $pdo->query('SELECT MAX(atualizado_em) FROM catalogo_obras')->fetchColumn();
$porPlataforma = [];

foreach ($pdo->query(
    'SELECT plataforma_id, COUNT(DISTINCT obra_id) AS total
       FROM catalogo_obra_plataformas
      GROUP BY plataforma_id'
)->fetchAll() as $linha) {
    $porPlataforma[(int) $linha['plataforma_id']] = (int) $linha['total'];
}

foreach ($plataformas as &$plataforma) {
    $plataforma['total_obras'] = $porPlataforma[(int) $plataforma['id']] ?? 0;
}
unset($plataforma);

require __DIR__ . '/partials/head.php';
?>

<div class="pc-admin__head">
    <div>
        <h1><?= e(t('admin.platforms')) ?></h1>
        <p class="pc-text-muted" style="font-size:13px">
            <?= e(t('admin.platforms_help')) ?>
        </p>
    </div>

    <div class="pc-admin__head-info">
        <span class="pc-badge-inline">
            <?= numero($totalDisponivel, 0) ?> / <?= numero($totalCatalogo, 0) ?> <?= e(t('admin.platforms_coverage')) ?>
        </span>

        <?php if ($catalogoAtualizado !== false && $catalogoAtualizado !== null): ?>
            <span class="pc-badge-inline">
                <?= e(t('admin.catalog_updated', [
                    'data' => date('d/m/Y', (int) strtotime((string) $catalogoAtualizado)),
                ])) ?>
            </span>
        <?php endif; ?>
    </div>
</div>

<?php if ($plataformas === []): ?>
    <div class="pc-empty"><?= e(t('admin.no_data')) ?></div>
<?php else: ?>
    <form class="pc-form pc-mb-3" method="post" action="<?= url('admin/plataformas.php') ?>">
        <?= Csrf::campo() ?>

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th><?= e(t('admin.platforms_name')) ?></th>
                        <th><?= e(t('admin.platforms_price')) ?></th>
                        <th><?= e(t('admin.platforms_checked')) ?></th>
                        <th><?= e(t('admin.platforms_order')) ?></th>
                        <th><?= e(t('admin.platforms_active')) ?></th>
                        <th><?= e(t('admin.platforms_catalog')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plataformas as $plataforma): ?>
                        <?php $logo = tmdb_logo($plataforma['logo_path'] ?? null); ?>
                        <tr>
                            <td>
                                <input type="hidden" name="id[]" value="<?= (int) $plataforma['id'] ?>">

                                <div class="pc-platform-cell">
                                    <?php if ($logo !== ''): ?>
                                        <img src="<?= e($logo) ?>" alt="" width="26" height="26" loading="lazy" decoding="async">
                                    <?php endif; ?>

                                    <input class="pc-input pc-input--sm" type="text"
                                           name="nome[<?= (int) $plataforma['id'] ?>]"
                                           value="<?= e((string) $plataforma['nome']) ?>" maxlength="80" required>

                                    <?php if (!empty($plataforma['url'])): ?>
                                        <a class="pc-link-arrow" href="<?= e((string) $plataforma['url']) ?>" target="_blank" rel="noopener noreferrer">
                                            <?= icon('external', 13) ?>
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <input class="pc-input pc-input--sm pc-mt-1" type="url"
                                       name="url[<?= (int) $plataforma['id'] ?>]"
                                       value="<?= e((string) ($plataforma['url'] ?? '')) ?>"
                                       placeholder="https://… (opcional)">

                                <small class="pc-text-muted">TMDB #<?= (int) $plataforma['tmdb_provider_id'] ?></small>
                            </td>
                            <td>
                                <input class="pc-input pc-input--sm" type="number" step="0.01" min="0" max="9999"
                                       name="preco_mensal[<?= (int) $plataforma['id'] ?>]"
                                       value="<?= $plataforma['preco_mensal'] !== null ? number_format((float) $plataforma['preco_mensal'], 2, '.', '') : '' ?>"
                                       placeholder="0,00">
                            </td>
                            <td>
                                <input class="pc-input pc-input--sm" type="date"
                                       name="preco_atualizado_em[<?= (int) $plataforma['id'] ?>]"
                                       value="<?= e((string) ($plataforma['preco_atualizado_em'] ?? '')) ?>">
                            </td>
                            <td>
                                <input class="pc-input pc-input--sm" type="number" min="0" max="255"
                                       name="prioridade[<?= (int) $plataforma['id'] ?>]"
                                       value="<?= (int) $plataforma['prioridade'] ?>">
                            </td>
                            <td>
                                <label class="pc-check">
                                    <input type="checkbox" name="ativa[<?= (int) $plataforma['id'] ?>]" value="1"
                                        <?= (int) $plataforma['ativa'] === 1 ? 'checked' : '' ?>>
                                    <span><?= e(t('admin.platforms_active')) ?></span>
                                </label>
                            </td>
                            <td>
                                <b class="pc-tabular"><?= numero((int) ($plataforma['total_obras'] ?? 0), 0) ?></b>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <button class="pc-btn pc-btn--sm pc-mt-3" type="submit">
            <?= icon('check', 15) ?>
            <?= e(t('admin.save')) ?>
        </button>
    </form>

    <p class="pc-text-muted" style="font-size:12px;max-width:640px">
        <?= e(t('admin.platforms_note')) ?>
    </p>
<?php endif; ?>

<?php require __DIR__ . '/partials/foot.php'; ?>
