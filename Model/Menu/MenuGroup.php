<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Model\Menu;

use Hryvinskyi\Base\Api\Menu\MenuGroupInterface;

/**
 * A declared admin menu group; created through the generated MenuGroupInterfaceFactory
 */
class MenuGroup implements MenuGroupInterface
{
    /**
     * @param string $code
     * @param string $label
     * @param int $sortOrder
     */
    public function __construct(
        private readonly string $code,
        private readonly string $label,
        private readonly int $sortOrder = 0
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @inheritDoc
     */
    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }
}
