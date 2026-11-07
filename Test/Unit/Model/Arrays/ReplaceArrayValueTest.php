<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Arrays;

use Hryvinskyi\Base\Model\Arrays\ReplaceArrayValue;
use PHPUnit\Framework\TestCase;

class ReplaceArrayValueTest extends TestCase
{
    public function testAFreshInstanceCarriesNull(): void
    {
        $this->assertNull((new ReplaceArrayValue())->getValue());
    }

    public function testWithValueReturnsACopyAndLeavesTheInstanceUnchanged(): void
    {
        $empty = new ReplaceArrayValue();
        $replacement = $empty->withValue(['gii']);

        $this->assertNotSame($empty, $replacement);
        $this->assertSame(['gii'], $replacement->getValue());
        $this->assertNull($empty->getValue());
    }

    public function testWithValueCarriesArbitraryDataAsItIs(): void
    {
        $value = ['argument' => 'x', 'nested' => ['instance' => 'stdClass']];

        $this->assertSame($value, (new ReplaceArrayValue())->withValue($value)->getValue());
    }
}
