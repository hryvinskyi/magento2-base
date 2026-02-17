<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Adds a section-specific layout handle to the system configuration page.
 *
 * Generated handle: system_config_edit_section_{section_id}
 *
 * Modules create a layout XML file with that name to inject custom blocks,
 * e.g. view/adminhtml/layout/system_config_edit_section_{some_section}.xml
 */
class AddMenuHandleToConfigPage implements ObserverInterface
{
    private const ACTION_NAME = 'adminhtml_system_config_edit';
    private const HANDLE_PREFIX = 'system_config_edit_section_';

    /**
     * @param RequestInterface $request
     */
    public function __construct(
        private readonly RequestInterface $request
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        if ($observer->getEvent()->getFullActionName() !== self::ACTION_NAME) {
            return;
        }

        $section = $this->request->getParam('section');

        if ($section === null || $section === '') {
            return;
        }

        $observer->getEvent()->getLayout()->getUpdate()->addHandle(
            self::HANDLE_PREFIX . $section
        );
    }
}
