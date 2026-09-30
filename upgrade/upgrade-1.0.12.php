<?php
/**
 * ShortCodes - Moteur de shortcodes pour PrestaShop
 *
 * @author    ZM40 — Nicolas Michaud (Magic Garden)
 * @copyright 2026 Nicolas Michaud — ZM40 / Magic Garden
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License version 3.0
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * SC-01 : on ne parse plus tout le HTML de la page, seulement les contenus saisis par les
 * employés : hooks filter* du cœur, et gabarits des modules autorisés (marqueurs signés,
 * rendus par actionOutputHTMLBefore, qui reste inscrit).
 */
function upgrade_module_1_0_12($module)
{
    if (Configuration::get('MGSC_TEMPLATE_MODULES') === false) {
        Configuration::updateValue('MGSC_TEMPLATE_MODULES', ShortCodes::DEFAULT_TEMPLATE_MODULES);
    }
    // Gabarits en cache écrits sans marqueurs : les shortcodes y resteraient en clair.
    Tools::clearSmartyCache();

    return $module->registerHook(ShortCodes::CONTENT_HOOKS)
        && $module->registerHook('actionOutputHTMLBefore');
}
