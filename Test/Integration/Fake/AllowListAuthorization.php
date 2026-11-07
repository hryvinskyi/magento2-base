<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Integration\Fake;

use Magento\Framework\AuthorizationInterface;

/**
 * Authorization that allows exactly the resources it is given and denies every other
 */
class AllowListAuthorization implements AuthorizationInterface
{
    /**
     * @param list<string> $allowedResources
     */
    public function __construct(
        private readonly array $allowedResources = []
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isAllowed($resource, $privilege = null)
    {
        return in_array($resource, $this->allowedResources, true);
    }
}
