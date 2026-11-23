<?php

declare(strict_types=1);

/** CRUD de perguntas do questionário. */

require __DIR__ . '/_guard.php';

use Core\Csrf;
use Models\Eixo;
use Models\Pergunta;
use Services\MidiaService;
use Services\SceneService;
use Services\TmdbService;

$adminTitulo = t('admin.questions');
$adminAtivo = 'perguntas.php';
$configMidia = require BASE_PATH . '/config/midia.php';
$eixos = Eixo::todos($pdo);
$eixosPorId = [];
$sugestoes = [];
$sugestoesErro = null;

foreach ($eixos as $eixo) {
    $eixosPorId[(int) $eixo['id']] = $eixo;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!Csrf::validarRequisicao()) {
        pc_admin_redirect(url('admin/perguntas.php'), t('erros.csrf'), 'erro');
    }

    $acao = pc_admin_texto('acao');

    if ($acao === 'sugerir') {
        $idSug = pc_admin_inteiro('id');
        $tmdbIdSug = pc_admin_inteiro('tmdb_id');

        if ($idSug <= 0) {
            $sugestoesErro = 'Salve a pergunta antes de sugerir clipes.';
        } else {
            $stmt = $pdo->prepare('SELECT * FROM perguntas WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $idSug]);
            $perguntaSug = $stmt->fetch();

            if (!$perguntaSug) {
                $sugestoesErro = 'Pergunta não encontrada.';
            } else {
                $metaSug = json_decode((string) ($perguntaSug['midia_meta'] ?? ''), true);
                $metaSug = is_array($metaSug) ? $metaSug : [];

                if ($tmdbIdSug > 0) {
                    $perguntaSug['tmdb_id'] = $tmdbIdSug;
                    $metaSug['tmdb_id'] = $tmdbIdSug;
                }

                $legendaPost = pc_admin_texto('midia_legenda');
                $anoPost = pc_admin_inteiro('midia_ano');

                if ($legendaPost !== '') {
                    $metaSug['legenda'] = $legendaPost;
                }

                if ($anoPost > 0) {
                    $metaSug['ano'] = $anoPost;
                }

                $perguntaSug['midia_meta'] = $metaSug;
                $perfilSug = SceneService::perfil($perguntaSug, $configMidia);

                if (empty($perfilSug['tmdb_id'])) {
                    $sugestoesErro = 'Informe o TMDB ID do filme antes de sugerir clipes.';
                } else {
                    $tmdb = new TmdbService($pdo, $config['tmdb']);

                    if (!$tmdb->disponivel()) {
                        $sugestoesErro = 'TMDB_API_KEY ausente. Configure no .env para buscar clipes.';
                    } else {
                        $candidatosSug = SceneService::candidatos($tmdb->videos((int) $perfilSug['tmdb_id']));
                        $avaliadosSug = SceneService::avaliarCandidatos($candidatosSug, $perfilSug, $configMidia);
                        $minimoSug = (float) ($configMidia['score_minimo'] ?? 8.0);

                        foreach (array_slice($avaliadosSug, 0, 8) as $candidatoSug) {
                            $candidatoSug['aprovado'] = (float) $candidatoSug['score'] >= $minimoSug;
                            $candidatoSug['atual'] = $candidatoSug['key'] === (string) ($perguntaSug['midia_url_ou_id'] ?? '');
                            $candidatoSug['legenda'] = (string) ($metaSug['legenda'] ?? '');
                            $candidatoSug['ano'] = (int) ($metaSug['ano'] ?? 0);
                            $sugestoes[] = $candidatoSug;
                        }

                        if ($sugestoes === []) {
                            $sugestoesErro = 'Nenhum clipe de cena encontrado para este filme. Mantenha a imagem atual.';
                        }
                    }
                }
            }
        }
    } elseif ($acao === 'usar_clipe') {
        $idClipe = pc_admin_inteiro('id');
        $keyClipe = MidiaService::youtubeId(pc_admin_texto('video_key'));

        if ($idClipe <= 0 || $keyClipe === null) {
            pc_admin_redirect(url('admin/perguntas.php'), 'Clipe inválido.', 'erro');
        }

        $stmt = $pdo->prepare('SELECT * FROM perguntas WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $idClipe]);
        $perguntaClipe = $stmt->fetch();

        if (!$perguntaClipe) {
            pc_admin_redirect(url('admin/perguntas.php'), 'Pergunta não encontrada.', 'erro');
        }

        $metaAtual = json_decode((string) ($perguntaClipe['midia_meta'] ?? ''), true);
        $metaAtual = is_array($metaAtual) ? $metaAtual : [];
        $posterClipe = (string) ($metaAtual['poster'] ?? '');

        if ($posterClipe === '' && ($perguntaClipe['midia_tipo'] ?? null) === 'imagem') {
            $posterClipe = (string) ($perguntaClipe['midia_url_ou_id'] ?? '');
        }

        $metaClipe = [
            'legenda'      => (string) ($metaAtual['legenda'] ?? ''),
            'ano'          => isset($metaAtual['ano']) ? (int) $metaAtual['ano'] : null,
            'start'        => 0,
            'end'          => (int) ($configMidia['duracao_padrao'] ?? 12),
            'fonte'        => 'tmdb',
            'tmdb_id'      => $perguntaClipe['tmdb_id'] !== null ? (int) $perguntaClipe['tmdb_id'] : null,
            'video_nome'   => pc_admin_texto('video_nome'),
            'video_tipo'   => 'Clip',
            'video_idioma' => pc_admin_texto('video_idioma'),
            'video_score'  => (float) str_replace(',', '.', pc_admin_texto('video_score')),
            'credito'      => 'YouTube',
        ];

        $legendaClipe = pc_admin_texto('legenda');
        $anoClipe = pc_admin_inteiro('ano');

        if ($legendaClipe !== '') {
            $metaClipe['legenda'] = $legendaClipe;
        }

        if ($anoClipe > 0) {
            $metaClipe['ano'] = $anoClipe;
        }

        if ($posterClipe !== '') {
            $metaClipe['poster'] = $posterClipe;
        }

        $stmt = $pdo->prepare(
            'UPDATE perguntas
                SET midia_tipo = :tipo, midia_url_ou_id = :url, midia_meta = :meta
              WHERE id = :id'
        );
        $stmt->execute([
            'tipo' => 'video_yt',
            'url'  => $keyClipe,
            'meta' => json_encode($metaClipe, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'id'   => $idClipe,
        ]);

        pc_admin_redirect(
            url('admin/perguntas.php', ['editar' => $idClipe]),
            'Clipe aplicado. Ajuste início/fim se precisar.',
            'ok'
        );
    } else {
    if ($acao === 'excluir') {
        $id = pc_admin_inteiro('id');
        $stmt = $pdo->prepare('DELETE FROM perguntas WHERE id = :id');
        $stmt->execute(['id' => $id]);

        pc_admin_redirect(url('admin/perguntas.php'), t('admin.deleted'));
    }

    $id = pc_admin_inteiro('id');
    $eixoId = pc_admin_inteiro('eixo_id');
    $texto = pc_admin_texto('texto');
    $textoKey = pc_admin_texto('texto_key');
    $direcao = pc_admin_inteiro('direcao', 1) === -1 ? -1 : 1;
    $peso = pc_admin_inteiro('peso', 1) === 2 ? 2 : 1;
    $modoRapido = isset($_POST['modo_rapido']) ? 1 : 0;
    $ordem = pc_admin_inteiro('ordem', 0);
    $tmdbId = pc_admin_inteiro('tmdb_id', 0);

    $midiaTipo = pc_admin_texto('midia_tipo');
    $midiaValor = pc_admin_texto('midia_url_ou_id');
    $midiaMetaDados = [
        'legenda' => pc_admin_texto('midia_legenda'),
        'ano'     => pc_admin_inteiro('midia_ano'),
        'start'   => $midiaTipo === 'video_yt' ? pc_admin_inteiro('midia_start') : null,
        'end'     => $midiaTipo === 'video_yt' ? pc_admin_inteiro('midia_end') : null,
    ];
    $midiaMetaJson = null;

    $erros = [];

    if (!isset($eixosPorId[$eixoId])) {
        $erros[] = 'Escolha um eixo válido.';
    }

    if (mb_strlen($texto) < 10 || mb_strlen($texto) > 255) {
        $erros[] = 'O texto deve ter entre 10 e 255 caracteres.';
    }

    if ($textoKey === '') {
        $textoKey = 'pergunta.' . (isset($eixosPorId[$eixoId]) ? $eixosPorId[$eixoId]['slug'] : 'geral')
            . '.' . substr(bin2hex(random_bytes(2)), 0, 4);
    }

    if ($midiaTipo === '') {
        $midiaValor = '';
    } elseif (!in_array($midiaTipo, MidiaService::TIPOS, true)) {
        $erros[] = 'Tipo de mídia inválido.';
    } elseif ($midiaValor === '') {
        $erros[] = 'Informe a URL ou o ID da cena ilustrativa.';
    } elseif ($midiaTipo === 'video_yt' && MidiaService::youtubeId($midiaValor) === null) {
        $erros[] = 'Informe um ID ou uma URL válida do YouTube.';
    } elseif ($midiaTipo === 'gif' && preg_match('#^https?://#i', $midiaValor) !== 1) {
        $erros[] = 'O GIF precisa de uma URL http(s) do GIPHY, Tenor ou de outro CDN.';
    } elseif (
        $midiaTipo === 'imagem'
        && preg_match('#^https?://#i', $midiaValor) !== 1
        && !str_starts_with($midiaValor, '/')
    ) {
        $erros[] = 'A imagem precisa ser um caminho da TMDB (começando com /) ou uma URL completa.';
    }

    if ($midiaTipo !== '' && $erros === []) {
        $midiaMetaJson = json_encode(
            MidiaService::metaParaSalvar($midiaMetaDados, $midiaValor),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) ?: null;
    } elseif ($midiaTipo === '') {
        $midiaMetaJson = null;
    }

    if ($erros !== []) {
        pc_admin_redirect(
            url('admin/perguntas.php', ['editar' => $id > 0 ? $id : 'novo']),
            implode(' ', $erros),
            'erro'
        );
    }

    try {
        if ($id > 0) {
            $stmt = $pdo->prepare(
                'UPDATE perguntas
                    SET eixo_id = :eixo_id, texto_key = :texto_key, texto = :texto,
                        direcao = :direcao, peso = :peso, modo_rapido = :modo_rapido, ordem = :ordem,
                        tmdb_id = :tmdb_id,
                        midia_tipo = :midia_tipo, midia_url_ou_id = :midia_url_ou_id,
                        midia_meta = :midia_meta
                  WHERE id = :id'
            );
            $stmt->execute([
                'eixo_id' => $eixoId, 'texto_key' => $textoKey, 'texto' => $texto,
                'direcao' => $direcao, 'peso' => $peso, 'modo_rapido' => $modoRapido,
                'ordem' => $ordem, 'tmdb_id' => $tmdbId > 0 ? $tmdbId : null,
                'midia_tipo' => $midiaTipo !== '' ? $midiaTipo : null,
                'midia_url_ou_id' => $midiaValor !== '' ? $midiaValor : null,
                'midia_meta' => $midiaMetaJson, 'id' => $id,
            ]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO perguntas
                    (eixo_id, texto_key, texto, direcao, peso, modo_rapido, ordem, tmdb_id,
                     midia_tipo, midia_url_ou_id, midia_meta)
                 VALUES (:eixo_id, :texto_key, :texto, :direcao, :peso, :modo_rapido, :ordem, :tmdb_id,
                         :midia_tipo, :midia_url_ou_id, :midia_meta)'
            );
            $stmt->execute([
                'eixo_id' => $eixoId, 'texto_key' => $textoKey, 'texto' => $texto,
                'direcao' => $direcao, 'peso' => $peso, 'modo_rapido' => $modoRapido,
                'ordem' => $ordem, 'tmdb_id' => $tmdbId > 0 ? $tmdbId : null,
                'midia_tipo' => $midiaTipo !== '' ? $midiaTipo : null,
                'midia_url_ou_id' => $midiaValor !== '' ? $midiaValor : null,
                'midia_meta' => $midiaMetaJson,
            ]);
        }
    } catch (PDOException $erro) {
        $mensagem = ($erro->errorInfo[1] ?? 0) === 1062
            ? 'Já existe uma pergunta com essa chave de texto.'
            : 'Erro ao salvar a pergunta: ' . $erro->getMessage();

        pc_admin_redirect(url('admin/perguntas.php'), $mensagem, 'erro');
    }

    pc_admin_redirect(url('admin/perguntas.php'), t('admin.saved'));
    }
}

$perguntas = Pergunta::porEixo($pdo);
$editar = $_GET['editar'] ?? null;
$editando = null;

if ($editar === 'novo') {
    $editando = [
        'id' => 0, 'eixo_id' => array_key_first($eixosPorId) ?? 1, 'texto_key' => '', 'texto' => '',
        'direcao' => 1, 'peso' => 2, 'modo_rapido' => 0, 'ordem' => count($perguntas) + 1,
        'tmdb_id' => null,
        'midia_tipo' => null, 'midia_url_ou_id' => null, 'midia_meta' => [],
    ];
} elseif ($editar !== null) {
    foreach ($perguntas as $pergunta) {
        if ((int) $pergunta['id'] === (int) $editar) {
            $editando = $pergunta;
        }
    }
}

require __DIR__ . '/partials/head.php';
?>

<div class="page-header">
    <div>
        <h1><i class="icon-base ti tabler-list"></i><?= e(t('admin.questions')) ?></h1>
        <span class="page-subtitle">
            <?= count($perguntas) ?> perguntas ·
            <?= count(array_filter($perguntas, static fn (array $p): bool => (int) $p['modo_rapido'] === 1)) ?> no núcleo ·
            rápido <?= \Models\Pergunta::MODOS['rapido'] ?> · completo <?= \Models\Pergunta::MODOS['completo'] ?> · extremo <?= \Models\Pergunta::MODOS['extremo'] ?>
        </span>
    </div>
    <div class="page-header-actions">
        <a class="btn btn-primary" href="<?= e(url('admin/perguntas.php', ['editar' => 'novo'])) ?>">
            <i class="icon-base ti tabler-plus"></i> <?= e(t('admin.new')) ?>
        </a>
    </div>
</div>

<?php if ($editando !== null): ?>
    <div class="card mb-4">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="card-title mb-1"><?= (int) $editando['id'] > 0 ? 'Editar pergunta' : 'Nova pergunta' ?></h5>
                <p class="card-subtitle mb-0">Afirmação, direção do eixo, peso e cena ilustrativa</p>
            </div>
            <?php if ((int) $editando['id'] > 0): ?>
                <span class="badge bg-label-primary rounded-pill">#<?= (int) $editando['id'] ?></span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <form method="post" action="<?= e(url('admin/perguntas.php', ['editar' => (int) $editando['id'] > 0 ? (int) $editando['id'] : 'novo'])) ?>">
                <?= Csrf::campo() ?>
                <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="eixo_id">Eixo</label>
                        <select class="form-select" id="eixo_id" name="eixo_id">
                            <?php foreach ($eixos as $eixo): ?>
                                <option value="<?= (int) $eixo['id'] ?>" <?= (int) $eixo['id'] === (int) $editando['eixo_id'] ? 'selected' : '' ?>>
                                    <?= e($eixo['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label" for="direcao">Direção</label>
                        <select class="form-select" id="direcao" name="direcao">
                            <option value="1" <?= (int) $editando['direcao'] === 1 ? 'selected' : '' ?>>Concordar empurra para o polo direito</option>
                            <option value="-1" <?= (int) $editando['direcao'] === -1 ? 'selected' : '' ?>>Concordar empurra para o polo esquerdo</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="peso">Peso</label>
                        <select class="form-select" id="peso" name="peso">
                            <option value="2" <?= (int) $editando['peso'] === 2 ? 'selected' : '' ?>>2 — define o eixo</option>
                            <option value="1" <?= (int) $editando['peso'] === 1 ? 'selected' : '' ?>>1 — nuance</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="texto">Afirmação</label>
                        <textarea class="form-control" id="texto" name="texto" maxlength="255" rows="2" required><?= e($editando['texto']) ?></textarea>
                    </div>
                </div>

                <?php
                $midiaMeta = is_array($editando['midia_meta'] ?? null) ? $editando['midia_meta'] : [];
                $midiaLegenda = (string) ($midiaMeta['legenda'] ?? '');
                $midiaAno = (int) ($midiaMeta['ano'] ?? 0);
                $midiaStart = (int) ($midiaMeta['start'] ?? 0);
                $midiaEnd = (int) ($midiaMeta['end'] ?? 0);
                ?>
                <fieldset class="mt-4 mb-4">
                    <legend class="h6 text-heading mb-3">Cena ilustrativa <small class="text-body-secondary fw-normal">(opcional)</small></legend>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="midia_tipo">Tipo de mídia</label>
                            <select class="form-select" id="midia_tipo" name="midia_tipo">
                                <option value="" <?= in_array($editando['midia_tipo'] ?? null, [null, ''], true) ? 'selected' : '' ?>>Sem cena</option>
                                <option value="imagem" <?= ($editando['midia_tipo'] ?? '') === 'imagem' ? 'selected' : '' ?>>Imagem (TMDB ou URL)</option>
                                <option value="gif" <?= ($editando['midia_tipo'] ?? '') === 'gif' ? 'selected' : '' ?>>GIF (GIPHY ou outro CDN)</option>
                                <option value="video_yt" <?= ($editando['midia_tipo'] ?? '') === 'video_yt' ? 'selected' : '' ?>>Vídeo do YouTube</option>
                            </select>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label" for="midia_url_ou_id">Caminho TMDB, URL ou ID do YouTube</label>
                            <input class="form-control" id="midia_url_ou_id" name="midia_url_ou_id" type="text" maxlength="255"
                                   value="<?= e((string) ($editando['midia_url_ou_id'] ?? '')) ?>"
                                   placeholder="/8K9qHeM6G6QjQN0C5XKFGvK5lzM.jpg ou dQw4w9WgXcQ">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="tmdb_id">TMDB ID do filme</label>
                            <input class="form-control" id="tmdb_id" name="tmdb_id" type="number" min="1" max="99999999"
                                   value="<?= (int) ($editando['tmdb_id'] ?? 0) > 0 ? (int) $editando['tmdb_id'] : '' ?>" placeholder="680">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="midia_legenda">Legenda na cena</label>
                            <input class="form-control" id="midia_legenda" name="midia_legenda" type="text" maxlength="120"
                                   value="<?= e($midiaLegenda) ?>" placeholder="Pulp Fiction (1994)">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="midia_ano">Ano do filme</label>
                            <input class="form-control" id="midia_ano" name="midia_ano" type="number" min="1888" max="2100"
                                   value="<?= $midiaAno > 0 ? $midiaAno : '' ?>" placeholder="1994">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="midia_start">Início do clipe (s)</label>
                            <input class="form-control" id="midia_start" name="midia_start" type="number" min="0" max="86399"
                                   value="<?= $midiaStart > 0 ? $midiaStart : '' ?>" placeholder="0">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="midia_end">Fim do clipe (s)</label>
                            <input class="form-control" id="midia_end" name="midia_end" type="number" min="0" max="86399"
                                   value="<?= $midiaEnd > 0 ? $midiaEnd : '' ?>" placeholder="12">
                        </div>

                        <div class="col-12">
                            <p class="alert alert-info py-2 px-3 mb-0 small" role="note">
                                <i class="icon-base ti tabler-info-circle me-2"></i>
                                Imagens: caminho do backdrop da TMDB. O TMDB ID é usado para sugerir clipes oficiais do YouTube;
                                início/fim só valem para vídeos. Salve a pergunta antes de aplicar uma sugestão.
                            </p>
                        </div>
                    </div>
                </fieldset>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="texto_key">Chave de tradução (opcional)</label>
                        <input class="form-control" id="texto_key" name="texto_key" type="text" value="<?= e($editando['texto_key']) ?>" placeholder="pergunta.estetica.05">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label" for="ordem">Ordem</label>
                        <input class="form-control" id="ordem" name="ordem" type="number" min="0" max="999" value="<?= (int) $editando['ordem'] ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="modo_rapido">Núcleo do eixo</label>
                        <div class="form-check form-switch my-2">
                            <input class="form-check-input" type="checkbox" id="modo_rapido" name="modo_rapido" value="1" <?= (int) $editando['modo_rapido'] === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="modo_rapido">Uma das duas primeiras do eixo</label>
                        </div>
                        <small class="text-body-secondary d-block">
                            O modo é definido pela ordem: as 4 primeiras de cada eixo entram no rápido, as 8 primeiras no completo, e todas as 30 no extremo.
                        </small>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button class="btn btn-primary" type="submit" name="acao" value="salvar">
                        <i class="icon-base ti tabler-device-floppy me-1"></i><?= e(t('admin.save')) ?>
                    </button>
                    <a class="btn btn-outline-secondary" href="<?= e(url('admin/perguntas.php')) ?>">
                        <i class="icon-base ti tabler-arrow-left me-1"></i><?= e(t('admin.back')) ?>
                    </a>

                    <?php if ((int) $editando['id'] > 0): ?>
                        <button class="btn btn-outline-info" type="submit" name="acao" value="sugerir" formnovalidate>
                            <i class="icon-base ti tabler-player-play me-1"></i> Sugerir clipes da TMDB
                        </button>
                        <button class="btn btn-outline-danger ms-auto" type="submit" form="form-excluir">
                            <i class="icon-base ti tabler-trash me-1"></i><?= e(t('admin.delete')) ?>
                        </button>
                    <?php endif; ?>
                </div>
            </form>

            <?php if ($sugestoesErro !== null): ?>
                <div class="alert alert-danger mt-4 mb-0" role="alert">
                    <i class="icon-base ti tabler-alert-circle me-2"></i><?= e($sugestoesErro) ?>
                </div>
            <?php endif; ?>

            <?php if ($sugestoes !== []): ?>
                <section class="mt-5">
                    <div class="card-header px-0 border-0 mb-3">
                        <h5 class="card-title mb-1"><i class="icon-base ti tabler-video me-2"></i>Sugestões de clipes oficiais</h5>
                        <p class="card-subtitle mb-0">
                            Clipes de cena da TMDB ordenados por afinidade com a pergunta.
                            “Usar este clipe” grava o vídeo e mantém legenda/ano atuais.
                        </p>
                    </div>

                    <div class="row g-3">
                        <?php foreach ($sugestoes as $sugestao): ?>
                            <div class="col-12 col-md-6 col-xl-4">
                                <article class="card h-100<?= !empty($sugestao['atual']) ? ' border-primary' : '' ?>">
                                    <img class="card-img-top" src="https://i.ytimg.com/vi/<?= e((string) $sugestao['key']) ?>/hqdefault.jpg"
                                         alt="" loading="lazy" width="320" height="180">
                                    <div class="card-body d-flex flex-column">
                                        <h6 class="card-title mb-2"><?= e((string) $sugestao['nome']) ?></h6>
                                        <p class="card-text mb-3">
                                            <span class="badge <?= !empty($sugestao['aprovado']) ? 'bg-label-success' : 'bg-label-warning' ?> rounded-pill">
                                                <?= !empty($sugestao['aprovado']) ? 'recomendado' : 'abaixo do limiar' ?>
                                            </span>
                                            <span class="text-body-secondary small ms-1">score <?= numero((float) $sugestao['score'], 1) ?></span>
                                            <?php if (!empty($sugestao['atual'])): ?>
                                                <span class="badge bg-label-primary rounded-pill ms-1">atual</span>
                                            <?php endif; ?>
                                        </p>
                                        <form class="mt-auto" method="post" action="<?= e(url('admin/perguntas.php', ['editar' => (int) $editando['id']])) ?>">
                                            <?= Csrf::campo() ?>
                                            <input type="hidden" name="acao" value="usar_clipe">
                                            <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">
                                            <input type="hidden" name="video_key" value="<?= e((string) $sugestao['key']) ?>">
                                            <input type="hidden" name="video_nome" value="<?= e((string) $sugestao['nome']) ?>">
                                            <input type="hidden" name="video_idioma" value="<?= e((string) ($sugestao['idioma'] ?? '')) ?>">
                                            <input type="hidden" name="video_score" value="<?= number_format((float) $sugestao['score'], 1, '.', '') ?>">
                                            <input type="hidden" name="legenda" value="<?= e((string) ($sugestao['legenda'] ?? '')) ?>">
                                            <input type="hidden" name="ano" value="<?= (int) ($sugestao['ano'] ?? 0) ?>">
                                            <button class="btn btn-sm btn-primary w-100" type="submit">
                                                <i class="icon-base ti tabler-check me-1"></i> Usar este clipe
                                            </button>
                                        </form>
                                    </div>
                                </article>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ((int) $editando['id'] > 0): ?>
                <form id="form-excluir" method="post" action="<?= e(url('admin/perguntas.php')) ?>" data-confirm="<?= e(t('admin.confirm_delete')) ?>">
                    <?= Csrf::campo() ?>
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h5 class="card-title mb-1"><?= e(t('admin.questions')) ?></h5>
            <p class="card-subtitle mb-0"><?= count($perguntas) ?> perguntas cadastradas</p>
        </div>
    </div>
    <div class="card-datatable table-responsive">
        <table class="table table-hover align-middle responsive-card mb-0">
            <thead class="border-top">
            <tr>
                <th>#</th>
                <th>Eixo</th>
                <th>Afirmação</th>
                <th>Dir.</th>
                <th>Peso</th>
                <th>Núcleo</th>
                <th>Mídia</th>
                <th class="text-end">Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($perguntas as $pergunta): ?>
                <tr>
                    <td data-label="#"><span class="badge bg-label-secondary rounded-pill"><?= (int) $pergunta['id'] ?></span></td>
                    <td data-label="Eixo" class="text-nowrap"><?= e($eixosPorId[(int) $pergunta['eixo_id']]['nome'] ?? '—') ?></td>
                    <td data-label="Afirmação"><?= e($pergunta['texto']) ?></td>
                    <td data-label="Dir."><i class="icon-base ti <?= (int) $pergunta['direcao'] === 1 ? 'tabler-arrow-right' : 'tabler-arrow-left' ?>"></i></td>
                    <td data-label="Peso"><span class="badge bg-label-<?= (int) $pergunta['peso'] === 2 ? 'primary' : 'secondary' ?> rounded-pill"><?= (int) $pergunta['peso'] ?></span></td>
                    <td data-label="Núcleo"><?= (int) $pergunta['modo_rapido'] === 1 ? '<span class="badge bg-label-primary rounded-pill">núcleo</span>' : '—' ?></td>
                    <td data-label="Mídia" class="text-nowrap">
                        <?php if (($pergunta['midia_tipo'] ?? null) !== null): ?>
                            <?php
                            $rotuloMidia = match ($pergunta['midia_tipo']) {
                                'imagem'   => 'imagem',
                                'gif'      => 'GIF',
                                'video_yt' => 'YouTube',
                                default    => (string) $pergunta['midia_tipo'],
                            };
                            ?>
                            <span class="badge bg-label-info rounded-pill"><?= e($rotuloMidia) ?></span>
                            <?php if (($pergunta['midia_meta']['legenda'] ?? '') !== ''): ?>
                                <small class="text-body-secondary ms-1"><?= e((string) $pergunta['midia_meta']['legenda']) ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td data-label="Ações" class="text-end">
                        <a class="btn btn-sm btn-icon btn-text-secondary rounded-pill" href="<?= e(url('admin/perguntas.php', ['editar' => $pergunta['id']])) ?>" title="<?= e(t('admin.edit')) ?>">
                            <i class="icon-base ti tabler-pencil"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/partials/foot.php'; ?>
