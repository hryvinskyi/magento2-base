<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Debug\Fixture;

/**
 * An object with an untyped __debugInfo(), which a test double can make break its contract by returning something
 * other than an array (a native return type would make PHP refuse that at compile time).
 */
class InvalidDebugInfo
{
    /**
     * Returns no debug values.
     *
     * @return array<mixed>
     */
    public function __debugInfo()
    {
        return [];
    }
}
