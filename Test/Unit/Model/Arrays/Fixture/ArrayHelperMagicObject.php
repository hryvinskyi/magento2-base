<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\Base\Test\Unit\Model\Arrays\Fixture;

/**
 * An object whose missing properties are served by `get<Name>()` getters, like a framework base object.
 */
class ArrayHelperMagicObject
{
    /**
     * Reads a property that is not accessible through its getter.
     *
     * @param string $name the property name
     * @return mixed the getter's value
     * @throws \Exception when the object has no getter for the property
     */
    public function __get(string $name): mixed
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        throw new \Exception('Getting unknown property: ' . static::class . '::' . $name);
    }
}
