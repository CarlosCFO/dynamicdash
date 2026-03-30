<?php

class PluginDynamicdashConfig extends CommonDBTM {

    static $rightname = 'config';

    static function getConfig(): array {
        return Config::getConfigurationValues('plugin:dynamicdash');
    }

    static function setConfig(array $values): void {
        Config::setConfigurationValues('plugin:dynamicdash', $values);
    }

    static function getThresholds(string $card): array {
        $config = self::getConfig();
        return [
            'green'  => (int)($config[$card . '_green_threshold'] ?? 90),
            'yellow' => (int)($config[$card . '_yellow_threshold'] ?? 75),
        ];
    }

    static function getState(float $value, string $card, bool $inverted = false): string {
        $t = self::getThresholds($card);

        if ($inverted) {
            if ($value <= $t['green'])  return 'positive';
            if ($value <= $t['yellow']) return 'warning';
            return 'critical';
        }

        if ($value >= $t['green'])  return 'positive';
        if ($value >= $t['yellow']) return 'warning';
        return 'critical';
    }

    static function getActiveCards(): array {
        $cards = [];
        $map = [
            'open'     => 'plugin_dynamicdash_card_open',
            'overdue'  => 'plugin_dynamicdash_card_overdue',
            'pending'  => 'plugin_dynamicdash_card_pending',
            'new'      => 'plugin_dynamicdash_card_new',
            'sla'      => 'plugin_dynamicdash_card_sla',
            'reopened' => 'plugin_dynamicdash_card_reopened',
            'tma'      => 'plugin_dynamicdash_card_tma',
            'tms'      => 'plugin_dynamicdash_card_tms',
            'csat'     => 'plugin_dynamicdash_card_csat',
            'aging'    => 'plugin_dynamicdash_card_aging',
        ];

        foreach ($map as $card => $right) {
            if (Session::haveRight($right, READ)) {
                $cards[] = $card;
            }
        }

        return $cards;
    }

    static function getCacheTTL(): int {
        $config = self::getConfig();
        return (int)($config['cache_ttl_seconds'] ?? 300);
    }

    function showConfigForm(): void {
        $config = self::getConfig();

        echo "<div class='center'>";
        echo "<form method='post' action='" . htmlspecialchars($_SERVER['REQUEST_URI']) . "'>";
        echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);

        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='headerRow'><th colspan='2'>Dynamic Dashboard</th></tr>";

        echo "<tr class='headerRow'><th colspan='2'>SLA (% no prazo)</th></tr>";
        self::renderRow($config, 'sla_green_threshold', 'Limite verde (%)', 'tab_bg_1');
        self::renderRow($config, 'sla_yellow_threshold', 'Limite amarelo (%)', 'tab_bg_2');

        echo "<tr class='headerRow'><th colspan='2'>Reabertos (%)</th></tr>";
        self::renderRow($config, 'reopened_green_threshold', 'Limite verde (%)', 'tab_bg_1');
        self::renderRow($config, 'reopened_yellow_threshold', 'Limite amarelo (%)', 'tab_bg_2');

        echo "<tr class='headerRow'><th colspan='2'>TMA - Tempo Medio Atendimento (minutos)</th></tr>";
        self::renderRow($config, 'tma_green_threshold', 'Limite verde (min)', 'tab_bg_1');
        self::renderRow($config, 'tma_yellow_threshold', 'Limite amarelo (min)', 'tab_bg_2');

        echo "<tr class='headerRow'><th colspan='2'>TMS - Tempo Medio Solucao (minutos)</th></tr>";
        self::renderRow($config, 'tms_green_threshold', 'Limite verde (min)', 'tab_bg_1');
        self::renderRow($config, 'tms_yellow_threshold', 'Limite amarelo (min)', 'tab_bg_2');

        echo "<tr class='headerRow'><th colspan='2'>CSAT - Satisfacao (% equivalente 0-100)</th></tr>";
        self::renderRow($config, 'csat_green_threshold', 'Limite verde (%)', 'tab_bg_1');
        self::renderRow($config, 'csat_yellow_threshold', 'Limite amarelo (%)', 'tab_bg_2');

        echo "<tr class='headerRow'><th colspan='2'>Aging - Idade Backlog (dias)</th></tr>";
        self::renderRow($config, 'aging_green_threshold', 'Limite verde (dias)', 'tab_bg_1');
        self::renderRow($config, 'aging_yellow_threshold', 'Limite amarelo (dias)', 'tab_bg_2');

        echo "<tr class='headerRow'><th colspan='2'>Performance</th></tr>";
        self::renderRow($config, 'cache_ttl_seconds', 'Cache (segundos)', 'tab_bg_1');

        echo "<tr class='tab_bg_2'><td colspan='2' class='center'>";
        echo "<input type='submit' name='update' value='Salvar' class='btn btn-primary'>";
        echo "</td></tr>";

        echo "</table>";
        Html::closeForm();
        echo "</div>";
    }

    private static function renderRow(array $config, string $name, string $label, string $bg): void {
        $value = htmlspecialchars($config[$name] ?? '0');
        echo "<tr class='$bg'>";
        echo "<td>$label</td>";
        echo "<td><input type='number' name='$name' value='$value' min='0' max='99999' style='width:100px'></td>";
        echo "</tr>";
    }
}
