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
    <div class="page-header">
        <div>
            <h1><i class="icon-base ti tabler-device-tv"></i><?= e(t('admin.platforms')) ?></h1>
            <span class="page-subtitle"><?= e(t('admin.platforms_help')) ?></span>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="alert alert-warning mb-3" role="alert">
                <i class="icon-base ti tabler-alert-triangle me-2"></i>
                <?= e(t('admin.platforms_missing')) ?>
            </div>
            <a class="btn btn-outline-primary" href="<?= e(url('admin/')) ?>">
                <i class="icon-base ti tabler-arrow-left"></i> <?= e(t('admin.back')) ?>
            </a>
        </div>
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

<div class="page-header">
    <div>
        <h1><i class="icon-base ti tabler-device-tv"></i><?= e(t('admin.platforms')) ?></h1>
        <span class="page-subtitle"><?= e(t('admin.platforms_help')) ?></span>
    </div>
    <div class="page-header-actions">
        <span class="badge bg-label-primary rounded-pill">
            <?= numero($totalDisponivel, 0) ?> / <?= numero($totalCatalogo, 0) ?> <?= e(t('admin.platforms_coverage')) ?>
        </span>
        <?php if ($catalogoAtualizado !== false && $catalogoAtualizado !== null): ?>
            <span class="badge bg-label-secondary rounded-pill">
                <i class="icon-base ti tabler-calendar me-1"></i>
                <?= e(t('admin.catalog_updated', ['data' => date('d/m/Y', (int) strtotime((string) $catalogoAtualizado))])) ?>
            </span>
        <?php endif; ?>
    </div>
</div>

<?php if ($plataformas === []): ?>
    <div class="card">
        <div class="card-body">
            <div class="alert alert-info mb-0" role="alert">
                <i class="icon-base ti tabler-info-circle me-2"></i>
                <?= e(t('admin.no_data')) ?>
            </div>
        </div>
    </div>
<?php else: ?>
    <form method="post" action="<?= e(url('admin/plataformas.php')) ?>">
        <?= Csrf::campo() ?>
        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h5 class="card-title mb-1"><?= e(t('admin.platforms')) ?></h5>
                    <p class="card-subtitle mb-0">Preço editorial e ordem de exibição no ranking</p>
                </div>
                <button class="btn btn-primary btn-sm" type="submit">
                    <i class="icon-base ti tabler-device-floppy me-1"></i>
                    <?= e(t('admin.save')) ?>
                </button>
            </div>

            <div class="card-datatable table-responsive">
                <table class="table table-hover align-middle responsive-card mb-0">
                    <thead class="border-top">
                    <tr>
                        <th><?= e(t('admin.platforms_name')) ?></th>
                        <th><?= e(t('admin.platforms_price')) ?></th>
                        <th><?= e(t('admin.platforms_checked')) ?></th>
                        <th><?= e(t('admin.platforms_order')) ?></th>
                        <th><?= e(t('admin.platforms_active')) ?></th>
                        <th class="text-end"><?= e(t('admin.platforms_catalog')) ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($plataformas as $plataforma): ?>
                        <?php
                        $id = (int) $plataforma['id'];
                        $logo = tmdb_logo($plataforma['logo_path'] ?? null);
                        $ativa = (int) $plataforma['ativa'] === 1;
                        ?>
                        <tr id="plataforma-<?= $id ?>">
                            <td data-label="<?= e(t('admin.platforms_name')) ?>">
                                <input type="hidden" name="id[]" value="<?= $id ?>">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <?php if ($logo !== ''): ?>
                                        <img src="<?= e($logo) ?>" alt="" width="28" height="28" class="rounded" loading="lazy" decoding="async">
                                    <?php else: ?>
                                        <span class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-primary"><i class="icon-base ti tabler-device-tv"></i></span></span>
                                    <?php endif; ?>
                                    <input class="form-control form-control-sm" type="text"
                                           name="nome[<?= $id ?>]" value="<?= e((string) $plataforma['nome']) ?>" maxlength="80" required
                                           aria-label="<?= e(t('admin.platforms_name')) ?>">
                                    <?php if (!empty($plataforma['url'])): ?>
                                        <a class="btn btn-icon btn-sm btn-text-secondary rounded-pill" href="<?= e((string) $plataforma['url']) ?>" target="_blank" rel="noopener noreferrer" title="Abrir site">
                                            <i class="icon-base ti tabler-external-link"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="icon-base ti tabler-link"></i></span>
                                    <input class="form-control form-control-sm" type="url" name="url[<?= $id ?>]"
                                           value="<?= e((string) ($plataforma['url'] ?? '')) ?>" placeholder="https://… (opcional)"
                                           aria-label="URL de assinatura">
                                </div>
                                <small class="text-body-secondary">TMDB #<?= (int) $plataforma['tmdb_provider_id'] ?></small>
                            </td>
                            <td data-label="<?= e(t('admin.platforms_price')) ?>">
                                <div class="input-group input-group-sm" style="max-width:130px">
                                    <span class="input-group-text">R$</span>
                                    <input class="form-control form-control-sm" type="number" step="0.01" min="0" max="9999"
                                           name="preco_mensal[<?= $id ?>]"
                                           value="<?= $plataforma['preco_mensal'] !== null ? number_format((float) $plataforma['preco_mensal'], 2, '.', '') : '' ?>"
                                           placeholder="0,00" aria-label="Preço mensal">
                                </div>
                            </td>
                            <td data-label="<?= e(t('admin.platforms_checked')) ?>">
                                <input class="form-control form-control-sm" type="date"
                                       name="preco_atualizado_em[<?= $id ?>]"
                                       value="<?= e((string) ($plataforma['preco_atualizado_em'] ?? '')) ?>"
                                       aria-label="<?= e(t('admin.platforms_checked')) ?>">
                            </td>
                            <td data-label="<?= e(t('admin.platforms_order')) ?>">
                                <input class="form-control form-control-sm" type="number" min="0" max="255" style="max-width:80px"
                                       name="prioridade[<?= $id ?>]" value="<?= (int) $plataforma['prioridade'] ?>"
                                       aria-label="<?= e(t('admin.platforms_order')) ?>">
                            </td>
                            <td data-label="<?= e(t('admin.platforms_active')) ?>">
                                <label class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" name="ativa[<?= $id ?>]" value="1"
                                        <?= $ativa ? 'checked' : '' ?>>
                                    <span class="form-check-label badge <?= $ativa ? 'bg-label-success' : 'bg-label-secondary' ?>">
                                        <?= $ativa ? 'Ativa' : 'Inativa' ?>
                                    </span>
                                </label>
                            </td>
                            <td data-label="<?= e(t('admin.platforms_catalog')) ?>" class="text-end">
                                <span class="badge bg-label-primary rounded-pill"><?= numero((int) ($plataforma['total_obras'] ?? 0), 0) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2">
                <small class="text-body-secondary"><?= e(t('admin.platforms_note')) ?></small>
                <button class="btn btn-primary btn-sm" type="submit">
                    <i class="icon-base ti tabler-device-floppy me-1"></i>
                    <?= e(t('admin.save')) ?>
                </button>
            </div>
        </div>
    </form>
<?php endif; ?>

<?php require __DIR__ . '/partials/foot.php'; ?>
