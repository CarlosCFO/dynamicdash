<?php

include(__DIR__ . '/../../../inc/includes.php');

Session::checkRight('profile', UPDATE);

if (isset($_POST['update']) && isset($_POST['profiles_id'])) {

    global $DB;

    $profile_id = (int)$_POST['profiles_id'];
    $submitted_rights = $_POST['rights'] ?? [];

    $all_rights = [
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

    foreach ($all_rights as $right_name) {
        $value = isset($submitted_rights[$right_name]) ? READ : 0;

        $DB->update(
            'glpi_profilerights',
            ['rights' => $value],
            [
                'profiles_id' => $profile_id,
                'name'        => $right_name,
            ]
        );
    }

    Session::addMessageAfterRedirect('Settings saved.', true, INFO);
}

Html::back();
