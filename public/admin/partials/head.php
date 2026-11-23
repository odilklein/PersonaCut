<?php

declare(strict_types=1);

/**
 * Abertura do layout do painel.
 *
 * Variáveis: $adminTitulo, $adminAtivo, $admin (vem de _guard.php), $config.
 */

use Core\Csrf;
use Core\Session;

$adminTitulo = $adminTitulo ?? 'Painel';
$adminAtivo = $adminAtivo ?? '';
$flashErro = Session::flash('erro');
$flashOk = Session::flash('ok');

$adminNome = trim((string) ($admin['nome'] ?? 'Administrador'));
$adminLogin = trim((string) ($admin['usuario'] ?? 'admin'));
$adminInicial = mb_strtoupper(mb_substr($adminNome !== '' ? $adminNome : 'A', 0, 1));

$secoesMenu = [
    'Visão geral' => [
        ['arquivo' => '', 'icone' => 'grid', 'rotulo' => t('admin.dashboard')],
    ],
    'Conteúdo' => [
        ['arquivo' => 'eixos.php', 'icone' => 'compass', 'rotulo' => t('admin.axes')],
        ['arquivo' => 'perguntas.php', 'icone' => 'list', 'rotulo' => t('admin.questions')],
        ['arquivo' => 'arquetipos.php', 'icone' => 'layers', 'rotulo' => t('admin.archetypes')],
        ['arquivo' => 'entidades.php', 'icone' => 'users', 'rotulo' => t('admin.entities')],
    ],
    'Catálogo' => [
        ['arquivo' => 'filmes.php', 'icone' => 'film', 'rotulo' => 'Filmes'],
        ['arquivo' => 'plataformas.php', 'icone' => 'globe', 'rotulo' => t('admin.platforms')],
    ],
];
?>
<!doctype html>
<html lang="pt-BR" class="layout-navbar-fixed layout-menu-fixed layout-compact" data-theme="light" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($adminTitulo) ?> — <?= e($config['nome']) ?></title>
    <link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
    <script src="<?= asset('js/theme.js') ?>"></script>
    <link rel="stylesheet" href="<?= asset('vendor/vuexy/core.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/personacut.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="pc-admin pc-admin-vuexy">
<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
        <aside id="layout-menu" class="layout-menu menu-vertical menu">
            <div class="app-brand demo">
                <a href="<?= url('admin/') ?>" class="app-brand-link">
                    <span class="app-brand-logo demo">
                        <span class="pc-admin__brand-mark">P</span>
                    </span>
                    <span class="app-brand-text demo menu-text fw-bold ms-3">Persona<span>Cut</span></span>
                </a>
                <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-xl-none" aria-label="Fechar menu">
                    <?= icon('x', 18) ?>
                </a>
            </div>

            <div class="menu-inner-shadow"></div>

            <ul class="menu-inner py-1">
                <?php foreach ($secoesMenu as $secao => $itens): ?>
                    <li class="menu-header small"><span class="menu-header-text"><?= e($secao) ?></span></li>

                    <?php foreach ($itens as $item): ?>
                        <?php $ativo = (string) $item['arquivo'] === (string) $adminAtivo; ?>
                        <li class="menu-item<?= $ativo ? ' active' : '' ?>">
                            <a class="menu-link" href="<?= e(url('admin/' . $item['arquivo'])) ?>">
                                <span class="menu-icon"><?= icon($item['icone'], 18) ?></span>
                                <div><?= e($item['rotulo']) ?></div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </ul>

            <div class="menu-user">
                <div class="menu-user-card">
                    <span class="avatar avatar-sm">
                        <span class="avatar-initial rounded-circle bg-primary"><?= e($adminInicial) ?></span>
                    </span>
                    <div class="menu-user-info">
                        <span class="menu-user-name"><?= e($adminNome) ?></span>
                        <span class="menu-user-email"><?= e($adminLogin) ?></span>
                    </div>
                    <form class="menu-user-logout" action="<?= e(url('admin/logout.php')) ?>" method="post">
                        <?= Csrf::campo() ?>
                        <button type="submit" title="<?= e(t('admin.logout')) ?>" aria-label="<?= e(t('admin.logout')) ?>">
                            <?= icon('external', 16) ?>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="layout-page">
            <nav class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
                <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
                    <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)" aria-label="Abrir menu">
                        <?= icon('list', 22) ?>
                    </a>
                </div>

                <div class="navbar-nav-right d-flex align-items-center justify-content-end w-100">
                    <div class="navbar-nav align-items-center">
                        <div class="nav-item d-flex align-items-center pc-admin__search">
                            <?= icon('search', 15) ?>
                            <span>Buscar no painel...</span>
                        </div>
                    </div>

                    <ul class="navbar-nav flex-row align-items-center ms-auto">
                        <li class="nav-item d-flex align-items-center me-2">
                            <form class="pc-admin__search-form d-flex align-items-center" action="<?= e(url('admin/buscar.php')) ?>" method="get" role="search">
                                <div class="pc-admin__search">
                                    <?= icon('search', 15) ?>
                                    <input type="search" name="q" id="pc-admin-q" value="<?= e($_GET['q'] ?? '') ?>"
                                           placeholder="<?= e(t('admin.search')) ?>" autocomplete="off" spellcheck="false" inputmode="search">
                                </div>
                                <button class="btn btn-sm btn-primary rounded-pill ms-2" type="submit" aria-label="<?= e(t('admin.search')) ?>">
                                    <?= icon('search', 14) ?>
                                </button>
                            </form>
                        </li>
                        <li class="nav-item d-none d-sm-block me-2">
                            <a class="btn btn-sm btn-label-primary" href="<?= e(url('/')) ?>" target="_blank" rel="noopener">
                                <?= e(t('admin.view_public')) ?>
                            </a>
                        </li>

                        <li class="nav-item me-2">
                            <button class="btn btn-icon btn-text-secondary rounded-pill" type="button" data-theme-toggle aria-label="<?= e(t('ui.nav.theme')) ?>">
                                <span class="pc-admin__theme-moon"><?= icon('moon', 18) ?></span>
                                <span class="pc-admin__theme-sun"><?= icon('sun', 18) ?></span>
                            </button>
                        </li>

                        <li class="nav-item navbar-dropdown dropdown-user dropdown">
                            <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0)" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="avatar avatar-online">
                                    <span class="avatar-initial rounded-circle bg-primary"><?= e($adminInicial) ?></span>
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <div class="dropdown-item">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0 me-2">
                                                <span class="avatar avatar-online">
                                                    <span class="avatar-initial rounded-circle bg-primary"><?= e($adminInicial) ?></span>
                                                </span>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-0"><?= e($adminNome) ?></h6>
                                                <small class="text-body-secondary"><?= e($adminLogin) ?> · painel</small>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li><div class="dropdown-divider my-1"></div></li>
                                <li>
                                    <a class="dropdown-item" href="<?= e(url('/')) ?>" target="_blank" rel="noopener">
                                        <?= e(t('admin.view_public')) ?>
                                    </a>
                                </li>
                                <li>
                                    <form action="<?= e(url('admin/logout.php')) ?>" method="post">
                                        <?= Csrf::campo() ?>
                                        <button type="submit" class="dropdown-item text-danger">
                                            <?= e(t('admin.logout')) ?>
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>

            <div class="content-wrapper container-xxl container-p-y">
                <main class="pc-admin__main">
                    <?php if ($flashErro !== null): ?>
                        <div class="pc-alert pc-alert--erro pc-mb-3"><?= e($flashErro) ?></div>
                    <?php endif; ?>

                    <?php if ($flashOk !== null): ?>
                        <div class="pc-alert pc-alert--ok pc-mb-3"><?= e($flashOk) ?></div>
                    <?php endif; ?>
