# Apresentação do Projeto

O PersonaCut é uma aplicação web que transforma gosto de cinema em um perfil
legível. O visitante responde a um questionário de afirmações e recebe um vetor
de seis eixos, um arquétipo, leituras em linguagem clara, dez filmes escolhidos
pelo próprio perfil, um ranking de plataformas de streaming e um card vertical
para compartilhar. O projeto foi desenvolvido para a disciplina Desenvolvimento
Web II, do curso de Desenvolvimento de Software Multiplataforma da Fatec Araras,
entre 16 e 20 de novembro de 2026.

![Figura 1 — Página inicial do PersonaCut, com o card de perfil em 9:16 e os três perfis de exemplo.](imagens/prints/home.jpg)

## 1.1 Contexto e problema

Quem assiste a muitos filmes costuma saber o que gostou, mas não consegue
explicar por quê. As listas de "melhores filmes" resolvem o problema da
curadoria geral e não o do gosto individual: duas pessoas podem dar cinco
estrelas ao mesmo título por motivos opostos. Plataformas de streaming, por sua
vez, recomendam a partir do histórico de consumo e mantêm o critério fechado.
O usuário vê o resultado, não o raciocínio.

O PersonaCut parte de outra pergunta: em vez de perguntar "que filme você
gostou", o sistema pergunta como a pessoa se posiciona em seis tensões do
cinema — popular ou autoral, claro ou sombrio, real ou imaginário, calmo ou
intenso, íntimo ou épico, circunscrito ou omnívoro. O resultado é um vetor de
seis coordenadas entre 0 e 100. A partir dele, o sistema calcula afinidade com
personagens, diretores, escolas e obras, e explica cada aproximação.

O projeto também tem uma restrição de produto deliberada: o resultado principal
não pode depender de inteligência artificial. Arquétipo, filmes, plataformas e
leituras são calculados por funções determinísticas e testadas. A IA é usada
apenas para redigir um retrato em prosa, uma vez por código, e pode ser
desligada sem que o restante do site deixe de funcionar.

## 1.2 Objetivos

**Objetivo geral.** Construir uma aplicação web que converta respostas de um
questionário Likert em um perfil cinéfilo explicável, com recomendações
determinísticas e compartilháveis.

**Objetivos específicos.**

- modelar seis eixos bipolares e um questionário equilibrado por eixo e por
  polo, em três profundidades (24, 36 e 48 afirmações);
- calcular o perfil com uma fórmula pública, reproduzível e coberta por testes;
- recomendar filmes, diretores, escolas e plataformas a partir do vetor, sem
  usar o histórico de consumo do visitante;
- oferecer um resultado compartilhável por código de oito caracteres, sem
  exigir cadastro;
- permitir que duas pessoas comparem seus perfis no modo Sinergia;
- gerar um card vertical de 1080 × 1920 px para redes sociais;
- manter um painel editorial para eixos, perguntas, entidades, arquétipos e
  plataformas;
- documentar as decisões de arquitetura, o modelo de dados e os testes.

## 1.3 Público-alvo

O público principal é o visitante anônimo que gosta de cinema e quer uma
recomendação que não seja a vitrine da plataforma. O sistema foi desenhado para
uso no celular: o quiz cabe em uma tela, o resultado é lido em blocos e o card
final é feito para stories. Um segundo público é o editor de conteúdo, que usa
o painel administrativo para ajustar perguntas, cenas, entidades e preços das
plataformas sem mexer no código.

## 1.4 Escopo acadêmico

O PersonaCut não tem cliente externo e não foi contratado por uma empresa. É um
projeto de avaliação da disciplina, com dois integrantes. Por isso, este
documento não apresenta empresa fictícia nem contrato comercial: descreve o
produto, as decisões técnicas e as evidências de execução. O sistema roda em
Docker no ambiente local e tem um pacote alternativo para hospedagem
compartilhada com PHP e MariaDB.

## 1.5 Equipe e repositório

| Integrante | Papel |
|---|---|
| Odil Klein da Silva Neto | Desenvolvimento back-end e front-end, banco de dados, testes e documentação técnica |
| Giovanni Xavier | Documentação e apoio no front-end |

O código-fonte está em
[github.com/odilklein/PersonaCut](https://github.com/odilklein/PersonaCut).
O histórico tem 21 commits entre 16 e 20 de novembro de 2026, com o núcleo do
produto concentrado nos dias 17 e 18. A documentação técnica de apoio fica em
`docs/` no mesmo repositório: `EIXOS.md`, `ARQUITETURA.md`, `RECOMENDACAO.md`,
`CALIBRACAO.md` e `CRONOGRAMA.md`.
