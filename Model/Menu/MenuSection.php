<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Model\Menu;

use Hryvinskyi\Base\Api\Menu\MenuGroupInterface;
use Hryvinskyi\Base\Api\Menu\MenuItemInterface;

/**
 * One rendered part of the admin menu: a group, or the untitled part after every group, laid out in columns
 *
 * Created through the generated MenuSectionFactory. The untitled section is created without a group argument:
 * the compiled object manager reads an explicit null for a class-typed argument as "no value" and would inject a
 * shared instance in its place, which is why the group is the last parameter and defaults to null.
 */
class MenuSection
{
    /**
     * @param list<list<MenuItemInterface>> $columns The section's items, column by column, in display order
     * @param MenuGroupInterface|null $group The group shown as the section's heading; null for the untitled section
     */
    public function __construct(
        private readonly array $columns,
        private readonly ?MenuGroupInterface $group = null
    ) {
    }

    /**
     * Get the group shown as the section's heading; null for the untitled section after every group
     *
     * @return MenuGroupInterface|null
     */
    public function getGroup(): ?MenuGroupInterface
    {
        return $this->group;
    }

    /**
     * Get the section's items, column by column, in display order
     *
     * @return list<list<MenuItemInterface>>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }
}
