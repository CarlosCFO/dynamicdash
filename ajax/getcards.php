<?php

include(__DIR__ . '/../../../inc/includes.php');

Session::checkLoginUser();

// Invalidar cache se refresh manual
if (isset($_GET['_nocache'])) {
    unset($_SESSION['plugin_dynamicdash_cache']);
}

$debug = isset($_GET['debug']) && $_GET['debug'] == '1';

if ($debug && Session::haveRight('config', UPDATE)) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<h2>Dynamic Dashboard - Debug v4</h2>";

    echo "<h3>1. Usuario</h3><pre>";
    echo "User ID: " . Session::getLoginUserID() . "\n";
    echo "Profile: " . ($_SESSION['glpiactiveprofile']['name'] ?? 'N/A') . "\n";
    echo "</pre>";

    echo "<h3>2. GET criteria</h3><pre>";
    $criteria = $_GET['criteria'] ?? [];
    if (!is_array($criteria)) $criteria = [];
    print_r($criteria);
    echo "</pre>";

    echo "<h3>3. Cards ativos</h3><pre>";
    $active = PluginDynamicdashConfig::getActiveCards();
    echo implode(', ', $active) . "\n";
    echo "</pre>";

    echo "<h3>4. Resultado</h3><pre>";
    $is_deleted = (int)($_GET['is_deleted'] ?? 0);
    $cards = PluginDynamicdashDashboard::getCards($criteria, $is_deleted);
    print_r($cards);
    echo "</pre>";

    echo "<h3>5. Entidades</h3><pre>";
    $entities = $_SESSION['glpiactiveentities'] ?? [];
    echo "Entidades: " . implode(', ', $entities) . "\n";
    echo "</pre>";

    exit;
}

// Modo normal
$criteria = $_GET['criteria'] ?? [];
if (!is_array($criteria)) {
    $criteria = [];
}
$is_deleted = (int)($_GET['is_deleted'] ?? 0);

$cards = PluginDynamicdashDashboard::getCards($criteria, $is_deleted);

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => true,
    'cards'   => $cards,
    'ts'      => time(),
], JSON_UNESCAPED_UNICODE);
