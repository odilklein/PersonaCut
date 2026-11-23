#!/usr/bin/env python3
"""Gera o dicionário de dados do PersonaCut a partir do MySQL em execução.

Uso:
    python3 docs/build/gerar_dicionario.py

Requisito: container `personacut-db` em execução (docker compose up -d).
Saída: docs/engenharia/04-modelo-dados.md
"""

from __future__ import annotations

import subprocess
from collections import OrderedDict
from pathlib import Path

RAIZ = Path(__file__).resolve().parents[2]
SAIDA = RAIZ / "docs" / "engenharia" / "04-modelo-dados.md"
DB = "personacut"
CONTAINER = "personacut-db"

GRUPOS = [
    ("Perfil e questionário", [
        "eixos", "perguntas", "arquetipos", "resultados_usuarios", "retratos",
    ]),
    ("Curadoria do matcher", [
        "entidades_match", "entidades_obras",
    ]),
    ("Catálogo audiovisual", [
        "catalogo_obras", "catalogo_generos", "catalogo_obra_generos",
        "catalogo_paises", "catalogo_obra_paises", "catalogo_pessoas",
        "catalogo_creditos",
    ]),
    ("Streaming", [
        "catalogo_plataformas", "catalogo_obra_plataformas",
    ]),
    ("Conta e lista pessoal", [
        "usuarios", "bookmarks",
    ]),
    ("Acesso e cache", [
        "usuarios_admin", "cache_tmdb",
    ]),
]

DESCRICOES = {
    "eixos": "Os seis eixos bipolares do perfil, com nomes dos polos, cores e chaves de tradução.",
    "perguntas": "Afirmações Likert, com direção, peso, modo rápido, filme da TMDB e cena ilustrativa.",
    "arquetipos": "Doze arquétipos (um por eixo dominante e polo), com vetor de referência e texto editorial.",
    "resultados_usuarios": "Perfil calculado: código público, respostas originais, vetor de seis eixos e origem (real ou sintética).",
    "retratos": "Retrato redigido por IA, uma linha por código, com frase, narrativa em JSON e modelo usado.",
    "entidades_match": "Espelhos de curadoria (personagens, diretores e escolas) com vetor legado de oito eixos e procedência.",
    "entidades_obras": "Obras essenciais de diretores e escolas, usadas na estante do perfil.",
    "catalogo_obras": "Filmes e séries ingeridos da TMDB, com vetor legado de oito eixos, confiança e pôster.",
    "catalogo_generos": "Gêneros normalizados da TMDB.",
    "catalogo_obra_generos": "Relação N:N entre obras e gêneros.",
    "catalogo_paises": "Países de origem (ISO 3166-1 alfa-2).",
    "catalogo_obra_paises": "Relação N:N entre obras e países de origem.",
    "catalogo_pessoas": "Pessoas do catálogo (elenco e direção), com identificador da TMDB.",
    "catalogo_creditos": "Créditos de elenco e direção por obra, com função e ordem no crédito.",
    "catalogo_plataformas": "Plataformas de streaming, com preço editorial, ordem e chave de ativação no ranking.",
    "catalogo_obra_plataformas": "Disponibilidade por obra, plataforma, região e tipo de oferta.",
    "usuarios": "Contas públicas opcionais, necessárias apenas para a lista pessoal.",
    "bookmarks": "Lista pessoal por usuário e obra, com status 'quero' ou 'visto'.",
    "usuarios_admin": "Contas do painel editorial (admin ou editor).",
    "cache_tmdb": "Cache de respostas da TMDB por chave sha256, com TTL de 24 horas.",
}


def mysql(sql: str) -> list[list[str]]:
    cmd = [
        "docker", "exec", CONTAINER, "sh", "-c",
        'mysql -upersonacut -ppersonacut123 -N -B -e ' + shquote(sql),
    ]
    out = subprocess.run(cmd, capture_output=True, text=True, check=True).stdout
    return [line.split("\t") for line in out.splitlines() if line]


def shquote(s: str) -> str:
    return "'" + s.replace("'", "'\\''") + "'"


def main() -> None:
    colunas = OrderedDict()
    for row in mysql(
        "SELECT table_name, column_name, column_type, is_nullable, column_key, "
        "extra, IFNULL(column_default,'') FROM information_schema.columns "
        f"WHERE table_schema='{DB}' ORDER BY table_name, ordinal_position"
    ):
        colunas.setdefault(row[0], []).append(row)

    fks = OrderedDict()
    for row in mysql(
        "SELECT table_name, column_name, referenced_table_name, "
        "referenced_column_name, constraint_name FROM information_schema.key_column_usage "
        f"WHERE table_schema='{DB}' AND referenced_table_name IS NOT NULL "
        "ORDER BY table_name, ordinal_position"
    ):
        fks.setdefault(row[0], []).append(row)

    contagens = {}
    for row in mysql(
        "SELECT table_name, table_rows FROM information_schema.tables "
        f"WHERE table_schema='{DB}'"
    ):
        contagens[row[0]] = row[1]

    linhas: list[str] = []
    add = linhas.append

    add("# Modelo de dados")
    add("")
    add("Dicionário gerado automaticamente do schema do banco `personacut` (MySQL 8.4).")
    add("Para regenerar: `python3 docs/build/gerar_dicionario.py`.")
    add("")
    add("## Visão geral")
    add("")
    add("| Grupo | Tabelas |")
    add("|---|---|")
    for nome, tabelas in GRUPOS:
        add(f"| {nome} | {len(tabelas)} |")
    add(f"| **Total** | **{sum(len(t) for _, t in GRUPOS)}** |")
    add("")
    add("O modelo separa três domínios que não se misturam: o **perfil** (eixos,")
    add("perguntas, resultados e retratos), a **curadoria do matcher** (52 espelhos com")
    add("vetor) e o **catálogo audiovisual** (obras, pessoas, gêneros, países e")
    add("plataformas). A separação é intencional: o catálogo muda toda semana, o")
    add("matcher é editorial e o perfil precisa continuar reproduzível.")
    add("")

    for nome, tabelas in GRUPOS:
        add(f"## {nome}")
        add("")
        for tabela in tabelas:
            if tabela not in colunas:
                add(f"> Tabela `{tabela}` não encontrada no banco.")
                add("")
                continue
            add(f"### `{tabela}`")
            add("")
            desc = DESCRICOES.get(tabela, "")
            if desc:
                add(desc)
                add("")
            qtd = contagens.get(tabela)
            if qtd not in (None, ""):
                add(f"Registros na base de demonstração: **{qtd}**.")
                add("")
            add("| Coluna | Tipo | Nulo | Chave | Extra | Padrão |")
            add("|---|---|---|---|---|---|")
            for c in colunas[tabela]:
                _, col, tipo, nulo, chave, extra, padrao = c
                padrao = padrao.replace("|", "\\|")
                add(f"| `{col}` | `{tipo}` | {nulo} | {chave or ''} | {extra or ''} | {padrao} |")
            add("")
            if tabela in fks:
                add("**Chaves estrangeiras**")
                add("")
                for _, col, ref_t, ref_c, nome_fk in fks[tabela]:
                    add(f"- `{col}` → `{ref_t}.{ref_c}` (`{nome_fk}`)")
                add("")

    SAIDA.parent.mkdir(parents=True, exist_ok=True)
    SAIDA.write_text("\n".join(linhas), encoding="utf-8")
    print(f"gerado: {SAIDA} ({len(linhas)} linhas)")


if __name__ == "__main__":
    main()
