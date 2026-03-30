<?php

class PluginDynamicdashDashboard {

    static function getCards(array $criteria = [], int $is_deleted = 0): array {

        $active = PluginDynamicdashConfig::getActiveCards();
        if (empty($active)) {
            return [];
        }

        $cache_key = self::buildCacheKey($criteria, $is_deleted);
        $cache_ttl = PluginDynamicdashConfig::getCacheTTL();
        $cached    = $_SESSION['plugin_dynamicdash_cache'] ?? null;

        if ($cached
            && ($cached['key'] === $cache_key)
            && (time() - $cached['time'] < $cache_ttl)) {
            return $cached['data'];
        }

        $cards = [];

        if (in_array('open', $active))    $cards['open']    = self::safeCall('cardOpen', $criteria, $is_deleted);
        if (in_array('pending', $active)) $cards['pending'] = self::safeCall('cardPending', $criteria, $is_deleted);
        if (in_array('new', $active))     $cards['new']     = self::safeCall('cardNew', $criteria, $is_deleted);

        $where = self::buildWhereFromCriteria($criteria);

        if (in_array('overdue', $active))  $cards['overdue']  = self::safeCall('cardOverdue', $where);
        if (in_array('sla', $active))      $cards['sla']      = self::safeCall('cardSla', $where);
        if (in_array('reopened', $active)) $cards['reopened'] = self::safeCall('cardReopened', $where);
        if (in_array('tma', $active))      $cards['tma']      = self::safeCall('cardTma', $where);
        if (in_array('tms', $active))      $cards['tms']      = self::safeCall('cardTms', $where);
        if (in_array('csat', $active))     $cards['csat']     = self::safeCall('cardCsat', $where);
        if (in_array('aging', $active))    $cards['aging']    = self::safeCall('cardAging', $where);

        $cards = array_filter($cards);

        $_SESSION['plugin_dynamicdash_cache'] = [
            'key'  => $cache_key,
            'time' => time(),
            'data' => $cards,
        ];

        return $cards;
    }

    private static function safeCall(string $method, ...$args): ?array {
        try {
            return self::$method(...$args);
        } catch (\Throwable $e) {
            Toolbox::logError("DynamicDash error in $method: " . $e->getMessage());
            return null;
        }
    }

    private static function buildCacheKey(array $criteria, int $is_deleted): string {
        return md5(
            Session::getLoginUserID()
            . '|' . serialize($criteria)
            . '|' . $is_deleted
            . '|' . ($_SESSION['glpiactive_entity'] ?? 0)
        );
    }

    private static function searchCount(array $criteria, int $is_deleted = 0): int {
        $params = [
            'criteria'   => $criteria,
            'is_deleted' => $is_deleted,
            'start'      => 0,
            'list_limit' => 1,
            'sort'       => [1],
            'order'      => ['ASC'],
        ];

        $data = Search::prepareDatasForSearch('Ticket', $params);
        Search::constructSQL($data);
        Search::constructData($data, true);

        return (int)($data['data']['totalcount'] ?? 0);
    }

    private static function buildWhereFromCriteria(array $criteria): string {
        $conditions = [];
        $conditions[] = "glpi_tickets.is_deleted = 0";

        $has_entity_filter = false;

        if (!empty($criteria)) {
            $grouped = self::groupCriteria($criteria);

            foreach ($grouped as $crit) {
                if (((int)($crit['field'] ?? 0)) === 8) {
                    $has_entity_filter = true;
                    break;
                }
            }

            foreach ($grouped as $crit) {
                $field      = $crit['field'] ?? '';
                $searchtype = $crit['searchtype'] ?? 'equals';
                $value      = $crit['value'] ?? '';

                if ($field === '' || $value === '') {
                    continue;
                }

                $safe_value = addslashes($value);

                switch ((int)$field) {
                    case 12:
                        if ($value === 'notclosed') {
                            $conditions[] = "glpi_tickets.status IN (1,2,3,4)";
                        } elseif ($value === 'notold') {
                            $conditions[] = "glpi_tickets.status IN (1,2,3,4,5)";
                        } elseif ($value === 'old') {
                            $conditions[] = "glpi_tickets.status = 6";
                        } elseif ($value === 'process') {
                            $conditions[] = "glpi_tickets.status IN (2,3)";
                        } elseif ($value === 'waiting') {
                            $conditions[] = "glpi_tickets.status = 4";
                        } elseif ($value === 'solved') {
                            $conditions[] = "glpi_tickets.status = 5";
                        } elseif ($value === 'closed') {
                            $conditions[] = "glpi_tickets.status = 6";
                        } elseif ($value === 'all') {
                            // sem filtro
                        } elseif (is_numeric($value)) {
                            $conditions[] = "glpi_tickets.status = " . (int)$value;
                        }
                        break;
                    case 7:
                        if (is_numeric($value)) {
                            $sons = self::getSons('glpi_itilcategories', (int)$value);
                            $conditions[] = "glpi_tickets.itilcategories_id IN ($sons)";
                        }
                        break;
                    case 8:
                        if (is_numeric($value)) {
                            $sons = self::getSons('glpi_entities', (int)$value);
                            $conditions[] = "glpi_tickets.entities_id IN ($sons)";
                        }
                        break;
                    case 4:
                        if (is_numeric($value)) {
                            $conditions[] = "glpi_tickets.id IN (SELECT tickets_id FROM glpi_tickets_users WHERE type = 2 AND users_id = " . (int)$value . ")";
                        }
                        break;
                    case 5:
                        if (is_numeric($value)) {
                            $conditions[] = "glpi_tickets.id IN (SELECT tickets_id FROM glpi_tickets_users WHERE type = 1 AND users_id = " . (int)$value . ")";
                        }
                        break;
                    case 71:
                        if (is_numeric($value)) {
                            $sons = self::getSons('glpi_groups', (int)$value);
                            $conditions[] = "glpi_tickets.id IN (SELECT tickets_id FROM glpi_groups_tickets WHERE type = 2 AND groups_id IN ($sons))";
                        }
                        break;
                    case 65:
                        if (is_numeric($value)) {
                            $sons = self::getSons('glpi_groups', (int)$value);
                            $conditions[] = "glpi_tickets.id IN (SELECT tickets_id FROM glpi_groups_tickets WHERE type = 1 AND groups_id IN ($sons))";
                        }
                        break;
                    case 10:
                        if (is_numeric($value)) {
                            $conditions[] = "glpi_tickets.urgency = " . (int)$value;
                        }
                        break;
                    case 11:
                        if (is_numeric($value)) {
                            $conditions[] = "glpi_tickets.impact = " . (int)$value;
                        }
                        break;
                    case 3:
                        if (is_numeric($value)) {
                            $conditions[] = "glpi_tickets.priority = " . (int)$value;
                        }
                        break;
                    case 14:
                        if (is_numeric($value)) {
                            $conditions[] = "glpi_tickets.type = " . (int)$value;
                        }
                        break;
                    case 15:
                        if ($searchtype === 'morethan') {
                            $conditions[] = "glpi_tickets.date >= '$safe_value'";
                        } elseif ($searchtype === 'lessthan') {
                            $conditions[] = "glpi_tickets.date <= '$safe_value'";
                        }
                        break;
                    case 16:
                        if ($searchtype === 'morethan') {
                            $conditions[] = "glpi_tickets.closedate >= '$safe_value'";
                        } elseif ($searchtype === 'lessthan') {
                            $conditions[] = "glpi_tickets.closedate <= '$safe_value'";
                        }
                        break;
                    case 18:
                        if ($searchtype === 'morethan') {
                            $conditions[] = "glpi_tickets.solvedate >= '$safe_value'";
                        } elseif ($searchtype === 'lessthan') {
                            $conditions[] = "glpi_tickets.solvedate <= '$safe_value'";
                        }
                        break;
                    case 1:
                        if ($searchtype === 'contains') {
                            $conditions[] = "glpi_tickets.name LIKE '%$safe_value%'";
                        }
                        break;
                    case 21:
                        if ($searchtype === 'contains') {
                            $conditions[] = "glpi_tickets.content LIKE '%$safe_value%'";
                        }
                        break;
                }
            }
        }

        if (!$has_entity_filter) {
            $entities = $_SESSION['glpiactiveentities'] ?? [0];
            $ent_list = implode(',', array_map('intval', $entities));
            $conditions[] = "glpi_tickets.entities_id IN ($ent_list)";
        }

        return implode(' AND ', $conditions);
    }

    private static function getSons(string $table, int $id): string {
        if (function_exists('getSonsOf')) {
            $sons = getSonsOf($table, $id);
            if (!empty($sons)) {
                return implode(',', array_map('intval', $sons));
            }
        }
        return (string)$id;
    }

    private static function groupCriteria(array $criteria): array {
        $grouped = [];
        foreach ($criteria as $key => $value) {
            if (is_array($value) && isset($value['field'])) {
                $grouped[] = $value;
            } elseif (is_string($key) && preg_match('/criteria\[(\d+)\]\[(\w+)\]/', $key, $m)) {
                $grouped[$m[1]][$m[2]] = $value;
            }
        }
        return $grouped;
    }

    private static function countQuery(string $sql): int {
        global $DB;
        $result = $DB->query($sql);
        if ($result && $row = $result->fetch_assoc()) {
            return (int)$row['total'];
        }
        return 0;
    }

    private static function formatMinutes(float $minutes): string {
        if ($minutes < 0) return 'N/A';
        $minutes = round($minutes);
        if ($minutes < 60) return $minutes . 'm';
        if ($minutes < 1440) {
            $h = floor($minutes / 60);
            $m = $minutes % 60;
            return $h . 'h' . ($m > 0 ? $m . 'm' : '');
        }
        $d = floor($minutes / 1440);
        $h = floor(($minutes % 1440) / 60);
        return $d . 'd' . ($h > 0 ? $h . 'h' : '');
    }

    private static function cardOpen(array $criteria, int $is_deleted): array {
        $count = self::searchCount($criteria, $is_deleted);
        return [
            'value' => $count,
            'label' => 'Abertos',
            'state' => 'info',
            'icon'  => 'info',
        ];
    }

    private static function cardPending(array $criteria, int $is_deleted): array {
        $c = $criteria;
        $c[] = ['link' => 'AND', 'field' => 12, 'searchtype' => 'equals', 'value' => 4];
        $count = self::searchCount($c, $is_deleted);
        return [
            'value' => $count,
            'label' => 'Pendentes',
            'state' => 'warning',
            'icon'  => 'hourglass',
        ];
    }

    private static function cardNew(array $criteria, int $is_deleted): array {
        $c = $criteria;
        $c[] = ['link' => 'AND', 'field' => 12, 'searchtype' => 'equals', 'value' => 1];
        $count = self::searchCount($c, $is_deleted);
        return [
            'value' => $count,
            'label' => 'Novos',
            'state' => 'neutral',
            'icon'  => 'circle',
        ];
    }

    private static function cardOverdue(string $where): array {
        $count = self::countQuery(
            "SELECT COUNT(*) AS total FROM glpi_tickets
             WHERE $where
             AND glpi_tickets.status IN (1,2,3,4)
             AND glpi_tickets.time_to_resolve IS NOT NULL
             AND glpi_tickets.time_to_resolve < NOW()"
        );
        return [
            'value' => $count,
            'label' => 'Atrasados',
            'state' => $count > 0 ? 'critical' : 'positive',
            'icon'  => $count > 0 ? 'blocked' : 'check',
        ];
    }

    private static function cardSla(string $where): array {
        global $DB;
        $sql = "SELECT COUNT(*) AS total_com_sla,
                SUM(CASE WHEN glpi_tickets.solvedate <= glpi_tickets.time_to_resolve
                    THEN 1 ELSE 0 END) AS dentro_prazo
                FROM glpi_tickets
                WHERE $where
                AND glpi_tickets.time_to_resolve IS NOT NULL
                AND glpi_tickets.solvedate IS NOT NULL";
        $total = 0;
        $dentro = 0;
        $result = $DB->query($sql);
        if ($result && $row = $result->fetch_assoc()) {
            $total  = (int)$row['total_com_sla'];
            $dentro = (int)$row['dentro_prazo'];
        }
        $pct = $total > 0 ? round(($dentro / $total) * 100) : 0;
        return [
            'value'  => $total > 0 ? $pct . '%' : 'N/A',
            'label'  => 'No prazo',
            'state'  => $total > 0 ? PluginDynamicdashConfig::getState($pct, 'sla') : 'neutral',
            'icon'   => $total > 0 ? ($pct >= 75 ? 'check' : 'warning') : 'circle',
            'detail' => "$dentro/$total",
        ];
    }

    private static function cardReopened(string $where): array {
        global $DB;
        $sql = "SELECT COUNT(DISTINCT l.items_id) AS total
                FROM glpi_logs l
                INNER JOIN glpi_tickets ON glpi_tickets.id = l.items_id
                WHERE l.itemtype = 'Ticket'
                AND l.id_search_option = 12
                AND l.old_value LIKE '%5%'
                AND l.new_value IN ('1','2','3','4')
                AND $where";
        $count = 0;
        $result = $DB->query($sql);
        if ($result && $row = $result->fetch_assoc()) {
            $count = (int)$row['total'];
        }
        $total = self::countQuery(
            "SELECT COUNT(*) AS total FROM glpi_tickets
             WHERE $where AND glpi_tickets.status IN (1,2,3,4,5,6)"
        );
        $pct = $total > 0 ? round(($count / $total) * 100) : 0;
        return [
            'value'  => $pct . '%',
            'label'  => 'Reabertos',
            'state'  => PluginDynamicdashConfig::getState($pct, 'reopened', true),
            'icon'   => $pct > 15 ? 'warning' : ($pct > 5 ? 'hourglass' : 'check'),
            'detail' => "$count de $total",
        ];
    }

    private static function cardTma(string $where): array {
        global $DB;
        $sql = "SELECT AVG(TIMESTAMPDIFF(MINUTE, glpi_tickets.date,
                    fa.first_assign_date)) AS tma_minutes, COUNT(*) AS total
                FROM glpi_tickets
                INNER JOIN (
                    SELECT l.items_id AS ticket_id,
                           MIN(l.date_mod) AS first_assign_date
                    FROM glpi_logs l
                    WHERE l.itemtype = 'Ticket'
                    AND l.id_search_option = 5
                    GROUP BY l.items_id
                ) AS fa ON fa.ticket_id = glpi_tickets.id
                WHERE $where AND glpi_tickets.status IN (2,3,4,5,6)";
        $tma = 0;
        $total = 0;
        $result = $DB->query($sql);
        if ($result && $row = $result->fetch_assoc()) {
            $tma   = (float)($row['tma_minutes'] ?? 0);
            $total = (int)($row['total'] ?? 0);
        }
        $formatted = $total > 0 ? self::formatMinutes($tma) : 'N/A';
        return [
            'value'  => $formatted,
            'label'  => 'TMA',
            'state'  => $total > 0 ? PluginDynamicdashConfig::getState($tma, 'tma', true) : 'neutral',
            'icon'   => 'stopwatch',
            'detail' => "Tempo medio atendimento ($total tickets)",
        ];
    }

    private static function cardTms(string $where): array {
        global $DB;
        $sql = "SELECT AVG(TIMESTAMPDIFF(MINUTE, fa.first_assign_date,
                    glpi_tickets.solvedate)) AS tms_minutes, COUNT(*) AS total
                FROM glpi_tickets
                INNER JOIN (
                    SELECT l.items_id AS ticket_id,
                           MIN(l.date_mod) AS first_assign_date
                    FROM glpi_logs l
                    WHERE l.itemtype = 'Ticket'
                    AND l.id_search_option = 5
                    GROUP BY l.items_id
                ) AS fa ON fa.ticket_id = glpi_tickets.id
                WHERE $where
                AND glpi_tickets.solvedate IS NOT NULL
                AND glpi_tickets.status IN (5,6)";
        $tms = 0;
        $total = 0;
        $result = $DB->query($sql);
        if ($result && $row = $result->fetch_assoc()) {
            $tms   = (float)($row['tms_minutes'] ?? 0);
            $total = (int)($row['total'] ?? 0);
        }
        $formatted = $total > 0 ? self::formatMinutes($tms) : 'N/A';
        return [
            'value'  => $formatted,
            'label'  => 'TMS',
            'state'  => $total > 0 ? PluginDynamicdashConfig::getState($tms, 'tms', true) : 'neutral',
            'icon'   => 'wrench',
            'detail' => "Tempo medio solucao ($total tickets)",
        ];
    }

    private static function cardCsat(string $where): array {
        global $DB;
        $sql = "SELECT ROUND(AVG(s.satisfaction), 1) AS nota_media,
                    COUNT(*) AS total_respostas
                FROM glpi_ticketsatisfactions s
                INNER JOIN glpi_tickets ON glpi_tickets.id = s.tickets_id
                WHERE $where
                AND s.satisfaction IS NOT NULL
                AND s.date_answered IS NOT NULL";
        $nota = 0;
        $total = 0;
        $result = $DB->query($sql);
        if ($result && $row = $result->fetch_assoc()) {
            $nota  = (float)($row['nota_media'] ?? 0);
            $total = (int)($row['total_respostas'] ?? 0);
        }
        $formatted = $total >= 5 ? "$nota" : 'N/A';
        $pct_equiv = $nota > 0 ? ($nota / 5) * 100 : 0;
        return [
            'value'  => $formatted,
            'label'  => 'CSAT',
            'state'  => $total >= 5 ? PluginDynamicdashConfig::getState($pct_equiv, 'csat') : 'neutral',
            'icon'   => 'star',
            'detail' => "$total respostas",
        ];
    }

    private static function cardAging(string $where): array {
        global $DB;
        $sql = "SELECT ROUND(AVG(DATEDIFF(NOW(), glpi_tickets.date)), 0) AS dias_medio,
                    COUNT(*) AS total_abertos
                FROM glpi_tickets
                WHERE $where AND glpi_tickets.status IN (1,2,3,4)";
        $dias = 0;
        $total = 0;
        $result = $DB->query($sql);
        if ($result && $row = $result->fetch_assoc()) {
            $dias  = (int)($row['dias_medio'] ?? 0);
            $total = (int)($row['total_abertos'] ?? 0);
        }
        $formatted = $total > 0 ? $dias . 'd' : 'N/A';
        return [
            'value'  => $formatted,
            'label'  => 'Aging',
            'state'  => $total > 0 ? PluginDynamicdashConfig::getState($dias, 'aging', true) : 'neutral',
            'icon'   => 'calendar',
            'detail' => "$total chamados abertos",
        ];
    }
}
