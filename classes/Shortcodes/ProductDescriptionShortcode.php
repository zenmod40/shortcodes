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

namespace ShortCodes\Shortcodes;

use Context;

class ProductDescriptionShortcode
{
    /**
     * [product-description:ID]
     */
    public static function render(array $args, Context $context): string
    {
        return \ShortCodes\Core\ShortcodeEngine::render('product-description', $args, $context);
    }

    /**
     * [product-description-short:ID]
     */
    public static function renderShort(array $args, Context $context): string
    {
        return \ShortCodes\Core\ShortcodeEngine::render('product-description-short', $args, $context);
    }
}
