<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\Base\Helper;

/**
 * InvalidValueException represents an exception caused by a function returning a value of unexpected type.
 */
class InvalidValueException extends \UnexpectedValueException
{
    /**
     * Returns the user-friendly name of this exception.
     *
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Invalid Return Value';
    }
}
