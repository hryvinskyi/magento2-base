<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Observer;

use Hryvinskyi\Base\Api\LayoutDebugConfigInterface;
use Hryvinskyi\Base\Model\Layout\LayoutXml;
use Magento\Framework\Event\ObserverInterface;

/**
 * Records the generated layout XML when the layout debug is on.
 */
class Layout implements ObserverInterface
{
    /**
     * @param LayoutDebugConfigInterface $config tells whether the layout debug is on
     * @param LayoutXml $layoutXml holds the recorded layout XML
     */
    public function __construct(
        private readonly LayoutDebugConfigInterface $config,
        private readonly LayoutXml $layoutXml
    ) {
    }

    /**
     * Records the XML of the layout the event carries.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
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
