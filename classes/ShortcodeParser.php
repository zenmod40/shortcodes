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

namespace ShortCodes;

use Context;
use PrestaShopLogger;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ShortcodeParser
{
    /** SC-01 : au-delà, les balises restantes sont laissées telles quelles. */
    public const MAX_RENDERS = 50;

    /** [tag:arg1:arg2] ou [tag arg1 arg2] */
    public const PATTERN = '/\[([a-zA-Z][a-zA-Z0-9_-]*)([^\]]*)\]/';

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
        $renders = 0;
        $result = preg_replace_callback(self::PATTERN, function (array $matches) use ($self, &$renders) {
            $name = strtolower($matches[1] ?? '');
            $tail = isset($matches[2]) ? (string) $matches[2] : '';
            $tail = trim($tail);
            // Remove an optional leading ':' to keep backward compatibility
            if ($tail !== '' && $tail[0] === ':') {
                $tail = ltrim($tail, ':');
            }
            // Split args by either ':' or any whitespace, ignoring empties
            $args = $tail === '' ? [] : preg_split('/\s+|:/', $tail, -1, PREG_SPLIT_NO_EMPTY);

            if (!$self->registry->has($name) || ++$renders > self::MAX_RENDERS) {
                return $matches[0]; // inconnue ou plafond atteint : laissée telle quelle
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
