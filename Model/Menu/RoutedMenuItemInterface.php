<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Model\Menu;

use Hryvinskyi\Base\Api\Menu\GroupedMenuItemInterface;

/**
 * A menu item whose route parameters are given after it has been created
 *
 * Route parameters come from layout XML and may hold keys such as "instance" or "argument". Handed to a generated
 * factory, such an array would be interpreted by the object manager in developer and default mode and passed
 * through untouched in production mode. The item is therefore created from scalar values only and receives its
 * route parameters through a copy, so this factory hop treats them the same in every mode. (The layout itself
 * still passes the block's whole `data`, these parameters included, through the object manager when it builds
 * the block.)
 */
interface RoutedMenuItemInterface extends GroupedMenuItemInterface
{
    /**
     * Get a copy of the item that builds its URL with the given route parameters
     *
     * @param array<string,mixed> $routeParams
     * @return RoutedMenuItemInterface
     */
    public function withRouteParams(array $routeParams): RoutedMenuItemInterface;
}
