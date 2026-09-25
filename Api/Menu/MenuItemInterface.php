<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Api\Menu;

/**
 * One entry of the in-page admin menu rendered by the Base menu block
 *
 * @api
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

    /**
     * Get the ACL resource id the current admin user needs to see this item
     *
     * Null means the item carries no permission check of its own and is shown to every admin user
     * who can open the page that renders the menu.
     *
     * @return string|null
     */
    public function getResource(): ?string;
}
