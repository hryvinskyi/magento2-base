<?php
/**
 * Copyright (c) 2017. Volodumur Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodumur@hryvinskyi.com>
 * @github: <https://github.com/scriptua>
 */

namespace Hryvinskyi\Base\Plugin;

use Hryvinskyi\Base\Helper\Config;
use Magento\Framework\App\Request\Http;
use Magento\Framework\View\Layout as ViewLayout;

/**
 * Class Layout
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
     * @param ViewLayout $subject
     * @param $result
     *
     * @return bool
     */
    public function afterIsCacheable(ViewLayout $subject, $result): bool
    {
        if (
            $this->config->isEnabledLayoutDebug()
            && (
                $this->request->getParam('xml')
                || $this->request->getParam('hints')
            )
        ) {
            return false;
        }

        return $result;
    }
}
