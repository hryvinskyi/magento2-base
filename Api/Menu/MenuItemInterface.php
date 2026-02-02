<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Api\Menu;

/**
 * Interface for menu items
 */
interface MenuItemInterface
{
    /**
     * Get menu item label
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Get menu item URL
     *
     * @return string
     */
    public function getUrl(): string;

    /**
     * Get menu item sort order
     *
     * @return int
     */
    public function getSortOrder(): int;

    /**
     * Check if menu item is active
     *
     * @return bool
     */
    public function isActive(): bool;

    /**
     * Get menu item CSS class
     *
     * @return string
     */
    public function getClass(): string;

    /**
     * Get menu item icon (SVG content or icon html)
     *
     * @return string
     */
    public function getIcon(): string;
}
