# Documentação do Sistema

## 3.1 Metodologia de desenvolvimento

O desenvolvimento foi incremental e guiado por execução. Cada camada nasceu com
um teste ou com uma consulta real ao banco, e o que não podia ser explicado por
uma regra do sistema era removido. O repositório registra 21 commits entre 16 e
20 de novembro de 2026. O núcleo (banco, cálculo, quiz, resultado e painel)
ficou pronto em 17; o catálogo, o ranking de plataformas e a auditoria de
segurança entraram em 18; o acabamento visual, a prévia de compartilhamento e os
ajustes de conta fecharam em 19 e 20.

O projeto usa três disciplinas como apoio: Banco de Dados (modelagem e
integridade), Desenvolvimento Web II (aplicação e interface) e Engenharia de
Software II (requisitos, testes e documentação). A organização em camadas,
descrita em 3.4, existe para que o cálculo possa ser testado sem banco e para
que a troca de uma fonte externa não contamine a regra de negócio.

**Uso de assistentes de IA no desenvolvimento.** Grande parte do código do
núcleo, dos serviços e dos controladores foi gerada com o assistente de IA
DeepSeek 4.1 Flash. A geração foi feita com instruções, e cada resultado passou
por revisão humana: pull request com revisão do outro integrante, testes de
serviço, conferência da fórmula e da curadoria antes de entrar no repositório.
O modelo atuou como instrumento de geração; as decisões de produto, a modelagem
e a validação dos cálculos são da equipe.

## 3.2 Diagramas UML

### 3.2.1 Diagramas de casos de uso

Os casos de uso foram separados em três atores: o visitante anônimo, o usuário
com conta e o administrador do painel. A separação evita um diagrama único com
vinte casos e deixa claro o que cada perfil pode fazer sem autenticação.

![Figura 2 — Casos de uso do visitante.](diagramas/png/01_casos_uso_visitante.png)

O visitante é o ator principal do produto. Ele responde ao quiz, recebe o
código e consulta o resultado. As leituras, o ranking de plataformas e o card
são extensões do resultado: dependem do vetor já calculado e não criam estado
novo no banco.

![Figura 3 — Casos de uso do usuário com conta.](diagramas/png/02_casos_uso_usuario.png)

A conta é opcional e serve apenas para a lista pessoal. O resultado continua
acessível por código, sem login. Bookmark e resultado são independentes: apagar
a conta não apaga o perfil público.

![Figura 4 — Casos de uso do administrador.](diagramas/png/03_casos_uso_admin.png)

O painel concentra a curadoria. O administrador mantém os seis eixos, as
perguntas e suas cenas, as entidades do matcher, os arquétipos e as plataformas.
A sugestão automática de clipes da TMDB é uma extensão do cadastro de perguntas:
ela propõe candidatos, mas a gravação continua sendo uma ação humana.

### 3.2.2 Diagrama de classes

O domínio foi dividido em dois diagramas para caber na página sem perder
legibilidade. O primeiro cobre o perfil e a conta; o segundo cobre o catálogo e
os serviços que operam sobre ele.

![Figura 5 — Classes do perfil, da curadoria e da conta.](diagramas/png/04_classes_dominio.png)

As classes `Eixo` e `Pergunta` formam o questionário. `Resultado` guarda o vetor
calculado e a versão do questionário; `Arquetipo` é a referência de comparação;
`Retrato` é opcional e derivado. `EntidadeMatch` e `EntidadeObra` pertencem ao
matcher editorial, não ao catálogo ingerido. `Usuario` e `Bookmark` ficam
isolados do perfil público.

![Figura 6 — Classes do catálogo audiovisual e serviços puros.](diagramas/png/05_classes_catalogo_servicos.png)

O catálogo tem obras, pessoas, créditos, gêneros, países e plataformas. Os
serviços puros (`RecomendacaoService`, `PlataformaRankingService`,
`TrilhaService`, `ElencoService` e `CalibracaoService`) recebem arrays e
devolvem arrays; não conhecem PDO, sessão nem tradução. Essa fronteira é o que
permite testar recomendação sem subir banco.

### 3.2.3 Diagramas de sequência

Os quatro fluxos abaixo cobrem as operações que criam ou transformam estado.

![Figura 7 — Envio do quiz e geração do código público.](diagramas/png/06_sequencia_quiz.png)

O navegador não envia uma resposta por requisição: o quiz monta o pacote e faz
um único `POST` ao final. O controlador valida o token CSRF, o limite por IP e a
cobertura das perguntas antes de calcular. O código é gerado no servidor e
gravado junto com o vetor; a resposta devolve apenas o código e o redirecionamento.

![Figura 8 — Montagem do resultado a partir do código.](diagramas/png/07_sequencia_resultado.png)

O resultado é reconstruído a cada consulta a partir do vetor salvo. Não há
estado em memória entre requisições. O controlador carrega eixos, arquétipos,
entidades e catálogo, e delega o cálculo de afinidade, calibração, recomendação
e ranking aos serviços.

![Figura 9 — Geração do retrato por IA.](diagramas/png/08_sequencia_retrato.png)

O retrato é a única parte que pode falhar sem derrubar o produto. A primeira
consulta mostra o texto determinístico; a geração por IA acontece sob demanda,
uma vez por código, e qualquer erro mantém o texto anterior. O retrato nunca
altera o vetor, o arquétipo ou a ordem das recomendações.

![Figura 10 — Comparação entre dois perfis no modo Sinergia.](diagramas/png/09_sequencia_sinergia.png)

A Sinergia usa a mesma função de compatibilidade do matcher, aplicada a dois
vetores de perfil. O resultado traz o percentual, um veredito qualitativo, os
eixos de maior divergência e um filme que fica entre os dois gostos.

## 3.3 Modelagem de banco de dados

### 3.3.1 Modelo conceitual

O modelo conceitual separa o que é do visitante, o que é curadoria e o que é
catálogo. Essa separação é a decisão de modelagem mais importante do projeto.

![Figura 11 — Modelo conceitual do PersonaCut.](diagramas/png/10_er_conceitual.png)

O eixo classifica perguntas e define o polo dominante de um arquétipo. O
resultado é composto pelas respostas, nomeado por um arquétipo e pode gerar um
retrato. A entidade do matcher possui obras essenciais. A conta mantém itens de
lista que apontam para obras do catálogo. O catálogo se relaciona com pessoas e
plataformas.

### 3.3.2 Modelo lógico

O modelo lógico foi dividido em dois diagramas: o núcleo do perfil e o catálogo.

![Figura 12 — Modelo lógico: perfil, curadoria e acesso.](diagramas/png/11_er_logico_perfil.png)

O núcleo tem nove tabelas. `eixos` e `perguntas` guardam o questionário;
`resultados_usuarios` guarda o vetor e as respostas originais; `arquetipos`
guarda a referência de comparação; `retratos` guarda o texto opcional;
`entidades_match` e `entidades_obras` formam o matcher; `usuarios` e
`bookmarks` formam a lista pessoal.

A opção por seis colunas fixas (`eixo_1..6`) em vez de uma tabela de junção é
deliberada: o domínio tem exatamente seis eixos, o cálculo fica legível e cada
perfil é lido em uma linha. O catálogo e as entidades antigas continuam com oito
coordenadas por compatibilidade e são projetados para seis na leitura, por
`Core\Vetor::projetar6`.

![Figura 13 — Modelo lógico: catálogo audiovisual e streaming.](diagramas/png/12_er_logico_catalogo.png)

O catálogo tem nove tabelas. Obras, gêneros, países e pessoas são normalizados;
`catalogo_obra_generos`, `catalogo_obra_paises`, `catalogo_creditos` e
`catalogo_obra_plataformas` resolvem as relações N:N. A disponibilidade guarda a
região e o tipo de oferta (`flatrate`, `free`, `ads`). O preço não vem da TMDB:
é campo editorial, com data de conferência.

### 3.3.3 Dicionário de dados

{{DICIONARIO}}

## 3.4 Arquitetura da solução

A aplicação segue uma regra de dependência única: apresentação conhece
controlador, controlador conhece serviço, serviço conhece modelo e modelo
conhece o core. Nenhuma camada de baixo importa uma de cima.

![Figura 14 — Arquitetura em camadas do PersonaCut.](diagramas/png/13_arquitetura.png)

`public/` é o DocumentRoot e concentra páginas, endpoints JSON e painel. Os
controladores (`QuizController` e `PerfilController`) orquestram o fluxo. Os
serviços concentram regra pura: Likert, matcher, recomendação, ranking,
calibração, mídia, cenas, trilhas e elenco. Os modelos encapsulam PDO com
prepared statements. O core reúne banco, sessão, CSRF, rate limit, vetor e o
radar SVG.

O ponto de entrada é `public/index.php`; o bootstrap registra autoload,
configuração, sessão e tratamento de erro. Não há framework MVC, ORM nem
container de dependência. A escolha reduz o custo de leitura do código e evita
que a instalação dependa de Composer no servidor.

A figura 15 mostra o caminho de uma recomendação. O vetor de seis eixos é o
único estado que atravessa o pipeline; obras e plataformas entram como dados,
não como decisão.

![Figura 15 — Do questionário à recomendação.](diagramas/png/14_fluxo_recomendacao.png)

A ingestão do catálogo acontece fora do request. `bin/catalogo_sync.php`
percorre as trilhas editoriais, consulta a TMDB, grava obras, gêneros, países,
pessoas, créditos e disponibilidade. O comando é retomável, idempotente e
revalida o que passou de noventa dias. `bin/catalogo_export.php` gera o snapshot
SQL usado no deploy. O perfil publicado não faz chamadas de rede para montar a
lista.

## 3.5 Segurança e integrações

### 3.5.1 Segurança

A aplicação trata segurança em três frentes: entrada, sessão e cabeçalhos.

- **SQL.** Todas as consultas usam PDO com `ATTR_EMULATE_PREPARES = false`.
  Não há concatenação de valor vindo do usuário.
- **Saída.** Todo texto é escapado com `htmlspecialchars`, pelo helper `e()`.
- **CSRF.** Todo `POST` exige token de sessão, no campo `csrf` ou no cabeçalho
  `X-CSRF-Token`. O quiz e o login usam o mesmo mecanismo.
- **Sessão.** Cookie `HttpOnly`, `SameSite=Lax` e `Secure` sob HTTPS. A sessão é
  regenerada no login e destruída no logout.
- **Senhas.** `password_hash` com `PASSWORD_DEFAULT` e `password_verify`.
- **Rate limit.** `Core\RateLimit` grava contadores em arquivo, com janela
  deslizante: 12 envios por minuto no quiz e 8 tentativas de login a cada 10
  minutos por IP. Se o diretório não for gravável, o limitador libera a
  requisição em vez de derrubar o site.
- **Privacidade.** O IP nunca é gravado em claro; o banco guarda
  `sha256(ip + salt)`. O resultado não exige cadastro.
- **Cabeçalhos.** `public/.htaccess` envia CSP com `script-src 'self'`,
  `X-Content-Type-Options`, `Referrer-Policy`, `X-Frame-Options` e
  `Permissions-Policy`, oculta `X-Powered-By` e aceita apenas `GET`, `POST` e
  `HEAD`.

A auditoria de intrusão foi feita em 18/11/2026 na instância local, com
HexStrike AI orquestrando nmap, nikto, nuclei, sqlmap, ffuf e wafw00f. Foram
cinco achados, nenhum crítico: falta de rate limit (média), cabeçalhos ausentes,
fingerprint exposto, métodos HTTP abertos e PHP desatualizado (baixos). Os
quatro primeiros foram corrigidos; o último ficou documentado. O relatório
completo está no Anexo IV. Nuclei não encontrou CVE, exposição ou configuração
insegura, e o sqlmap não conseguiu injetar no parâmetro `codigo`.

### 3.5.2 Integrações

| Integração | Uso | Dependência |
|---|---|---|
| TMDB | catálogo, pôsteres, elenco, cenas e disponibilidade | opcional em tempo de execução; catálogo local cobre o perfil |
| JustWatch (via TMDB) | disponibilidade de streaming por região | ingerida offline para o MySQL |
| Groq | retrato e frase do perfil | opcional; fallback determinístico |
| HexStrike AI | auditoria de segurança local | apenas no ambiente de teste |
| HTML2Canvas | captura do card 9:16 | vendorizado no navegador |
| Swiper | carrossel de perfis na landing | vendorizado no navegador |

As chaves ficam no servidor. O navegador conversa apenas com `/api/*`. O proxy
`/api/imagem.php` existe porque `image.tmdb.org` não envia CORS e o canvas do
HTML2Canvas seria contaminado; ele usa uma allowlist de hosts e cache em disco.

### 3.5.3 Implantação

O ambiente local sobe com Docker Compose: Apache e PHP 8.2, MySQL 8.4 e
phpMyAdmin. A aplicação fica em `http://localhost:8090`, o phpMyAdmin em
`http://localhost:8091` e o MySQL em `localhost:3308`.

![Figura 16 — Implantação local e alternativa em hospedagem compartilhada.](diagramas/png/15_implantacao.png)

Para hospedagem compartilhada, o pacote `deploy/infinityfree` traz o schema em
`utf8mb4_unicode_ci`, o snapshot do catálogo, o `.htaccess` que roteia a raiz
para `public/` e o modelo de `config/env.php`. O `Core\Env` lê um mapa estático,
porque `putenv()` é desabilitado em parte das hospedagens.

## 3.6 Recursos e ferramentas

| Camada | Tecnologia |
|---|---|
| Linguagem | PHP 8.2 puro, orientação a objetos e PSR-12 |
| Banco | MySQL 8.4, `utf8mb4_unicode_ci` |
| Front-end | HTML, CSS próprio e JavaScript puro com Fetch |
| Gráficos | radar bipolar em SVG gerado no servidor |
| Card | HTML2Canvas vendorizado e Web Share API |
| Carrossel | Swiper vendorizado |
| IA | Groq, modelo configurável |
| Catálogo | TMDB e JustWatch (via TMDB) |
| Ambiente | Docker, Docker Compose, Apache e phpMyAdmin |
| Testes | micro-framework próprio, sem PHPUnit |
| Diagramas | PlantUML 1.2025, renderizado em PNG a 300 dpi |
| Documento | python-docx, LibreOffice e PyMuPDF |

O projeto não usa Composer em produção, Node no front nem framework de SPA. As
duas dependências de navegador são vendorizadas, e o restante é escrito à mão.
Essa escolha reduz o número de camadas e facilita a publicação em hospedagem
compartilhada.

## 3.7 Funcionalidades implementadas

| Módulo | O que está implementado | Situação |
|---|---|---|
| Questionário | 24, 36 e 48 afirmações, cotas por eixo e polo, rascunho local, teclado | Completo |
| Cálculo | Likert, vetor de seis eixos, arquétipo, raridade e confiança | Completo |
| Resultado | leituras, dez filmes, descobertas, diretores, escolas, décadas e elenco | Completo |
| Streaming | ranking de dez plataformas, exclusividade, preço e cobertura conjunta | Completo |
| Catálogo | 3.943 obras, 9.189 pessoas, 12.039 créditos e trilhas de curadoria | Completo |
| Compartilhamento | código de oito caracteres, card 9:16, Open Graph e Twitter Card | Completo |
| Sinergia | comparação de dois códigos, radar duplo e filme da dupla | Completo |
| Conta | cadastro, login, lista "quero ver" e "já vi" | Completo |
| Painel | CRUD de eixos, perguntas, entidades, arquétipos e plataformas | Completo |
| IA | retrato e frase do perfil, com fallback e limite de uso | Completo |
| i18n | chaves de tradução e estrutura de locale | Estrutura pronta, interface em pt-BR |
| GeoCinema | cinemas próximos por CEP e geolocalização | Não implementado |

Os números de catálogo foram medidos no banco de demonstração em 20/11/2026:
3.943 obras ativas, 9.189 pessoas, 12.039 créditos, 10 plataformas, 52
entidades do matcher (36 personagens, 10 diretores e 6 escolas), 32 obras
essenciais, 12 arquétipos e 48 perguntas. A base de raridade tem 603 perfis
sintéticos declarados e um perfil real.

## 3.8 Interface do usuário

As telas abaixo foram capturadas do sistema em execução, em 1440 × 900 px. O
tema claro usa papel envelhecido e vinho; o tema escuro usa marrom quente e
mantém o mesmo contraste.

![Figura 17 — Escolha do formato do questionário.](imagens/prints/formatos.jpg)

![Figura 18 — Questionário em uma tela, com cena do filme e progresso.](imagens/prints/quiz.jpg)

![Figura 19 — Modal "Entender este eixo", com os dois polos e referências.](imagens/prints/guia.jpg)

![Figura 20 — Resultado com arquétipo, radar e leituras.](imagens/prints/resultado.jpg)

![Figura 21 — Leituras do perfil em linguagem clara.](imagens/prints/leituras.jpg)

![Figura 22 — Insights: arquétipo secundário, década e descobertas.](imagens/prints/insights.jpg)

![Figura 23 — Retrospectiva em capítulos.](imagens/prints/retro.jpg)

![Figura 24 — Ficha rápida de personagem.](imagens/prints/quickview.jpg)

![Figura 25 — Os seis eixos na página inicial.](imagens/prints/eixos.jpg)

![Figura 26 — Modo Sinergia entre dois perfis.](imagens/prints/sinergia.jpg)

![Figura 27 — Card 9:16 gerado para compartilhamento.](imagens/prints/story-card.jpg)

![Figura 28 — A mesma interface no tema escuro.](imagens/prints/dark.jpg)

![Figura 29 — Painel administrativo.](imagens/prints/admin.jpg)

![Figura 30 — Cadastro de perguntas e mídia do quiz.](imagens/prints/admin-perguntas.jpg)
