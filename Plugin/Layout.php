<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\Base\Plugin;

use Hryvinskyi\Base\Helper\Config;
use Magento\Framework\App\Request\Http;
use Magento\Framework\View\Layout as ViewLayout;

/**
 * Keeps the page out of the full page cache while the layout debug output is asked for.
 */
class Layout
{
    /**
     * @var Http
     */
    private $request;

    /**
     * @var Config
     */
    private $config;

    /**
     * Layout constructor.
     *
     * @param Http $request
     * @param Config $config
     */
    public function __construct(
        Http $request,
        Config $config
    ) {
        $this->request = $request;
        $this->config = $config;
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
