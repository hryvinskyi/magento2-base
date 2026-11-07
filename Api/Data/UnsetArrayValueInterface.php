<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Api\Data;

/**
 * Marks a key that `ArrayMergerInterface::merge()` removes from the array merged so far.
 *
 * Obtain an instance from the generated `UnsetArrayValueInterfaceFactory`; `create()` takes no arguments.
 *
 * @api
 */
interface UnsetArrayValueInterface
{
}
