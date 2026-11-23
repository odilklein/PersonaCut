# Anexo I - Diário de bordo

O planejamento de doze semanas e a curadoria de conteúdo precederam o
repositório: os eixos, a régua de 0 a 100, a matriz de 48 afirmações e as 52
entidades do matcher foram fechados antes de 16/11 e estão registrados em
`docs/EIXOS.md`. O diário abaixo registra a execução versionada, que ocorreu
entre 16 e 20 de novembro de 2026 e corresponde aos 21 commits do repositório.

Tabela 1 – Diário de bordo

| Data | Atividade | Responsável |
|---|---|---|
| 16/11 | Estrutura inicial do projeto, Docker e documentação dos eixos | Odil |
| 17/11 | Schema, seed, cálculo Likert, quiz, resultado, matcher e Sinergia | Odil |
| 17/11 | Painel administrativo, testes de serviço e README | Odil |
| 17/11 | Revisão dos pull requests 1 e 2, landing e leituras do perfil | Equipe |
| 17/11 | Pacote de deploy para InfinityFree e SQL portátil | Odil |
| 18/11 | Catálogo de 3.943 obras, ranking de plataformas e trilhas | Odil |
| 18/11 | Guia dos eixos, pôsteres e frase do perfil | Odil |
| 18/11 | Rate limit, cabeçalhos, métodos HTTP e auditoria de segurança | Odil |
| 18/11 | Revisão do README e instruções de atualização do banco | Odil |
| 19/11 | Moldura de celular, slider de perfis e tags dos filmes | Odil |
| 20/11 | Open Graph, pausa de contas e retrato no free tier da Groq | Odil |
| 20/11 | 101 testes, 15 diagramas e build do DOCX/PDF | Equipe |

A execução seguiu a ordem de dependência do plano, mas concentrou a
implementação nos cinco dias finais. O núcleo (banco, cálculo, quiz, resultado e
painel) ficou pronto em 17/11; o catálogo, o ranking e a auditoria entraram em
18/11; o acabamento visual e a prévia de compartilhamento fecharam em 19 e
20/11.

As frentes de inglês e GeoCinema, previstas nas semanas 8 e 11, não foram
iniciadas. O tempo foi redirecionado para o catálogo audiovisual, o ranking de
plataformas e a auditoria, entregas que afetam o resultado principal. A
estrutura de tradução existe no código, mas a interface publicada permaneceu em
português.

A documentação, os diagramas e o build foram fechados depois do último commit, a
partir do mesmo código. O PDF e o DOCX não alteram o sistema: apenas registram o
que foi executado e testado.
