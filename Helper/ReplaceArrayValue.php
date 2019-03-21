<?php
/**
 * Copyright (c) 2019. Volodumur Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodumur@hryvinskyi.com>
 * @github: <https://github.com/scriptua>
 */

namespace Hryvinskyi\Base\Helper;


class ReplaceArrayValue
{
    /**
     * @var mixed value used as replacement.
     */
    public $value;


    /**
     * Constructor.
     * @param mixed $value value used as replacement.
     */
    public function __construct($value)
    {
        $this->value = $value;
    }
}
