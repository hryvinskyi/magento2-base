<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Plugin;

use Hryvinskyi\Base\Api\LayoutDebugConfigInterface;
use Magento\Developer\Model\TemplateEngine\Decorator\DebugHintsFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\TemplateEngineFactory;
use Magento\Framework\View\TemplateEngineInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Shows the template hints on a request that asks for them while the layout debug is on.
 */
class DebugHints
{
    /**
     * XPath of configuration of the debug block names
     */
    public const XML_PATH_DEBUG_TEMPLATE_HINTS_BLOCKS = 'dev/debug/template_hints_blocks';

    /**
     * @param RequestInterface $request carries the debug parameters
     * @param LayoutDebugConfigInterface $config tells whether the layout debug is on
     * @param ScopeConfigInterface $scopeConfig reads the block hints setting
     * @param StoreManagerInterface $storeManager gives the store the setting is read for
     * @param DebugHintsFactory $debugHintsFactory wraps the template engine in the hints decorator
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly LayoutDebugConfigInterface $config,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly StoreManagerInterface $storeManager,
        private readonly DebugHintsFactory $debugHintsFactory
    ) {
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
        if ($this->config->isEnabledLayoutDebug() && $this->request->getParam('hints')) {
            $showBlockHints = (
                $this->scopeConfig->getValue(
                    self::XML_PATH_DEBUG_TEMPLATE_HINTS_BLOCKS,
                    ScopeInterface::SCOPE_STORE,
                    $this->storeManager->getStore()->getCode()
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
