<?php
/**
 * Dynamic Dashboard - Micro-dashboard dinâmico para chamados GLPI
 *
 * @author    Carlos Alberto Correa Filho - IPT.br
 * @license   GPLv3+
 * @link      https://ipt.br
 * @version   1.0.0
 * @date      24/03/2026
 */

define('PLUGIN_DYNAMICDASH_VERSION', '1.0.0');

function plugin_init_dynamicdash() {
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['dynamicdash'] = true;

    // Só carrega recursos se o plugin estiver ativo
    $plugin = new Plugin();
    if (!$plugin->isActivated('dynamicdash')) {
        return;
    }

    // ← NOVO: Registrar classe de perfil com aba no Profile
    Plugin::registerClass(
        'PluginDynamicdashProfile',
        ['addtabon' => ['Profile']]
    );

    // CSS e JS injetados
    $PLUGIN_HOOKS['add_css']['dynamicdash']       = 'css/dynamicdash.css';
    $PLUGIN_HOOKS['add_javascript']['dynamicdash'] = 'js/dynamicdash.js';

    // Link de configuração no menu de plugins
    if (Session::haveRight('config', UPDATE)) {
        $PLUGIN_HOOKS['config_page']['dynamicdash'] = 'front/config.form.php';
    }
}

function plugin_version_dynamicdash() {
    return [
        'name'           => 'Dynamic Dashboard',
        'version'        => PLUGIN_DYNAMICDASH_VERSION,
        'author'         => 'Carlos Alberto Correa Filho - IPT.br',
        'license'        => 'GPLv3+',
        'homepage'       => 'https://ipt.br',
        'requirements'   => [
            'glpi' => [
                'min' => '10.0',
                'max' => '10.1',
            ],
            'php' => [
                'min' => '8.1',
            ],
        ],
    ];
}

function plugin_dynamicdash_check_prerequisites() {
    return true;
}

function plugin_dynamicdash_check_config($verbose = false) {
    return true;
}
