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

namespace ShortCodes\Core;

use Context;
use Product;
use Validate;
use Image;
use Tools;
use PrestaShopLogger;
use ShortCodes\Support\ShortcodeHelpers;
use Manufacturer;

class ShortcodeEngine
{
    use ShortcodeHelpers;

    /**
     * Central dispatcher. Returns templates-only HTML or debug overlay.
     *
     * @param string  $name    Shortcode key (product, products, category, last-products, product-description, product-description-short)
     * @param mixed[] $args    Parsed args from parser
     */
    public static function render(string $name, array $args, Context $context): string
    {
        switch (strtolower($name)) {
            case 'product':
                return self::renderProduct($args, $context);
            case 'products':
                return self::renderProducts($args, $context);
            case 'category':
                return self::renderCategory($args, $context);
            case 'last-products':
                return self::renderLastProducts($args, $context);
            case 'product-description':
                return self::renderProductDescription($args, $context, false);
            case 'product-description-short':
                return self::renderProductDescription($args, $context, true);
            case 'brands':
                return self::renderBrands($args, $context);
            default:
                return '';
        }
    }

    private static function renderProduct(array $args, Context $context): string
    {
        $id = isset($args[0]) ? (int)$args[0] : 0;
        if ($id <= 0) {
            return '';
        }
        $idLang = (int)$context->language->id;
        $product = new Product($id, true, $idLang, $context->shop->id);
        if (!Validate::isLoadedObject($product) || !$product->active) {
            return '';
        }
        $link = '';
        try { $link = $context->link->getProductLink($product); } catch (\Throwable $e) { $link = 'index.php?controller=product&id_product='.(int)$product->id; }
        $imageUrl = '';
        try {
            $cover = Image::getCover($product->id);
            if ($cover && isset($cover['id_image'])) {
                $imageUrl = $context->link->getImageLink($product->link_rewrite, (int)$cover['id_image'], 'home_default');
            }
        } catch (\Throwable $e) { $imageUrl = ''; }

        $smarty = $context->smarty;
        $priceStr = '';
        try {
            $amount = method_exists($product, 'getPrice') ? (float)$product->getPrice(true) : (float)$product->price;
            $priceStr = self::formatPrice($amount);
        } catch (\Throwable $e) { $priceStr = ''; }

        $presented = (self::presentProduct($product, $context) ?? self::presentProductFallback($product, $context));
        $presented = self::ensureCoverUrls($presented, $product, $context);
        $smarty->assign([
            'sc_product' => $presented,
            'sc_product_link' => $link,
            'sc_product_image' => $imageUrl,
            'sc_product_price' => $priceStr,
        ]);
        try {
            $out = $smarty->fetch(_PS_MODULE_DIR_ . 'shortcodes/views/templates/front/product_card.tpl');
        } catch (\Throwable $e) {
            try { PrestaShopLogger::addLog('[ShortCodes] product fetch error: '.$e->getMessage(), 3); } catch (\Throwable $ignored) {}
            $out = '';
        }
        if (!is_string($out) || trim((string)$out) === '') {
            try { $out = $smarty->fetch('module:shortcodes/views/templates/front/product_card.tpl'); } catch (\Throwable $e) { /* ignore */ }
        }
        $fetchedEmpty = !is_string($out) || trim((string)$out) === '';
        if ($fetchedEmpty) { return ''; }
        return (string)$out;
    }

    private static function renderProducts(array $args, Context $context): string
    {
        if (empty($args[0])) { return ''; }
        $ids = array_filter(array_map('intval', explode(',', (string)$args[0])));
        if (!$ids) { return ''; }
        $idLang = (int)$context->language->id;
        $useSlider = self::isSlider($args);

        $products = [];
        foreach ($ids as $id) {
            $p = new Product($id, true, $idLang, $context->shop->id);
            if (Validate::isLoadedObject($p) && $p->active) { $products[] = $p; }
        }
        if (!$products) { return ''; }

        $items = [];
        foreach ($products as $p) {
            $imageUrl = '';
            try {
                $cover = Image::getCover($p->id);
                if ($cover && isset($cover['id_image']) && isset($context->link)) {
                    $imageUrl = $context->link->getImageLink($p->link_rewrite, (int)$cover['id_image'], 'home_default');
                }
            } catch (\Throwable $e) { $imageUrl = ''; }
            $productLink = '';
            try {
                if (isset($context->link)) {
                    $productLink = $context->link->getProductLink($p);
                } else {
                    $productLink = 'index.php?controller=product&id_product=' . (int)$p->id;
                }
            } catch (\Throwable $e) { $productLink = 'index.php?controller=product&id_product=' . (int)$p->id; }
            $priceStr = '';
            try {
                $amount = method_exists($p, 'getPrice') ? (float)$p->getPrice(true) : (float)$p->price;
                $priceStr = self::formatPrice($amount);
            } catch (\Throwable $e) { $priceStr = ''; }
            $presented = (self::presentProduct($p, $context) ?? self::presentProductFallback($p, $context));
            $presented = self::ensureCoverUrls($presented, $p, $context);
            $items[] = [
                'product' => $presented,
                'product_link' => $productLink,
                'product_image' => $imageUrl,
                'product_price' => $priceStr,
            ];
        }

        $assign = [
            'sc_items' => $items,
            'sc_slider' => $useSlider,
            'sc_slider_id' => 'sc-products-' . uniqid(),
        ];
        if ($useSlider) {
            $cfg = self::parseSliderConfig($args);
            $assign['sc_spv'] = $cfg['spv'];
            $assign['sc_spv_xl'] = $cfg['spv_xl'];
            $assign['sc_spv_lg'] = $cfg['spv_lg'];
            $assign['sc_spv_md'] = $cfg['spv_md'];
            $assign['sc_spv_sm'] = $cfg['spv_sm'];
            $assign['sc_spv_xs'] = $cfg['spv_xs'];
            $bh = self::getSliderBehavior($context);
            $ovAutoplay = self::parseAutoplayOverride($args);
            if ($ovAutoplay !== null) { $bh['autoplay_enabled'] = (bool)$ovAutoplay; }
            $assign['sc_autoplay_enabled'] = $bh['autoplay_enabled'];
            $assign['sc_space_between'] = $bh['space_between'];
            $assign['sc_speed'] = $bh['speed'];
            $assign['sc_auto_height'] = $bh['auto_height'];
            // Center flags per breakpoint: start from BO defaults, allow shortcode to override
            $centerDefaults = self::defaultCenterConfig($context);
            $center = self::parseCenterConfigWithDefaults($args, $centerDefaults);
            $assign['sc_center_enabled'] = $center['base'];
            $assign['sc_center_enabled_xl'] = $center['xl'];
            $assign['sc_center_enabled_lg'] = $center['lg'];
            $assign['sc_center_enabled_md'] = $center['md'];
            $assign['sc_center_enabled_sm'] = $center['sm'];
            $assign['sc_center_enabled_xs'] = $center['xs'];
        }
        $context->smarty->assign($assign);
        try {
            $tpl = $useSlider
                ? _PS_MODULE_DIR_ . 'shortcodes/views/templates/front/product_slider.tpl'
                : _PS_MODULE_DIR_ . 'shortcodes/views/templates/front/product_list.tpl';
            $out = $context->smarty->fetch($tpl);
        } catch (\Throwable $e) {
            try { PrestaShopLogger::addLog('[ShortCodes] products fetch error: ' . $e->getMessage(), 3); } catch (\Throwable $ignored) {}
            $out = '';
        }
        if (!is_string($out) || trim((string)$out) === '') {
            $tpl2 = $useSlider
                ? 'module:shortcodes/views/templates/front/product_slider.tpl'
                : 'module:shortcodes/views/templates/front/product_list.tpl';
            try { $out = $context->smarty->fetch($tpl2); } catch (\Throwable $e) { /* ignore */ }
        }
        $fetchedEmpty = !is_string($out) || trim((string)$out) === '';
        if ($fetchedEmpty) { return ''; }
        return (string)$out;
    }

    private static function renderCategory(array $args, Context $context): string
    {
        $catId = isset($args[0]) ? (int)$args[0] : 0;
        $limit = isset($args[1]) ? max(1, (int)$args[1]) : 8;
        $orderBy = isset($args[2]) ? self::normalizeOrderBy((string)$args[2], 'position') : 'position';
        $orderWay = isset($args[3]) ? self::normalizeOrderWay((string)$args[3], 'DESC') : 'DESC';
        $useSlider = self::isSlider($args);

        if ($catId <= 0) { return ''; }
        $idLang = (int)$context->language->id;

        if ($orderBy === 'random') { $orderBy = 'RAND()'; $orderWay = ''; }

        $products = Product::getProducts(
            (int) $context->language->id,
            0,
            $limit,
            $orderBy,
            $orderWay,
            $catId,
            true,
            $context
        );
        if (!$products) { return ''; }

        $items = [];
        foreach ($products as $row) {
            $p = new Product((int)$row['id_product'], true, $idLang, $context->shop->id);
            $imageUrl = '';
            try {
                $cover = Image::getCover($p->id);
                if ($cover && isset($cover['id_image']) && isset($context->link)) {
                    $imageUrl = $context->link->getImageLink($p->link_rewrite, (int)$cover['id_image'], 'home_default');
                }
            } catch (\Throwable $e) { $imageUrl = ''; }
            $productLink = '';
            try {
                if (isset($context->link)) {
                    $productLink = $context->link->getProductLink($p);
                } else {
                    $productLink = 'index.php?controller=product&id_product=' . (int)$p->id;
                }
            } catch (\Throwable $e) { $productLink = 'index.php?controller=product&id_product=' . (int)$p->id; }
            $priceStr = '';
            try {
                $amount = method_exists($p, 'getPrice') ? (float)$p->getPrice(true) : (float)$p->price;
                $priceStr = self::formatPrice($amount);
            } catch (\Throwable $e) { $priceStr = ''; }
            $presented = (self::presentProduct($p, $context) ?? self::presentProductFallback($p, $context));
            $presented = self::ensureCoverUrls($presented, $p, $context);
            $items[] = [
                'product' => $presented,
                'product_link' => $productLink,
                'product_image' => $imageUrl,
                'product_price' => $priceStr,
            ];
        }

        $assign = [
            'sc_items' => $items,
            'sc_slider' => $useSlider,
            'sc_slider_id' => 'sc-cat-' . uniqid(),
        ];
        if ($useSlider) {
            $cfg = self::parseSliderConfig($args);
            $assign['sc_spv'] = $cfg['spv'];
            $assign['sc_spv_xl'] = $cfg['spv_xl'];
            $assign['sc_spv_lg'] = $cfg['spv_lg'];
            $assign['sc_spv_md'] = $cfg['spv_md'];
            $assign['sc_spv_sm'] = $cfg['spv_sm'];
            $assign['sc_spv_xs'] = $cfg['spv_xs'];
            $bh = self::getSliderBehavior($context);
            $ovAutoplay = self::parseAutoplayOverride($args);
            if ($ovAutoplay !== null) { $bh['autoplay_enabled'] = (bool)$ovAutoplay; }
            $assign['sc_autoplay_enabled'] = $bh['autoplay_enabled'];
            $assign['sc_space_between'] = $bh['space_between'];
            $assign['sc_speed'] = $bh['speed'];
            $assign['sc_auto_height'] = $bh['auto_height'];
            // Center flags per breakpoint: BO defaults + shortcode overrides
            $centerDefaults = self::defaultCenterConfig($context);
            $center = self::parseCenterConfigWithDefaults($args, $centerDefaults);
            $assign['sc_center_enabled'] = $center['base'];
            $assign['sc_center_enabled_xl'] = $center['xl'];
            $assign['sc_center_enabled_lg'] = $center['lg'];
            $assign['sc_center_enabled_md'] = $center['md'];
            $assign['sc_center_enabled_sm'] = $center['sm'];
            $assign['sc_center_enabled_xs'] = $center['xs'];
        }
        $context->smarty->assign($assign);
        try {
            $tpl = $useSlider
                ? _PS_MODULE_DIR_ . 'shortcodes/views/templates/front/product_slider.tpl'
                : _PS_MODULE_DIR_ . 'shortcodes/views/templates/front/product_list.tpl';
            $out = $context->smarty->fetch($tpl);
        } catch (\Throwable $e) {
            try { PrestaShopLogger::addLog('[ShortCodes] category fetch error: ' . $e->getMessage(), 3); } catch (\Throwable $ignored) {}
            $out = '';
        }
        if (!is_string($out) || trim((string)$out) === '') {
            $tpl2 = $useSlider
                ? 'module:shortcodes/views/templates/front/product_slider.tpl'
                : 'module:shortcodes/views/templates/front/product_list.tpl';
            try { $out = $context->smarty->fetch($tpl2); } catch (\Throwable $e) { /* ignore */ }
        }
        $fetchedEmpty = !is_string($out) || trim((string)$out) === '';
        if ($fetchedEmpty) { return ''; }
        return (string)$out;
    }

    private static function renderLastProducts(array $args, Context $context): string
    {
        $limit = isset($args[0]) ? max(1, (int)$args[0]) : 8;
        $useSlider = self::isSlider($args);
        $idLang = (int) $context->language->id;

        $products = Product::getNewProducts($idLang, 0, $limit, false, 'date_add', 'DESC', $context);
        if (!$products) { return ''; }

        $items = [];
        foreach ($products as $row) {
            $p = new Product((int)$row['id_product'], true, $idLang, $context->shop->id);
            $imageUrl = '';
            try {
                $cover = Image::getCover($p->id);
                if ($cover && isset($cover['id_image']) && isset($context->link)) {
                    $imageUrl = $context->link->getImageLink($p->link_rewrite, (int)$cover['id_image'], 'home_default');
                }
            } catch (\Throwable $e) { $imageUrl = ''; }
            $productLink = '';
            try {
                if (isset($context->link)) {
                    $productLink = $context->link->getProductLink($p);
                } else {
                    $productLink = 'index.php?controller=product&id_product=' . (int)$p->id;
                }
            } catch (\Throwable $e) { $productLink = 'index.php?controller=product&id_product=' . (int)$p->id; }
            $priceStr = '';
            try {
                $amount = method_exists($p, 'getPrice') ? (float)$p->getPrice(true) : (float)$p->price;
                $priceStr = self::formatPrice($amount);
            } catch (\Throwable $e) { $priceStr = ''; }
            $presented = (self::presentProduct($p, $context) ?? self::presentProductFallback($p, $context));
            $presented = self::ensureCoverUrls($presented, $p, $context);
            $items[] = [
                'product' => $presented,
                'product_link' => $productLink,
                'product_image' => $imageUrl,
                'product_price' => $priceStr,
            ];
        }

        $assign = [
            'sc_items' => $items,
            'sc_slider' => $useSlider,
            'sc_slider_id' => 'sc-last-' . uniqid(),
        ];
        if ($useSlider) {
            $cfg = self::parseSliderConfig($args);
            $assign['sc_spv'] = $cfg['spv'];
            $assign['sc_spv_xl'] = $cfg['spv_xl'];
            $assign['sc_spv_lg'] = $cfg['spv_lg'];
            $assign['sc_spv_md'] = $cfg['spv_md'];
            $assign['sc_spv_sm'] = $cfg['spv_sm'];
            $assign['sc_spv_xs'] = $cfg['spv_xs'];
            $bh = self::getSliderBehavior($context);
            $ovAutoplay = self::parseAutoplayOverride($args);
            if ($ovAutoplay !== null) { $bh['autoplay_enabled'] = (bool)$ovAutoplay; }
            $assign['sc_autoplay_enabled'] = $bh['autoplay_enabled'];
            $assign['sc_space_between'] = $bh['space_between'];
            $assign['sc_speed'] = $bh['speed'];
            $assign['sc_auto_height'] = $bh['auto_height'];
            // Center flags per breakpoint: BO defaults + shortcode overrides
            $centerDefaults = self::defaultCenterConfig($context);
            $center = self::parseCenterConfigWithDefaults($args, $centerDefaults);
            $assign['sc_center_enabled'] = $center['base'];
            $assign['sc_center_enabled_xl'] = $center['xl'];
            $assign['sc_center_enabled_lg'] = $center['lg'];
            $assign['sc_center_enabled_md'] = $center['md'];
            $assign['sc_center_enabled_sm'] = $center['sm'];
            $assign['sc_center_enabled_xs'] = $center['xs'];
        }
        $context->smarty->assign($assign);
        try {
            $tpl = $useSlider
                ? _PS_MODULE_DIR_ . 'shortcodes/views/templates/front/product_slider.tpl'
                : _PS_MODULE_DIR_ . 'shortcodes/views/templates/front/product_list.tpl';
            $out = $context->smarty->fetch($tpl);
        } catch (\Throwable $e) {
            try { PrestaShopLogger::addLog('[ShortCodes] last-products fetch error: ' . $e->getMessage(), 3); } catch (\Throwable $ignored) {}
            $out = '';
        }
        if (!is_string($out) || trim((string)$out) === '') {
            $tpl2 = $useSlider
                ? 'module:shortcodes/views/templates/front/product_slider.tpl'
                : 'module:shortcodes/views/templates/front/product_list.tpl';
            try { $out = $context->smarty->fetch($tpl2); } catch (\Throwable $e) { /* ignore */ }
        }
        $fetchedEmpty = !is_string($out) || trim((string)$out) === '';
        if ($fetchedEmpty) { return ''; }
        return (string)$out;
    }

    private static function renderProductDescription(array $args, Context $context, bool $short): string
    {
        $id = isset($args[0]) ? (int)$args[0] : 0;
        if ($id <= 0) { return ''; }
        $product = new Product($id, true, (int)$context->language->id, $context->shop->id);
        if (!Validate::isLoadedObject($product) || !$product->active) { return ''; }
        return (string) ($short ? $product->description_short : $product->description);
    }

    /**
     * [brands limit=24 order=name]
     * Renders a simple brands (manufacturers) logo grid using templates-only output.
     */
    private static function renderBrands(array $args, Context $context): string
    {
        $idLang = (int) $context->language->id;
        $idShop = (int) $context->shop->id;
        $limit = 24;
        if (isset($args[0]) && is_numeric($args[0])) { $limit = max(1, (int)$args[0]); }
        $order = 'name';
        if (isset($args[1]) && is_string($args[1])) { $o = strtolower((string)$args[1]); if (in_array($o, ['name','position','random'], true)) { $order = $o; } }

        // Optional options (from position 2+ or in free-form tokens): ratio=1/1, hover=veil|shine|diagonal|none
        $ratioClass = '';
        $hoverClass = 'mgsc-hover-veil';
        if (!empty($args)) {
            for ($i = 2; $i < count($args); $i++) {
                $token = trim((string)$args[$i]);
                if ($token === '') { continue; }
                if (strpos($token, '=') !== false) {
                    list($k, $v) = array_map('trim', explode('=', $token, 2));
                    $k = strtolower($k);
                    $v = strtolower($v);
                    if ($k === 'ratio') {
                        $v = str_replace([' ', '\\'], ['', '/'], $v);
                        if (in_array($v, ['1/1','4/3','16/9'], true)) {
                            $ratioClass = 'mgsc-ratio-' . str_replace('/', '-', $v);
                        }
                    } elseif ($k === 'hover') {
                        if (in_array($v, ['veil','shine','diagonal','none'], true)) {
                            $hoverClass = ($v === 'none') ? '' : 'mgsc-hover-' . $v;
                        }
                    }
                }
            }
        }

        // Fetch manufacturers (simple and robust): use manufacturer.name directly
        try {
            $orderBy = ($order === 'position') ? 'm.`position`' : 'm.`name`';
            $orderWay = 'ASC';
            if ($order === 'random') { $orderBy = 'RAND()'; }
            $sql = 'SELECT m.`id_manufacturer` AS id, m.`name`
                    FROM `' . _DB_PREFIX_ . 'manufacturer` m
                    WHERE m.`active` = 1
                    ORDER BY ' . $orderBy . ' ' . ($order === 'random' ? '' : $orderWay) . '
                    LIMIT ' . (int)$limit;
            $rows = \Db::getInstance()->executeS($sql) ?: [];
        } catch (\Throwable $e) {
            try { PrestaShopLogger::addLog('[ShortCodes] brands query error: ' . $e->getMessage(), 3); } catch (\Throwable $ignored) {}
            $rows = [];
        }

        // Fallback: try with manufacturer_lang join only if needed (should be rare)
        if (!$rows) {
            try {
                $orderBy = ($order === 'position') ? 'm.`position`' : 'COALESCE(m.`name`, ml.`description`)';
                $orderWay = 'ASC';
                if ($order === 'random') { $orderBy = 'RAND()'; }
                $sql2 = 'SELECT m.`id_manufacturer` AS id, m.`name` AS name
                         FROM `' . _DB_PREFIX_ . 'manufacturer` m
                         WHERE m.`active` = 1
                         ORDER BY ' . $orderBy . ' ' . ($order === 'random' ? '' : $orderWay) . '
                         LIMIT ' . (int)$limit;
                $rows = \Db::getInstance()->executeS($sql2) ?: [];
                try { PrestaShopLogger::addLog('[ShortCodes] brands fallback SQL used (prefix=' . _DB_PREFIX_ . ', id_lang='.(int)$idLang.', id_shop='.(int)$idShop.'): ' . $sql2, 1); } catch (\Throwable $e) {}
            } catch (\Throwable $e) {
                try { PrestaShopLogger::addLog('[ShortCodes] brands fallback query error: ' . $e->getMessage(), 3); } catch (\Throwable $ignored) {}
                $rows = [];
            }
        }

        try { PrestaShopLogger::addLog('[ShortCodes] brands rows found: ' . (is_array($rows) ? count($rows) : 0), 1); } catch (\Throwable $e) {}
        if (!$rows) {
            // Second fallback: use PS core API
            try {
                $mans = \Manufacturer::getManufacturers((int)$idLang, true, (int)$idShop, false, false, (int)$limit);
                if (is_array($mans) && count($mans) > 0) {
                    $rows = [];
                    foreach ($mans as $m) {
                        $rows[] = [ 'id' => (int)$m['id_manufacturer'], 'name' => (string)$m['name'] ];
                    }
                    try { PrestaShopLogger::addLog('[ShortCodes] brands PS API fallback used: ' . count($rows) . ' items', 1); } catch (\Throwable $e) {}
                }
            } catch (\Throwable $e) {
                try { PrestaShopLogger::addLog('[ShortCodes] brands PS API fallback error: ' . $e->getMessage(), 2); } catch (\Throwable $ignored) {}
            }

            if (!$rows) {
                // Deep diagnostic: count active manufacturers directly
                try {
                    $cntRow = \Db::getInstance()->getRow('SELECT COUNT(*) AS c FROM `' . _DB_PREFIX_ . 'manufacturer` WHERE `active` = 1');
                    $cnt = isset($cntRow['c']) ? (int)$cntRow['c'] : -1;
                    try { PrestaShopLogger::addLog('[ShortCodes] brands diag: COUNT(active manufacturers)=' . $cnt . ' (prefix=' . _DB_PREFIX_ . ', id_lang='.(int)$idLang.', id_shop='.(int)$idShop.')', 1); } catch (\Throwable $e) {}
                } catch (\Throwable $e) {
                    try { PrestaShopLogger::addLog('[ShortCodes] brands diag count error: ' . $e->getMessage(), 2); } catch (\Throwable $ignored) {}
                }
                return '<!-- mgsc: brands=0 -->';
            }
        }

        $items = [];
        foreach ($rows as $r) {
            $id = (int) $r['id'];
            $name = (string) $r['name'];
            $link = '';
            try { $link = $context->link->getManufacturerLink($id); } catch (\Throwable $e) { $link = 'index.php?controller=manufacturer&id_manufacturer=' . $id; }
            // Try to build a media URL to the original logo (/img/m/{id}.jpg). If not present, leave empty.
            $logo = '';
            try { $logo = $context->link->getMediaLink('/img/m/' . $id . '.jpg'); } catch (\Throwable $e) { $logo = ''; }
            $items[] = [
                'id' => $id,
                'name' => $name,
                'link' => $link,
                'logo' => $logo,
            ];
        }

        $wrapperClasses = trim(($ratioClass ? $ratioClass . ' ' : '') . ($hoverClass ?: ''));
        $context->smarty->assign([
            'sc_brands' => $items,
            'sc_brand_grid_classes' => $wrapperClasses,
        ]);

        try {
            $out = $context->smarty->fetch(_PS_MODULE_DIR_ . 'shortcodes/views/templates/front/brand_grid.tpl');
        } catch (\Throwable $e) {
            try { PrestaShopLogger::addLog('[ShortCodes] brands fetch error: ' . $e->getMessage(), 3); } catch (\Throwable $ignored) {}
            $out = '';
        }
        if (!is_string($out) || trim((string)$out) === '') {
            try { $out = $context->smarty->fetch('module:shortcodes/views/templates/front/brand_grid.tpl'); } catch (\Throwable $e) { /* ignore */ }
        }
        $fetchedEmpty = !is_string($out) || trim((string)$out) === '';
        if ($fetchedEmpty) { return ''; }
        return (string)$out;
    }
}
