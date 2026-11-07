<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Debug\Fixture;

use Hryvinskyi\Base\Api\Data\ArrayableInterface;

/**
 * An arrayable object that cannot be serialized because it holds a closure.
 */
class VarDumperArrayable implements ArrayableInterface
{
    /**
     * A closure, which makes the object unserializable.
     *
     * @var \Closure
     */
    public \Closure $callback;

    /**
     * Stores a closure so that serialization fails on the object.
     */
    public function __construct()
    {
        $this->callback = static fn (): int => 1;
    }

    /**
     * @inheritDoc
     */
    public function fields(): array
    {
        return ['id', 'label'];
    }

    /**
     * @inheritDoc
     */
    public function extraFields(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function toArray(array $fields = [], array $expand = [], bool $recursive = true): array
    {
        return ['id' => 3, 'label' => 'arrayable'];
    }
}
