<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Debug\Fixture;

/**
 * An object that refuses to be serialized and has neither a string form nor items, so its export is its dump.
 */
class VarDumperUnserializable
{
    /**
     * A property the dump shows.
     *
     * @var string
     */
    public string $name = 'value';

    /**
     * Refuses serialization.
     *
     * @return array<string, mixed>
     * @throws \LogicException always
     */
    public function __serialize(): array
    {
        throw new \LogicException('This object cannot be serialized.');
    }
}
