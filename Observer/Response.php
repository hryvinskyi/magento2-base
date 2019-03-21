<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Observer;

use Hryvinskyi\Base\Helper\Config;
use Hryvinskyi\Base\Model\Layout\LayoutXml;
use Hryvinskyi\Base\Model\Layout\NoSetLayoutException;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;

class Response implements ObserverInterface
{
    /**
     * @var LayoutXml
     */
    private $layoutXml;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ManagerInterface
     */
    private $messageManager;


    /**
     * Response constructor.
     *
     * @param LayoutXml $layoutXml
     * @param Config $config
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        LayoutXml $layoutXml,
        Config $config,
        ManagerInterface $messageManager
    ) {
        $this->layoutXml = $layoutXml;
        $this->config = $config;
        $this->messageManager = $messageManager;
    }

    /**
     * @param Observer $observer
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
