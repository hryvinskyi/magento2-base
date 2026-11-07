<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Api\Menu;

/**
 * Creates admin menu groups from layout configuration
 *
 * @api
 */
interface MenuGroupFactoryInterface
{
    /**
     * Create the group declared under the given code
     *
     * Known keys: label (required, a non-empty string or a translatable phrase), sort_order (a number, default 0).
     * A group without a label, an empty code, or a known key holding a value of the wrong type is rejected rather
     * than coerced.
     *
     * @param string $code The group's code, which menu items name in their "group" key
     * @param array<string,mixed> $data
     * @return MenuGroupInterface
     * @throws \InvalidArgumentException When the group is malformed; the message names the offending key
     */
    public function create(string $code, array $data): MenuGroupInterface;
}
