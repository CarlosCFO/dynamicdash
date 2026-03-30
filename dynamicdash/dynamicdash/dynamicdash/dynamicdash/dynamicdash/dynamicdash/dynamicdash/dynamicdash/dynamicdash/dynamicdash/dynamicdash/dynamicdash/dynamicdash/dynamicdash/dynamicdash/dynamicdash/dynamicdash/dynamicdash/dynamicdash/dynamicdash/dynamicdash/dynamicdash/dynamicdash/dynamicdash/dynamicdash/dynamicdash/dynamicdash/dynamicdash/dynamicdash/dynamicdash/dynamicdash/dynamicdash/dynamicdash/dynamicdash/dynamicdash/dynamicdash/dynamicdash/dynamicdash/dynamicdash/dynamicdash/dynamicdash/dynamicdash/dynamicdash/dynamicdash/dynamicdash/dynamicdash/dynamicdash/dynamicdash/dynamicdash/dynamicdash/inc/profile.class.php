<?php

class PluginDynamicdashProfile extends CommonDBTM {

    static $rightname = 'profile';

    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if (!($item instanceof Profile)) {
            return '';
        }
        if (!Session::haveRight('profile', READ)) {
            return '';
        }
        return 'Dynamic Dashboard';
    }

    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if (!($item instanceof Profile)) {
            return false;
        }
        self::showProfileForm($item->getID());
        return true;
    }

    static function showProfileForm(int $profile_id): void {
        global $DB, $CFG_GLPI;

        if (!Session::haveRight('profile', UPDATE)) {
            echo "<div class='center'><br>";
            echo "<p>Sem permissao para editar perfis.</p>";
            echo "</div>";
            return;
        }

        $cards = [
            'plugin_dynamicdash_card_open'     => 'Abertos',
            'plugin_dynamicdash_card_overdue'  => 'Atrasados',
            'plugin_dynamicdash_card_pending'  => 'Pendentes',
            'plugin_dynamicdash_card_new'      => 'Novos',
            'plugin_dynamicdash_card_sla'      => 'No prazo (SLA)',
            'plugin_dynamicdash_card_reopened' => 'Reabertos',
            'plugin_dynamicdash_card_tma'      => 'TMA - Tempo Medio Atendimento',
            'plugin_dynamicdash_card_tms'      => 'TMS - Tempo Medio Solucao',
            'plugin_dynamicdash_card_csat'     => 'CSAT - Satisfacao',
            'plugin_dynamicdash_card_aging'    => 'Aging - Idade do Backlog',
        ];

        $current_rights = [];
        $iterator = $DB->request([
            'FROM'  => 'glpi_profilerights',
            'WHERE' => [
                'profiles_id' => $profile_id,
                'name'        => array_keys($cards),
            ],
        ]);
        foreach ($iterator as $row) {
            $current_rights[$row['name']] = (int)$row['rights'];
        }

        $root_doc = $CFG_GLPI['root_doc'] ?? '';
        $form_url = $root_doc . '/plugins/dynamicdash/front/profile.form.php';

        echo "<div class='center'>";
        echo "<form method='post' action='" . htmlspecialchars($form_url) . "'>";
        echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
        echo Html::hidden('profiles_id', ['value' => $profile_id]);

        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='headerRow'>";
        echo "<th colspan='2'>Dynamic Dashboard - Cards para este perfil</th>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td colspan='2'><em>Marque os indicadores que este perfil podera ver na tela de chamados.</em></td>";
        echo "</tr>";

        echo "<tr class='headerRow'><th colspan='2'>Cards Core</th></tr>";

        $i = 0;
        foreach ($cards as $right_name => $label) {
            $bg = ($i % 2 === 0) ? 'tab_bg_1' : 'tab_bg_2';
            $checked = (isset($current_rights[$right_name])
                       && ($current_rights[$right_name] & READ))
                       ? 'checked' : '';

            if ($right_name === 'plugin_dynamicdash_card_reopened') {
                echo "<tr class='headerRow'><th colspan='2'>Cards Opcionais</th></tr>";
            }

            echo "<tr class='$bg'>";
            echo "<td style='padding-left:20px;'>$label</td>";
            echo "<td class='center'>";
            echo "<input type='checkbox' name='rights[$right_name]' value='1' $checked>";
            echo "</td>";
            echo "</tr>";
            $i++;
        }

        echo "<tr class='tab_bg_2'>";
        echo "<td colspan='2' class='center'>";
        echo "<input type='submit' name='update' value='Salvar' class='btn btn-primary'>";
        echo "</td>";
        echo "</tr>";

        echo "</table>";
        Html::closeForm();
        echo "</div>";
    }
}
