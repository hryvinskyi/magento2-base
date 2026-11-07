<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\Base\Test\Unit\Helpers\Fixture;

/**
 * An object whose __debugInfo() shows a computed total instead of its unit price.
 */
class CustomDebugInfo
{
    /**
     * Number of units.
     *
     * @var int
     */
    public int $volume = 0;

    /**
     * Price of one unit.
     *
     * @var int
     */
    public int $unitPrice = 0;

    /**
     * Returns the volume and the total price, leaving the unit price out.
     *
     * @see http://php.net/manual/en/language.oop5.magic.php#language.oop5.magic.debuginfo
     *
     * @return array{volume: int, totalPrice: int}
     */
    public function __debugInfo(): array
    {
        return [
            'volume' => $this->volume,
            'totalPrice' => $this->volume * $this->unitPrice,
        ];
    }
}
