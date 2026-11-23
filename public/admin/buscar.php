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
        'SELECT id, nome, slug, ordem FROM eixos WHERE nome LIKE :p1 OR slug LIKE :p2 ORDER BY ordem LIMIT 20'
    );
    $stmt->execute(['p1' => $padrao, 'p2' => $padrao]);
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

<div class="page-header">
    <div>
        <h1><i class="icon-base ti tabler-search"></i><?= e($temBusca ? t('admin.search') : t('admin.no_data')) ?></h1>
        <span class="page-subtitle"><?= e($temBusca ? t('admin.search_results_for', ['q' => $q]) : t('admin.no_data')) ?></span>
    </div>
</div>

<?php if (!$temBusca): ?>
    <div class="card">
        <div class="card-body">
            <div class="alert alert-info mb-0" role="alert">
                <i class="icon-base ti tabler-info-circle me-2"></i>
                Digite um termo na busca global do topo (ou use <kbd>Ctrl</kbd> + <kbd>K</kbd>) para localizar eixos, perguntas, arquétipos, entidades, plataformas e títulos do catálogo.
            </div>
        </div>
    </div>
<?php elseif ($grupos === []): ?>
    <div class="card">
        <div class="card-body">
            <div class="alert alert-warning mb-0" role="alert">
                <i class="icon-base ti tabler-file-search me-2"></i>
                <?= e(t('admin.no_data')) ?>
            </div>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($grupos as $grupo): ?>
        <div class="card mb-3">
            <h5 class="card-header border-bottom"><?= e($grupo['titulo']) ?></h5>
            <ul class="list-group list-group-flush">
                <?php foreach ($grupo['itens'] as $item): ?>
                    <li class="list-group-item d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <span class="text-heading fw-medium"><?= e($item['rotulo']) ?></span>
                            <small class="text-body-secondary d-block"><?= e($item['subtitulo']) ?></small>
                        </div>
                        <a class="btn btn-sm btn-outline-primary" href="<?= e($item['url']) ?>">
                            <i class="icon-base ti tabler-pencil me-1"></i><?= e(t('admin.edit')) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/partials/foot.php'; ?>
