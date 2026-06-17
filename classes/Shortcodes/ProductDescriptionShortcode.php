<?php
/**
 * ShortCodes - Moteur de shortcodes pour PrestaShop
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
