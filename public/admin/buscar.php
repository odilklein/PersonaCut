<?php

declare(strict_types=1);

/** Busca unificada no painel administrativo. */

require __DIR__ . '/_guard.php';

$q = trim((string) ($_GET['q'] ?? ''));
$temBusca = $q !== '';

$grupos = [];

if ($temBusca) {
    $adicionar = static function (string $titulo, array $itens) use (&$grupos): void {
        if ($itens !== []) {
            $grupos[] = ['titulo' => $titulo, 'itens' => $itens];
        }
    };

    $padrao = '%' . $q . '%';

    $itens = [];
    $stmt = $pdo->prepare(
        'SELECT id, nome, slug FROM eixos WHERE nome LIKE :p OR slug LIKE :p ORDER BY ordem LIMIT 20'
    );
    $stmt->execute(['p' => $padrao]);
    foreach ($stmt->fetchAll() as $linha) {
        $itens[] = [
            'rotulo' => (string) $linha['nome'],
            'subtitulo' => 'Eixo ' . (int) $linha['ordem'] . ' · ' . (string) $linha['slug'],
            'url' => url('admin/eixos.php', ['editar' => (int) $linha['id']]),
        ];
    }
    $adicionar(t('admin.axes'), $itens);

    $itens = [];
    $stmt = $pdo->prepare(
        'SELECT id, eixo_id, texto FROM perguntas WHERE texto LIKE :p ORDER BY eixo_id, id LIMIT 20'
    );
    $stmt->execute(['p' => $padrao]);
    foreach ($stmt->fetchAll() as $linha) {
        $itens[] = [
            'rotulo' => (string) $linha['texto'],
            'subtitulo' => t('admin.questions') . ' · eixo ' . (int) $linha['eixo_id'],
            'url' => url('admin/perguntas.php', ['editar' => (int) $linha['id']]),
        ];
    }
    $adicionar(t('admin.questions'), $itens);

    $itens = [];
    $stmt = $pdo->prepare(
        'SELECT id, titulo, eixo_dominante_1 FROM arquetipos WHERE titulo LIKE :p ORDER BY id LIMIT 20'
    );
    $stmt->execute(['p' => $padrao]);
    foreach ($stmt->fetchAll() as $linha) {
        $itens[] = [
            'rotulo' => (string) $linha['titulo'],
            'subtitulo' => 'Arquétipo · eixo ' . (int) $linha['eixo_dominante_1'],
            'url' => url('admin/arquetipos.php', ['editar' => (int) $linha['id']]),
        ];
    }
    $adicionar(t('admin.archetypes'), $itens);

    $itens = [];
    $stmt = $pdo->prepare(
        'SELECT id, tipo, nome FROM entidades_match WHERE nome LIKE :p ORDER BY id LIMIT 20'
    );
    $stmt->execute(['p' => $padrao]);
    foreach ($stmt->fetchAll() as $linha) {
        $itens[] = [
            'rotulo' => (string) $linha['nome'],
            'subtitulo' => t('admin.entities') . ' · ' . t('resultado.types.' . (string) $linha['tipo']),
            'url' => url('admin/entidades.php', [
                'editar' => (int) $linha['id'],
                'tipo' => (string) $linha['tipo'],
            ]),
        ];
    }
    $adicionar(t('admin.entities'), $itens);

    $itens = [];
    $stmt = $pdo->prepare(
        'SELECT id, nome, preco_mensal FROM catalogo_plataformas WHERE nome LIKE :p ORDER BY prioridade LIMIT 20'
    );
    $stmt->execute(['p' => $padrao]);
    foreach ($stmt->fetchAll() as $linha) {
        $itens[] = [
            'rotulo' => (string) $linha['nome'],
            'subtitulo' => t('admin.platforms') . ' · ' . t('admin.platforms_price') . ': '
                . ($linha['preco_mensal'] !== null ? numero((float) $linha['preco_mensal']) . ' R$/mês' : '—'),
            'url' => url('admin/plataformas.php'),
        ];
    }
    $adicionar(t('admin.platforms'), $itens);

    $itens = [];
    $stmt = $pdo->prepare(
        'SELECT id, titulo, tipo FROM catalogo_obras WHERE titulo LIKE :p ORDER BY titulo LIMIT 20'
    );
    $stmt->execute(['p' => $padrao]);
    foreach ($stmt->fetchAll() as $linha) {
        $itens[] = [
            'rotulo' => (string) $linha['titulo'],
            'subtitulo' => 'Catálogo · ' . ((string) $linha['tipo']) . ' · TMDB #' . (int) $linha['id'],
            'url' => url('admin/filmes.php', ['editar' => (int) $linha['id']]),
        ];
    }
    $adicionar(t('admin.catalog'), $itens);
}

require __DIR__ . '/partials/head.php';
?>
<div class="pc-admin__head">
    <div>
        <h1><?= e($temBusca ? t('admin.search') : t('admin.no_data')) ?></h1>
        <p class="pc-text-muted" style="font-size:13px">
            <?= e($temBusca ? t('admin.search_results_for', ['q' => $q]) : t('admin.no_data')) ?>
        </p>
    </div>
</div>

<?php if (!$temBusca): ?>
    <div class="pc-empty"><?= e(t('admin.no_data')) ?></div>
<?php else: ?>
    <?php foreach ($grupos as $grupo): ?>
        <section class="pc-panel" style="margin-bottom:1rem">
            <div class="pc-panel__head">
                <div>
                    <h2><?= e($grupo['titulo']) ?></h2>
                </div>
            </div>
            <?php foreach ($grupo['itens'] as $item): ?>
                <div style="padding:0.9rem 1.2rem;border-top:1px solid var(--pc-line);display:flex;justify-content:space-between;align-items:center;gap:1rem">
                    <div>
                        <b><?= e($item['rotulo']) ?></b>
                        <small class="pc-text-muted" style="display:block"><?= e($item['subtitulo']) ?></small>
                    </div>
                    <a class="pc-btn pc-btn--sm pc-link-arrow" href="<?= e($item['url']) ?>">
                        <?= e(t('admin.edit')) ?>
                        <?= icon('arrow-right', 13) ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/partials/foot.php'; ?>
