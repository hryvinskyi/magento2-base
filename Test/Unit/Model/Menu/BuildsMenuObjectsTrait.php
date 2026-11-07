<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Menu;

use Hryvinskyi\Base\Api\Menu\MenuGroupInterface;
use Hryvinskyi\Base\Api\Menu\MenuItemInterface;
use Hryvinskyi\Base\Model\Menu\MenuGroup;
use Hryvinskyi\Base\Model\Menu\MenuItem;
use Hryvinskyi\Base\Model\Menu\MenuSection;
use Magento\Backend\Model\UrlInterface;

/**
 * Builds the real menu objects from the arguments a generated factory receives, so a factory double creates what
 * the object manager would; every argument is checked to have the type the constructor declares
 */
trait BuildsMenuObjectsTrait
{
    /**
     * Build a menu item the way the generated item factory would
     *
     * @param UrlInterface $urlBuilder
     * @param array<mixed> $data
     * @return MenuItem
     */
    private static function menuItemFrom(UrlInterface $urlBuilder, array $data): MenuItem
    {
        $resource = $data['resource'] ?? null;
        self::assertTrue($resource === null || is_string($resource));

        return new MenuItem(
            $urlBuilder,
            label: self::stringArgument($data, 'label'),
            route: self::stringArgument($data, 'route'),
            sortOrder: self::intArgument($data, 'sortOrder'),
            isActive: self::boolArgument($data, 'isActive'),
            cssClass: self::stringArgument($data, 'cssClass'),
            icon: self::stringArgument($data, 'icon'),
            resource: is_string($resource) ? $resource : null,
            group: self::stringArgument($data, 'group')
        );
    }

    /**
     * Build a menu group the way the generated group factory would
     *
     * @param array<mixed> $data
     * @return MenuGroup
     */
    private static function menuGroupFrom(array $data): MenuGroup
    {
        return new MenuGroup(
            self::stringArgument($data, 'code'),
            self::stringArgument($data, 'label'),
            self::intArgument($data, 'sortOrder')
        );
    }

    /**
     * Build a menu section the way the generated section factory would
     *
     * @param array<mixed> $data
     * @return MenuSection
     */
    private static function menuSectionFrom(array $data): MenuSection
    {
        $columns = [];
        self::assertIsArray($data['columns'] ?? null);
        foreach ($data['columns'] as $column) {
            self::assertIsArray($column);
            $items = [];
            foreach ($column as $item) {
                self::assertInstanceOf(MenuItemInterface::class, $item);
                $items[] = $item;
            }
            $columns[] = $items;
        }

        if (!array_key_exists('group', $data)) {
            return new MenuSection($columns);
        }
        self::assertInstanceOf(MenuGroupInterface::class, $data['group']);

        return new MenuSection($columns, $data['group']);
    }

    /**
     * Read a string argument, empty when absent
     *
     * @param array<mixed> $data
     * @param string $key
     * @return string
     */
    private static function stringArgument(array $data, string $key): string
    {
        $value = $data[$key] ?? '';
        self::assertIsString($value, $key);

        return $value;
    }

    /**
     * Read an integer argument, 0 when absent
     *
     * @param array<mixed> $data
     * @param string $key
     * @return int
     */
    private static function intArgument(array $data, string $key): int
    {
        $value = $data[$key] ?? 0;
        self::assertIsInt($value, $key);

        return $value;
    }

    /**
     * Read a boolean argument, true when absent
     *
     * @param array<mixed> $data
     * @param string $key
     * @return bool
     */
    private static function boolArgument(array $data, string $key): bool
    {
        $value = $data[$key] ?? true;
        self::assertIsBool($value, $key);

        return $value;
    }
}
