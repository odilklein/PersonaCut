# Escopo do Sistema

## 2.1 Objetivos do projeto

O escopo funcional cobre o caminho completo do visitante: escolher a
profundidade do questionário, responder, receber um código, consultar o
resultado, explorar as recomendações, comparar com outro perfil e compartilhar
o card. Cobre também o painel editorial que alimenta o questionário e o
catálogo.

**Está no escopo:**

- questionário Likert em três modos (24, 36 e 48 afirmações);
- cálculo determinístico do vetor de seis eixos e do arquétipo;
- código público de oito caracteres, sem cadastro obrigatório;
- leituras, filmes, diretores, escolas, décadas e elenco por afinidade;
- ranking de plataformas de streaming e cobertura conjunta;
- retrospectiva em capítulos e card 9:16;
- modo Sinergia entre dois códigos;
- conta opcional com lista pessoal ("quero ver" e "já vi");
- painel administrativo com CRUD de conteúdo;
- retrato em prosa por IA, opcional e desacoplado do cálculo.

**Está fora do escopo:**

- cadastro obrigatório, feed social, comentários e seguidores;
- player de filmes ou integração de reprodução;
- cobrança, assinatura ou qualquer transação financeira;
- aplicativo nativo para iOS ou Android;
- recomendação por histórico de consumo ou por câmera;
- tradução completa para outros idiomas (a estrutura de chaves existe, mas a
  interface publicada está em português).

## 2.2 Técnica de levantamento de requisitos

O levantamento combinou três fontes. A primeira foi a **análise de sistemas
similares** (Letterboxd, FilmAffinity e Criticker), para identificar o que já
existe e onde há espaço para um recorte diferente. A segunda foi a **revisão da
literatura de psicometria**, em especial a escala Likert e o uso de eixos
bipolares, para não tratar preferência como nota única. A terceira foi a
**prototipação incremental**: cada tela foi construída, executada e ajustada
com dados reais do banco, e o que não se sustentava era removido.

Como não há cliente externo, não houve entrevista formal nem aceite de
requisitos. A validação foi feita por execução: testes automatizados dos
serviços de cálculo, auditoria de segurança na instância local e revisão visual
das telas em desktop e celular. Essa limitação está registrada nas
considerações finais.

## 2.3 Requisitos funcionais

### RF01 — Responder ao questionário Likert — Essencial

O sistema deve apresentar afirmações em escala de cinco pontos e permitir que o visitante responda ao questionário completo sem recarregar a página.

### RF02 — Escolher a profundidade do teste — Importante

O sistema deve oferecer três modos de questionário com 24, 36 ou 48 afirmações, equilibrados por eixo e por polo.

### RF03 — Calcular o perfil em seis eixos — Essencial

O sistema deve converter as respostas em seis percentuais de 0 a 100, um por eixo bipolar, com a fórmula publicada e reproduzível.

### RF04 — Gerar código público do resultado — Essencial

O sistema deve gerar um código único de oito caracteres para cada resultado e permitir a consulta posterior sem exigir cadastro.

### RF05 — Identificar o arquétipo do perfil — Essencial

O sistema deve comparar o vetor do visitante com os doze arquétipos cadastrados e apresentar o mais próximo, com distribuição entre os três primeiros.

### RF06 — Exibir leituras em linguagem clara — Essencial

O sistema deve traduzir os eixos mais extremos em textos sobre o que o visitante procura, o que o afasta e quais filmes representam o perfil.

### RF07 — Recomendar filmes e descobertas — Essencial

O sistema deve selecionar dez filmes por afinidade e oferecer um seletor entre clássicos, misturado e descobertas.

### RF08 — Comparar diretores e escolas — Importante

O sistema deve ranquear diretores e escolas pela filmografia, mostrando posição no grupo, margem e os eixos que explicam a proximidade.

### RF09 — Ranquear plataformas de streaming — Importante

O sistema deve calcular um pódio de plataformas por relevância, acervo, exclusividade, variedade e preço, além da cobertura conjunta dos melhores títulos.

### RF10 — Explorar trilhas de curadoria — Desejável

O sistema deve oferecer trilhas de cinema fora do cânone (doramas, coreano, indiano, japonês, brasileiro e outras) com títulos do catálogo e onde assistir.

### RF11 — Abrir ficha rápida de uma entidade — Desejável

O sistema deve abrir uma ficha com sinopse, elenco, biografia ou obras essenciais ao clicar em um card, sem sair do resultado.

### RF12 — Gerar retrospectiva em capítulos — Desejável

O sistema deve apresentar o perfil em capítulos navegáveis, no formato de stories, com swipe e teclado.

### RF13 — Gerar card 9:16 para compartilhamento — Importante

O sistema deve gerar um card vertical de 1080 × 1920 px com o resumo do perfil e permitir baixar ou compartilhar o PNG.

### RF14 — Comparar dois perfis no modo Sinergia — Desejável

O sistema deve receber dois códigos e calcular compatibilidade, divergências e um filme sugerido para a dupla.

### RF15 — Manter conta e lista pessoal — Desejável

O sistema deve permitir criar conta, entrar e marcar obras como "quero ver" ou "já vi", com a lista persistida no banco.

### RF16 — Gerenciar conteúdo no painel — Importante

O sistema deve permitir que o administrador autenticado cadastre, edite e remova eixos, perguntas, entidades, arquétipos e plataformas.

### RF17 — Redigir retrato do perfil por IA — Desejável

O sistema deve gerar, sob demanda, um retrato em prosa a partir do vetor do perfil, mantendo um texto determinístico quando a IA estiver indisponível.

### RF18 — Compartilhar com prévia rica — Desejável

O sistema deve publicar metadados Open Graph e Twitter Card nas páginas de resultado para que o link mostre título, descrição e imagem.

## 2.4 Requisitos não funcionais

### RNF01 — Segurança de entrada e saída — Essencial

Toda consulta deve usar prepared statements e toda saída HTML deve ser escapada, sem concatenação de valores vindos do usuário.

### RNF02 — Autenticação e sessão — Essencial

O painel deve exigir login, regenerar a sessão no acesso, usar hash de senha e cookie HttpOnly com SameSite.

### RNF03 — Desempenho no carregamento do resultado — Importante

O resultado deve ser montado com um número fixo de consultas, sem N+1 e sem chamadas de rede para montar cards ou ranking.

### RNF04 — Usabilidade — Importante

O quiz deve caber em uma tela no desktop, aceitar teclado e manter o rascunho em localStorage.

### RNF05 — Responsividade — Importante

As páginas públicas e o card devem funcionar de 390 px a 1440 px sem rolagem horizontal.

### RNF06 — Disponibilidade com degradação graciosa — Essencial

O site deve funcionar sem chave da TMDB e sem chave da Groq, usando o catálogo local e o retrato determinístico.

### RNF07 — Determinismo e integridade — Essencial

Arquétipo, afinidade, raridade e ranking não podem depender de IA, e o resultado salvo deve guardar a versão do questionário usado.

### RNF08 — Manutenibilidade — Importante

A regra de dependência deve apontar sempre para baixo (apresentação, controller, serviço, modelo, core), com serviços puros testáveis sem banco.

### RNF09 — Portabilidade — Importante

O sistema deve subir com Docker Compose e também rodar em hospedagem compartilhada com PHP e MariaDB, sem dependência de framework.

### RNF10 — Privacidade — Essencial

O sistema não deve exigir cadastro para o resultado e deve gravar apenas hash do IP, nunca o endereço em claro.

### RNF11 — Escalabilidade do catálogo — Desejável

O catálogo deve ser ingerido fora do request, de forma retomável e idempotente, sem alterar o tempo de resposta do perfil.

### RNF12 — Compatibilidade de navegador — Importante

As páginas devem funcionar nas versões atuais de Chrome, Firefox, Safari e Edge, incluindo o compartilhamento nativo quando disponível.

## 2.5 Comparativo entre sistemas

O comparativo usa dois produtos reais de registro e recomendação de cinema. A
coluna PersonaCut não repete funcionalidades de catálogo social; marca o que o
projeto entrega com recorte próprio.

| Funcionalidade | Letterboxd | FilmAffinity | PersonaCut |
|---|---|---|---|
| Registrar filmes assistidos | Sim | Sim | Lista pessoal opcional |
| Dar nota e escrever resenha | Sim | Sim | Não |
| Seguir pessoas e comentar | Sim | Parcial | Não |
| Perfil de gosto em eixos | Não | Não | Sim, seis eixos |
| Código público sem cadastro | Não | Não | Sim, oito caracteres |
| Recomendação explicada por eixo | Não | Parcial | Sim |
| Ranking de plataformas por perfil | Não | Não | Sim |
| Card vertical para stories | Não | Não | Sim, 1080 × 1920 |
| Comparação entre dois perfis | Parcial | Não | Sim, modo Sinergia |
| Curadoria fora de Hollywood | Parcial | Sim | Sim, trilhas por perfil |

Fonte: elaborado pelos autores a partir dos sites oficiais dos três produtos,
consultados em novembro de 2026.

## 2.6 Cronograma

O cronograma abaixo é o planejamento de doze semanas registrado em
`docs/CRONOGRAMA.md`. A execução efetiva, concentrada em cinco dias, está no
Anexo II.

| Etapa | S1–2 | S3–4 | S5–6 | S7–8 | S9–10 | S11 | S12 | Entrega |
|---|---|---|---|---|---|---|---|---|
| Base, conteúdo e banco | X |  |  |  |  |  |  | Docker no ar |
| Núcleo PHP e testes |  | X |  |  |  |  |  | Serviços puros |
| Quiz, resultado e admin |  |  | X |  |  |  |  | MVP |
| Sinergia e idioma |  |  |  | X |  |  |  | Dois códigos |
| Catálogo TMDB e streaming |  |  |  |  | X |  |  | Catálogo local |
| GeoCinema |  |  |  |  |  | X |  | Fora da entrega |
| Acabamento e release |  |  |  |  |  |  | X | Documento e prints |

Fonte: docs/CRONOGRAMA.md, adaptado para a entrega da disciplina.
