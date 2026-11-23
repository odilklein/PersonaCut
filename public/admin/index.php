<?php

declare(strict_types=1);

/** Visão geral do painel. */

require __DIR__ . '/_guard.php';

use Models\Arquetipo;
use Models\Resultado;

$adminTitulo = t('admin.dashboard');
$adminAtivo = '';

$totalReais = Resultado::totalReais($pdo);
$totalBase = Resultado::total($pdo);
$arquetipos = Arquetipo::todos($pdo);
$porArquetipo = Resultado::contarPorArquetipo($pdo);
$ultimos = Resultado::ultimos($pdo, 8);
$totalEntidades = (int) $pdo->query('SELECT COUNT(*) FROM entidades_match')->fetchColumn();
$totalPerguntas = (int) $pdo->query('SELECT COUNT(*) FROM perguntas')->fetchColumn();
$temCatalogo = false;

try {
    $temCatalogo = (bool) $pdo->query("SHOW TABLES LIKE 'catalogo_obras'")->fetchColumn();
} catch (Throwable $erro) {
    $temCatalogo = false;
}

$totalCatalogo = $temCatalogo
    ? (int) $pdo->query('SELECT COUNT(*) FROM catalogo_obras WHERE ativo = 1')->fetchColumn()
    : 0;
$totalDisponiveis = $temCatalogo
    ? (int) $pdo->query('SELECT COUNT(DISTINCT obra_id) FROM catalogo_obra_plataformas')->fetchColumn()
    : 0;
$totalPessoas = $temCatalogo
    ? (int) $pdo->query('SELECT COUNT(*) FROM catalogo_pessoas')->fetchColumn()
    : 0;

$tituloPorId = [];

foreach ($arquetipos as $arquetipo) {
    $tituloPorId[(int) $arquetipo['id']] = $arquetipo['titulo'];
}

$maiorContagem = 0;

foreach ($porArquetipo as $linha) {
    $maiorContagem = max($maiorContagem, (int) $linha['total']);
}

require __DIR__ . '/partials/head.php';
?>

<div class="page-header">
    <div>
        <h1><i class="icon-base ti tabler-layout-dashboard"></i><?= e(t('admin.dashboard')) ?></h1>
        <span class="page-subtitle"><?= e(t('admin.title')) ?> · <?= e(t('ui.made_with')) ?></span>
    </div>
    <div class="page-header-actions">
        <a class="btn btn-primary" href="<?= e(url('/')) ?>" target="_blank" rel="noopener">
            <i class="icon-base ti tabler-external-link"></i> <?= e(t('admin.view_public')) ?>
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php foreach ([
        ['rotulo' => t('admin.total_results'), 'valor' => $totalReais, 'icone' => 'tabler-users-group', 'cor' => 'primary'],
        ['rotulo' => t('admin.total_base'), 'valor' => $totalBase, 'icone' => 'tabler-database', 'cor' => 'info'],
        ['rotulo' => t('admin.questions'), 'valor' => $totalPerguntas, 'icone' => 'tabler-list', 'cor' => 'success'],
        ['rotulo' => t('admin.entities'), 'valor' => $totalEntidades, 'icone' => 'tabler-users', 'cor' => 'warning'],
        ['rotulo' => t('admin.catalog'), 'valor' => $totalCatalogo, 'icone' => 'tabler-movies', 'cor' => 'primary'],
        ['rotulo' => t('admin.catalog_available'), 'valor' => $totalDisponiveis, 'icone' => 'tabler-device-tv', 'cor' => 'success'],
        ['rotulo' => t('admin.catalog_people'), 'valor' => $totalPessoas, 'icone' => 'tabler-user-search', 'cor' => 'info'],
    ] as $kpi): ?>
        <div class="col-6 col-md-4 col-xl-3 col-xxl-2">
            <div class="card h-100">
                <div class="card-body">
                    <div class="badge p-2 bg-label-<?= e($kpi['cor']) ?> mb-3 rounded">
                        <i class="icon-base ti <?= e($kpi['icone']) ?> icon-24px"></i>
                    </div>
                    <h6 class="card-title mb-1"><?= e($kpi['rotulo']) ?></h6>
                    <p class="text-heading mb-0 fs-4 fw-semibold"><?= numero((int) $kpi['valor'], 0) ?></p>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-1"><?= e(t('admin.top_archetypes')) ?></h5>
                <p class="card-subtitle"><?= e(t('admin.total_results')) ?>: <?= numero($totalReais, 0) ?></p>
            </div>
            <div class="card-body">
                <?php if ($totalReais === 0): ?>
                    <p class="text-body-secondary mb-0"><?= e(t('admin.no_data')) ?></p>
                <?php else: ?>
                    <div class="d-grid gap-3">
                        <?php foreach ($porArquetipo as $linha): ?>
                            <?php if ((int) $linha['total'] === 0): ?>
                                <?php continue; ?>
                            <?php endif; ?>
                            <?php $porcentagem = $maiorContagem > 0 ? (int) round(((int) $linha['total'] / $maiorContagem) * 100) : 0; ?>
                            <div>
                                <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                                    <span class="text-heading small text-truncate"><?= e($linha['titulo']) ?></span>
                                    <span class="badge bg-label-primary rounded-pill"><?= numero((int) $linha['total'], 0) ?></span>
                                </div>
                                <div class="progress" style="height:8px" role="progressbar"
                                     aria-label="<?= e($linha['titulo']) ?>"
                                     aria-valuenow="<?= $porcentagem ?>" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar bg-primary" style="width:<?= $porcentagem ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-1"><?= e(t('admin.last_results')) ?></h5>
                <p class="card-subtitle"><?= e(t('resultado.share_subtitle')) ?></p>
            </div>
            <div class="card-datatable table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="border-top">
                    <tr>
                        <th>Código</th>
                        <th>Modo</th>
                        <th>Arquétipo</th>
                        <th>Data</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($ultimos === []): ?>
                        <tr><td colspan="4" class="text-center text-body-secondary py-5"><?= e(t('admin.no_data')) ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($ultimos as $linha): ?>
                            <tr>
                                <td>
                                    <a href="<?= e(url('resultado.php', ['codigo' => $linha['codigo_unico']])) ?>" target="_blank" rel="noopener" class="text-heading fw-medium">
                                        <?= e($linha['codigo_unico']) ?>
                                    </a>
                                </td>
                                <td><span class="badge bg-label-secondary"><?= e(modo_rotulo((string) $linha['modo'])) ?></span></td>
                                <td><?= e($tituloPorId[(int) ($linha['arquetipo_id'] ?? 0)] ?? t('resultado.neutral_title')) ?></td>
                                <td class="text-nowrap"><?= e(date('d/m H:i', strtotime((string) $linha['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="page-header mb-3">
    <div>
        <h2><?= e(t('admin.title')) ?></h2>
        <span class="page-subtitle">Atalhos para gestão do conteúdo</span>
    </div>
</div>

<div class="row g-3">
    <?php foreach ([
        ['icone' => 'tabler-compass', 'titulo' => t('admin.axes'), 'texto' => '6 eixos', 'url' => 'eixos.php', 'cor' => 'primary'],
        ['icone' => 'tabler-list', 'titulo' => t('admin.questions'), 'texto' => $totalPerguntas . ' perguntas', 'url' => 'perguntas.php', 'cor' => 'info'],
        ['icone' => 'tabler-users', 'titulo' => t('admin.entities'), 'texto' => $totalEntidades . ' entidades', 'url' => 'entidades.php', 'cor' => 'success'],
        ['icone' => 'tabler-layers', 'titulo' => t('admin.archetypes'), 'texto' => count($arquetipos) . ' arquétipos', 'url' => 'arquetipos.php', 'cor' => 'warning'],
        ['icone' => 'tabler-movies', 'titulo' => t('admin.catalog'), 'texto' => numero($totalCatalogo, 0) . ' obras', 'url' => 'filmes.php', 'cor' => 'primary'],
        ['icone' => 'tabler-device-tv', 'titulo' => t('admin.platforms'), 'texto' => $totalDisponiveis . ' disponíveis', 'url' => 'plataformas.php', 'cor' => 'info'],
    ] as $atalho): ?>
        <div class="col-12 col-sm-6 col-xl-4">
            <a class="card card-link h-100 text-reset" href="<?= e(url('admin/' . $atalho['url'])) ?>">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="badge p-3 bg-label-<?= e($atalho['cor']) ?> rounded">
                        <i class="icon-base ti <?= e($atalho['icone']) ?> icon-24px"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-heading"><?= e($atalho['titulo']) ?></h6>
                        <small class="text-body-secondary"><?= e($atalho['texto']) ?></small>
                    </div>
                    <i class="icon-base ti tabler-chevron-right ms-auto text-body-secondary"></i>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/partials/foot.php'; ?>
