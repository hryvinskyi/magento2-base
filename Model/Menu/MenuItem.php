<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Model\Menu;

use Hryvinskyi\Base\Api\Menu\MenuItemInterface;
use Magento\Backend\Model\UrlInterface;

/**
 * Menu item implementation with route-based URL generation
 */
class MenuItem implements MenuItemInterface
{
    /**
     * @var string|null
     */
    private ?string $generatedUrl = null;

    /**
     * @param UrlInterface $urlBuilder
     * @param string $label
     * @param string $route
     * @param array<string, mixed> $routeParams
     * @param int $sortOrder
     * @param bool $isActive
     * @param string $class
     * @param string $icon
     */
    public function __construct(
        private readonly UrlInterface $urlBuilder,
        private readonly string $label = '',
        private readonly string $route = '',
        private readonly array $routeParams = [],
        private readonly int $sortOrder = 0,
        private readonly bool $isActive = true,
        private readonly string $class = '',
        private readonly string $icon = ''
    ) {
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
    public function getUrl(): string
    {
        if ($this->generatedUrl === null) {
            $this->generatedUrl = $this->urlBuilder->getUrl($this->route, $this->routeParams);
        }

        return $this->generatedUrl;
    }

    /**
     * @inheritDoc
     */
    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    /**
     * @inheritDoc
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * @inheritDoc
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @inheritDoc
     */
    public function getIcon(): string
    {
        return $this->icon;
    }
}
