<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\Base\Test\Unit\Model\Debug\Fixture;

/**
 * An iterable object that cannot be serialized because it holds a closure.
 *
 * @implements \IteratorAggregate<string, string>
 */
class VarDumperIterable implements \IteratorAggregate
{
    /**
     * A closure, which makes the object unserializable.
     *
     * @var \Closure
     */
    public \Closure $callback;

    /**
     * Stores a closure so that serialize() fails on the object.
     */
    public function __construct()
    {
        $this->callback = static fn (): int => 1;
    }

    /**
     * Yields the items the export falls back to.
     *
     * @return \Generator<string, string>
     */
    public function getIterator(): \Generator
    {
        yield 'first' => 'one';
        yield 'second' => 'two';
    }
}
