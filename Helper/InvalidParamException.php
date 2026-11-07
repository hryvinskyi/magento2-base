<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\Base\Helper;

/**
 * InvalidParamException represents an exception caused by invalid parameters passed to a method.
 */
class InvalidParamException extends \BadMethodCallException
{
    /**
     * Returns the user-friendly name of this exception.
     *
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Invalid Parameter';
    }
}
