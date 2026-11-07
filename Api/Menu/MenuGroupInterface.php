<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Api\Menu;

/**
 * A titled group of entries in the admin menu, declared by the menu's owner
 *
 * Groups are listed by sort order; groups with the same sort order keep the order they are declared in.
 *
 * @api
 */
interface MenuGroupInterface
{
    /**
     * Get the code menu items name to be listed in this group
     *
     * @return string
     */
    public function getCode(): string;

    /**
     * Get the heading shown above the group's items
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Get the group's sort order; lower comes first
     *
     * @return int
     */
    public function getSortOrder(): int;
}
