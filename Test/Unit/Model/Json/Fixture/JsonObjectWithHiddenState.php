<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\Base\Test\Unit\Model\Json\Fixture;

/**
 * A plain object with public, protected and private properties.
 */
class JsonObjectWithHiddenState
{
    /**
     * A public property, which is encoded.
     *
     * @var string
     */
    public string $visible = 'shown';

    /**
     * A protected property, which is not encoded.
     *
     * @var string
     */
    protected string $guarded = 'hidden';

    /**
     * A private property, which is not encoded.
     *
     * @var string
     */
    private string $secret = 'hidden';

    /**
     * Returns the hidden state, so that it is used.
     *
     * @return string
     */
    public function hiddenState(): string
    {
        return $this->guarded . $this->secret;
    }
}
