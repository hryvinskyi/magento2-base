<?php
/**
 * Copyright (c) 2019. Volodumur Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodumur@hryvinskyi.com>
 * @github: <https://github.com/scriptua>
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Model\Layout;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * Class NoSetLayoutException
 */
class NoSetLayoutException extends LocalizedException
{
    /**
     * @param Phrase $phrase
     * @param Exception $cause
     * @param int $code
     */
    public function __construct(Phrase $phrase = null, Exception $cause = null, $code = 0)
    {
        if ($phrase === null) {
            $phrase = new Phrase('Layout was not set');
        }

        parent::__construct($phrase, $cause, $code);
    }
}