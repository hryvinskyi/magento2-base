<?php
/**
 * Copyright (c) 2019. Volodumur Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodumur@hryvinskyi.com>
 * @github: <https://github.com/scriptua>
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\State;

/**
 * Class Config
 */
class Config extends AbstractHelper
{
    /**
     * @var State
     */
    private $state;

    /**
     * Config constructor.
     *
     * @param Context $context
     * @param State $state
     */
    public function __construct(
        Context $context,
        State $state
    ) {
        parent::__construct($context);

        $this->state = $state;
    }

    /**
     * Returns whether debugging is enabled
     *
     * @return bool
     */
    public function isEnabledLayoutDebug(): bool
    {
        return $this->state->getMode() === State::MODE_DEVELOPER;
    }
}