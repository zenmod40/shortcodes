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

namespace ShortCodes;

use Context;
use PrestaShopLogger;

class ShortcodeParser
{
    private ShortcodeRegistry $registry;
    private Context $context;

    public function __construct(ShortcodeRegistry $registry, Context $context)
    {
        $this->registry = $registry;
        $this->context = $context;
    }

    public function parse(string $content): string
    {
        if ($content === '') {
            return $content;
        }

        $self = $this;
        // Match tag name and capture any trailing content inside the brackets.
        // This supports both syntaxes:
        //   [tag:arg1:arg2] and [tag arg1 arg2]
        $pattern = '/\[([a-zA-Z][a-zA-Z0-9_-]*)([^\]]*)\]/';

        $result = preg_replace_callback($pattern, function (array $matches) use ($self) {
            $name = strtolower($matches[1] ?? '');
            $tail = isset($matches[2]) ? (string) $matches[2] : '';
            $tail = trim($tail);
            // Remove an optional leading ':' to keep backward compatibility
            if ($tail !== '' && $tail[0] === ':') {
                $tail = ltrim($tail, ':');
            }
            // Split args by either ':' or any whitespace, ignoring empties
            $args = $tail === '' ? [] : preg_split('/\s+|:/', $tail, -1, PREG_SPLIT_NO_EMPTY);

            if (!$self->registry->has($name)) {
                return $matches[0]; // leave untouched if unknown
            }

            $handler = $self->registry->get($name);
            try {
                return (string) call_user_func($handler, $args, $self->context);
            } catch (\Throwable $e) {
                // Log and fail safe: keep original tag visible to help diagnose
                try {
                    PrestaShopLogger::addLog('[ShortCodes] Error rendering [' . $name . ']: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine(), 3);
                } catch (\Throwable $ignored) {}
                return $matches[0];
            }
        }, $content);

        return $result ?? $content;
    }
}
