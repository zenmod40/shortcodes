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

use Module;

class ShortcodeRegistry
{
    /** @var array<string, callable> */
    private array $handlers = [];

    private Module $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    public function register(string $name, callable $handler): void
    {
        $key = strtolower($name);
        $this->handlers[$key] = $handler;
    }

    public function has(string $name): bool
    {
        return isset($this->handlers[strtolower($name)]);
    }

    public function get(string $name): ?callable
    {
        $key = strtolower($name);
        return $this->handlers[$key] ?? null;
    }

    public function registerDefaults(): void
    {
        // Centralized dispatch via Core ShortcodeEngine
        $this->register('product', function (array $args, \Context $context) {
            return \ShortCodes\Core\ShortcodeEngine::render('product', $args, $context);
        });
        $this->register('products', function (array $args, \Context $context) {
            return \ShortCodes\Core\ShortcodeEngine::render('products', $args, $context);
        });
        $this->register('product-description', function (array $args, \Context $context) {
            return \ShortCodes\Core\ShortcodeEngine::render('product-description', $args, $context);
        });
        $this->register('product-description-short', function (array $args, \Context $context) {
            return \ShortCodes\Core\ShortcodeEngine::render('product-description-short', $args, $context);
        });
        $this->register('last-products', function (array $args, \Context $context) {
            return \ShortCodes\Core\ShortcodeEngine::render('last-products', $args, $context);
        });
        $this->register('category', function (array $args, \Context $context) {
            return \ShortCodes\Core\ShortcodeEngine::render('category', $args, $context);
        });
        $this->register('brands', function (array $args, \Context $context) {
            return \ShortCodes\Core\ShortcodeEngine::render('brands', $args, $context);
        });
        // TODO: supplier, sale, productgallery, categories links, special modules
    }
}
