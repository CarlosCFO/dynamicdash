<?php

include(__DIR__ . '/../../../inc/includes.php');

Session::checkLoginUser();

$debug = isset($_GET['debug']) && $_GET['debug'] == '1';

if ($debug && Session::haveRight('config', UPDATE)) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<h2>Dynamic Dashboard - Debug</h2>";

    echo "<h3>1. Usuario logado</h3><pre>";
    echo "User ID: " . Session::getLoginUserID() . "\n";
    echo "Profile: " . ($_SESSION['glpiactiveprofile']['name'] ?? 'N/A') . "\n";
    echo "</pre>";

    echo "<h3>2. GET raw</h3><pre>";
    print_r($_GET);
    echo "</pre>";

    echo "<h3>3. Criteria extraido</h3><pre>";
    $criteria = $_GET['criteria'] ?? [];
    print_r($criteria);
    echo "</pre>";

    echo "<h3>4. Direitos</h3><pre>";
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
    foreach ($all_rights as $r) {
        $has = Session::haveRight($r, READ) ? 'SIM' : 'NAO';
        echo "$r: $has\n";
    }
    echo "</pre>";

    echo "<h3>5. Cards ativos</h3><pre>";
    $active = PluginDynamicdashConfig::getActiveCards();
    echo implode(', ', $active) . "\n";
    echo "</pre>";

    if (!empty($active)) {
        echo "<h3>6. Resultado dos cards</h3><pre>";
        $cards = PluginDynamicdashDashboard::getCards($criteria);
        print_r($cards);
        echo "</pre>";
    }

    exit;
}

$criteria = $_GET['criteria'] ?? [];
if (!is_array($criteria)) {
    $criteria = [];
}

$cards = PluginDynamicdashDashboard::getCards($criteria);

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => true,
    'cards'   => $cards,
    'ts'      => time(),
], JSON_UNESCAPED_UNICODE);
