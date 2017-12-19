<?php
/**
 * Copyright (c) 2017. Volodumur Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodumur@hryvinskyi.com>
 * @github: <https://github.com/scriptua>
 */

namespace Script\Base\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\State;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /** @var \Magento\Framework\App\State */
    protected $state;

    protected $enabled = null;

    public function __construct(
        Context $context,
        State $state
    ) {
        parent::__construct($context);
        $this->state = $state;
    }

    public function isEnabledLayoutDebug()
    {
        if (is_null($this->enabled)) $this->enabled = $this->state->getMode() === State::MODE_DEVELOPER;

        return $this->enabled;
    }
}
