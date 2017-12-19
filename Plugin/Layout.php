<?php
/**
 * Copyright (c) 2017. Volodumur Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodumur@hryvinskyi.com>
 * @github: <https://github.com/scriptua>
 */

namespace Script\Base\Plugin;

use Magento\Framework\App\Request\Http;
use Script\Base\Helper\Data;

class Layout
{
    /** @var Http */
    protected $request;

    /** @var Data */
    protected $helper;

    public function __construct(
        Http $request,
        Data $helper
    ) {
        $this->request = $request;
        $this->helper = $helper;
    }

    public function afterIsCacheable(\Magento\Framework\View\Layout $subject, $result)
    {
        if ($this->helper->isEnabledLayoutDebug() && ($this->request->getParam('xml') || $this->request->getParam('hints'))) {
            return false;
        }

        return $result;
    }
}
