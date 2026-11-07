<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\Base\Test\Unit\Helpers\Fixture;

/**
 * A selection that is Traversable but not an array, for the list helpers of Html.
 *
 * @implements \IteratorAggregate<int, string>
 */
class HtmlTraversableSelection implements \IteratorAggregate
{
    /**
     * @param list<string> $values the selected values
     */
    public function __construct(private readonly array $values)
    {
    }

    /**
     * Yields the selected values.
     *
     * @return \Generator<int, string>
     */
    public function getIterator(): \Generator
    {
        yield from $this->values;
    }
}
