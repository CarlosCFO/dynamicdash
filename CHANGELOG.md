# Changelog

Todas as mudancas relevantes deste projeto serao documentadas neste arquivo.

O formato segue [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/),
e o projeto adere ao [Semantic Versioning](https://semver.org/).

## [0.9.0-beta.1] - 2026-03-30

### Importante
- A partir desta versao, o projeto adota Semantic Versioning.
- Versoes anteriores (1.0.0, 1.1.0) foram numeracao interna de desenvolvimento.
- **0.9.0 e a versao mais recente e SUPERIOR as anteriores.**

### Adicionado
- 10 cards de indicadores (5 core + 5 opcionais)
- Cards reagem aos filtros ativos da busca do GLPI
- Permissoes configuraveis por perfil (admin escolhe quais cards cada perfil ve)
- Thresholds de cor configuraveis (verde/amarelo/vermelho)
- Drill-down: clique no card abre busca filtrada em nova aba
- Botao refresh manual
- Endpoint de debug (?debug=1)
- Suporte a arvores de grupos, categorias e entidades (getSonsOf)
- Cache em sessao com TTL configuravel
- Layout 120px fixo por card (estilo Jira SM)
- Acessibilidade: icones com formas unicas para daltonicos
- Deploy script automatizado com backup

### Cards disponiveis
- **Atribuidos**: tickets com tecnico (status 2-3)
- **Atrasados**: tickets com SLA estourado
- **Pendentes**: tickets em espera (status 4)
- **Novos**: tickets sem tecnico (status 1)
- **No prazo**: % de tickets resolvidos dentro do SLA
- **TMA**: tempo medio de atendimento em horas decimais
- **TMS**: tempo medio de solucao em horas decimais
- **CSAT**: nota media de satisfacao
- **Aging**: idade media do backlog em dias
- **Reabertos**: % de tickets reabertos

### Tecnico
- Nao cria tabelas no banco de dados
- Usa glpi_configs e glpi_profilerights nativos do GLPI
- TMA calculado via campo takeintoaccount_delay_stat
- TMS calculado via TIMESTAMPDIFF(date, solvedate)
- Compativel com GLPI 10.0.x, PHP 8.1+, MariaDB 10.5+

## [1.1.0] - 2026-03-27 (numeracao interna - obsoleta)

### Adicionado
- Cards TMA, TMS, CSAT, Aging, Reabertos
- Filtros via getSonsOf para arvores

### Corrigido
- CSS layout 120px uniforme
- TMA/TMS via glpi_logs (substituido em 0.9.0)

## [1.0.0] - 2026-03-24 (numeracao interna - obsoleta)

### Adicionado
- Plugin inicial com 5 cards core
- Sistema de permissoes por perfil
- Configuracao de thresholds
- Deploy script
