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

<div class="pc-admin__head">
    <div>
        <h1><?= e(t('admin.dashboard')) ?></h1>
        <p class="pc-text-muted" style="font-size:13px"><?= e(t('admin.title')) ?> · <?= e(t('ui.made_with')) ?></p>
    </div>
</div>

<section class="pc-stats">
    <div class="pc-stat">
        <span><?= e(t('admin.total_results')) ?></span>
        <b><?= numero($totalReais, 0) ?></b>
    </div>
    <div class="pc-stat">
        <span><?= e(t('admin.total_base')) ?></span>
        <b><?= numero($totalBase, 0) ?></b>
    </div>
    <div class="pc-stat">
        <span><?= e(t('admin.public_results')) ?></span>
        <b><?= numero($totalPerguntas, 0) ?></b>
    </div>
    <div class="pc-stat">
        <span><?= e(t('admin.entities')) ?></span>
        <b><?= numero($totalEntidades, 0) ?></b>
    </div>
    <div class="pc-stat">
        <span><?= e(t('admin.catalog')) ?></span>
        <b><?= numero($totalCatalogo, 0) ?></b>
    </div>
    <div class="pc-stat">
        <span><?= e(t('admin.catalog_available')) ?></span>
        <b><?= numero($totalDisponiveis, 0) ?></b>
    </div>
    <div class="pc-stat">
        <span><?= e(t('admin.catalog_people')) ?></span>
        <b><?= numero($totalPessoas, 0) ?></b>
    </div>
</section>

<div class="pc-result__grid">
    <section class="pc-panel">
        <div class="pc-panel__head">
            <div>
                <h2><?= e(t('admin.top_archetypes')) ?></h2>
                <p><?= e(t('admin.total_results')) ?>: <?= numero($totalReais, 0) ?></p>
            </div>
        </div>

        <?php if ($totalReais === 0): ?>
            <div class="pc-empty"><?= e(t('admin.no_data')) ?></div>
        <?php else: ?>
            <div class="pc-conv">
                <?php foreach ($porArquetipo as $linha): ?>
                    <?php if ((int) $linha['total'] === 0): ?>
                        <?php continue; ?>
                    <?php endif; ?>
                    <div class="pc-conv__row">
                        <span class="pc-conv__name" style="width:150px"><?= e($linha['titulo']) ?></span>
                        <span class="pc-conv__bar">
                            <i style="width:<?= $maiorContagem > 0 ? (int) round(((int) $linha['total'] / $maiorContagem) * 100) : 0 ?>%"></i>
                        </span>
                        <span class="pc-conv__value"><?= numero((int) $linha['total'], 0) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="pc-panel">
        <div class="pc-panel__head">
            <div>
                <h2><?= e(t('admin.last_results')) ?></h2>
                <p><?= e(t('resultado.share_subtitle')) ?></p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                <tr>
                    <th>Código</th>
                    <th>Modo</th>
                    <th>Arquétipo</th>
                    <th>Data</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($ultimos === []): ?>
                    <tr><td colspan="4" class="text-center text-muted py-3"><?= e(t('admin.no_data')) ?></td></tr>
                <?php else: ?>
                    <?php foreach ($ultimos as $linha): ?>
                        <tr>
                            <td>
                                <a href="<?= url('resultado.php', ['codigo' => $linha['codigo_unico']]) ?>" target="_blank" rel="noopener">
                                    <?= e($linha['codigo_unico']) ?>
                                </a>
                            </td>
                            <td><?= e(modo_rotulo((string) $linha['modo'])) ?></td>
                            <td><?= e($tituloPorId[(int) ($linha['arquetipo_id'] ?? 0)] ?? t('resultado.neutral_title')) ?></td>
                            <td><?= e(date('d/m H:i', strtotime((string) $linha['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<div class="pc-admin__head pc-mt-4">
    <h2 style="font-size:18px"><?= e(t('admin.title')) ?></h2>
</div>

<div class="pc-features">
    <?php foreach ([
        ['icon' => 'grid', 'titulo' => t('admin.axes'), 'texto' => '6 eixos', 'url' => 'eixos.php'],
        ['icon' => 'list', 'titulo' => t('admin.questions'), 'texto' => $totalPerguntas . ' perguntas', 'url' => 'perguntas.php'],
        ['icon' => 'users', 'titulo' => t('admin.entities'), 'texto' => $totalEntidades . ' entidades', 'url' => 'entidades.php'],
        ['icon' => 'compass', 'titulo' => t('admin.archetypes'), 'texto' => count($arquetipos) . ' arquétipos', 'url' => 'arquetipos.php'],
    ] as $atalho): ?>
        <a class="pc-feature" href="<?= url('admin/' . $atalho['url']) ?>">
            <span class="pc-feature__icon"><?= icon($atalho['icon'], 20) ?></span>
            <div>
                <h3><?= e($atalho['titulo']) ?></h3>
                <p><?= e($atalho['texto']) ?></p>
            </div>
        </a>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/partials/foot.php'; ?>
