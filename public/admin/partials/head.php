<?php

declare(strict_types=1);

/**
 * Abertura do layout do painel administrativo.
 * Espelha o shell validado do sistema de mecânica (Vuexy):
 * sidebar, navbar, busca global, troca de tema e toasts.
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
        ['arquivo' => '', 'icone' => 'layout-dashboard', 'rotulo' => t('admin.dashboard')],
    ],
    'Conteúdo' => [
        ['arquivo' => 'eixos.php', 'icone' => 'compass', 'rotulo' => t('admin.axes')],
        ['arquivo' => 'perguntas.php', 'icone' => 'list', 'rotulo' => t('admin.questions')],
        ['arquivo' => 'arquetipos.php', 'icone' => 'layers', 'rotulo' => t('admin.archetypes')],
        ['arquivo' => 'entidades.php', 'icone' => 'users', 'rotulo' => t('admin.entities')],
    ],
    'Catálogo' => [
        ['arquivo' => 'filmes.php', 'icone' => 'movies', 'rotulo' => t('admin.catalog')],
        ['arquivo' => 'plataformas.php', 'icone' => 'device-tv', 'rotulo' => t('admin.platforms')],
    ],
];

$sugestoesBusca = [
    'Acesso rápido' => [
        ['name' => t('admin.dashboard'), 'icon' => 'tabler-layout-dashboard', 'url' => url('admin/')],
        ['name' => t('admin.axes'), 'icon' => 'tabler-compass', 'url' => url('admin/eixos.php')],
        ['name' => t('admin.questions'), 'icon' => 'tabler-list', 'url' => url('admin/perguntas.php')],
        ['name' => t('admin.archetypes'), 'icon' => 'tabler-layers', 'url' => url('admin/arquetipos.php')],
    ],
    'Catálogo' => [
        ['name' => t('admin.catalog'), 'icon' => 'tabler-movies', 'url' => url('admin/filmes.php')],
        ['name' => t('admin.platforms'), 'icon' => 'tabler-device-tv', 'url' => url('admin/plataformas.php')],
    ],
];
?>
<!DOCTYPE html>
<html lang="pt-BR"
      class="layout-navbar-fixed layout-menu-fixed layout-compact"
      dir="ltr" data-skin="default" data-bs-theme="light"
      data-assets-path="<?= e(asset('')) ?>" data-template="vertical-menu-template">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#53201c">
    <title><?= e($config['nome']) ?> | <?= e($adminTitulo) ?></title>

    <script>
        // Aplica o tema salvo antes do CSS para evitar flash claro/escuro.
        (function () {
            try {
                var root = document.documentElement;
                var pref = localStorage.getItem('personacut-admin-theme') || 'light';
                var resolved = pref === 'system'
                    ? (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                    : pref;
                root.setAttribute('data-bs-theme', resolved);
                root.setAttribute('data-theme-pref', pref);
            } catch (e) {}
        })();
    </script>

    <link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= e(asset('vendor/fonts/iconify-icons.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('vendor/libs/perfect-scrollbar/perfect-scrollbar.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('vendor/vuexy/core.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/demo.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('vendor/libs/select2/select2.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/admin-components.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/admin-theme.css')) ?>">

    <script src="<?= e(asset('vendor/js/helpers.js')) ?>"></script>
    <script src="<?= e(asset('js/config.js')) ?>"></script>
</head>
<body>
<a class="skip-link" href="#conteudo">Pular para o conteúdo</a>
<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
        <!-- Menu -->
        <aside id="layout-menu" class="layout-menu menu-vertical menu">
            <div class="app-brand demo">
                <a href="<?= e(url('admin/')) ?>" class="app-brand-link">
                    <span class="app-brand-logo demo">
                        <span class="pc-admin-brand-mark">P</span>
                    </span>
                    <span class="app-brand-text demo menu-text fw-bold ms-3">Persona<span>Cut</span></span>
                </a>
                <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-xl-none" aria-label="Fechar menu">
                    <i class="icon-base ti tabler-x"></i>
                </a>
            </div>

            <div class="menu-inner-shadow"></div>

            <ul class="menu-inner py-1">
                <?php foreach ($secoesMenu as $secao => $itens): ?>
                    <li class="menu-header small"><span class="menu-header-text"><?= e($secao) ?></span></li>
                    <?php foreach ($itens as $item): ?>
                        <?php $ativo = (string) $item['arquivo'] === (string) $adminAtivo; ?>
                        <li class="menu-item<?= $ativo ? ' active' : '' ?>">
                            <a href="<?= e(url('admin/' . $item['arquivo'])) ?>" class="menu-link">
                                <i class="menu-icon icon-base ti tabler-<?= e($item['icone']) ?>"></i>
                                <div><?= e($item['rotulo']) ?></div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </ul>

            <div class="menu-user">
                <div class="menu-user-card">
                    <a href="<?= e(url('/')) ?>" class="menu-user-profile" target="_blank" rel="noopener" title="<?= e(t('admin.view_public')) ?>">
                        <span class="avatar avatar-sm avatar-online">
                            <span class="avatar-initial rounded-circle bg-primary"><?= e($adminInicial) ?></span>
                        </span>
                    </a>
                    <div class="menu-user-info">
                        <span class="menu-user-name" title="<?= e($adminNome) ?>"><?= e($adminNome) ?></span>
                        <span class="menu-user-email" title="<?= e($adminLogin) ?>"><?= e($adminLogin) ?></span>
                    </div>
                    <form class="menu-user-logout" action="<?= e(url('admin/logout.php')) ?>" method="post">
                        <?= Csrf::campo() ?>
                        <button type="submit" title="<?= e(t('admin.logout')) ?>" aria-label="<?= e(t('admin.logout')) ?>">
                            <i class="icon-base ti tabler-logout"></i>
                        </button>
                    </form>
                </div>
            </div>
        </aside>
        <!-- / Menu -->

        <div class="layout-page">
            <nav class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
                <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
                    <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)" aria-label="Abrir menu">
                        <i class="icon-base ti tabler-menu-2 icon-md"></i>
                    </a>
                </div>

                <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
                    <!-- Busca global (padrão do sistema) -->
                    <div class="navbar-nav align-items-center navbar-search">
                        <div class="nav-item navbar-search-wrapper px-md-0 px-2 mb-0">
                            <div class="nav-item nav-link search-toggler d-flex align-items-center px-0">
                                <span class="d-inline-block text-body-secondary fw-normal" id="global-search"></span>
                            </div>
                        </div>
                    </div>

                    <ul class="navbar-nav flex-row align-items-center ms-md-auto">
                        <li class="nav-item d-none d-sm-block me-3 me-xl-2">
                            <a class="btn btn-sm btn-label-primary" href="<?= e(url('/')) ?>" target="_blank" rel="noopener">
                                <i class="icon-base ti tabler-external-link me-1"></i>
                                <?= e(t('admin.view_public')) ?>
                            </a>
                        </li>

                        <!-- Tema -->
                        <li class="nav-item dropdown me-3 me-xl-2">
                            <a class="nav-link dropdown-toggle hide-arrow btn btn-icon btn-text-secondary rounded-pill"
                               id="nav-theme" href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="visually-hidden" id="nav-theme-text">Tema</span>
                                <i class="icon-base ti tabler-sun icon-22px theme-icon-active" id="themeIcon" data-icon="sun"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="nav-theme">
                                <li><button type="button" class="dropdown-item" data-bs-theme-value="light"><i class="icon-base ti tabler-sun me-2" data-icon="sun"></i>Claro</button></li>
                                <li><button type="button" class="dropdown-item" data-bs-theme-value="dark"><i class="icon-base ti tabler-moon-stars me-2" data-icon="moon-stars"></i>Escuro</button></li>
                                <li><button type="button" class="dropdown-item" data-bs-theme-value="system"><i class="icon-base ti tabler-device-desktop-analytics me-2" data-icon="device-desktop-analytics"></i>Sistema</button></li>
                            </ul>
                        </li>

                        <!-- Usuário -->
                        <li class="nav-item navbar-dropdown dropdown-user dropdown">
                            <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
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
                                        <i class="icon-base ti tabler-external-link me-2"></i><?= e(t('admin.view_public')) ?>
                                    </a>
                                </li>
                                <li>
                                    <form action="<?= e(url('admin/logout.php')) ?>" method="post">
                                        <?= Csrf::campo() ?>
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="icon-base ti tabler-logout me-2"></i><?= e(t('admin.logout')) ?>
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Content wrapper -->
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y" id="conteudo" tabindex="-1">
                    <?php if ($flashErro !== null || $flashOk !== null): ?>
                        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090;">
                            <div class="toast align-items-center text-bg-<?= $flashErro !== null ? 'danger' : 'success' ?> border-0" role="alert" data-autohide="true">
                                <div class="d-flex">
                                    <div class="toast-body"><?= e($flashErro ?? $flashOk) ?></div>
                                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fechar"></button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
