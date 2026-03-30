# Dynamic Dashboard para GLPI

**Autor:** Carlos Alberto Correa Filho - IPT.br
**Versão:** 1.0.0
**Data:** 24/03/2026
**Licença:** GPLv3+
**Compatibilidade:** GLPI 10.0.x
**Requisitos:** PHP 8.1+, Debian 12

## Descrição

Micro-dashboard dinâmico que exibe indicadores operacionais
na tela de chamados do GLPI, reagindo aos filtros ativos do usuário.

## Cards disponíveis

### Core
| Card | Descrição | Ícone |
|------|-----------|-------|
| Abertos | Total de tickets não fechados | ℹ |
| Atrasados | Tickets com SLA estourado | ⊘ |
| Pendentes | Tickets em status pendente | ⏳ |
| Novos | Tickets sem técnico atribuído | ○ |
| No prazo | % de tickets resolvidos no SLA | ✓ |

### Opcional
| Card | Descrição | Ícone |
|------|-----------|-------|
| Reabertos | Tickets reabertos após solução | ⚠ |

## Instalação

1. Copiar pasta `dynamicdash` para `/path/glpi/plugins/`
2. No GLPI: Configuração → Plugins → Dynamic Dashboard → Instalar → Ativar
3. Configurar cards por perfil: Administração → Perfis → [perfil] → Dynamic Dashboard
4. Configurar thresholds: Configuração → Plugins → Dynamic Dashboard

## Design

- Fundo branco, texto preto (padrão GLPI)
- Borda esquerda: cor do tema do usuário (automático via --tblr-primary)
- Ícones: cores semânticas com formas únicas (acessível para daltônicos)

## Características técnicas

- Não cria tabelas no banco de dados
- Usa glpi_configs e glpi_profilerights nativos do GLPI
- Cache em sessão (configurável: 30s a 3600s)
- Somente calcula cards habilitados para o perfil
- Click no card abre busca filtrada em nova aba
