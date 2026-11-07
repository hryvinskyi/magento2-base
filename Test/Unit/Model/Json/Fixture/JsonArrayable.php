<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Json\Fixture;

use Hryvinskyi\Base\Api\Data\ArrayableInterface;

/**
 * An arrayable object whose array differs from its public properties and holds a nested object.
 */
class JsonArrayable implements ArrayableInterface
{
    /**
     * A public property the array does not include.
     *
     * @var string
     */
    public string $notInTheArray = 'property';

    /**
     * @inheritDoc
     */
    public function fields(): array
    {
        return ['id'];
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
        return ['id' => 7, 'nested' => (object) ['a' => 1]];
    }
}
