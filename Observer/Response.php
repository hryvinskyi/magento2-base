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
use Hryvinskyi\Base\Model\Layout\NoSetLayoutException;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;

/**
 * Answers a request with the page's layout XML when the layout debug is on and the request asks for it.
 */
class Response implements ObserverInterface
{
    /**
     * @param LayoutXml $layoutXml holds the recorded layout XML
     * @param LayoutDebugConfigInterface $config tells whether the layout debug is on
     * @param ManagerInterface $messageManager reports a page that recorded no layout
     */
    public function __construct(
        private readonly LayoutXml $layoutXml,
        private readonly LayoutDebugConfigInterface $config,
        private readonly ManagerInterface $messageManager
    ) {
    }

    /**
     * Replaces the response with the layout XML for a request carrying the "xml" parameter.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (!$this->config->isEnabledLayoutDebug()) {
            return;
        }

        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $observer->getData('request');
        if ($request->getParam('xml')) {
            try {
                /** @var \Magento\Framework\App\Response\Http $response */
                $response = $observer->getData('response');
                $response->setHeader('Content-type', 'application/xml', true);

                $layout = str_replace(
                    '<layout>',
                    '<layout xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">',
                    $this->layoutXml->getLayout()
                );
                $response->setContent('<?xml version="1.0" encoding="UTF-8" ?>' . $layout);
            } catch (NoSetLayoutException $exception) {
                $this->messageManager->addErrorMessage($exception->getMessage());
            }
        }
    }
}
