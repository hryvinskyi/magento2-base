<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\Base\Plugin;

use Hryvinskyi\Base\Api\LayoutDebugConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Layout as ViewLayout;

/**
 * Keeps the page out of the full page cache while the layout debug output is asked for.
 */
class Layout
{
    /**
     * @param RequestInterface $request carries the debug parameters
     * @param LayoutDebugConfigInterface $config tells whether the layout debug is on
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly LayoutDebugConfigInterface $config
    ) {
    }

    /**
     * Makes the layout uncacheable when the layout debug is on and the request asks for the XML or the hints.
     *
     * @param ViewLayout $subject
     * @param bool $result
     * @return bool
     */
    public function afterIsCacheable(ViewLayout $subject, $result): bool
    {
        if ($this->config->isEnabledLayoutDebug()
            && (
                $this->request->getParam('xml')
                || $this->request->getParam('hints')
            )
        ) {
            return false;
        }

        return (bool) $result;
    }
}
