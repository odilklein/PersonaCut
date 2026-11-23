<?php

declare(strict_types=1);

/**
 * GET /api/search.php?q=termo
 * Busca global do painel administrativo, no mesmo formato do
 * sistema de mecânica: { query, groups: [{ key, label, items }] }.
 */

$config = require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Core\Database;
use Core\Http;
use Core\SessionUser;

if (SessionUser::atual() === null) {
    Http::json(['query' => '', 'groups' => []], 401);
}

// Este endpoint não grava na sessão: liberar o lock evita que buscas
// consecutivas fiquem enfileiradas no arquivo de sessão do PHP.
session_write_close();

$consulta = trim((string) ($_GET['q'] ?? ''));
$consulta = mb_substr($consulta, 0, 80);

if (mb_strlen($consulta) < 2) {
    Http::json(['query' => $consulta, 'groups' => []]);
}

$pdo = Database::pdo();
$like = '%' . addcslashes($consulta, '\\%_') . '%';

/** @return list<array<string, string>> */
function pc_search_build(string $sql, array $params, string $urlBase, string $icon, string $subtitlePrefix): array
{
    global $pdo;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $itens = [];
    foreach ($stmt->fetchAll() as $linha) {
        $id = (int) $linha['id'];
        $itens[] = [
            'name' => (string) $linha['nome'],
            'subtitle' => $subtitlePrefix . ' #' . $id,
            'url' => $urlBase . $id,
            'icon' => $icon,
        ];
    }

    return $itens;
}

$grupos = [];

$itens = pc_search_build(
    'SELECT id, nome FROM eixos WHERE nome LIKE :p1 OR slug LIKE :p2 ORDER BY ordem LIMIT 5',
    ['p1' => $like, 'p2' => $like],
    url('admin/eixos.php') . '?editar=',
    'tabler-compass',
    t('admin.axes')
);
if ($itens !== []) {
    $grupos[] = ['key' => 'eixos', 'label' => t('admin.axes'), 'items' => $itens];
}

$stmt = $pdo->prepare(
    'SELECT p.id, p.texto, e.nome AS eixo
       FROM perguntas p
       LEFT JOIN eixos e ON e.id = p.eixo_id
      WHERE p.texto LIKE :p
      ORDER BY p.eixo_id, p.id
      LIMIT 5'
);
$stmt->execute(['p' => $like]);
$itens = [];
foreach ($stmt->fetchAll() as $linha) {
    $itens[] = [
        'name' => mb_substr((string) $linha['texto'], 0, 90),
        'subtitle' => t('admin.questions') . ' · ' . (string) ($linha['eixo'] ?? '—'),
        'url' => url('admin/perguntas.php') . '?editar=' . (int) $linha['id'],
        'icon' => 'tabler-list',
    ];
}
if ($itens !== []) {
    $grupos[] = ['key' => 'perguntas', 'label' => t('admin.questions'), 'items' => $itens];
}

$itens = pc_search_build(
    'SELECT id, titulo AS nome FROM arquetipos WHERE titulo LIKE :p ORDER BY id LIMIT 5',
    ['p' => $like],
    url('admin/arquetipos.php') . '?editar=',
    'tabler-shape',
    t('admin.archetypes')
);
if ($itens !== []) {
    $grupos[] = ['key' => 'arquetipos', 'label' => t('admin.archetypes'), 'items' => $itens];
}

$stmt = $pdo->prepare(
    'SELECT id, nome, tipo FROM entidades_match WHERE nome LIKE :p ORDER BY id LIMIT 5'
);
$stmt->execute(['p' => $like]);
$itens = [];
foreach ($stmt->fetchAll() as $linha) {
    $itens[] = [
        'name' => (string) $linha['nome'],
        'subtitle' => t('admin.entities') . ' · ' . (string) $linha['tipo'],
        'url' => url('admin/entidades.php') . '?editar=' . (int) $linha['id'] . '&tipo=' . rawurlencode((string) $linha['tipo']),
        'icon' => 'tabler-users',
    ];
}
if ($itens !== []) {
    $grupos[] = ['key' => 'entidades', 'label' => t('admin.entities'), 'items' => $itens];
}

$itens = pc_search_build(
    'SELECT id, nome FROM catalogo_plataformas WHERE nome LIKE :p ORDER BY prioridade LIMIT 5',
    ['p' => $like],
    url('admin/plataformas.php') . '#plataforma-',
    'tabler-device-tv',
    t('admin.platforms')
);
if ($itens !== []) {
    $grupos[] = ['key' => 'plataformas', 'label' => t('admin.platforms'), 'items' => $itens];
}

$itens = pc_search_build(
    'SELECT id, titulo AS nome FROM catalogo_obras WHERE titulo LIKE :p ORDER BY titulo LIMIT 5',
    ['p' => $like],
    url('admin/filmes.php') . '?editar=',
    'tabler-movie',
    t('admin.catalog')
);
if ($itens !== []) {
    $grupos[] = ['key' => 'catalogo', 'label' => t('admin.catalog'), 'items' => $itens];
}

Http::json(['query' => $consulta, 'groups' => $grupos]);
