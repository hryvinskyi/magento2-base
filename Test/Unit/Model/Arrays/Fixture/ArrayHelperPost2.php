<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\Base\Test\Unit\Model\Arrays\Fixture;

/**
 * An object with public properties and a private one readable only through its getter.
 */
class ArrayHelperPost2 extends ArrayHelperMagicObject
{
    /**
     * @var int the post id
     */
    public int $id = 123;

    /**
     * @var string the post content
     */
    public string $content = 'test';

    /**
     * @var string a value hidden from property iteration
     */
    private string $secret = 's';

    /**
     * Returns the hidden value.
     *
     * @return string the hidden value
     */
    public function getSecret(): string
    {
        return $this->secret;
    }
}
