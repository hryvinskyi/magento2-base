<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Model;

use Hryvinskyi\Base\Api\LayoutDebugConfigInterface;
use Magento\Developer\Helper\Data as DevHelper;
use Magento\Framework\App\State;

/**
 * Turns the layout debug tools on in developer mode, for a visitor allowed to see developer output.
 */
class LayoutDebugConfig implements LayoutDebugConfigInterface
{
    /**
     * @param State $state tells the application mode
     * @param DevHelper $devHelper tells whether the visitor may see developer output
     */
    public function __construct(
        private readonly State $state,
        private readonly DevHelper $devHelper
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isEnabledLayoutDebug(): bool
    {
        return $this->state->getMode() === State::MODE_DEVELOPER && $this->devHelper->isDevAllowed();
    }
}
