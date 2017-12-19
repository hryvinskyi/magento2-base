<?php
/**
 * Copyright (c) 2017. Volodumur Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodumur@hryvinskyi.com>
 * @github: <https://github.com/scriptua>
 */

namespace Script\Base\Observer;

use Magento\Framework\Event\ObserverInterface;
use Script\Base\Helper\Data;

class Layout implements ObserverInterface
{
    /** @var string */
    protected $layoutXml;

    /** @var Data */
    protected $helper;

    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->helper->isEnabledLayoutDebug()) {
            return;
        }

        /** @var \Magento\Framework\View\Layout $layout */
        $layout = $observer->getLayout();
        $this->layoutXml = $layout->getXmlString();
    }

    public function getLayoutXml()
    {
        return $this->layoutXml;
    }
}
