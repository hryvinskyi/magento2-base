<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Model\Menu;

use Magento\Backend\Model\UrlInterface;

/**
 * Menu item implementation with route-based URL generation
 *
 * Created through the generated RoutedMenuItemInterfaceFactory from scalar values; the route parameters arrive
 * through withRouteParams().
 */
class MenuItem implements RoutedMenuItemInterface
{
    /**
     * @var array<string,mixed>
     */
    private array $routeParams = [];

    /**
     * @var string|null
     */
    private ?string $generatedUrl = null;

    /**
     * @param UrlInterface $urlBuilder
     * @param string $label
     * @param string $route
     * @param int $sortOrder
     * @param bool $isActive
     * @param string $cssClass
     * @param string $icon
     * @param string|null $resource ACL resource id required to see the item; null means no check
     * @param string $group Code of the group the item is listed in; empty for none
     */
    public function __construct(
        private readonly UrlInterface $urlBuilder,
        private readonly string $label = '',
        private readonly string $route = '',
        private readonly int $sortOrder = 0,
        private readonly bool $isActive = true,
        private readonly string $cssClass = '',
        private readonly string $icon = '',
        private readonly ?string $resource = null,
        private readonly string $group = ''
    ) {
    }

    /**
     * @inheritDoc
     */
    public function withRouteParams(array $routeParams): RoutedMenuItemInterface
    {
        $copy = clone $this;
        $copy->routeParams = $routeParams;
        $copy->generatedUrl = null;

        return $copy;
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
        return $this->cssClass;
    }

    /**
     * @inheritDoc
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * @inheritDoc
     */
    public function getResource(): ?string
    {
        return $this->resource;
    }

    /**
     * @inheritDoc
     */
    public function getGroup(): string
    {
        return $this->group;
    }
}
