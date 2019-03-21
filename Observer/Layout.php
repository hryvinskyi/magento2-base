<?php
/**
 * Copyright (c) 2019. Volodumur Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodumur@hryvinskyi.com>
 * @github: <https://github.com/scriptua>
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Observer;

use Hryvinskyi\Base\Helper\Config;
use Hryvinskyi\Base\Model\Layout\LayoutXml;
use Magento\Framework\Event\ObserverInterface;

class Layout implements ObserverInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var LayoutXml
     */
    private $layoutXml;

    /**
     * Layout constructor.
     *
     * @param Config $config
     * @param LayoutXml $layoutXml
     */
    public function __construct(
        Config $config,
        LayoutXml $layoutXml
    ) {
        $this->config = $config;
        $this->layoutXml = $layoutXml;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->config->isEnabledLayoutDebug()) {
            return;
        }

        /** @var \Magento\Framework\View\Layout $layout */
        $layout = $observer->getData('layout');
        $this->layoutXml->setLayout($layout->getXmlString());
    }
}
