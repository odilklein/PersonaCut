# Modelo de dados

Dicionário gerado automaticamente do schema do banco `personacut` (MySQL 8.4).
Para regenerar: `python3 docs/build/gerar_dicionario.py`.

## Visão geral

| Grupo | Tabelas |
|---|---|
| Perfil e questionário | 5 |
| Curadoria do matcher | 2 |
| Catálogo audiovisual | 7 |
| Streaming | 2 |
| Conta e lista pessoal | 2 |
| Acesso e cache | 2 |
| **Total** | **20** |

O modelo separa três domínios que não se misturam: o **perfil** (eixos,
perguntas, resultados e retratos), a **curadoria do matcher** (52 espelhos com
vetor) e o **catálogo audiovisual** (obras, pessoas, gêneros, países e
plataformas). A separação é intencional: o catálogo muda toda semana, o
matcher é editorial e o perfil precisa continuar reproduzível.

## Perfil e questionário

### `eixos`

Os seis eixos bipolares do perfil, com nomes dos polos, cores e chaves de tradução.

Registros na base de demonstração: **6**.

| Coluna | Tipo | Nulo | Chave | Extra | Padrão |
|---|---|---|---|---|---|
| `id` | `tinyint unsigned` | NO | PRI | auto_increment |  |
| `ordem` | `tinyint unsigned` | NO | UNI |  |  |
| `slug` | `varchar(40)` | NO | UNI |  |  |
| `nome` | `varchar(60)` | NO |  |  |  |
| `descricao` | `text` | YES |  |  |  |
| `polo_esq_key` | `varchar(80)` | NO |  |  |  |
| `polo_esq_nome` | `varchar(60)` | NO |  |  |  |
| `polo_dir_key` | `varchar(80)` | NO |  |  |  |
| `polo_dir_nome` | `varchar(60)` | NO |  |  |  |
| `cor_esq` | `char(7)` | NO |  |  |  |
| `cor_dir` | `char(7)` | NO |  |  |  |
| `icone_esq` | `varchar(40)` | NO |  |  |  |
| `icone_dir` | `varchar(40)` | NO |  |  |  |
| `tmdb_genres_esq` | `varchar(120)` | NO |  |  |  |
| `tmdb_genres_dir` | `varchar(120)` | NO |  |  |  |
| `tmdb_keywords_esq` | `varchar(255)` | NO |  |  |  |
| `tmdb_keywords_dir` | `varchar(255)` | NO |  |  |  |

### `perguntas`

Afirmações Likert, com direção, peso, modo rápido, filme da TMDB e cena ilustrativa.

Registros na base de demonstração: **48**.

| Coluna | Tipo | Nulo | Chave | Extra | Padrão |
|---|---|---|---|---|---|
| `id` | `smallint unsigned` | NO | PRI | auto_increment |  |
| `eixo_id` | `tinyint unsigned` | NO | MUL |  |  |
| `texto_key` | `varchar(80)` | NO | UNI |  |  |
| `texto` | `varchar(255)` | NO |  |  |  |
| `direcao` | `tinyint` | NO |  |  |  |
| `peso` | `tinyint unsigned` | NO |  |  | 1 |
| `modo_rapido` | `tinyint(1)` | NO | MUL |  | 0 |
| `ordem` | `smallint unsigned` | NO |  |  | 0 |
| `tmdb_id` | `int unsigned` | YES | MUL |  |  |
| `midia_tipo` | `enum('imagem','gif','video_yt')` | YES |  |  |  |
| `midia_url_ou_id` | `varchar(255)` | YES |  |  |  |
| `midia_meta` | `json` | YES |  |  |  |

**Chaves estrangeiras**

- `eixo_id` → `eixos.id` (`fk_perguntas_eixo`)

### `arquetipos`

Doze arquétipos (um por eixo dominante e polo), com vetor de referência e texto editorial.

Registros na base de demonstração: **12**.

| Coluna | Tipo | Nulo | Chave | Extra | Padrão |
|---|---|---|---|---|---|
| `id` | `smallint unsigned` | NO | PRI | auto_increment |  |
| `eixo_dominante_1` | `tinyint unsigned` | NO | MUL |  |  |
| `polo_1` | `enum('E','D')` | NO |  |  |  |
| `eixo_dominante_2` | `tinyint unsigned` | YES | MUL |  |  |
| `polo_2` | `enum('E','D')` | YES |  |  |  |
| `eixo_1` | `tinyint unsigned` | NO |  |  | 50 |
| `eixo_2` | `tinyint unsigned` | NO |  |  | 50 |
| `eixo_3` | `tinyint unsigned` | NO |  |  | 50 |
| `eixo_4` | `tinyint unsigned` | NO |  |  | 50 |
| `eixo_5` | `tinyint unsigned` | NO |  |  | 50 |
| `eixo_6` | `tinyint unsigned` | NO |  |  | 50 |
| `titulo_key` | `varchar(80)` | NO | UNI |  |  |
| `descricao_key` | `varchar(80)` | NO |  |  |  |
| `ritual_cinema_key` | `varchar(80)` | NO |  |  |  |
| `titulo` | `varchar(120)` | NO |  |  |  |
| `descricao` | `text` | NO |  |  |  |
| `ritual_cinema` | `varchar(255)` | NO |  |  |  |

**Chaves estrangeiras**

- `eixo_dominante_1` → `eixos.id` (`fk_arq_eixo1`)
- `eixo_dominante_2` → `eixos.id` (`fk_arq_eixo2`)

### `resultados_usuarios`

Perfil calculado: código público, respostas originais, vetor de seis eixos e origem (real ou sintética).

Registros na base de demonstração: **602**.

| Coluna | Tipo | Nulo | Chave | Extra | Padrão |
|---|---|---|---|---|---|
| `id` | `bigint unsigned` | NO | PRI | auto_increment |  |
| `codigo_unico` | `char(8)` | NO | UNI |  |  |
| `idioma` | `varchar(5)` | NO |  |  | pt-BR |
| `modo` | `enum('rapido','completo','extremo')` | NO |  |  | completo |
| `questionario_versao` | `varchar(40)` | YES |  |  |  |
| `respostas_json` | `json` | YES |  |  |  |
| `eixo_1` | `decimal(5,2)` | NO |  |  |  |
| `eixo_2` | `decimal(5,2)` | NO |  |  |  |
| `eixo_3` | `decimal(5,2)` | NO |  |  |  |
| `eixo_4` | `decimal(5,2)` | NO |  |  |  |
| `eixo_5` | `decimal(5,2)` | NO |  |  |  |
| `eixo_6` | `decimal(5,2)` | NO |  |  |  |
| `arquetipo_id` | `smallint unsigned` | YES | MUL |  |  |
| `origem` | `enum('real','sintetico')` | NO | MUL |  | real |
| `ip_hash` | `char(64)` | YES |  |  |  |
| `created_at` | `timestamp` | NO | MUL | DEFAULT_GENERATED | CURRENT_TIMESTAMP |

**Chaves estrangeiras**

- `arquetipo_id` → `arquetipos.id` (`fk_resultados_arquetipo`)

### `retratos`

Retrato redigido por IA, uma linha por código, com frase, narrativa em JSON e modelo usado.

Registros na base de demonstração: **1**.

| Coluna | Tipo | Nulo | Chave | Extra | Padrão |
|---|---|---|---|---|---|
| `id` | `int unsigned` | NO | PRI | auto_increment |  |
| `codigo_unico` | `char(8)` | NO | UNI |  |  |
| `idioma` | `varchar(5)` | NO |  |  | pt-BR |
| `texto` | `mediumtext` | NO |  |  |  |
| `frase` | `varchar(280)` | YES |  |  |  |
| `narrativa_json` | `json` | YES |  |  |  |
| `modelo` | `varchar(80)` | NO |  |  |  |
| `prompt_versao` | `varchar(20)` | NO |  |  |  |
| `tokens` | `int unsigned` | YES |  |  |  |
| `created_at` | `timestamp` | NO |  | DEFAULT_GENERATED | CURRENT_TIMESTAMP |

**Chaves estrangeiras**

- `codigo_unico` → `resultados_usuarios.codigo_unico` (`fk_retratos_resultado`)

## Curadoria do matcher

### `entidades_match`

Espelhos de curadoria (personagens, diretores e escolas) com vetor legado de oito eixos e procedência.

Registros na base de demonstração: **52**.

| Coluna | Tipo | Nulo | Chave | Extra | Padrão |
|---|---|---|---|---|---|
| `id` | `int unsigned` | NO | PRI | auto_increment |  |
| `tipo` | `enum('personagem','diretor','estudio')` | NO | MUL |  |  |
| `nome` | `varchar(120)` | NO |  |  |  |
| `obra_origem` | `varchar(160)` | NO |  |  |  |
| `ano` | `smallint unsigned` | YES |  |  |  |
| `frase_key` | `varchar(80)` | NO |  |  |  |
| `frase` | `varchar(255)` | NO |  |  |  |
| `bio` | `text` | YES |  |  |  |
| `pais` | `varchar(80)` | YES |  |  |  |
| `nascimento` | `smallint unsigned` | YES |  |  |  |
| `ator_nome` | `varchar(120)` | YES |  |  |  |
| `ator_tmdb_id` | `int unsigned` | YES |  |  |  |
| `ator_foto_url` | `varchar(255)` | YES |  |  |  |
| `imagem_url` | `varchar(255)` | YES |  |  |  |
| `popularidade` | `decimal(8,2)` | YES |  |  |  |
| `enriquecido_em` | `datetime` | YES |  |  |  |
| `tmdb_id` | `int unsigned` | YES |  |  |  |
| `eixo_1` | `tinyint unsigned` | NO |  |  |  |
| `eixo_2` | `tinyint unsigned` | NO |  |  |  |
| `eixo_3` | `tinyint unsigned` | NO |  |  |  |
| `eixo_4` | `tinyint unsigned` | NO |  |  |  |
| `eixo_5` | `tinyint unsigned` | NO |  |  |  |
| `eixo_6` | `tinyint unsigned` | NO |  |  |  |
| `eixo_7` | `tinyint unsigned` | NO |  |  |  |
| `eixo_8` | `tinyint unsigned` | NO |  |  |  |
| `vetor_origem` | `enum('editorial','catalogo','misto')` | NO |  |  | editorial |

### `entidades_obras`

Obras essenciais de diretores e escolas, usadas na estante do perfil.

Registros na base de demonstração: **32**.

| Coluna | Tipo | Nulo | Chave | Extra | Padrão |
|---|---|---|---|---|---|
| `id` | `int unsigned` | NO | PRI | auto_increment |  |
| `entidade_id` | `int unsigned` | NO | MUL |  |  |
| `titulo` | `varchar(160)` | NO |  |  |  |
| `ano` | `smallint unsigned` | YES |  |  |  |
| `personagem` | `varchar(120)` | YES |  |  |  |
| `tmdb_id` | `int unsigned` | YES |  |  |  |
| `poster_url` | `varchar(255)` | YES |  |  |  |
| `ordem` | `tinyint unsigned` | NO |  |  | 0 |

**Chaves estrangeiras**

- `entidade_id` → `entidades_match.id` (`fk_obras_entidade`)

## Catálogo audiovisual

### `catalogo_obras`

Filmes e séries ingeridos da TMDB, com vetor legado de oito eixos, confiança e pôster.

Registros na base de demonstração: **3943**.

| Coluna | Tipo | Nulo | Chave | Extra | Padrão |
|---|---|---|---|---|---|
| `id` | `int unsigned` | NO | PRI | auto_increment |  |
| `tmdb_id` | `int unsigned` | NO |  |  |  |
| `tipo` | `enum('filme','serie')` | NO | MUL |  |  |
| `titulo` | `varchar(200)` | NO |  |  |  |
| `titulo_original` | `varchar(200)` | YES |  |  |  |
| `ano` | `smallint unsigned` | YES | MUL |  |  |
| `sinopse` | `varchar(600)` | YES |  |  |  |
| `duracao` | `smallint unsigned` | YES |  |  |  |
| `temporadas` | `tinyint unsigned` | YES |  |  |  |
| `poster_path` | `varchar(160)` | YES |  |  |  |
| `backdrop_path` | `varchar(160)` | YES |  |  |  |
| `nota` | `decimal(3,1)` | YES |  |  |  |
| `votos` | `int unsigned` | NO |  |  | 0 |
| `popularidade` | `decimal(10,2)` | NO | MUL |  | 0.00 |
| `idioma_original` | `char(5)` | YES | MUL |  |  |
| `eixo_1` | `tinyint unsigned` | NO |  |  | 50 |
| `eixo_2` | `tinyint unsigned` | NO |  |  | 50 |
| `eixo_3` | `tinyint unsigned` | NO |  |  | 50 |
| `eixo_4` | `tinyint unsigned` | NO |  |  | 50 |
| `eixo_5` | `tinyint unsigned` | NO |  |  | 50 |
| `eixo_6` | `tinyint unsigned` | NO |  |  | 50 |
| `eixo_7` | `tinyint unsigned` | NO |  |  | 50 |
| `eixo_8` | `tinyint unsigned` | NO |  |  | 50 |
| `confianca` | `tinyint unsigned` | NO |  |  | 0 |
| `ativo` | `tinyint(1)` | NO |  |  | 1 |
| `atualizado_em` | `datetime` | YES |  |  |  |
| `criado_em` | `timestamp` | NO |  | DEFAULT_GENERATED | CURRENT_TIMESTAMP |

### `catalogo_generos`

Gêneros normalizados da TMDB.

Registros na base de demonstração: **27**.

| Coluna | Tipo | Nulo | Chave | Extra | Padrão |
|---|---|---|---|---|---|
| `id` | `smallint unsigned` | NO | PRI |  |  |
| `slug` | `varchar(40)` | NO | UNI |  |  |
| `nome` | `varchar(60)` | NO |  |  |  |

### `catalogo_obra_generos`

Relação N:N entre obras e gêneros.

Registros na base de demonstração: **9949**.

| Coluna | Tipo | Nulo | Chave | Extra | Padrão |
|---|---|---|---|---|---|
| `obra_id` | `int unsigned` | NO | PRI |  |  |
| `genero_id` | `smallint unsigned` | NO | PRI |  |  |

**Chaves estrangeiras**

- `genero_id` → `catalogo_generos.id` (`fk_catalogo_og_genero`)
- `obra_id` → `catalogo_obras.id` (`fk_catalogo_og_obra`)

### `catalogo_paises`

Países de origem (ISO 3166-1 alfa-2).

Registros na base de demonstração: **81**.

| Coluna | Tipo | Nulo | Chave | Extra | Padrão |
|---|---|---|---|---|---|
| `codigo` | `char(2)` | NO | PRI |  |  |
| `nome` | `varchar(80)` | NO |  |  |  |

### `catalogo_obra_paises`

Relação N:N entre obras e países de origem.

Registros na base de demonstração: **5371**.

| Coluna | Tipo | Nulo | Chave | Extra | Padrão |
|---|---|---|---|---|---|
| `obra_id` | `int unsigned` | NO | PRI |  |  |
| `pais_codigo` | `char(2)` | NO | PRI |  |  |

**Chaves estrangeiras**

- `obra_id` → `catalogo_obras.id` (`fk_catalogo_op_obra`)
- `pais_codigo` → `catalogo_paises.codigo` (`fk_catalogo_op_pais`)

### `catalogo_pessoas`

Pessoas do catálogo (elenco e direção), com identificador da TMDB.

Registros na base de demonstração: **9189**.

| Coluna | Tipo | Nulo | Chave | Extra | Padrão |
|---|---|---|---|---|---|
| `id` | `int unsigned` | NO | PRI | auto_increment |  |
| `tmdb_id` | `int unsigned` | NO | UNI |  |  |
| `nome` | `varchar(120)` | NO | MUL |  |  |
| `foto_path` | `varchar(160)` | YES |  |  |  |
| `popularidade` | `decimal(10,2)` | YES |  |  |  |
| `atualizado_em` | `datetime` | YES |  |  |  |

### `catalogo_creditos`

Créditos de elenco e direção por obra, com função e ordem no crédito.

Registros na base de demonstração: **12039**.

| Coluna | Tipo | Nulo | Chave | Extra | Padrão |
|---|---|---|---|---|---|
| `obra_id` | `int unsigned` | NO | PRI |  |  |
| `pessoa_id` | `int unsigned` | NO | PRI |  |  |
| `funcao` | `enum('elenco','direcao')` | NO | PRI |  | elenco |
| `personagem` | `varchar(160)` | YES |  |  |  |
| `ordem` | `tinyint unsigned` | NO |  |  | 0 |

**Chaves estrangeiras**

- `obra_id` → `catalogo_obras.id` (`fk_catalogo_creditos_obra`)
- `pessoa_id` → `catalogo_pessoas.id` (`fk_catalogo_creditos_pessoa`)

## Streaming

### `catalogo_plataformas`

Plataformas de streaming, com preço editorial, ordem e chave de ativação no ranking.

Registros na base de demonstração: **10**.

| Coluna | Tipo | Nulo | Chave | Extra | Padrão |
|---|---|---|---|---|---|
| `id` | `smallint unsigned` | NO | PRI | auto_increment |  |
| `tmdb_provider_id` | `int unsigned` | NO | UNI |  |  |
| `slug` | `varchar(40)` | NO | UNI |  |  |
| `nome` | `varchar(80)` | NO |  |  |  |
| `logo_path` | `varchar(160)` | YES |  |  |  |
| `preco_mensal` | `decimal(6,2)` | YES |  |  |  |
| `preco_atualizado_em` | `date` | YES |  |  |  |
| `url` | `varchar(255)` | YES |  |  |  |
| `prioridade` | `tinyint unsigned` | NO |  |  | 0 |
| `ativa` | `tinyint(1)` | NO |  |  | 1 |
| `atualizado_em` | `datetime` | YES |  |  |  |

### `catalogo_obra_plataformas`

Disponibilidade por obra, plataforma, região e tipo de oferta.

Registros na base de demonstração: **2851**.

| Coluna | Tipo | Nulo | Chave | Extra | Padrão |
|---|---|---|---|---|---|
| `obra_id` | `int unsigned` | NO | PRI |  |  |
| `plataforma_id` | `smallint unsigned` | NO | PRI |  |  |
| `oferta` | `enum('flatrate','free','ads')` | NO | PRI |  | flatrate |
| `regiao` | `char(2)` | NO | PRI |  | BR |
| `atualizado_em` | `datetime` | YES |  |  |  |

**Chaves estrangeiras**

- `obra_id` → `catalogo_obras.id` (`fk_catalogo_opl_obra`)
- `plataforma_id` → `catalogo_plataformas.id` (`fk_catalogo_opl_plataforma`)

## Conta e lista pessoal

### `usuarios`

Contas públicas opcionais, necessárias apenas para a lista pessoal.

Registros na base de demonstração: **2**.

| Coluna | Tipo | Nulo | Chave | Extra | Padrão |
|---|---|---|---|---|---|
| `id` | `int unsigned` | NO | PRI | auto_increment |  |
| `nome` | `varchar(80)` | NO |  |  |  |
| `usuario` | `varchar(40)` | NO | UNI |  |  |
| `senha` | `varchar(255)` | NO |  |  |  |
| `criado_em` | `timestamp` | NO |  | DEFAULT_GENERATED | CURRENT_TIMESTAMP |
| `ultimo_login` | `datetime` | YES |  |  |  |

### `bookmarks`

Lista pessoal por usuário e obra, com status 'quero' ou 'visto'.

Registros na base de demonstração: **2**.

| Coluna | Tipo | Nulo | Chave | Extra | Padrão |
|---|---|---|---|---|---|
| `id` | `bigint unsigned` | NO | PRI | auto_increment |  |
| `usuario_id` | `int unsigned` | NO | MUL |  |  |
| `tmdb_id` | `int unsigned` | NO |  |  |  |
| `tipo` | `enum('filme','serie')` | NO |  |  | filme |
| `status` | `enum('quero','visto')` | NO |  |  |  |
| `titulo` | `varchar(200)` | NO |  |  |  |
| `ano` | `smallint unsigned` | YES |  |  |  |
| `poster_path` | `varchar(160)` | YES |  |  |  |
| `created_at` | `timestamp` | NO |  | DEFAULT_GENERATED | CURRENT_TIMESTAMP |
| `updated_at` | `timestamp` | NO |  | DEFAULT_GENERATED on update CURRENT_TIMESTAMP | CURRENT_TIMESTAMP |

**Chaves estrangeiras**

- `usuario_id` → `usuarios.id` (`fk_bookmarks_usuario`)

## Acesso e cache

### `usuarios_admin`

Contas do painel editorial (admin ou editor).

Registros na base de demonstração: **0**.

| Coluna | Tipo | Nulo | Chave | Extra | Padrão |
|---|---|---|---|---|---|
| `id` | `int unsigned` | NO | PRI | auto_increment |  |
| `nome` | `varchar(100)` | NO |  |  |  |
| `usuario` | `varchar(50)` | NO | UNI |  |  |
| `senha` | `varchar(255)` | NO |  |  |  |
| `perfil` | `enum('admin','editor')` | NO |  |  | editor |
| `criado_em` | `timestamp` | NO |  | DEFAULT_GENERATED | CURRENT_TIMESTAMP |

### `cache_tmdb`

Cache de respostas da TMDB por chave sha256, com TTL de 24 horas.

Registros na base de demonstração: **4**.

| Coluna | Tipo | Nulo | Chave | Extra | Padrão |
|---|---|---|---|---|---|
| `cache_key` | `char(64)` | NO | PRI |  |  |
| `payload_json` | `longtext` | NO |  |  |  |
| `expires_at` | `datetime` | NO | MUL |  |  |
| `created_at` | `timestamp` | NO |  | DEFAULT_GENERATED | CURRENT_TIMESTAMP |
