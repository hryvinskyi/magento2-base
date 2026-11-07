<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\Base\Test\Unit\Helpers\Fixture;

/**
 * A traversable object whose items differ from its public properties.
 *
 * @implements \IteratorAggregate<string, mixed>
 */
class JsonTraversable implements \IteratorAggregate
{
    /**
     * A public property the items do not include.
     *
     * @var string
     */
    public string $notAnItem = 'property';

    /**
     * Yields the items to encode.
     *
     * @return \Generator<string, mixed>
     */
    public function getIterator(): \Generator
    {
        yield 'item' => 'value';
        yield 'nested' => ['a' => 1];
    }
}
