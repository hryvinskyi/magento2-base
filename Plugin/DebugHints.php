<?php
/**
 * Copyright (c) 2017. Volodumur Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodumur@hryvinskyi.com>
 * @github: <https://github.com/scriptua>
 */
namespace Script\Base\Plugin;

use Magento\Developer\Helper\Data as DevHelper;
use Magento\Developer\Model\TemplateEngine\Decorator\DebugHintsFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\TemplateEngineFactory;
use Magento\Framework\View\TemplateEngineInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Request\Http;
use Script\Base\Helper\Data;
use Script\Base\Helpers\VarDumper;

class DebugHints
{
    /**
     * XPath of configuration of the debug block names
     */
    const XML_PATH_DEBUG_TEMPLATE_HINTS_BLOCKS = 'dev/debug/template_hints_blocks';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var DevHelper
     */
    protected $devHelper;

    /**
     * @var DebugHintsFactory
     */
    protected $debugHintsFactory;

    /** @var Http */
    protected $request;

    /** @var Data */
    protected $helper;

    /**
     * @param Http $request
     * @param Data $helper
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param DevHelper $devHelper
     * @param DebugHintsFactory $debugHintsFactory
     */
    public function __construct(
        Http $request,
        Data $helper,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        DevHelper $devHelper,
        DebugHintsFactory $debugHintsFactory
    ) {
        $this->request = $request;
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->devHelper = $devHelper;
        $this->debugHintsFactory = $debugHintsFactory;
    }

    /**
     * Wrap template engine instance with the debugging hints decorator, depending of the store configuration
     *
     * @param TemplateEngineFactory $subject
     * @param TemplateEngineInterface $invocationResult
     *
     * @return TemplateEngineInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCreate(
        TemplateEngineFactory $subject,
        TemplateEngineInterface $invocationResult
    ) {
        $storeCode = $this->storeManager->getStore()->getCode();
        if (
            $this->helper->isEnabledLayoutDebug() &&
            $this->request->getParam('hints') &&
            $this->devHelper->isDevAllowed()
        ) {
            $showBlockHints = (
                $this->scopeConfig->getValue(self::XML_PATH_DEBUG_TEMPLATE_HINTS_BLOCKS, ScopeInterface::SCOPE_STORE, $storeCode) ||
                $this->request->getParam('hints-block')
            );
            return $this->debugHintsFactory->create([
                'subject' => $invocationResult,
                'showBlockHints' => $showBlockHints,
            ]);
        }
        return $invocationResult;
    }
}
