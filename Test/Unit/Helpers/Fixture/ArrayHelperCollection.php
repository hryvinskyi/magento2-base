<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\Base\Test\Unit\Helpers\Fixture;

/**
 * A collection that is Traversable and ArrayAccess and exposes its items as properties.
 *
 * Reading an item that does not exist as a property raises PHP's undefined array key warning.
 *
 * @implements \ArrayAccess<array-key, mixed>
 * @implements \IteratorAggregate<array-key, mixed>
 */
class ArrayHelperCollection implements \ArrayAccess, \IteratorAggregate
{
    /**
     * @param array<array-key,mixed> $items the items of the collection
     */
    public function __construct(private array $items = [])
    {
    }

    /**
     * Reads an item as a property.
     *
     * @param string $name the item key
     * @return mixed the item
     */
    public function __get(string $name): mixed
    {
        return $this->items[$name];
    }

    /**
     * Yields the items with their keys.
     *
     * @return \Generator<array-key,mixed>
     */
    public function getIterator(): \Generator
    {
        yield from $this->items;
    }

    /**
     * Tells whether an item exists under the key, a `null` item included.
     *
     * @param array-key $offset the item key
     * @return bool whether the item exists
     */
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->items);
    }

    /**
     * Returns the item under the key.
     *
     * @param array-key $offset the item key
     * @return mixed the item
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset];
    }

    /**
     * Stores an item under the key, or appends it when the key is `null`.
     *
     * @param array-key|null $offset the item key
     * @param mixed $value the item
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
            return;
        }
        $this->items[$offset] = $value;
    }

    /**
     * Removes the item under the key.
     *
     * @param array-key $offset the item key
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }
}
