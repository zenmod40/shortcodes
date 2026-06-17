<?php
/**
 * ShortCodes - Moteur de shortcodes pour PrestaShop
 * Insère des shortcodes (produits, sliders, descriptions, marques, etc.) dans les
 * contenus CMS et autres zones HTML. Compatible PrestaShop 1.7 / 8 / 9+.
 *
 * @author    ZM40 — Nicolas Michaud (Magic Garden)
 * @copyright 2026 Nicolas Michaud — ZM40 / Magic Garden
 * @license   GPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'shortcodes/lib/zm40/Zm40Common.php';

class ShortCodes extends Module
{
    public function __construct()
    {
        $this->name = 'shortcodes';
        $this->tab = 'advertising_marketing';
        $this->version = '1.0.9';
        $this->author = 'ZM40';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->l('ShortCodes');
        $this->description = $this->l('Insérez des shortcodes (produits, sliders, descriptions, etc.) dans les contenus CMS et autres zones HTML.');
        $this->confirmUninstall = $this->l('Êtes-vous sûr de vouloir désinstaller ShortCodes ?');

        // Lazy include of classes (PS autoload loads classes/ automatically, but ensure availability for CLI/contextless)
        $base = __DIR__ . '/classes/';
        if (is_dir($base)) {
            @require_once $base . 'Support/ShortcodeHelpers.php';
            @require_once $base . 'Core/ShortcodeEngine.php';
            @require_once $base . 'ShortcodeRegistry.php';
            @require_once $base . 'ShortcodeParser.php';
            @require_once $base . 'Shortcodes/ProductShortcode.php';
            @require_once $base . 'Shortcodes/ProductsShortcode.php';
            @require_once $base . 'Shortcodes/ProductDescriptionShortcode.php';
            @require_once $base . 'Shortcodes/LastProductsShortcode.php';
            @require_once $base . 'Shortcodes/CategoryProductsShortcode.php';
        }
    }

    public function install(): bool
    {
        $ok = parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('actionFrontControllerSetMedia')
            && $this->registerHook('actionOutputHTMLBefore')
            && $this->registerHook('displayShortcodesProductCard');
        if ($ok) {
            // Defaults for slider behavior (global). Overrides (HOME/CMS) left unset to fallback to global.
            try {
                // Assets loading (front)
                Configuration::updateValue('MGSC_LOAD_SWIPER_ASSETS', true);
                Configuration::updateValue('MGSC_ASSETS_LOAD_ENABLED', true);
                Configuration::updateValue('MGSC_SLIDER_AUTOPLAY_ENABLED', false);
                Configuration::updateValue('MGSC_SLIDER_SPACE_BETWEEN', 20);
                Configuration::updateValue('MGSC_SLIDER_SPEED', 600);
                Configuration::updateValue('MGSC_SLIDER_AUTO_HEIGHT', false);
                Configuration::updateValue('MGSC_SLIDER_CENTER_ENABLED', false);
                // Default Slides Per View per breakpoint (used when shortcode doesn't override)
                Configuration::updateValue('MGSC_SLIDER_SPV', '1');
                Configuration::updateValue('MGSC_SLIDER_SPV_XL', '4');
                Configuration::updateValue('MGSC_SLIDER_SPV_LG', '4');
                Configuration::updateValue('MGSC_SLIDER_SPV_MD', '3');
                Configuration::updateValue('MGSC_SLIDER_SPV_SM', '2');
                Configuration::updateValue('MGSC_SLIDER_SPV_XS', '1');
                // Center per breakpoint defaults (empty = inherit from base)
                Configuration::updateValue('MGSC_SLIDER_CENTER_ENABLED_XL', '');
                Configuration::updateValue('MGSC_SLIDER_CENTER_ENABLED_LG', '');
                Configuration::updateValue('MGSC_SLIDER_CENTER_ENABLED_MD', '');
                Configuration::updateValue('MGSC_SLIDER_CENTER_ENABLED_SM', '');
                Configuration::updateValue('MGSC_SLIDER_CENTER_ENABLED_XS', '');
                // ZM40 Common — interrupteur réseau (activé par défaut, opt-out)
                Configuration::updateValue('ZM40_NET_ENABLED', 1);
            } catch (\Throwable $e) { /* ignore */ }
        }
        return $ok;
    }

    public function uninstall(): bool
    {
        // Clean configuration keys
        try {
            $keys = [
                'MGSC_LOAD_SWIPER_ASSETS',
                'MGSC_ASSETS_LOAD_ENABLED',
                'MGSC_SLIDER_AUTOPLAY_ENABLED', 'MGSC_SLIDER_SPACE_BETWEEN', 'MGSC_SLIDER_SPEED', 'MGSC_SLIDER_AUTO_HEIGHT', 'MGSC_SLIDER_CENTER_ENABLED',
                'MGSC_SLIDER_AUTOPLAY_ENABLED_HOME', 'MGSC_SLIDER_SPACE_BETWEEN_HOME', 'MGSC_SLIDER_SPEED_HOME', 'MGSC_SLIDER_AUTO_HEIGHT_HOME', 'MGSC_SLIDER_CENTER_ENABLED_HOME',
                'MGSC_SLIDER_AUTOPLAY_ENABLED_CMS', 'MGSC_SLIDER_SPACE_BETWEEN_CMS', 'MGSC_SLIDER_SPEED_CMS', 'MGSC_SLIDER_AUTO_HEIGHT_CMS', 'MGSC_SLIDER_CENTER_ENABLED_CMS',
                'MGSC_SLIDER_SPV', 'MGSC_SLIDER_SPV_XL', 'MGSC_SLIDER_SPV_LG', 'MGSC_SLIDER_SPV_MD', 'MGSC_SLIDER_SPV_SM', 'MGSC_SLIDER_SPV_XS',
                'MGSC_SLIDER_CENTER_ENABLED_XL', 'MGSC_SLIDER_CENTER_ENABLED_LG', 'MGSC_SLIDER_CENTER_ENABLED_MD', 'MGSC_SLIDER_CENTER_ENABLED_SM', 'MGSC_SLIDER_CENTER_ENABLED_XS',
                // ZM40 Common
                'ZM40_NET_ENABLED', 'ZM40_FEED_CACHE', 'ZM40_FEED_CACHE_TS',
                'ZM40_LASTCHECK_SHORTCODES', 'ZM40_LATEST_SHORTCODES',
            ];
            foreach ($keys as $k) { Configuration::deleteByName($k); }
        } catch (\Throwable $e) { /* ignore */ }
        return parent::uninstall();
    }

    public function hookDisplayHeader($params)
    {
        if (!isset($this->context->controller) || $this->context->controller->controller_type !== 'front') {
            return '';
        }
        // Register Smarty plugins (FO only)
        $this->registerSmartyPlugins(false);

        // Register assets conditionally.
        $loadAssets = (bool) Configuration::get('MGSC_ASSETS_LOAD_ENABLED');
        if ($loadAssets && isset($this->context->controller)) {
            $this->context->controller->registerStylesheet(
                $this->name . '-front-css',
                $this->_path . 'views/css/front.css',
                ['media' => 'all', 'priority' => 200]
            );

            // Optionally load Swiper from CDN so Swiper mode is available everywhere
            if ((bool) Configuration::get('MGSC_LOAD_SWIPER_ASSETS')) {
                try {
                    $this->context->controller->registerStylesheet(
                        $this->name . '-swiper-css',
                        'https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css',
                        ['server' => 'remote', 'priority' => 120, 'media' => 'all']
                    );
                } catch (\Throwable $e) {}
                try {
                    $this->context->controller->registerJavascript(
                        $this->name . '-swiper-js',
                        'https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js',
                        ['server' => 'remote', 'position' => 'bottom', 'priority' => 150]
                    );
                } catch (\Throwable $e) {}
            }

            // Front behaviors (slider init, fallbacks)
            $this->context->controller->registerJavascript(
                $this->name . '-front-js',
                $this->_path . 'views/js/front.js',
                ['position' => 'bottom', 'priority' => 200]
            );
        }
    }

    /**
     * PS 1.7/8+: Reliable place to register FO CSS/JS. Some themes may not surface assets registered in displayHeader.
     */
    public function hookActionFrontControllerSetMedia($params)
    {
        if (!isset($this->context->controller) || $this->context->controller->controller_type !== 'front') {
            return;
        }

        if ((bool) Configuration::get('MGSC_ASSETS_LOAD_ENABLED')) {
            $c = $this->context->controller;
            // Primary (PS 1.7/8): register* API
            try {
                $c->registerStylesheet(
                    $this->name . '-front-css',
                    $this->_path . 'views/css/front.css',
                    ['media' => 'all', 'priority' => 200]
                );
            } catch (\Throwable $e) {}
            // Swiper (remote) before our JS so window.Swiper exists
            if ((bool) Configuration::get('MGSC_LOAD_SWIPER_ASSETS')) {
                try {
                    $c->registerStylesheet(
                        $this->name . '-swiper-css',
                        'https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css',
                        ['server' => 'remote', 'priority' => 120, 'media' => 'all']
                    );
                } catch (\Throwable $e) {}
                try {
                    $c->registerJavascript(
                        $this->name . '-swiper-js',
                        'https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js',
                        ['server' => 'remote', 'position' => 'bottom', 'priority' => 150]
                    );
                } catch (\Throwable $e) {}
            }
            try {
                $c->registerJavascript(
                    $this->name . '-front-js',
                    $this->_path . 'views/js/front.js',
                    ['position' => 'bottom', 'priority' => 200]
                );
            } catch (\Throwable $e) {}

            // Compatibility fallback: legacy addCSS/addJS if theme overrides asset stack
            try { if (method_exists($c, 'addCSS')) { $c->addCSS($this->_path . 'views/css/front.css', 'all'); } } catch (\Throwable $e) {}
            // Legacy helpers for remote assets (best effort)
            if ((bool) Configuration::get('MGSC_LOAD_SWIPER_ASSETS')) {
                try { if (method_exists($c, 'addCSS')) { $c->addCSS('https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css', 'all'); } } catch (\Throwable $e) {}
                try { if (method_exists($c, 'addJS')) { $c->addJS('https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js'); } } catch (\Throwable $e) {}
            }
            try { if (method_exists($c, 'addJS')) { $c->addJS($this->_path . 'views/js/front.js'); } } catch (\Throwable $e) {}

            // Diagnostics removed after validation
        }
    }

    public function hookDisplayBackOfficeHeader($params)
    {
        // Intentionally do nothing in BO to avoid interfering with Back Office rendering
        return '';
    }

    /**
     * Custom hook: displayShortcodesProductCard
     * This module exposes the hook for other modules, but does not render anything itself.
     * Keeps templates-only rendering with no PHP fallback output.
     *
     * @param array<string,mixed> $params
     * @return string
     */
    public function hookDisplayShortcodesProductCard($params)
    {
        return '';
    }

    /**
     * Global FO HTML parser: parse shortcodes in final HTML response.
     * Note: This hook provides $params['html'] string by reference in PS 1.7+/8/9.
     * We only act on Front controllers to avoid BO interference.
     *
     * @param array<string,mixed> $params
     * @return void|string
     */
    public function hookActionOutputHTMLBefore(&$params)
    {
        // Safety checks
        if (!isset($this->context->controller) || $this->context->controller->controller_type !== 'front') {
            return '';
        }
        if (!isset($params['html']) || !is_string($params['html'])) {
            return '';
        }
        $html = $params['html'];

        // Micro-guard: avoid work if no shortcode-like pattern
        if (strpos($html, '[') === false || strpos($html, ']') === false) {
            return '';
        }

        // Parse and replace
        $parsed = $this->parseShortcodes($html);
        if (is_string($parsed) && $parsed !== $html) {
            $params['html'] = $parsed;
        }

        return '';
    }

    protected function registerSmartyPlugins(bool $backOffice = false): void
    {
        if (!isset($this->context) || !isset($this->context->smarty)) {
            return;
        }
        $smarty = $this->context->smarty;
        if (method_exists($smarty, 'registerPlugin')) {
            // Always register unique plugin names for this module
            try { $smarty->registerPlugin('function', 'shortcodes', [$this, 'smartyShortcodeFunction']); } catch (\Throwable $e) {}
            try { $smarty->registerPlugin('modifier', 'shortcodes', [$this, 'smartyShortcodeModifier']); } catch (\Throwable $e) {}

            // Front office alias for convenience (may already exist in other modules)
            if (!$backOffice) {
                try { $smarty->registerPlugin('function', 'shortcode', [$this, 'smartyShortcodeFunction']); } catch (\Throwable $e) {}
                try { $smarty->registerPlugin('modifier', 'shortcode', [$this, 'smartyShortcodeModifier']); } catch (\Throwable $e) {}
            }
        }
    }

    public function smartyShortcodeFunction($params, $smarty)
    {
        $text = isset($params['text']) ? (string) $params['text'] : '';
        return $this->parseShortcodes($text);
    }

    public function smartyShortcodeModifier($text)
    {
        return $this->parseShortcodes((string) $text);
    }

    public function parseShortcodes(string $content): string
    {
        $registry = new \ShortCodes\ShortcodeRegistry($this);
        // Register built-in handlers
        $registry->registerDefaults();

        $parser = new \ShortCodes\ShortcodeParser($registry, $this->context);
        return $parser->parse($content);
    }

    /**
     * Back Office configuration page content
     */
    public function getContent(): string
    {
        // Register BO-only smarty plugins aliases if needed
        $this->registerSmartyPlugins(true);

        $output = '';

        // Handle form submit
        if (Tools::isSubmit('submitMgShortcodeConfig')) {
            $this->postProcess();
            // Redirect to clear POST and display persisted values from Configuration
            $url = AdminController::$currentIndex . '&configure=' . $this->name . '&conf=4&token=' . Tools::getAdminTokenLite('AdminModules');
            Tools::redirectAdmin($url);
        }

        // Success message handled by conf parameter after redirect

        if (isset($this->context) && isset($this->context->smarty)) {
            $this->context->smarty->assign([
                'module_dir' => $this->_path,
                'module_name' => $this->displayName,
                'module_version' => $this->version,
                'zm40_ah_name' => 'ShortCodes',
                'zm40_ah_sub' => $this->l('Moteur de shortcodes pour CMS et HTML'),
                'zm40_ah_version' => $this->version,
                'zm40_ah_shop' => Configuration::get('PS_SHOP_NAME'),
                // ── ZM40 Common (footer + notice MAJ + bloc open source + autres modules) ──
                'zm40_net_enabled'   => Zm40Common::isNetEnabled() ? 1 : 0,
                'zm40_footer_html'   => Zm40Common::footer('ShortCodes', $this->version, 'shortcodes'),
                'zm40_update'        => Zm40Common::checkUpdate('shortcodes', $this->version),
                'zm40_modules'       => Zm40Common::modulesFeed('shortcodes'),
                'zm40_about_name'    => 'ShortCodes',
                'zm40_about_license' => 'GPL v3',
                'zm40_about_github'  => Zm40Common::githubUrl('shortcodes'),
                'zm40_about_site'    => Zm40Common::siteUrl('shortcodes', 'panel', '/contact'),
                'zm40_about_modules' => Zm40Common::siteUrl('shortcodes', 'panel', '/'),
            ]);
            try {
                $guide = (string) $this->context->smarty->fetch('module:shortcodes/views/templates/admin/configure.tpl');
                return $output . $this->renderForm() . $guide;
            } catch (\Throwable $e) {
                $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
                return '<div class="alert alert-danger">' . $this->l('Erreur de rendu du panneau de configuration: ') . $msg . '</div>';
            }
        }
        return '<div class="alert alert-info">' . $this->l('Contexte Smarty indisponible.') . '</div>';
    }

    protected function renderForm(): string
    {
        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');
        $fieldsForm = [];

        // Assets (FO)
        $fieldsForm[] = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Assets Front Office'),
                    'icon' => 'icon-code',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Charger Swiper (CDN)'),
                        'name' => 'MGSC_LOAD_SWIPER_ASSETS',
                        'is_bool' => true,
                        'desc' => $this->l('Active le chargement de Swiper JS/CSS depuis jsDelivr. Désactivez si votre thème inclut déjà Swiper.'),
                        'values' => [
                            ['id' => 'MGSC_LOAD_SWIPER_ASSETS_on', 'value' => 1, 'label' => $this->l('Oui')],
                            ['id' => 'MGSC_LOAD_SWIPER_ASSETS_off', 'value' => 0, 'label' => $this->l('Non')],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Charger le CSS/JS du module'),
                        'name' => 'MGSC_ASSETS_LOAD_ENABLED',
                        'is_bool' => true,
                        'desc' => $this->l('Désactivez si votre thème gère déjà le style et le JS des sliders.'),
                        'values' => [
                            ['id' => 'MGSC_ASSETS_LOAD_ENABLED_on', 'value' => 1, 'label' => $this->l('Oui')],
                            ['id' => 'MGSC_ASSETS_LOAD_ENABLED_off', 'value' => 0, 'label' => $this->l('Non')],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Enregistrer'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        // Global behavior
        $fieldsForm[] = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Comportement du slider (global)'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Lecture automatique (autoplay)'),
                        'name' => 'MGSC_SLIDER_AUTOPLAY_ENABLED',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'on', 'value' => 1, 'label' => $this->l('Oui')],
                            ['id' => 'off', 'value' => 0, 'label' => $this->l('Non')],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Espacement entre slides (px)'),
                        'name' => 'MGSC_SLIDER_SPACE_BETWEEN',
                        'class' => 'fixed-width-sm',
                        'desc' => $this->l('Nombre entier. Défaut: 20'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Vitesse de transition (ms)'),
                        'name' => 'MGSC_SLIDER_SPEED',
                        'class' => 'fixed-width-sm',
                        'desc' => $this->l('Nombre entier. Défaut: 600'),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Hauteur automatique'),
                        'name' => 'MGSC_SLIDER_AUTO_HEIGHT',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'on', 'value' => 1, 'label' => $this->l('Oui')],
                            ['id' => 'off', 'value' => 0, 'label' => $this->l('Non')],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Centrer les slides'),
                        'name' => 'MGSC_SLIDER_CENTER_ENABLED',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'on', 'value' => 1, 'label' => $this->l('Oui')],
                            ['id' => 'off', 'value' => 0, 'label' => $this->l('Non')],
                        ],
                    ],
                    [
                        'type' => 'html',
                        'name' => 'center_help',
                        'label' => $this->l('Centrage par breakpoint'),
                        'html_content' => '<div class="help-block">' . $this->l('Laisser vide pour hériter du réglage global ci-dessus. Sinon, forcer le centrage par breakpoint.') . '</div>',
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Centrer – XL (≥1200px)'),
                        'name' => 'MGSC_SLIDER_CENTER_ENABLED_XL',
                        'options' => [
                            'query' => [
                                ['id' => '', 'name' => $this->l('Hériter du global')],
                                ['id' => '1', 'name' => $this->l('Oui')],
                                ['id' => '0', 'name' => $this->l('Non')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Centrer – LG (≥992px)'),
                        'name' => 'MGSC_SLIDER_CENTER_ENABLED_LG',
                        'options' => [
                            'query' => [
                                ['id' => '', 'name' => $this->l('Hériter du global')],
                                ['id' => '1', 'name' => $this->l('Oui')],
                                ['id' => '0', 'name' => $this->l('Non')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Centrer – MD (≥768px)'),
                        'name' => 'MGSC_SLIDER_CENTER_ENABLED_MD',
                        'options' => [
                            'query' => [
                                ['id' => '', 'name' => $this->l('Hériter du global')],
                                ['id' => '1', 'name' => $this->l('Oui')],
                                ['id' => '0', 'name' => $this->l('Non')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Centrer – SM (≥480px)'),
                        'name' => 'MGSC_SLIDER_CENTER_ENABLED_SM',
                        'options' => [
                            'query' => [
                                ['id' => '', 'name' => $this->l('Hériter du global')],
                                ['id' => '1', 'name' => $this->l('Oui')],
                                ['id' => '0', 'name' => $this->l('Non')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Centrer – XS (<480px)'),
                        'name' => 'MGSC_SLIDER_CENTER_ENABLED_XS',
                        'options' => [
                            'query' => [
                                ['id' => '', 'name' => $this->l('Hériter du global')],
                                ['id' => '1', 'name' => $this->l('Oui')],
                                ['id' => '0', 'name' => $this->l('Non')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'html',
                        'name' => 'spv_help',
                        'label' => $this->l('Articles par vue (Slides Per View)'),
                        'html_content' => '<div class="help-block">' . $this->l('Valeurs par défaut pour le nombre de cartes visibles selon la taille d\'écran. Accepte des décimaux (ex: 2.5). Les shortcodes peuvent surcharger ces valeurs, par ex:') . ' <code>[products 1,2,3 slider 5,4,3,2,2.5,1]</code><br>' . $this->l('La valeur Base sert de valeur par défaut pour toutes les tailles. Le champ XS (<480px) est optionnel : s\'il est vide, la valeur Base sera utilisée.') . '</div>',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('SPV – Base (défaut)'),
                        'name' => 'MGSC_SLIDER_SPV',
                        'class' => 'fixed-width-sm',
                        'desc' => $this->l('Par défaut: 1'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('SPV – SM (≥480px)'),
                        'name' => 'MGSC_SLIDER_SPV_SM',
                        'class' => 'fixed-width-sm',
                        'desc' => $this->l('Par défaut: 2'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('SPV – MD (≥768px)'),
                        'name' => 'MGSC_SLIDER_SPV_MD',
                        'class' => 'fixed-width-sm',
                        'desc' => $this->l('Par défaut: 3'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('SPV – LG (≥992px)'),
                        'name' => 'MGSC_SLIDER_SPV_LG',
                        'class' => 'fixed-width-sm',
                        'desc' => $this->l('Par défaut: 4'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('SPV – XL (≥1200px)'),
                        'name' => 'MGSC_SLIDER_SPV_XL',
                        'class' => 'fixed-width-sm',
                        'desc' => $this->l('Par défaut: 4'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('SPV – XS (<480px) (optionnel)'),
                        'name' => 'MGSC_SLIDER_SPV_XS',
                        'class' => 'fixed-width-sm',
                        'desc' => $this->l('Si vide, la valeur Base est utilisée.'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Enregistrer'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        // Accueil (Home) overrides
        $fieldsForm[] = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Accueil / HomePage — Overrides (laisser vide pour hériter)'),
                    'icon' => 'icon-home',
                ],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->l('Autoplay – Accueil'),
                        'name' => 'MGSC_SLIDER_AUTOPLAY_ENABLED_HOME',
                        'options' => [
                            'query' => [
                                ['id' => '', 'name' => $this->l('Hériter du global')],
                                ['id' => '1', 'name' => $this->l('Oui')],
                                ['id' => '0', 'name' => $this->l('Non')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Espacement – Accueil (px)'),
                        'name' => 'MGSC_SLIDER_SPACE_BETWEEN_HOME',
                        'class' => 'fixed-width-sm',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Vitesse – Accueil (ms)'),
                        'name' => 'MGSC_SLIDER_SPEED_HOME',
                        'class' => 'fixed-width-sm',
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Hauteur auto – Accueil'),
                        'name' => 'MGSC_SLIDER_AUTO_HEIGHT_HOME',
                        'options' => [
                            'query' => [
                                ['id' => '', 'name' => $this->l('Hériter du global')],
                                ['id' => '1', 'name' => $this->l('Oui')],
                                ['id' => '0', 'name' => $this->l('Non')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Centrer – Accueil'),
                        'name' => 'MGSC_SLIDER_CENTER_ENABLED_HOME',
                        'options' => [
                            'query' => [
                                ['id' => '', 'name' => $this->l('Hériter du global')],
                                ['id' => '1', 'name' => $this->l('Oui')],
                                ['id' => '0', 'name' => $this->l('Non')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Enregistrer'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        // CMS pages overrides
        $fieldsForm[] = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Pages CMS — Overrides (laisser vide pour hériter)'),
                    'icon' => 'icon-file-text',
                ],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->l('Autoplay – CMS'),
                        'name' => 'MGSC_SLIDER_AUTOPLAY_ENABLED_CMS',
                        'options' => [
                            'query' => [
                                ['id' => '', 'name' => $this->l('Hériter du global')],
                                ['id' => '1', 'name' => $this->l('Oui')],
                                ['id' => '0', 'name' => $this->l('Non')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Espacement – CMS (px)'),
                        'name' => 'MGSC_SLIDER_SPACE_BETWEEN_CMS',
                        'class' => 'fixed-width-sm',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Vitesse – CMS (ms)'),
                        'name' => 'MGSC_SLIDER_SPEED_CMS',
                        'class' => 'fixed-width-sm',
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Hauteur auto – CMS'),
                        'name' => 'MGSC_SLIDER_AUTO_HEIGHT_CMS',
                        'options' => [
                            'query' => [
                                ['id' => '', 'name' => $this->l('Hériter du global')],
                                ['id' => '1', 'name' => $this->l('Oui')],
                                ['id' => '0', 'name' => $this->l('Non')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Centrer – CMS'),
                        'name' => 'MGSC_SLIDER_CENTER_ENABLED_CMS',
                        'options' => [
                            'query' => [
                                ['id' => '', 'name' => $this->l('Hériter du global')],
                                ['id' => '1', 'name' => $this->l('Oui')],
                                ['id' => '0', 'name' => $this->l('Non')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Enregistrer'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        // Mises à jour & écosystème (ZM40) — opt-out réseau
        $fieldsForm[] = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Mises à jour & écosystème (ZM40)'),
                    'icon' => 'icon-refresh',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Vérifier les mises à jour'),
                        'name' => 'ZM40_NET_ENABLED',
                        'is_bool' => true,
                        'desc' => $this->l('Vérifie au maximum une fois par jour si une nouvelle version est disponible via l\'API publique de GitHub, et affiche les autres modules ZM40 depuis zm40.com. Requêtes anonymes : aucune donnée de votre boutique n\'est transmise. Décochez pour tout désactiver.'),
                        'values' => [
                            ['id' => 'ZM40_NET_ENABLED_on', 'value' => 1, 'label' => $this->l('Oui')],
                            ['id' => 'ZM40_NET_ENABLED_off', 'value' => 0, 'label' => $this->l('Non')],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Enregistrer'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = (int)Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitMgShortcodeConfig';
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm($fieldsForm);
    }

    protected function getConfigFormValues(): array
    {
        $get = static function(string $k) {
            $v = Configuration::get($k);
            return $v;
        };
        return [
            'MGSC_LOAD_SWIPER_ASSETS' => (int) (($v = Configuration::get('MGSC_LOAD_SWIPER_ASSETS')) !== false ? (int)$v : 1),
            'MGSC_ASSETS_LOAD_ENABLED' => (int) (($v = Configuration::get('MGSC_ASSETS_LOAD_ENABLED')) !== false ? (int)$v : 1),
            'MGSC_SLIDER_AUTOPLAY_ENABLED' => (int) (Configuration::get('MGSC_SLIDER_AUTOPLAY_ENABLED') ? 1 : 0),
            'MGSC_SLIDER_SPACE_BETWEEN' => (string) ($get('MGSC_SLIDER_SPACE_BETWEEN') !== false ? $get('MGSC_SLIDER_SPACE_BETWEEN') : '20'),
            'MGSC_SLIDER_SPEED' => (string) ($get('MGSC_SLIDER_SPEED') !== false ? $get('MGSC_SLIDER_SPEED') : '600'),
            'MGSC_SLIDER_AUTO_HEIGHT' => (int) (Configuration::get('MGSC_SLIDER_AUTO_HEIGHT') ? 1 : 0),
            'MGSC_SLIDER_CENTER_ENABLED' => (int) (Configuration::get('MGSC_SLIDER_CENTER_ENABLED') ? 1 : 0),

            // Center per breakpoint (tri-state: '', '1', '0')
            'MGSC_SLIDER_CENTER_ENABLED_XL' => ($get('MGSC_SLIDER_CENTER_ENABLED_XL') !== false ? (string)$get('MGSC_SLIDER_CENTER_ENABLED_XL') : ''),
            'MGSC_SLIDER_CENTER_ENABLED_LG' => ($get('MGSC_SLIDER_CENTER_ENABLED_LG') !== false ? (string)$get('MGSC_SLIDER_CENTER_ENABLED_LG') : ''),
            'MGSC_SLIDER_CENTER_ENABLED_MD' => ($get('MGSC_SLIDER_CENTER_ENABLED_MD') !== false ? (string)$get('MGSC_SLIDER_CENTER_ENABLED_MD') : ''),
            'MGSC_SLIDER_CENTER_ENABLED_SM' => ($get('MGSC_SLIDER_CENTER_ENABLED_SM') !== false ? (string)$get('MGSC_SLIDER_CENTER_ENABLED_SM') : ''),
            'MGSC_SLIDER_CENTER_ENABLED_XS' => ($get('MGSC_SLIDER_CENTER_ENABLED_XS') !== false ? (string)$get('MGSC_SLIDER_CENTER_ENABLED_XS') : ''),

            // SPV defaults
            'MGSC_SLIDER_SPV' => (string) ($get('MGSC_SLIDER_SPV') !== false ? $get('MGSC_SLIDER_SPV') : '1'),
            'MGSC_SLIDER_SPV_XL' => (string) ($get('MGSC_SLIDER_SPV_XL') !== false ? $get('MGSC_SLIDER_SPV_XL') : '4'),
            'MGSC_SLIDER_SPV_LG' => (string) ($get('MGSC_SLIDER_SPV_LG') !== false ? $get('MGSC_SLIDER_SPV_LG') : '4'),
            'MGSC_SLIDER_SPV_MD' => (string) ($get('MGSC_SLIDER_SPV_MD') !== false ? $get('MGSC_SLIDER_SPV_MD') : '3'),
            'MGSC_SLIDER_SPV_SM' => (string) ($get('MGSC_SLIDER_SPV_SM') !== false ? $get('MGSC_SLIDER_SPV_SM') : '2'),
            'MGSC_SLIDER_SPV_XS' => (string) ($get('MGSC_SLIDER_SPV_XS') !== false ? $get('MGSC_SLIDER_SPV_XS') : '1'),

            'MGSC_SLIDER_AUTOPLAY_ENABLED_HOME' => ($get('MGSC_SLIDER_AUTOPLAY_ENABLED_HOME') !== false ? (string)$get('MGSC_SLIDER_AUTOPLAY_ENABLED_HOME') : ''),
            'MGSC_SLIDER_SPACE_BETWEEN_HOME' => (string) ($get('MGSC_SLIDER_SPACE_BETWEEN_HOME') !== false ? $get('MGSC_SLIDER_SPACE_BETWEEN_HOME') : ''),
            'MGSC_SLIDER_SPEED_HOME' => (string) ($get('MGSC_SLIDER_SPEED_HOME') !== false ? $get('MGSC_SLIDER_SPEED_HOME') : ''),
            'MGSC_SLIDER_AUTO_HEIGHT_HOME' => ($get('MGSC_SLIDER_AUTO_HEIGHT_HOME') !== false ? (string)$get('MGSC_SLIDER_AUTO_HEIGHT_HOME') : ''),
            'MGSC_SLIDER_CENTER_ENABLED_HOME' => ($get('MGSC_SLIDER_CENTER_ENABLED_HOME') !== false ? (string)$get('MGSC_SLIDER_CENTER_ENABLED_HOME') : ''),

            'MGSC_SLIDER_AUTOPLAY_ENABLED_CMS' => ($get('MGSC_SLIDER_AUTOPLAY_ENABLED_CMS') !== false ? (string)$get('MGSC_SLIDER_AUTOPLAY_ENABLED_CMS') : ''),
            'MGSC_SLIDER_SPACE_BETWEEN_CMS' => (string) ($get('MGSC_SLIDER_SPACE_BETWEEN_CMS') !== false ? $get('MGSC_SLIDER_SPACE_BETWEEN_CMS') : ''),
            'MGSC_SLIDER_SPEED_CMS' => (string) ($get('MGSC_SLIDER_SPEED_CMS') !== false ? $get('MGSC_SLIDER_SPEED_CMS') : ''),
            'MGSC_SLIDER_AUTO_HEIGHT_CMS' => ($get('MGSC_SLIDER_AUTO_HEIGHT_CMS') !== false ? (string)$get('MGSC_SLIDER_AUTO_HEIGHT_CMS') : ''),
            'MGSC_SLIDER_CENTER_ENABLED_CMS' => ($get('MGSC_SLIDER_CENTER_ENABLED_CMS') !== false ? (string)$get('MGSC_SLIDER_CENTER_ENABLED_CMS') : ''),

            'ZM40_NET_ENABLED' => (int) (Zm40Common::isNetEnabled() ? 1 : 0),
        ];
    }

    protected function postProcess(): void
    {
        // Sanitize and persist values; empty strings for overrides mean fallback to global
        $bool = static function($k) {
            $v = Tools::getValue($k, null);
            if ($v === null) { return false; }
            $s = strtolower(trim((string)$v));
            if ($s === '0' || $s === 'off' || $s === 'false' || $s === '') { return false; }
            if ($s === '1' || $s === 'on' || $s === 'true' || $s === 'yes') { return true; }
            return (bool) $v;
        };
        $intOrEmpty = static function($k) {
            $v = Tools::getValue($k, '');
            if ($v === '' || $v === null) { return ''; }
            return (int) $v;
        };
        $floatSanitized = static function($k, $def) {
            $v = Tools::getValue($k, null);
            if ($v === null || $v === '') { return (string)$def; }
            $s = str_replace(',', '.', (string)$v);
            $s = preg_replace('/[^0-9\.]/', '', (string)$s);
            if ($s === '' || !preg_match('/^-?\d+(?:\.\d+)?$/', (string)$s)) { return (string)$def; }
            return (string)$s;
        };

        try {
            // Update assets loading flag ONLY if the field is posted (this page has multiple forms)
            if (Tools::getIsset('MGSC_LOAD_SWIPER_ASSETS')) {
                Configuration::updateValue('MGSC_LOAD_SWIPER_ASSETS', $bool('MGSC_LOAD_SWIPER_ASSETS'));
            }
            if (Tools::getIsset('MGSC_ASSETS_LOAD_ENABLED')) {
                Configuration::updateValue('MGSC_ASSETS_LOAD_ENABLED', $bool('MGSC_ASSETS_LOAD_ENABLED'));
            }
            Configuration::updateValue('MGSC_SLIDER_AUTOPLAY_ENABLED', $bool('MGSC_SLIDER_AUTOPLAY_ENABLED'));
            Configuration::updateValue('MGSC_SLIDER_SPACE_BETWEEN', (int) Tools::getValue('MGSC_SLIDER_SPACE_BETWEEN', 20));
            Configuration::updateValue('MGSC_SLIDER_SPEED', (int) Tools::getValue('MGSC_SLIDER_SPEED', 600));
            Configuration::updateValue('MGSC_SLIDER_AUTO_HEIGHT', $bool('MGSC_SLIDER_AUTO_HEIGHT'));
            Configuration::updateValue('MGSC_SLIDER_CENTER_ENABLED', $bool('MGSC_SLIDER_CENTER_ENABLED'));

            // Center per breakpoint
            $ov = Tools::getValue('MGSC_SLIDER_CENTER_ENABLED_XL', '');
            Configuration::updateValue('MGSC_SLIDER_CENTER_ENABLED_XL', ($ov === '' ? '' : (int)((bool)$ov)));
            $ov = Tools::getValue('MGSC_SLIDER_CENTER_ENABLED_LG', '');
            Configuration::updateValue('MGSC_SLIDER_CENTER_ENABLED_LG', ($ov === '' ? '' : (int)((bool)$ov)));
            $ov = Tools::getValue('MGSC_SLIDER_CENTER_ENABLED_MD', '');
            Configuration::updateValue('MGSC_SLIDER_CENTER_ENABLED_MD', ($ov === '' ? '' : (int)((bool)$ov)));
            $ov = Tools::getValue('MGSC_SLIDER_CENTER_ENABLED_SM', '');
            Configuration::updateValue('MGSC_SLIDER_CENTER_ENABLED_SM', ($ov === '' ? '' : (int)((bool)$ov)));
            $ov = Tools::getValue('MGSC_SLIDER_CENTER_ENABLED_XS', '');
            Configuration::updateValue('MGSC_SLIDER_CENTER_ENABLED_XS', ($ov === '' ? '' : (int)((bool)$ov)));

            // SPV per breakpoint (store as string to keep decimals)
            Configuration::updateValue('MGSC_SLIDER_SPV', $floatSanitized('MGSC_SLIDER_SPV', '1'));
            Configuration::updateValue('MGSC_SLIDER_SPV_XL', $floatSanitized('MGSC_SLIDER_SPV_XL', '4'));
            Configuration::updateValue('MGSC_SLIDER_SPV_LG', $floatSanitized('MGSC_SLIDER_SPV_LG', '4'));
            Configuration::updateValue('MGSC_SLIDER_SPV_MD', $floatSanitized('MGSC_SLIDER_SPV_MD', '3'));
            Configuration::updateValue('MGSC_SLIDER_SPV_SM', $floatSanitized('MGSC_SLIDER_SPV_SM', '2'));
            // XS is optional: if left empty, store empty string to inherit Base at runtime
            $xsRaw = Tools::getValue('MGSC_SLIDER_SPV_XS', null);
            if ($xsRaw === null || $xsRaw === '') {
                Configuration::updateValue('MGSC_SLIDER_SPV_XS', '');
            } else {
                Configuration::updateValue('MGSC_SLIDER_SPV_XS', $floatSanitized('MGSC_SLIDER_SPV_XS', '1'));
            }

            // Overrides: store empty string if not provided
            $ov = Tools::getValue('MGSC_SLIDER_AUTOPLAY_ENABLED_HOME', '');
            Configuration::updateValue('MGSC_SLIDER_AUTOPLAY_ENABLED_HOME', ($ov === '' ? '' : (int)((bool)$ov)));
            Configuration::updateValue('MGSC_SLIDER_SPACE_BETWEEN_HOME', $intOrEmpty('MGSC_SLIDER_SPACE_BETWEEN_HOME'));
            Configuration::updateValue('MGSC_SLIDER_SPEED_HOME', $intOrEmpty('MGSC_SLIDER_SPEED_HOME'));
            $ov = Tools::getValue('MGSC_SLIDER_AUTO_HEIGHT_HOME', '');
            Configuration::updateValue('MGSC_SLIDER_AUTO_HEIGHT_HOME', ($ov === '' ? '' : (int)((bool)$ov)));
            $ov = Tools::getValue('MGSC_SLIDER_CENTER_ENABLED_HOME', '');
            Configuration::updateValue('MGSC_SLIDER_CENTER_ENABLED_HOME', ($ov === '' ? '' : (int)((bool)$ov)));

            $ov = Tools::getValue('MGSC_SLIDER_AUTOPLAY_ENABLED_CMS', '');
            Configuration::updateValue('MGSC_SLIDER_AUTOPLAY_ENABLED_CMS', ($ov === '' ? '' : (int)((bool)$ov)));
            Configuration::updateValue('MGSC_SLIDER_SPACE_BETWEEN_CMS', $intOrEmpty('MGSC_SLIDER_SPACE_BETWEEN_CMS'));
            Configuration::updateValue('MGSC_SLIDER_SPEED_CMS', $intOrEmpty('MGSC_SLIDER_SPEED_CMS'));
            $ov = Tools::getValue('MGSC_SLIDER_AUTO_HEIGHT_CMS', '');
            Configuration::updateValue('MGSC_SLIDER_AUTO_HEIGHT_CMS', ($ov === '' ? '' : (int)((bool)$ov)));
            $ov = Tools::getValue('MGSC_SLIDER_CENTER_ENABLED_CMS', '');
            Configuration::updateValue('MGSC_SLIDER_CENTER_ENABLED_CMS', ($ov === '' ? '' : (int)((bool)$ov)));

            // ZM40 Common — interrupteur réseau (switch is_bool, posté 0/1)
            if (Tools::getIsset('ZM40_NET_ENABLED')) {
                Configuration::updateValue('ZM40_NET_ENABLED', (int) Tools::getValue('ZM40_NET_ENABLED'));
            }
            // Sauvegarde = rafraîchir le feed ZM40 au prochain rendu
            Zm40Common::clearFeedCache();
        } catch (\Throwable $e) {
            // Swallow errors in BO; PS will display PHP error logs if necessary
        }
    }
}
