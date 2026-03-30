# Dynamic Dashboard para GLPI

**Micro-dashboard dinamico que exibe indicadores operacionais na tela de chamados do GLPI, reagindo em tempo real aos filtros ativos do usuario.**

[![Version](https://img.shields.io/badge/version-0.9.0--beta.1-blue)](https://github.com/CarlosCFO/dynamicdash/releases)
[![GLPI](https://img.shields.io/badge/GLPI-10.0.x-green)](https://glpi-project.org)
[![License](https://img.shields.io/badge/license-GPLv3+-orange)](LICENSE)

> **Autor:** Carlos Alberto Correa Filho - IPT.br
> **Licenca:** GPLv3+
> **Compatibilidade testada:** GLPI 10.0.17 | PHP 8.1+ | MariaDB 10.5+

---

## O que este plugin faz

O GLPI possui um mini-dashboard nativo na tela de chamados, porem os cards sao **estaticos** e nao reagem aos filtros aplicados pelo usuario. Este plugin adiciona uma linha de cards **dinamicos** que:

- **Reagem aos filtros** da busca (grupo tecnico, entidade, status, categoria, etc.)
- **Atualizam automaticamente** quando o usuario clica em "Pesquisar"
- **Sao configuráveis** por perfil (admin escolhe quais cards cada perfil ve)
- **Possuem thresholds** de cor configuraveis (verde/amarelo/vermelho)
- **Permitem drill-down** (clique no card abre busca filtrada em nova aba)

---

## Cards disponiveis

### Core (operacionais)

| Card | Descricao | Calculo |
|------|-----------|---------|
| **Atribuidos** | Tickets com tecnico atribuido | Status 2-3 com tecnico |
| **Atrasados** | Tickets com SLA estourado | Status 1-4 com time_to_resolve < agora |
| **Pendentes** | Tickets aguardando retorno | Status 4 |
| **Novos** | Tickets sem tecnico atribuido | Status 1 sem tecnico |
| **No prazo** | % de tickets resolvidos dentro do SLA | solvedate <= time_to_resolve |

### Opcionais (qualidade e performance)

| Card | Descricao | Calculo |
|------|-----------|---------|
| **TMA** | Tempo Medio de Atendimento | Horas decimais (takeintoaccount_delay_stat) |
| **TMS** | Tempo Medio de Solucao | Horas decimais (abertura ate solucao) |
| **CSAT** | Satisfacao do usuario | Nota media (min. 5 respostas) |
| **Aging** | Idade media do backlog | Dias medios dos chamados abertos |
| **Reabertos** | % de tickets reabertos | Tickets que voltaram de resolvido para ativo |

---

## Screenshots

*Em breve*

---

## Instalacao

### Requisitos

- GLPI >= 10.0.x
- PHP >= 8.1
- MariaDB >= 10.5 ou MySQL >= 8.0

### Passos

1. Baixar o release mais recente em [Releases](https://github.com/CarlosCFO/dynamicdash/releases)
2. Extrair na pasta de plugins do GLPI:
   ```bash
   cd /path/to/glpi/plugins/
   tar -xzf dynamicdash-v0.9.0-beta.1.tar.gz
   chown -R www-data:www-data dynamicdash/
   chmod -R 755 dynamicdash/
3. No GLPI: Configuracao > Plugins > Dynamic Dashboard > Instalar > Ativar
4. Configurar cards por perfil: Administracao > Perfis > [perfil] > aba Dynamic Dashboard
5. Configurar thresholds: Configuracao > Plugins > Dynamic Dashboard

### Apos a instalacao

- Faca logout e login no GLPI (os direitos do plugin sao carregados na sessao de login)
- Acesse Assistencia > Chamados para ver os cards

## Configuracao

### Cards por perfil

O administrador define quais cards cada perfil pode ver:

Administracao > Perfis > [perfil] > aba Dynamic Dashboard

- Cards core sao habilitados automaticamente para perfis com acesso a tickets
- Cards opcionais precisam ser habilitados manualmente

### Thresholds de cor

O administrador define os limites de cor (verde/amarelo/vermelho):

Configuracao > Plugins > Dynamic Dashboard

### Design

- Fundo branco, texto preto (padrao GLPI)
- Borda esquerda: cor do tema do usuario (automatico)
- Icones: cores semanticas com formas unicas (acessivel para daltonicos)
- Tamanho fixo: 120px por card (estilo Jira Service Management)
- Responsivo: adapta-se a telas menores

### Caracteristicas tecnicas

- Nao cria tabelas no banco de dados (usa glpi_configs e glpi_profilerights nativos)
- Cache em sessao (TTL configuravel, padrao 300 segundos)
- Calcula apenas cards habilitados para o perfil do usuario
- Suporta arvores de grupos, categorias e entidades (getSonsOf)
- Drill-down: clique no card abre busca filtrada em nova aba
- Botao refresh: atualiza cards sem recarregar a pagina
- Endpoint de debug: ?debug=1 para troubleshooting

### Seguranca

- O plugin respeita todas as permissoes do GLPI (entidades, perfis, direitos)
- Nenhum dado e exposto sem autenticacao
- SQL injection protegido via escaping e parametros inteiros
- CSRF protegido via tokens nativos do GLPI
- O plugin e read-only: nao altera nenhum dado no banco

## Avisos importantes

VERSAO BETA: Este plugin esta em fase de testes.
Recomendamos fortemente a instalacao em ambiente de homologacao antes da producao.

GLPI 11: Este plugin foi desenvolvido e testado no GLPI 10.0.x.
A compatibilidade com GLPI 11.x nao foi testada e nao e garantida.

Backup: Embora o plugin nao crie tabelas, sempre faca backup antes de instalar qualquer plugin.

## Versionamento

Este projeto segue o Semantic Versioning:

- Versoes 0.x.y = Beta (atual)
- Versao 1.0.0 = Release estavel de producao
- v0.9.0-beta.1 e SUPERIOR a v1.1.0 (a numeracao foi corrigida a partir da Sprint 4)

## Feedback e contribuicoes

Sua opiniao e muito importante para evoluir este plugin!

- Issues e bugs: GitHub Issues
- Sugestoes: Abra uma issue com a tag enhancement
- Comunidade GLPI: Discussoes no grupo da comunidade

### Como reportar um bug

1. Descreva o que aconteceu e o que esperava
2. Informe a versao do GLPI, PHP e MariaDB
3. Se possivel, inclua um screenshot
4. Acesse o endpoint de debug: /plugins/dynamicdash/ajax/getcards.php?debug=1 e inclua a saida

## Licenca

GPLv3+ - Veja o arquivo LICENSE para detalhes.

## Autor

Carlos Alberto Correa Filho - IPT.br
