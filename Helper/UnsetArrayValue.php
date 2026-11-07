<?php
/**
 * Copyright (c) 2019-2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

namespace Hryvinskyi\Base\Helper;

/**
 * Marks a key that `ArrayHelper::merge()` removes from the former array.
 */
class UnsetArrayValue
{
    /**
     * Restores class state after using `var_export()`.
     *
     * @param array<string,mixed> $state the exported properties; the marker has none
     * @return UnsetArrayValue
     * @see https://www.php.net/manual/en/function.var-export.php
     */
    public static function __set_state($state)
    {
        return new self();
    }
}
