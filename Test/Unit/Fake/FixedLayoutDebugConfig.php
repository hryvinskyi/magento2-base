<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Fake;

use Hryvinskyi\Base\Api\LayoutDebugConfigInterface;

/**
 * Layout debug configuration that answers what it is given
 */
class FixedLayoutDebugConfig implements LayoutDebugConfigInterface
{
    /**
     * @param bool $enabled the answer
     */
    public function __construct(
        private readonly bool $enabled
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isEnabledLayoutDebug(): bool
    {
        return $this->enabled;
    }
}
