<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Api\Menu;

/**
 * Factory interface for creating menu items from array configuration
 */
interface MenuItemFactoryInterface
{
    /**
     * Create menu item from array configuration
     *
     * @param array<string, mixed> $data
     * @return MenuItemInterface
     */
    public function create(array $data): MenuItemInterface;
}
