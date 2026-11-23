# Anexo IV – Relatório de segurança

Este anexo registra a avaliação de segurança feita no PersonaCut em 18/11/2026.
O trabalho combinou varredura dinâmica com o HexStrike AI, em container Kali
Linux, e revisão do código-fonte. O teste foi autorizado e executado pelo
próprio mantenedor, contra uma instância local da aplicação. Nenhum host
externo foi testado.

## IV.1 Resumo

A aplicação passou por fingerprint de serviços, detecção de WAF, varredura de
más configurações, templates de vulnerabilidade, injeção SQL e cerca de 80
testes manuais de sessão, CSRF, autorização, SSRF e cabeçalhos. O núcleo
resistiu: não foi encontrada falha explorável de SQL Injection, XSS, CSRF, SSRF,
RCE, path traversal, mass assignment ou open redirect.

Os problemas reais estavam na ausência de limite de requisições e no acabamento
da implantação. Foram registrados cinco achados, distribuídos como na Tabela 3.

Tabela 3 – Achados por severidade

| Severidade | Quantidade | Destaques |
|---|---|---|
| Crítico | 0 | nenhum achado com exploração direta |
| Alto | 0 | nenhuma falha de injeção, autorização ou exposição de segredo |
| Médio | 1 | quiz anônimo e login administrativo sem limite de requisições |
| Baixo | 4 | cabeçalhos ausentes, fingerprint exposto, métodos HTTP abertos e PHP desatualizado |

Quatro achados foram corrigidos no mesmo dia. O quinto, a versão do PHP, ficou
documentado como manutenção programada.

## IV.2 Escopo e autorização

O alvo foi a aplicação local em `http://127.0.0.1:8090`, com MySQL em
`localhost:3308`, no estado do código de 18/11/2026, antes das correções. O
ambiente é o mesmo usado na demonstração do projeto, com dados fictícios. Os
testes foram não destrutivos: não houve negação de serviço, exclusão de dados
nem força bruta real.

Foram testados os dois perfis do sistema: o visitante anônimo e o administrador
do painel editorial. O visitante cobre quiz, resultado, Sinergia e card; o
administrador cobre login, CRUD, mídia e plataformas.

## IV.3 Ambiente e ferramentas

Tabela 4 – Ambiente de teste

| Componente | Configuração |
|---|---|
| Orquestrador | HexStrike AI, com API local e integração por MCP no OpenCode |
| Alvo | aplicação PHP 8.2.35 / Apache 2.4.69 / MySQL 8.4 em Docker |
| Ferramentas disponíveis | nmap 7.99, Nikto 2.6.1, Nuclei 3.11.1, sqlmap 1.10.8, ffuf 2.1.0, wafw00f 2.4.2 e curl |
| Rede | container Kali isolado, restrito à máquina local |

Tabela 5 – Ferramentas usadas

| Ferramenta | Objetivo | Execução |
|---|---|---|
| nmap | portas, serviços e métodos HTTP | alvo `127.0.0.1:8090`, scripts `http-headers` e `http-methods` |
| wafw00f | detecção de WAF | aplicação local |
| Nikto | más configurações do servidor web | varredura completa, sem interação |
| Nuclei | CVEs, exposições e configuração insegura | templates de CVE, exposure, misconfig, default-login e SSL |
| sqlmap | injeção SQL | `resultado.php?codigo=`, com `--level 1 --risk 1` |
| ffuf | descoberta de conteúdo | diretórios e arquivos internos |
| curl | validação manual | sessão, CSRF, SSRF, cabeçalhos e métodos |

## IV.4 Metodologia

A avaliação teve três frentes:

- **Caixa-preta:** fingerprint de serviços, detecção de WAF, varredura de más
  configurações, templates de vulnerabilidade e fuzzing de conteúdo, sem
  credenciais.
- **Sessão autenticada:** visitante e administrador usados para testar
  autorização, CSRF, acesso ao painel e isolamento do resultado público.
- **Revisão de código:** leitura do núcleo (`Database`, `Session`, `Csrf`,
  `RateLimit`, `Env`, `Vetor`), dos controladores, dos serviços, das rotas de
  `public/`, do `Dockerfile`, do `docker-compose.yml` e do `public/.htaccess`.

## IV.5 Resultados

### IV.5.1 Portas e serviços

Tabela 6 – Portas e serviços no ambiente local

| Porta | Estado | Serviço | Observação |
|---|---|---|---|
| 8090 | aberto | Apache 2.4.69 + PHP 8.2.35 | aplicação e endpoints JSON |
| 8091 | aberto | phpMyAdmin | acesso restrito ao ambiente de desenvolvimento |
| 3308 | aberto | MySQL 8.4 | porta publicada apenas para o ambiente local |
| 22, 80 e 443 | fechados | — | sem serviço exposto |

### IV.5.2 Cabeçalhos HTTP

Antes das correções, a aplicação não enviava `Content-Security-Policy`,
`X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy` nem
`X-Frame-Options`, e anunciava `X-Powered-By: PHP/8.2.35`. O cookie de sessão já
saía com `HttpOnly` e `SameSite=Lax`.

Depois das correções, o `.htaccess` passou a enviar a política completa,
`script-src 'self'`, `nosniff`, `Referrer-Policy`, `X-Frame-Options` e
`Permissions-Policy`, além de ocultar `X-Powered-By`. O HSTS ficou comentado
até a publicação em HTTPS.

### IV.5.3 Varreduras automatizadas

O Nikto apontou os cabeçalhos ausentes, a versão do PHP e uma página de login
encontrada. O Nuclei não encontrou correspondência nas classes de CVE,
exposição, configuração insegura, login padrão e SSL testadas. O resultado
indica ausência de problemas conhecidos cobertos por template; não substitui a
revisão de falhas lógicas, feita no código.

O nmap confirmou `GET`, `HEAD` e `POST` como métodos aceitos depois da correção.
Antes, `PUT`, `DELETE` e `OPTIONS` na raiz devolviam 200, servindo a home por
causa do rewrite.

### IV.5.4 Caminhos expostos

O fuzzing de conteúdo não encontrou arquivo interno acessível. O DocumentRoot
aponta para `public/`, e os diretórios de código, configuração e banco ficam
fora dele.

| Caminho | Resposta | Observação |
|---|---|---|
| `/.git/`, `/.env`, `/config/`, `/storage/`, `/sql/`, `/docs/` | 404 | fora do diretório público |
| `/api/`, `/.htaccess` | 403 | listagem e acesso direto negados |
| `/api/quiz.php` sem token | 403 | CSRF exigido |
| `api/imagem.php` com host interno | 400 | allowlist de hosts |

### IV.5.5 Testes manuais

Tabela 7 – O que foi testado e está correto

| Teste | Resultado |
|---|---|
| SQL injection em `resultado.php?codigo=` (sqlmap) | não injetável |
| XSS refletido em `codigo` e em `sinergia.php?a=&b=` | payload não refletido |
| CSRF no `POST /api/quiz.php` | 403 sem token; 422 com valores inválidos |
| Validação de payload (JSON quebrado, escala inválida, vazio) | 422 com campo |
| SSRF em `api/imagem.php?u=` (localhost, `169.254.169.254`, `file://`, externo) | 400 pela allowlist |
| Cookie de sessão | `HttpOnly`, `SameSite=Lax` e `Secure` sob HTTPS |
| WAF | nenhum, como esperado em ambiente local |
| Nuclei (CVE, exposição, misconfig, login padrão e SSL) | 0 achados |

### IV.5.6 Falso positivo

O Nikto reportou phpAuction / CVE-2002-0995 em
`/admin/login.php?action=insert...`. A página responde o mesmo conteúdo para
qualquer query string e a aplicação ignora `action`; o achado foi descartado
após conferência manual.

## IV.6 Achados e correções

Tabela 8 – Achados e situação após a correção

| ID | Severidade | Achado | Situação |
|---|---|---|---|
| SEC-01 | Média | quiz anônimo e login administrativo sem limite de requisições | corrigido |
| SEC-02 | Baixa | cabeçalhos de segurança ausentes (CSP, nosniff, referrer, permissions e HSTS) | corrigido |
| SEC-03 | Baixa | fingerprint exposto (`X-Powered-By` e versão do Apache) | corrigido |
| SEC-04 | Baixa | `PUT`, `DELETE` e `OPTIONS` respondendo 200 na home | corrigido |
| SEC-05 | Baixa | PHP 8.2.35 desatualizado | documentado |

### IV.6.1 SEC-01 — limite de requisições

**Evidência.** O `POST /api/quiz.php` aceitava envios seguidos e criava um
resultado novo em cada um. O login em `/admin/auth.php` não tinha atraso,
bloqueio progressivo nem limite por IP. O schema já gravava `ip_hash`, mas ele
não era usado para limitar nada.

**Impacto.** Enchimento do banco com resultados anônimos e força bruta de senha
no painel, cuja autenticação é de fator único.

**Correção.** Foi criado `src/Core/RateLimit.php`, um limitador em arquivo com
`flock`, janela deslizante e faxina ocasional, que libera a requisição
(*fail-open*) se o diretório não for gravável. O quiz passou a aceitar 12
envios por minuto por IP, com resposta `429` e `Retry-After`. O login passou a
aceitar 8 tentativas a cada 10 minutos por IP, zeradas no sucesso. Os três casos
têm teste em `tests/RateLimitTest.php`.

### IV.6.2 SEC-02 — cabeçalhos de segurança

**Correção.** O `public/.htaccess` recebeu CSP com `script-src 'self'`,
`X-Content-Type-Options`, `Referrer-Policy`, `X-Frame-Options`,
`Permissions-Policy` e remoção do `X-Powered-By`. A política exigiu duas
limpezas: o script de tema saiu do HTML inline para `public/assets/js/theme.js`,
e os `onsubmit="confirm(...)"` do painel viraram `data-confirm`, com delegação
em `app.js`. O bundle `bootstrap.bundle.js`, que não era usado e continha
`eval`, foi removido.

### IV.6.3 SEC-03 — fingerprint

**Correção.** `docker/php-security.ini` passou a definir `expose_php=Off`, e
`docker/apache-security.conf` recebeu `ServerTokens Prod`, `ServerSignature Off`
e `TraceEnable Off`, carregado depois da configuração padrão. Em hospedagem
compartilhada, o `.htaccess` remove `X-Powered-By`.

### IV.6.4 SEC-04 — métodos HTTP

**Correção.** O `.htaccess` passou a usar
`<LimitExcept GET POST HEAD> Require all denied </LimitExcept>`. Os três métodos
agora respondem `403`.

### IV.6.5 SEC-05 — versão do PHP

A imagem continua em PHP 8.2 por compatibilidade com a hospedagem de
demonstração. A atualização para 8.3 ou 8.4 ficou registrada como próximo passo
de manutenção, junto com a repetição da varredura.

## IV.7 Controles que resistiram

Tabela 9 – Controles que resistiram aos testes

| Controle | Evidência |
|---|---|
| SQL Injection | PDO com prepared statements em toda a aplicação, `ATTR_EMULATE_PREPARES` desligado e valores numéricos convertidos para inteiro. |
| XSS | Saída escapada com `htmlspecialchars` pelo helper `e()`; JSON com conteúdo tratado e uso de `textContent` no JavaScript. |
| CSRF | Token por sessão em todo `POST`, no campo `csrf` ou no cabeçalho `X-CSRF-Token`, comparado com `hash_equals`. |
| SSRF | `api/imagem.php` usa allowlist de hosts, recusa IP interno e esquemas diferentes de HTTPS. |
| Path traversal | DocumentRoot em `public/`; código, configuração, banco e documentação fora do alcance do servidor. |
| Sessão | Cookie `HttpOnly` e `SameSite=Lax`, regeneração no login e destruição no logout. |
| Senhas | `password_hash` com `PASSWORD_DEFAULT` e `password_verify`; hash nunca sai em JSON. |
| Painel | Login próprio, sessão separada e verificação de perfil no servidor. |
| Segredos | Chaves da TMDB e da Groq em `config/env.php` ou `.env` não versionado; o pacote de deploy traz apenas o modelo. |
| Privacidade | IP gravado como `sha256(ip + salt)`; o resultado principal não exige cadastro. |

## IV.8 Verificação após as correções

Tabela 10 – Verificação após as correções

| Teste | Resultado |
|---|---|
| Quiz acima de 12 envios por minuto | `429` com `Retry-After` |
| Login acima de 8 tentativas em 10 minutos | bloqueio com mensagem e contador em arquivo |
| Cabeçalhos CSP, `nosniff`, referrer e permissions | presentes |
| `Server` e `X-Powered-By` | `Server: Apache`, sem `X-Powered-By` |
| `PUT`, `DELETE` e `OPTIONS` na raiz | `403` |
| Nuclei (CVE, exposição, misconfig, login padrão e SSL) | 0 achados |
| sqlmap no parâmetro `codigo` | não injetável |
| SSRF no proxy de imagem | `400` |
| Suíte de testes | 101 testes, 0 falhas |

## IV.9 Pendências

1. Atualizar a imagem de PHP 8.2 para 8.3 ou 8.4 e repetir a varredura.
2. Ativar HSTS depois da publicação em HTTPS.
3. Repetir o teste em ambiente com proxy reverso e domínio público.
4. Revalidar o snapshot de catálogo e de disponibilidade de streaming antes de
   cada publicação.

## IV.10 Como reproduzir

Os comandos ficam em `docs/security/relatorio-2026-11-18.md` e a receita do
container em `docs/security/hexstrike.Dockerfile`.

```bash
git clone https://github.com/0x4m4/hexstrike-ai ~/tools/hexstrike-ai
cp docs/security/hexstrike.Dockerfile ~/tools/hexstrike-ai/Dockerfile
docker build -t hexstrike-ai:kali ~/tools/hexstrike-ai
docker run -d --name hexstrike --network host hexstrike-ai:kali

docker exec hexstrike nmap -sT -sV -Pn -p 8090 --script http-headers,http-methods 127.0.0.1
docker exec hexstrike nikto -h http://127.0.0.1:8090 -nointeractive
docker exec hexstrike sqlmap -u 'http://127.0.0.1:8090/resultado.php?codigo=ABCD1234' --batch
```

## IV.11 Conclusão

O código tem controles de segurança adequados para um projeto acadêmico. A
varredura dinâmica não encontrou injeção, XSS, SSRF nem exposição de arquivos.
O risco estava na operação: sem limite de requisições, o quiz podia encher o
banco e o login ficava aberto à força bruta. Com as correções aplicadas, o risco
residual se concentra na versão do PHP e na repetição do teste em ambiente
público com HTTPS.
