<?php

function plugin_dynamicdash_install() {
    global $DB;

    $defaults = [
        'sla_green_threshold'       => '90',
        'sla_yellow_threshold'      => '75',
        'reopened_green_threshold'   => '5',
        'reopened_yellow_threshold'  => '15',
        'tma_green_threshold'       => '30',
        'tma_yellow_threshold'      => '120',
        'tms_green_threshold'       => '240',
        'tms_yellow_threshold'      => '960',
        'csat_green_threshold'      => '80',
        'csat_yellow_threshold'     => '60',
        'aging_green_threshold'     => '5',
        'aging_yellow_threshold'    => '15',
        'cache_ttl_seconds'         => '300',
    ];

    $existing = Config::getConfigurationValues('plugin:dynamicdash');
    $to_set = [];
    foreach ($defaults as $key => $value) {
        if (!isset($existing[$key])) {
            $to_set[$key] = $value;
        }
    }
    if (!empty($to_set)) {
        Config::setConfigurationValues('plugin:dynamicdash', $to_set);
    }

    $rights = [
        'plugin_dynamicdash_card_open',
        'plugin_dynamicdash_card_overdue',
        'plugin_dynamicdash_card_pending',
        'plugin_dynamicdash_card_new',
        'plugin_dynamicdash_card_sla',
        'plugin_dynamicdash_card_reopened',
        'plugin_dynamicdash_card_tma',
        'plugin_dynamicdash_card_tms',
        'plugin_dynamicdash_card_csat',
        'plugin_dynamicdash_card_aging',
    ];

    ProfileRight::addProfileRights($rights);

    $core_rights = [
        'plugin_dynamicdash_card_open',
        'plugin_dynamicdash_card_overdue',
        'plugin_dynamicdash_card_pending',
        'plugin_dynamicdash_card_new',
        'plugin_dynamicdash_card_sla',
    ];

    $profiles_with_ticket = $DB->request([
        'SELECT' => ['profiles_id'],
        'FROM'   => 'glpi_profilerights',
        'WHERE'  => [
            'name'   => 'ticket',
            'rights' => ['>', 0],
        ],
    ]);

    foreach ($profiles_with_ticket as $row) {
        $pid = $row['profiles_id'];
        foreach ($core_rights as $right_name) {
            $current = $DB->request([
                'FROM'  => 'glpi_profilerights',
                'WHERE' => [
                    'profiles_id' => $pid,
                    'name'        => $right_name,
                    'rights'      => ['>', 0],
                ],
            ]);
            if (count($current) === 0) {
                $DB->update(
                    'glpi_profilerights',
                    ['rights' => READ],
                    [
                        'profiles_id' => $pid,
                        'name'        => $right_name,
                    ]
                );
            }
        }
    }

    return true;
}

function plugin_dynamicdash_uninstall() {

    Config::deleteConfigurationValues('plugin:dynamicdash');

    ProfileRight::deleteProfileRights([
        'plugin_dynamicdash_card_open',
        'plugin_dynamicdash_card_overdue',
        'plugin_dynamicdash_card_pending',
        'plugin_dynamicdash_card_new',
        'plugin_dynamicdash_card_sla',
        'plugin_dynamicdash_card_reopened',
        'plugin_dynamicdash_card_tma',
        'plugin_dynamicdash_card_tms',
        'plugin_dynamicdash_card_csat',
        'plugin_dynamicdash_card_aging',
    ]);

    return true;
}
