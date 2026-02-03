<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Model\Menu;

use Hryvinskyi\Base\Api\Menu\MenuItemFactoryInterface;
use Hryvinskyi\Base\Api\Menu\MenuItemInterface;
use Magento\Backend\Model\UrlInterface;

/**
 * Factory for creating menu items from array configuration
 */
class MenuItemFactory implements MenuItemFactoryInterface
{
    /**
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        private readonly UrlInterface $urlBuilder
    ) {
    }

    /**
     * @inheritDoc
     */
    public function create(array $data): MenuItemInterface
    {
        return new MenuItem(
            urlBuilder: $this->urlBuilder,
            label: $data['label'] ?? '',
            route: $data['route'] ?? '',
            routeParams: $data['route_params'] ?? [],
            sortOrder: (int) ($data['sort_order'] ?? 0),
            isActive: (bool) ($data['is_active'] ?? true),
            cssClass: $this->buildClassString($data['class'] ?? ''),
            icon: $data['icon'] ?? ''
        );
    }

    /**
     * Build class string from array or string configuration
     *
     * @param array<string, bool>|string $classConfig
     * @return string
     */
    private function buildClassString(array|string $classConfig): string
    {
        if (is_string($classConfig)) {
            return $classConfig;
        }

        $enabledClasses = [];
        foreach ($classConfig as $className => $isEnabled) {
            if ($isEnabled) {
                $enabledClasses[] = $className;
            }
        }

        return implode(' ', $enabledClasses);
    }
}
