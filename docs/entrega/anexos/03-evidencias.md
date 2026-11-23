# Anexo III – Evidências

**Repositório**

- Código-fonte: https://github.com/odilklein/PersonaCut
- Histórico de commits: https://github.com/odilklein/PersonaCut/commits
- 21 commits entre 16 e 20 de novembro de 2026, com dois pull requests revisados.
- Documentação técnica de apoio: `docs/EIXOS.md`, `docs/ARQUITETURA.md`,
  `docs/RECOMENDACAO.md`, `docs/CALIBRACAO.md` e `docs/CRONOGRAMA.md`.

**Relatório de segurança**

- Relatório completo: `docs/security/relatorio-2026-11-18.md`
- Receita do ambiente de teste: `docs/security/hexstrike.Dockerfile`
- Data da auditoria: 18/11/2026, na instância local.

**Ambiente de demonstração**

- Aplicação: `http://localhost:8090`
- Painel editorial: `http://localhost:8090/admin`
- phpMyAdmin: `http://localhost:8091`
- MySQL: `localhost:3308`, base `personacut`
- Usuário do painel: `admin`; a senha inicial é definida no seed de
  demonstração (`sql/002_seed.sql`) e deve ser trocada em qualquer uso real.

**Evidências visuais**

As capturas de tela do sistema estão reunidas neste documento (Figuras 17 a 30)
e nos arquivos de `docs/imagens/prints/`. Os 15 diagramas têm fonte PlantUML
versionada em `docs/diagramas/fontes/` e PNG a 300 dpi em `docs/diagramas/png/`.

**Evidências de banco de dados**

O dicionário foi gerado do `information_schema` em 20/11/2026 e está em
`docs/engenharia/04-modelo-dados.md`. A base de demonstração tem 20 tabelas.

- 6 eixos, 48 perguntas e 12 arquétipos;
- 52 entidades do matcher (36 personagens, 10 diretores e 6 escolas) e 32 obras
  essenciais;
- 3.943 obras, 9.189 pessoas, 12.039 créditos e 10 plataformas;
- 603 perfis sintéticos declarados e 1 perfil real de demonstração.

**Evidências de código e testes**

- 101 testes de serviço, com 0 falhas, executados em 20/11/2026.
- Comando: `docker compose exec -T app php tests/run.php`.
- Saída: `101 testes · 101 ok · 0 falha(s)`.
- 15 diagramas e 30 figuras no documento.
- Build do documento em duas passagens, com sumário, DOCX e PDF conferidos
  página a página.

**Limpeza e privacidade**

O resultado criado durante o teste de intrusão (`2GC2YXQP`) foi removido do
banco ao final da auditoria. A base de raridade é declaradamente sintética. O IP
do visitante não é gravado em claro: o banco guarda `sha256(ip + salt)`. As
chaves da TMDB e da Groq ficam fora do Git, em `config/env.php` ou no `.env`
local, e o pacote de deploy traz apenas o modelo de configuração.
