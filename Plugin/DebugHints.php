<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Plugin;

use Hryvinskyi\Base\Helper\Config;
use Magento\Developer\Helper\Data as DevHelper;
use Magento\Developer\Model\TemplateEngine\Decorator\DebugHintsFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\View\TemplateEngineFactory;
use Magento\Framework\View\TemplateEngineInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class DebugHints
 */
class DebugHints
{
    /**
     * XPath of configuration of the debug block names
     */
    const XML_PATH_DEBUG_TEMPLATE_HINTS_BLOCKS = 'dev/debug/template_hints_blocks';

    /**
     * @var Http
     */
    private $request;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var DevHelper
     */
    private $devHelper;

    /**
     * @var DebugHintsFactory
     */
    private $debugHintsFactory;

    /**
     * DebugHints constructor.
     *
     * @param Http $request
     * @param Config $config
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param DevHelper $devHelper
     * @param DebugHintsFactory $debugHintsFactory
     */
    public function __construct(
        Http $request,
        Config $config,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        DevHelper $devHelper,
        DebugHintsFactory $debugHintsFactory
    ) {
        $this->request = $request;
        $this->config = $config;
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
     */
    public function afterCreate(
        TemplateEngineFactory $subject,
        TemplateEngineInterface $invocationResult
    ): TemplateEngineInterface {
        $storeCode = $this->storeManager->getStore()->getCode();
        if (
            $this->config->isEnabledLayoutDebug() &&
            $this->request->getParam('hints') &&
            $this->devHelper->isDevAllowed()
        ) {
            $showBlockHints = (
                $this->scopeConfig->getValue(
                    self::XML_PATH_DEBUG_TEMPLATE_HINTS_BLOCKS,
                    ScopeInterface::SCOPE_STORE,
                    $storeCode
                ) || $this->request->getParam('hints-block')
            );

            return $this->debugHintsFactory->create([
                'subject' => $invocationResult,
                'showBlockHints' => $showBlockHints,
            ]);
        }

        return $invocationResult;
    }
}
