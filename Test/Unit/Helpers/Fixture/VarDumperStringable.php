<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\Base\Test\Unit\Helpers\Fixture;

/**
 * A stringable object that cannot be serialized because it holds a closure.
 */
class VarDumperStringable
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
     * Returns the text the export falls back to.
     *
     * @return string
     */
    public function __toString(): string
    {
        return 'stringable fixture';
    }
}
