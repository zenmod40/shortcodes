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
declare(strict_types=1);

namespace ShortCodes\Support;

use Context;
use Product;
use Tools;

trait ShortcodeHelpers
{

    /**
     * Format un montant selon la locale courante.
     *
     * PrestaShop < 9 : utilise Tools::displayPrice() (rétro-compat).
     * PrestaShop 9+ : Tools::displayPrice a été retirée → on bascule sur
     * Context::getCurrentLocale()->formatPrice() avec l'ISO de la devise active.
     */
    protected static function formatPrice($amount): string
    {
        if (method_exists('Tools', 'displayPrice')) {
            return Tools::displayPrice($amount);
        }
        $ctx = Context::getContext();
        if ($ctx && method_exists($ctx, 'getCurrentLocale') && $ctx->getCurrentLocale()) {
            $iso = (isset($ctx->currency) && $ctx->currency) ? $ctx->currency->iso_code : 'EUR';
            return $ctx->getCurrentLocale()->formatPrice((float) $amount, $iso);
        }
        // Dernier recours (extrêmement improbable)
        return number_format((float) $amount, 2, ',', ' ') . ' €';
    }

    /**
     * Detect if shortcode should render as slider
     */
    protected static function isSlider(array $args): bool
    {
        foreach ($args as $a) {
            $tokens = preg_split('/\s+/', trim((string) $a)) ?: [];
            foreach ($tokens as $t) {
                $s = strtolower($t);
                if ($s === 'slider' || $s === 'view=slider') {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Parse slider config from shortcode args.
     * Supported syntaxes:
     * - "slider" → use module defaults
     * - "slider 4" → apply 4 to all breakpoints (base,xl,lg,md,sm,xs)
     * - "slider 5,4,3,2,2.5,1" → map to (base,xl,lg,md,sm,xs)
     * Any missing values fall back to sensible defaults below.
     *
     * @return array{spv:float,spv_xl:float,spv_lg:float,spv_md:float,spv_sm:float,spv_xs:float}
     */
    protected static function parseSliderConfig(array $args): array
    {
        // Defaults from configuration (fallback to sensible constants)
        $cfg = self::defaultSpvConfig();

        // Flatten tokens, keep original chunks to detect numbers next to 'slider'
        $chunks = [];
        foreach ($args as $a) {
            $a = trim((string) $a);
            if ($a === '') { continue; }
            $parts = preg_split('/\s+/', $a) ?: [];
            foreach ($parts as $p) { $chunks[] = $p; }
        }

        for ($i = 0; $i < count($chunks); $i++) {
            $t = strtolower((string)$chunks[$i]);
            $isSliderToken = ($t === 'slider' || $t === 'view=slider' || str_starts_with($t, 'slider='));
            if (!$isSliderToken) { continue; }

            // Try to read value list from same token (e.g., slider=4 or slider=5,4,3,2,2.5,1)
            $valueStr = '';
            if (str_contains($chunks[$i], '=')) {
                $parts = explode('=', (string)$chunks[$i], 2);
                $valueStr = trim((string)($parts[1] ?? ''));
            } elseif (isset($chunks[$i+1])) {
                $valueStr = trim((string)$chunks[$i+1]);
            }

            if ($valueStr === '') {
                // No overrides → keep defaults
                break;
            }

            // Accept either single number or up to 6 comma-separated numbers
            $nums = array_values(array_filter(array_map(function ($s) {
                $s = trim((string)$s);
                if ($s === '') { return null; }
                // Allow decimals like 2.5
                if (!preg_match('/^-?\d+(?:\.\d+)?$/', $s)) { return null; }
                return (float)$s;
            }, explode(',', $valueStr)), function ($v) { return $v !== null; }));

            if (!$nums) { break; }

            if (count($nums) === 1) {
                $v = (float)$nums[0];
                $cfg['spv'] = $v;
                $cfg['spv_xl'] = $v;
                $cfg['spv_lg'] = $v;
                $cfg['spv_md'] = $v;
                $cfg['spv_sm'] = $v;
                $cfg['spv_xs'] = $v;
            } else {
                // Map in order: base, xl, lg, md, sm, xs
                $map = ['spv','spv_xl','spv_lg','spv_md','spv_sm','spv_xs'];
                foreach ($map as $idx => $key) {
                    if (isset($nums[$idx])) {
                        $cfg[$key] = (float)$nums[$idx];
                    }
                }
            }
            break; // Only first slider override considered
        }

        return $cfg;
    }

    /**
     * Parse center flags from shortcode args.
     * Supported syntaxes:
     *  - center=true|false
     *  - center-xl=true|false (also -lg, -md, -sm, -xs)
     * Missing values inherit from $default.
     *
     * @return array{base:bool,xl:bool,lg:bool,md:bool,sm:bool,xs:bool}
     */
    protected static function parseCenterConfig(array $args, bool $default): array
    {
        $centers = [
            'base' => $default,
            'xl' => $default,
            'lg' => $default,
            'md' => $default,
            'sm' => $default,
            'xs' => $default,
        ];

        // Flatten tokens
        $chunks = [];
        foreach ($args as $a) {
            $a = trim((string)$a);
            if ($a === '') { continue; }
            $parts = preg_split('/\s+/', $a) ?: [];
            foreach ($parts as $p) { $chunks[] = $p; }
        }

        foreach ($chunks as $t) {
            $t = trim((string)$t);
            if ($t === '' || strpos($t, '=') === false) { continue; }
            $kv = explode('=', $t, 2);
            $key = strtolower(trim((string)($kv[0] ?? '')));
            $val = trim((string)($kv[1] ?? ''));
            $parsed = self::parseBoolToken($val, null);
            if ($parsed === null) { continue; }
            if ($key === 'center' || $key === 'centered' || $key === 'center-enabled') {
                $centers['base'] = $parsed;
                // Do not break; allow specific breakpoints to override after
                continue;
            }
            if (str_starts_with($key, 'center-') || str_starts_with($key, 'centered-') || str_starts_with($key, 'center-enabled-')) {
                if (preg_match('/^(?:center|centered|center-enabled)-(xl|lg|md|sm|xs)$/', $key, $m)) {
                    $bp = $m[1];
                    $centers[$bp] = $parsed;
                }
            }
        }

        return $centers;
    }

    /**
     * Read an explicit autoplay override from shortcode args.
     * Supports keys: autoplay, autoplay_enabled, autoplay-enabled
     * Returns null if no explicit override was found.
     */
    protected static function parseAutoplayOverride(array $args): ?bool
    {
        // Flatten tokens
        $chunks = [];
        foreach ($args as $a) {
            $a = trim((string)$a);
            if ($a === '') { continue; }
            $parts = preg_split('/\s+/', $a) ?: [];
            foreach ($parts as $p) { $chunks[] = $p; }
        }

        foreach ($chunks as $t) {
            $t = trim((string)$t);
            if ($t === '' || strpos($t, '=') === false) { continue; }
            $kv = explode('=', $t, 2);
            $key = strtolower(trim((string)($kv[0] ?? '')));
            $val = trim((string)($kv[1] ?? ''));
            if ($key === 'autoplay' || $key === 'autoplay_enabled' || $key === 'autoplay-enabled') {
                return self::parseBoolToken($val, null);
            }
        }

        return null;
    }

    /**
     * Build per-breakpoint default center configuration from BO settings.
     * Returns array with keys base,xl,lg,md,sm,xs. Base comes from getSliderBehavior(center_enabled)
     * which already accounts for HOME/CMS overrides. Each breakpoint inherits base unless a
     * specific BO key is set (MGSC_SLIDER_CENTER_ENABLED_XL|LG|MD|SM|XS).
     *
     * @return array{base:bool,xl:bool,lg:bool,md:bool,sm:bool,xs:bool}
     */
    protected static function defaultCenterConfig(Context $context): array
    {
        $bh = self::getSliderBehavior($context);
        $base = (bool)$bh['center_enabled'];
        $cfg = [
            'base' => $base,
            'xl' => $base,
            'lg' => $base,
            'md' => $base,
            'sm' => $base,
            'xs' => $base,
        ];
        try {
            $xl = self::cfgBoolNullable('MGSC_SLIDER_CENTER_ENABLED_XL'); if ($xl !== null) { $cfg['xl'] = $xl; }
            $lg = self::cfgBoolNullable('MGSC_SLIDER_CENTER_ENABLED_LG'); if ($lg !== null) { $cfg['lg'] = $lg; }
            $md = self::cfgBoolNullable('MGSC_SLIDER_CENTER_ENABLED_MD'); if ($md !== null) { $cfg['md'] = $md; }
            $sm = self::cfgBoolNullable('MGSC_SLIDER_CENTER_ENABLED_SM'); if ($sm !== null) { $cfg['sm'] = $sm; }
            $xs = self::cfgBoolNullable('MGSC_SLIDER_CENTER_ENABLED_XS'); if ($xs !== null) { $cfg['xs'] = $xs; }
        } catch (\Throwable $e) { /* ignore */ }
        return $cfg;
    }

    /**
     * Like parseCenterConfig but starting from per-breakpoint defaults array.
     * Shortcode tokens override the provided defaults.
     *
     * @param array{base:bool,xl:bool,lg:bool,md:bool,sm:bool,xs:bool} $defaults
     * @return array{base:bool,xl:bool,lg:bool,md:bool,sm:bool,xs:bool}
     */
    protected static function parseCenterConfigWithDefaults(array $args, array $defaults): array
    {
        $centers = [
            'base' => (bool)$defaults['base'],
            'xl' => (bool)$defaults['xl'],
            'lg' => (bool)$defaults['lg'],
            'md' => (bool)$defaults['md'],
            'sm' => (bool)$defaults['sm'],
            'xs' => (bool)$defaults['xs'],
        ];

        // Flatten tokens
        $chunks = [];
        foreach ($args as $a) {
            $a = trim((string)$a);
            if ($a === '') { continue; }
            $parts = preg_split('/\s+/', $a) ?: [];
            foreach ($parts as $p) { $chunks[] = $p; }
        }

        foreach ($chunks as $t) {
            $t = trim((string)$t);
            if ($t === '' || strpos($t, '=') === false) { continue; }
            $kv = explode('=', $t, 2);
            $key = strtolower(trim((string)($kv[0] ?? '')));
            $val = trim((string)($kv[1] ?? ''));
            $parsed = self::parseBoolToken($val, null);
            if ($parsed === null) { continue; }
            if ($key === 'center' || $key === 'centered' || $key === 'center-enabled') {
                $centers['base'] = $parsed;
                // Do not break; allow specific breakpoints to override after
                continue;
            }
            if (str_starts_with($key, 'center-') || str_starts_with($key, 'centered-') || str_starts_with($key, 'center-enabled-')) {
                if (preg_match('/^(?:center|centered|center-enabled)-(xl|lg|md|sm|xs)$/', $key, $m)) {
                    $bp = $m[1];
                    $centers[$bp] = $parsed;
                }
            }
        }

        return $centers;
    }

    /**
     * Defaults for SPV loaded from Configuration with sane fallbacks.
     *
     * @return array{spv:float,spv_xl:float,spv_lg:float,spv_md:float,spv_sm:float,spv_xs:float}
     */
    protected static function defaultSpvConfig(): array
    {
        $def = [
            'spv' => 1.0,
            'spv_xl' => 4.0,
            'spv_lg' => 4.0,
            'spv_md' => 3.0,
            'spv_sm' => 2.0,
            'spv_xs' => 1.0,
        ];
        try {
            // Base and standard breakpoints
            $def['spv'] = self::cfgFloat('MGSC_SLIDER_SPV', $def['spv']);
            $def['spv_xl'] = self::cfgFloat('MGSC_SLIDER_SPV_XL', $def['spv_xl']);
            $def['spv_lg'] = self::cfgFloat('MGSC_SLIDER_SPV_LG', $def['spv_lg']);
            $def['spv_md'] = self::cfgFloat('MGSC_SLIDER_SPV_MD', $def['spv_md']);
            $def['spv_sm'] = self::cfgFloat('MGSC_SLIDER_SPV_SM', $def['spv_sm']);
            // XS: allow empty config to inherit Base (spv)
            $rawXs = \Configuration::get('MGSC_SLIDER_SPV_XS');
            if ($rawXs === '' || $rawXs === null || $rawXs === false) {
                $def['spv_xs'] = $def['spv'];
            } else {
                $def['spv_xs'] = self::cfgFloat('MGSC_SLIDER_SPV_XS', $def['spv_xs']);
            }
        } catch (\Throwable $e) { /* ignore and keep defaults */ }
        return $def;
    }

    /**
     * Determine a coarse page type for overrides: 'home', 'cms', or 'other'.
     */
    protected static function getPageType(Context $context): string
    {
        try {
            $self = isset($context->controller) && isset($context->controller->php_self)
                ? (string)$context->controller->php_self
                : '';
            if ($self === 'index') { return 'home'; }
            if ($self === 'cms') { return 'cms'; }
        } catch (\Throwable $e) { /* ignore */ }
        return 'other';
    }

    /**
     * Read slider behavior config (autoplay, spacing, speed, autoHeight, center)
     * with sensible defaults and per-page overrides for home/cms.
     *
     * Configuration keys used (optionally, MultiShop-aware via core):
     *  - MGSC_SLIDER_AUTOPLAY_ENABLED
     *  - MGSC_SLIDER_SPACE_BETWEEN
     *  - MGSC_SLIDER_SPEED
     *  - MGSC_SLIDER_AUTO_HEIGHT
     *  - MGSC_SLIDER_CENTER_ENABLED
     * Per-page overrides (optional): add suffix _HOME or _CMS.
     *  e.g., MGSC_SLIDER_SPACE_BETWEEN_HOME, MGSC_SLIDER_CENTER_ENABLED_CMS
     *
     * @return array{autoplay_enabled:bool,space_between:int,speed:int,auto_height:bool,center_enabled:bool}
     */
    protected static function getSliderBehavior(Context $context): array
    {
        $defaults = [
            'autoplay_enabled' => false,
            'space_between' => 20,
            'speed' => 600,
            'auto_height' => false,
            'center_enabled' => false,
        ];

        $base = [
            'autoplay_enabled' => self::cfgBool('MGSC_SLIDER_AUTOPLAY_ENABLED', $defaults['autoplay_enabled']),
            'space_between' => self::cfgInt('MGSC_SLIDER_SPACE_BETWEEN', $defaults['space_between']),
            'speed' => self::cfgInt('MGSC_SLIDER_SPEED', $defaults['speed']),
            'auto_height' => self::cfgBool('MGSC_SLIDER_AUTO_HEIGHT', $defaults['auto_height']),
            'center_enabled' => self::cfgBool('MGSC_SLIDER_CENTER_ENABLED', $defaults['center_enabled']),
        ];

        $type = self::getPageType($context);
        if ($type === 'home' || $type === 'cms') {
            $suffix = strtoupper($type);
            $ovr = [
                'autoplay_enabled' => self::cfgBoolNullable('MGSC_SLIDER_AUTOPLAY_ENABLED_' . $suffix),
                'space_between' => self::cfgIntNullable('MGSC_SLIDER_SPACE_BETWEEN_' . $suffix),
                'speed' => self::cfgIntNullable('MGSC_SLIDER_SPEED_' . $suffix),
                'auto_height' => self::cfgBoolNullable('MGSC_SLIDER_AUTO_HEIGHT_' . $suffix),
                'center_enabled' => self::cfgBoolNullable('MGSC_SLIDER_CENTER_ENABLED_' . $suffix),
            ];
            foreach ($ovr as $k => $v) {
                if ($v !== null) { $base[$k] = $v; }
            }
        }

        return $base;
    }

    /**
     * Configuration helpers with sane fallbacks.
     */
    protected static function cfgBool(string $key, bool $default): bool
    {
        try {
            $v = \Configuration::get($key);
            if ($v === false || $v === null || $v === '') { return $default; }
            $s = strtolower((string)$v);
            if ($s === '1' || $s === 'true' || $s === 'yes' || $s === 'on') { return true; }
            if ($s === '0' || $s === 'false' || $s === 'no' || $s === 'off') { return false; }
            return $default;
        } catch (\Throwable $e) { return $default; }
    }
    protected static function cfgInt(string $key, int $default): int
    {
        try {
            $v = \Configuration::get($key);
            if ($v === false || $v === null || $v === '') { return $default; }
            $n = (int) $v;
            return ($n === 0 && $v !== '0') ? $default : $n;
        } catch (\Throwable $e) { return $default; }
    }
    protected static function cfgFloat(string $key, float $default): float
    {
        try {
            $v = \Configuration::get($key);
            if ($v === false || $v === null || $v === '') { return $default; }
            $s = str_replace(',', '.', (string)$v);
            if (!preg_match('/^-?\d+(?:\.\d+)?$/', $s)) { return $default; }
            return (float)$s;
        } catch (\Throwable $e) { return $default; }
    }
    protected static function cfgBoolNullable(string $key): ?bool
    {
        try {
            $v = \Configuration::get($key);
            if ($v === false || $v === null || $v === '') { return null; }
            $s = strtolower((string)$v);
            if ($s === '1' || $s === 'true' || $s === 'yes' || $s === 'on') { return true; }
            if ($s === '0' || $s === 'false' || $s === 'no' || $s === 'off') { return false; }
            return null;
        } catch (\Throwable $e) { return null; }
    }
    protected static function cfgIntNullable(string $key): ?int
    {
        try {
            $v = \Configuration::get($key);
            if ($v === false || $v === null || $v === '') { return null; }
            $n = (int) $v;
            return ($n === 0 && $v !== '0') ? null : $n;
        } catch (\Throwable $e) { return null; }
    }

    /**
     * Parse a boolean token value like "true", "1", "yes", "false", "0", "no".
     */
    protected static function parseBoolToken(string $val, ?bool $default = null): ?bool
    {
        $s = strtolower(trim($val));
        if ($s === '1' || $s === 'true' || $s === 'yes' || $s === 'on') { return true; }
        if ($s === '0' || $s === 'false' || $s === 'no' || $s === 'off') { return false; }
        return $default;
    }

    /**
     * Normalize order way to ASC/DESC and strip any extra tokens.
     */
    protected static function normalizeOrderWay(string $way, string $default = 'DESC'): string
    {
        $first = strtoupper((string)preg_split('/\s+/', trim($way))[0] ?? '');
        if ($first === 'ASC' || $first === 'DESC') {
            return $first;
        }
        return strtoupper($default);
    }

    /**
     * Normalize orderBy against an allowlist. Supports aliases like 'rand'/'random'.
     */
    protected static function normalizeOrderBy(string $by, string $default = 'position'): string
    {
        $first = strtolower((string)preg_split('/\s+/', trim($by))[0] ?? '');
        if ($first === 'rand' || $first === 'random') {
            return 'random';
        }
        $allowed = [
            'price', 'name', 'id_product', 'manufacturer', 'position', 'date_add', 'date_upd'
        ];
        if (in_array($first, $allowed, true)) {
            return $first;
        }
        return strtolower($default);
    }

    /**
     * Try to present a product using PrestaShop presenters. Returns null on failure.
     * We avoid strict imports to keep compatibility across PS versions.
     */
    protected static function presentProduct(Product $p, Context $context): ?array
    {
        try {
            if (class_exists('PrestaShop\\PrestaShop\\Adapter\\Presenter\\Product\\ProductListingPresenter')) {
                $assembler = new \PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductAssembler($context);
                $factory = new \PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductPresenterFactory($context);
                $presenter = new \PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductListingPresenter(
                    new \PrestaShop\PrestaShop\Adapter\Image\ImageRetriever($context->link),
                    $context->link,
                    new \PrestaShop\PrestaShop\Adapter\Product\PriceFormatter(),
                    new \PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever(),
                    $context->getTranslator()
                );
                return (array) $presenter->present(
                    $factory->getPresentationSettings(),
                    $assembler->assembleProduct($p),
                    $context->language
                );
            }
        } catch (\Throwable $e) { /* ignore */ }
        try {
            if (class_exists('PrestaShop\\PrestaShop\\Adapter\\Presenter\\Product\\ProductPresenter')) {
                $presenter = new \PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductPresenter($context->link, $context->getTranslator());
                return (array) $presenter->present($p);
            }
        } catch (\Throwable $e) { /* ignore */ }
        return null;
    }

    /**
     * Minimal fallback presentation when presenters are not available.
     * Ensures templates/hooks always receive an array, not a Product object.
     */
    protected static function presentProductFallback(Product $p, Context $context): array
    {
        $link = '';
        try {
            if (isset($context->link)) {
                $link = $context->link->getProductLink($p);
            } else {
                $link = 'index.php?controller=product&id_product=' . (int)$p->id;
            }
        } catch (\Throwable $e) {
            $link = 'index.php?controller=product&id_product=' . (int)$p->id;
        }

        $idImage = 0;
        try {
            $cover = \Image::getCover($p->id);
            if ($cover && isset($cover['id_image'])) {
                $idImage = (int) $cover['id_image'];
            }
        } catch (\Throwable $e) { $idImage = 0; }

        $rewrite = '';
        try {
            $rw = $p->link_rewrite;
            if (is_array($rw)) {
                $idLang = isset($context->language) ? (int)$context->language->id : null;
                $rewrite = $idLang && isset($rw[$idLang]) ? (string)$rw[$idLang] : (string)reset($rw);
            } else {
                $rewrite = (string)$rw;
            }
        } catch (\Throwable $e) { $rewrite = ''; }

        // Build a set of common types expected by themes
        $types = [
            'home_default',
            'category_product_default',
            'category_default',
            'large_default',
            'medium_default',
            'small_default',
        ];

        $bySize = [];
        foreach ($types as $t) {
            $url = '';
            try {
                if ($idImage > 0 && isset($context->link)) {
                    $url = $context->link->getImageLink($rewrite, $idImage, $t);
                }
            } catch (\Throwable $e) { $url = ''; }
            $bySize[$t] = ['url' => $url];
        }

        // Map convenience keys like cover.large.url if theme uses them
        $largeUrl = $bySize['large_default']['url'] ?? ($bySize['category_product_default']['url'] ?? '');
        $mediumUrl = $bySize['medium_default']['url'] ?? ($bySize['home_default']['url'] ?? '');
        $smallUrl = $bySize['small_default']['url'] ?? ($bySize['home_default']['url'] ?? '');

        $priceAmount = 0.0;
        $priceStr = '';
        try {
            $priceAmount = method_exists($p, 'getPrice') ? (float)$p->getPrice(true) : (float)$p->price;
            $priceStr = self::formatPrice($priceAmount);
        } catch (\Throwable $e) {
            $priceAmount = 0.0;
            $priceStr = '';
        }

        // Manufacturer data
        $manufacturerName = '';
        $idManufacturer = 0;
        try {
            $idManufacturer = (int) ($p->id_manufacturer ?? 0);
            if ($idManufacturer > 0) {
                if (isset($p->manufacturer_name) && is_string($p->manufacturer_name) && $p->manufacturer_name !== '') {
                    $manufacturerName = (string) $p->manufacturer_name;
                } else {
                    $manufacturerName = (string) \Manufacturer::getNameById($idManufacturer);
                }
            }
        } catch (\Throwable $e) { $manufacturerName = ''; }

        // Basic references
        $reference = '';
        $ean13 = '';
        try {
            $reference = (string) ($p->reference ?? '');
            $ean13 = (string) ($p->ean13 ?? '');
        } catch (\Throwable $e) { /* ignore */ }

        // SEO availability (schema.org)
        $seoAvailability = 'https://schema.org/OutOfStock';
        try {
            $qty = (int) ($p->quantity ?? 0);
            $available = property_exists($p, 'available_for_order') ? (bool) $p->available_for_order : true;
            $seoAvailability = ($qty > 0 && $available) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';
        } catch (\Throwable $e) { /* ignore */ }

        // Images list (used by theme to iterate over first two images)
        $images = [];
        try {
            $idLang = isset($context->language) ? (int) $context->language->id : 0;
            $rows = \Image::getImages($idLang, (int) $p->id);
            if (is_array($rows)) {
                foreach ($rows as $r) {
                    $iid = (int) ($r['id_image'] ?? 0);
                    if ($iid <= 0) { continue; }
                    $images[] = [
                        'id_image' => $iid,
                        'legend' => (string) ($r['legend'] ?? ''),
                        'cover' => isset($r['cover']) ? (bool) $r['cover'] : ($iid === $idImage),
                    ];
                }
            }
        } catch (\Throwable $e) { /* ignore */ }

        // Default image size info
        $msize = \Image::getSize('medium_default');
        $mw = isset($msize['width']) ? (int) $msize['width'] : 0;
        $mh = isset($msize['height']) ? (int) $msize['height'] : 0;

        return [
            'id' => (int)$p->id,
            'id_product' => (int)$p->id,
            'id_product_attribute' => property_exists($p, 'id_product_attribute') ? (int)$p->id_product_attribute : 0,
            'name' => (string)$p->name,
            'link_rewrite' => $rewrite,
            'link' => $link,
            'url' => $link,
            'id_manufacturer' => $idManufacturer,
            'manufacturer_name' => $manufacturerName,
            'reference' => $reference,
            'ean13' => $ean13,
            'seo_availability' => $seoAvailability,
            'price_amount' => $priceAmount,
            'price' => $priceStr,
            'show_price' => isset($p->show_price) ? (bool)$p->show_price : true,
            'cover' => [
                'id_image' => $idImage,
                'bySize' => $bySize,
                'url' => $largeUrl ?: $mediumUrl,
                'large' => ['url' => $largeUrl],
                'medium' => ['url' => $mediumUrl],
                'small' => ['url' => $smallUrl],
            ],
            'default_image' => [
                'id_image' => $idImage,
                'bySize' => [
                    'medium_default' => [
                        'width' => $mw,
                        'height' => $mh,
                        'url' => $mediumUrl,
                    ],
                ],
            ],
            'images' => $images,
            'id_image' => $idImage,
            'flags' => [],
        ];
    }

    /**
     * Ensure the presented product array contains URLs for commonly used image types
     * (e.g., category_product_default, large/medium/small). This prevents blanks like
     * "-category_product_default-.jpg" when a theme expects specific keys.
     */
    protected static function ensureCoverUrls(array $presented, Product $p, Context $context): array
    {
        try {
            if (!isset($presented['cover'])) { $presented['cover'] = []; }
            if (!isset($presented['cover']['bySize']) || !is_array($presented['cover']['bySize'])) {
                $presented['cover']['bySize'] = [];
            }

            $idImage = 0;
            $cover = \Image::getCover($p->id);
            if ($cover && isset($cover['id_image'])) { $idImage = (int)$cover['id_image']; }

            $rw = $p->link_rewrite;
            if (is_array($rw)) {
                $idLang = isset($context->language) ? (int)$context->language->id : null;
                $rewrite = $idLang && isset($rw[$idLang]) ? (string)$rw[$idLang] : (string)reset($rw);
            } else {
                $rewrite = (string)$rw;
            }

            $types = [
                'home_default', 'category_product_default', 'category_default',
                'large_default', 'medium_default', 'small_default'
            ];

            foreach ($types as $t) {
                if (!isset($presented['cover']['bySize'][$t]['url']) || $presented['cover']['bySize'][$t]['url'] === '') {
                    $url = '';
                    try {
                        if ($idImage > 0 && isset($context->link)) {
                            $url = $context->link->getImageLink($rewrite, $idImage, $t);
                        }
                    } catch (\Throwable $e) { $url = ''; }
                    $presented['cover']['bySize'][$t]['url'] = $url;
                }
            }

            $largeUrl = $presented['cover']['bySize']['large_default']['url'] ?? ($presented['cover']['bySize']['category_product_default']['url'] ?? '');
            $mediumUrl = $presented['cover']['bySize']['medium_default']['url'] ?? ($presented['cover']['bySize']['home_default']['url'] ?? '');
            $smallUrl = $presented['cover']['bySize']['small_default']['url'] ?? ($presented['cover']['bySize']['home_default']['url'] ?? '');

            if (!isset($presented['cover']['large'])) { $presented['cover']['large'] = ['url' => $largeUrl]; }
            if (!isset($presented['cover']['medium'])) { $presented['cover']['medium'] = ['url' => $mediumUrl]; }
            if (!isset($presented['cover']['small'])) { $presented['cover']['small'] = ['url' => $smallUrl]; }
            if (!isset($presented['cover']['url']) || $presented['cover']['url'] === '') {
                $presented['cover']['url'] = $largeUrl ?: $mediumUrl;
            }
            // Ensure webp source key for theme condition
            if (!isset($presented['cover']['bySize']['medium_default']['sources']) || !is_array($presented['cover']['bySize']['medium_default']['sources'])) {
                $presented['cover']['bySize']['medium_default']['sources'] = [];
            }
            if (empty($presented['cover']['bySize']['medium_default']['sources']['webp'])) {
                $webp = '';
                try {
                    if ($idImage > 0 && isset($context->link)) {
                        $webp = $context->link->getImageLink($rewrite, $idImage, 'medium_default', 'webp');
                    }
                } catch (\Throwable $e) { $webp = ''; }
                $presented['cover']['bySize']['medium_default']['sources']['webp'] = $webp;
            }

            // Ensure common top-level keys used by some themes
            if (!isset($presented['id_image'])) { $presented['id_image'] = $idImage; }
            if (!isset($presented['cover']['id_image'])) { $presented['cover']['id_image'] = $idImage; }
            if (!isset($presented['link_rewrite']) || $presented['link_rewrite'] === '') { $presented['link_rewrite'] = $rewrite; }

            // Ensure default_image with size info (width/height) for medium_default
            if (!isset($presented['default_image']) || !is_array($presented['default_image'])) { $presented['default_image'] = []; }
            if (!isset($presented['default_image']['id_image'])) { $presented['default_image']['id_image'] = $idImage; }
            if (!isset($presented['default_image']['bySize']) || !is_array($presented['default_image']['bySize'])) { $presented['default_image']['bySize'] = []; }
            if (!isset($presented['default_image']['bySize']['medium_default']) || !is_array($presented['default_image']['bySize']['medium_default'])) { $presented['default_image']['bySize']['medium_default'] = []; }
            $msize = \Image::getSize('medium_default');
            $mw = isset($msize['width']) ? (int) $msize['width'] : 0;
            $mh = isset($msize['height']) ? (int) $msize['height'] : 0;
            if (!isset($presented['default_image']['bySize']['medium_default']['width'])) { $presented['default_image']['bySize']['medium_default']['width'] = $mw; }
            if (!isset($presented['default_image']['bySize']['medium_default']['height'])) { $presented['default_image']['bySize']['medium_default']['height'] = $mh; }
            if (!isset($presented['default_image']['bySize']['medium_default']['url'])) { $presented['default_image']['bySize']['medium_default']['url'] = $mediumUrl; }

            // Ensure theme-specific optional keys exist to avoid undefined index notices
            if (!isset($presented['main_variants'])) { $presented['main_variants'] = []; }
        } catch (\Throwable $e) { /* keep presented as-is on failure */ }
        return $presented;
    }
}
