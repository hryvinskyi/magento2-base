<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Model\Arrays;

use Hryvinskyi\Base\Api\Data\ReplaceArrayValueInterface;

/**
 * Carries a value that the array merger puts in place of the former one instead of merging recursively.
 *
 * Created empty, with no constructor arguments; the value arrives through `withValue()`, which returns a copy.
 */
class ReplaceArrayValue implements ReplaceArrayValueInterface
{
    /**
     * @var mixed the replacement value, set only on the copy `withValue()` returns
     */
    private mixed $value = null;

    /**
     * @inheritdoc
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * @inheritdoc
     */
    public function withValue(mixed $value): ReplaceArrayValueInterface
    {
        $copy = clone $this;
        $copy->value = $value;

        return $copy;
    }
}
