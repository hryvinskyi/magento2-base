<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\Base\Test\Unit\Model\Arrays\Fixture;

/**
 * An object that embeds another object in a public property.
 */
class ArrayHelperPost3 extends ArrayHelperMagicObject
{
    /**
     * @var int the post id
     */
    public int $id = 33;

    /**
     * @var ArrayHelperPost2 the embedded object
     */
    public ArrayHelperPost2 $subObject;

    /**
     * Builds the embedded object.
     */
    public function __construct()
    {
        $this->subObject = new ArrayHelperPost2();
    }
}
