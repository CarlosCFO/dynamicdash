<?php

include(__DIR__ . '/../../../inc/includes.php');

Session::checkRight('config', UPDATE);

if (isset($_POST['update'])) {
    $fields = [
        'sla_green_threshold',
        'sla_yellow_threshold',
        'reopened_green_threshold',
        'reopened_yellow_threshold',
        'tma_green_threshold',
        'tma_yellow_threshold',
        'tms_green_threshold',
        'tms_yellow_threshold',
        'csat_green_threshold',
        'csat_yellow_threshold',
        'aging_green_threshold',
        'aging_yellow_threshold',
        'cache_ttl_seconds',
    ];

    $values = [];
    foreach ($fields as $f) {
        if (isset($_POST[$f])) {
            $values[$f] = (string)(int)$_POST[$f];
        }
    }

    PluginDynamicdashConfig::setConfig($values);
    Session::addMessageAfterRedirect('Settings saved.', true, INFO);
    Html::back();
}

Html::header('Dynamic Dashboard', $_SERVER['PHP_SELF'], 'config', 'plugins');

$config = new PluginDynamicdashConfig();
$config->showConfigForm();

Html::footer();
