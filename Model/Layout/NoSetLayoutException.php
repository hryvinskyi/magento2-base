<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Model\Layout;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * Thrown when the page's layout XML is asked for before the layout was generated.
 */
class NoSetLayoutException extends LocalizedException
{
    /**
     * Builds the exception, with "Layout was not set" when no message is given.
     *
     * @param Phrase|null $phrase
     * @param Exception|null $cause
     * @param int $code
     */
    public function __construct(?Phrase $phrase = null, ?Exception $cause = null, $code = 0)
    {
        parent::__construct($phrase ?? __('Layout was not set'), $cause, $code);
    }
}
