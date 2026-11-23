# Testes e Qualidade

## 4.1 Estratégia de testes

A estratégia separa o que pode ser provado por código do que depende de
julgamento humano.

**Testes automatizados.** Os serviços puros são testados sem banco, com um
micro-framework próprio (`tests/_assert.php`) e um executor (`tests/run.php`).
A suíte cobre a matemática do Likert, o matcher, a calibração, os modos do
questionário, o radar, a mídia, a escolha de cenas, o guia dos eixos, as
leituras, o catálogo, a recomendação, o ranking de plataformas, as trilhas, o
elenco e o rate limit. O comando é:

```bash
docker compose exec app php tests/run.php
```

**Testes de integração manuais.** Os fluxos de quiz, resultado, conta, admin e
Sinergia foram executados no navegador com dados reais do banco. As capturas da
seção 3.8 são evidência desses testes.

**Auditoria de segurança.** A instância local foi varrida com HexStrike AI,
nmap, nikto, nuclei, sqlmap, ffuf e wafw00f. O relatório está no Anexo IV.

**Revisão visual.** Cada página do DOCX/PDF foi rasterizada e conferida
página a página depois do build, para checar figuras, tabelas e sumário.

## 4.2 Resultados dos testes

A execução de 20/11/2026 terminou com 101 testes e nenhuma falha.

| Suíte | Testes | O que cobre |
|---|---|---|
| Likert | 12 | fórmula, neutro, pesos, direção, limites e eixos incompletos |
| Matcher | 15 | compatibilidade, ranking, arquétipo, raridade e sinergia |
| Recomendação | 17 | vetor de obra, confiança, afinidade, motivos, trilhas e alinhamento |
| Calibração | 9 | posição no grupo, margem, z-score e bootstrap |
| Catálogo | 9 | normalização de obra, gêneros, países e plataformas |
| Cenas | 9 | candidatos, pontuação, descarte de trailer e escolha final |
| Mídia | 7 | imagem, GIF, vídeo e pré-carga |
| Modos | 7 | cotas por eixo, equilíbrio de polos e subconjunto entre modos |
| Leituras | 6 | seleção de eixos extremos e exemplos de filmes |
| Guia dos eixos | 4 | conteúdo editorial e polos |
| Radar | 3 | geometria, anéis e polígono |
| Rate limit | 3 | limite, janela e persistência em arquivo |
| **Total** | **101** | **0 falhas** |

Saída resumida da execução:

```text
PersonaCut · testes de serviço
──────────────────────────────────────────────────────
  ✓ ... (101 linhas)
──────────────────────────────────────────────────────
  101 testes · 101 ok · 0 falha(s)
  Tudo certo.
```

A auditoria de segurança encontrou cinco achados e nenhum crítico. Quatro foram
corrigidos e um ficou documentado como manutenção. Nuclei retornou zero
resultados para CVE, exposição, configuração insegura, login padrão e SSL;
sqlmap não conseguiu injetar no parâmetro `codigo`; o proxy de imagem recusou
SSRF para `localhost`, `169.254.169.254` e `file://`.

## 4.3 Garantia da qualidade

O projeto não tem integração contínua nem ferramenta de cobertura. A garantia
vem de quatro práticas:

1. **Fronteira de camadas.** Serviços puros não importam PDO nem sessão. Se um
   teste precisa de banco para passar, a regra está no lugar errado.
2. **Teste antes do ajuste.** Toda mudança de fórmula ou de peso entra com um
   caso que falha antes e passa depois.
3. **Consulta preparada e escape.** O padrão é obrigatório em todo o código; a
   auditoria confirma que não há caminho alternativo.
4. **Revisão visual do documento.** O build gera DOCX, PDF e as páginas
   rasterizadas; o sumário é recalculado em duas passagens para conferir a
   paginação.

As limitações são reconhecidas: não há teste de interface automatizado, não há
teste de carga e a calibração dos eixos ainda é editorial, não empírica. O
caminho para a calibração com dados reais está descrito em
`docs/CALIBRACAO.md`.

## 4.4 Requisitos mínimos de hardware e software

**Cliente (navegador):**

- Chrome, Firefox, Safari ou Edge em versão atual;
- tela de 390 px ou mais;
- JavaScript habilitado para o quiz, o card e o tema;
- Web Share API é opcional; sem ela, o card é baixado como PNG.

**Servidor local:**

- Docker 24 ou superior com Docker Compose;
- 2 GB de RAM e 2 GB de disco livres (MySQL e catálogo);
- portas 8090, 8091 e 3308 livres.

**Servidor de aplicação:**

- PHP 8.2 com `pdo_mysql`, `curl`, `mbstring`, `json` e `fileinfo`;
- MySQL 8.0 ou superior, ou MariaDB compatível com `utf8mb4`;
- Apache com `mod_rewrite` e `mod_headers`, ou hospedagem equivalente;
- HTTPS recomendado para o cookie `Secure` e para a Web Share API.

**Hospedagem compartilhada:**

- PHP 8.x e MariaDB;
- limite de 10 MB por arquivo de importação (o pacote já separa schema e
  snapshot);
- permissão de escrita em `storage/cache` para o rate limit e o cache de
  imagens. Sem permissão, o rate limit libera a requisição.

