# Considerações Finais

O PersonaCut chegou ao fim da disciplina com o fluxo principal completo:
questionário, cálculo, código público, resultado explicado, recomendações,
ranking de plataformas, card de compartilhamento e painel editorial. O que
começou como um exercício de questionário virou um produto com catálogo de 3.943
obras, 101 testes e uma auditoria de segurança documentada.

## 5.1 O que funcionou

A decisão mais produtiva foi separar o que mede do que é curadoria. O quiz mede
seis eixos; o catálogo é uma base editorial com vetor estimado. Essa separação
evitou duas armadilhas comuns: vender recomendação como se fosse medida de
gosto e esconder a regra atrás de um modelo de linguagem. A fórmula do Likert, a
afinidade composta e o ranking de plataformas são públicos e testáveis.

A segunda decisão foi tratar a IA como acessório. O retrato em prosa é a única
parte gerada por modelo, é opcional e nunca altera a ordem das recomendações.
Quando a chave não existe ou a chamada falha, o site continua completo. Isso
reduziu custo, latência e risco de o resultado mudar sem explicação.

A terceira foi a fronteira de camadas. Como os serviços não conhecem banco nem
sessão, foi possível testar 101 casos sem subir MySQL. A mesma fronteira
permitiu trocar a fonte do catálogo sem reescrever o cálculo.

## 5.2 Limitações

- **Calibração editorial.** Os vetores de obras e de entidades foram estimados
  a partir de gênero, país, idioma e década. Não há validação empírica com uma
  amostra grande de espectadores. O sistema mostra confiança e posição, mas não
  pode afirmar que a régua representa toda a população.
- **Base sintética.** A raridade usa 603 perfis sintéticos declarados. Eles
  servem para o selo funcionar na demonstração, não como estatística real.
- **Um idioma.** A estrutura de chaves e o tradutor existem, mas a interface
  publicada está em português.
- **Sem teste de interface automatizado.** A validação visual foi manual, por
  captura e inspeção.
- **Catálogo dependente da TMDB.** A disponibilidade de streaming muda com
  frequência; o snapshot precisa ser revalidado.
- **Escopo de segurança local.** A auditoria foi feita na instância de
  desenvolvimento, não em produção exposta à internet.

## 5.3 Trabalhos futuros

1. **Calibrar com dados reais.** Aplicar o questionário a uma amostra e comparar
   a ordem dos eixos com a de especialistas, ajustando pesos e limiares.
2. **Completar o i18n.** Traduzir a interface para inglês e espanhol usando as
   chaves já existentes.
3. **GeoCinema.** Implementar a busca de cinemas próximos por CEP e
   geolocalização, prevista no cronograma e não entregue.
4. **Testes de interface.** Adicionar Playwright para os fluxos de quiz,
   resultado e painel.
5. **Histórico do perfil.** Permitir que o visitante refaça o teste e compare a
   evolução do próprio vetor ao longo do tempo.
6. **Observabilidade.** Registrar tempo de resposta e falhas de integração sem
   gravar dado pessoal.
7. **Atualização do PHP.** Migrar de 8.2 para 8.3 ou 8.4, conforme o achado 5
   da auditoria.

## 5.4 Contribuições

**Odil Klein da Silva Neto.** Modelagem do banco, núcleo PHP, serviços de
cálculo, matcher, recomendação, ranking de plataformas, integração com TMDB e
Groq, painel administrativo, testes, auditoria de segurança e documentação
técnica.

**Giovanni Xavier.** Apoio na documentação, revisão de textos e acompanhamento
da interface.

O projeto foi construído com apoio de ferramentas de IA no desenvolvimento e na
redação, sempre com revisão e validação por execução. As decisões de produto, a
modelagem e a conferência dos resultados são da equipe.
