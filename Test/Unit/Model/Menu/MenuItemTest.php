<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Menu;

use Hryvinskyi\Base\Model\Menu\MenuItem;
use Magento\Backend\Model\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hryvinskyi\Base\Model\Menu\MenuItem
 */
class MenuItemTest extends TestCase
{
    /**
     * @var UrlInterface&MockObject
     */
    private MockObject $urlBuilder;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->urlBuilder = $this->createMock(UrlInterface::class);
        $this->urlBuilder->method('getUrl')->willReturnCallback(
            static fn (string $route, array $params): string => $route . '?' . http_build_query($params)
        );
    }

    /**
     * Route parameters are given to a copy; the original item keeps its own and every other value is kept
     *
     * @return void
     */
    public function testWithRouteParamsReturnsACopyCarryingTheParameters(): void
    {
        $item = new MenuItem(
            $this->urlBuilder,
            label: 'Reports',
            route: 'vendor/reports/index',
            sortOrder: 5,
            isActive: false,
            cssClass: 'current',
            icon: '<svg></svg>',
            resource: 'Vendor_Module::reports',
            group: 'reports'
        );

        $withParams = $item->withRouteParams(['id' => '7']);

        self::assertNotSame($item, $withParams);
        self::assertSame('vendor/reports/index?id=7', $withParams->getUrl());
        self::assertSame('vendor/reports/index?', $item->getUrl());
        self::assertSame('Reports', $withParams->getLabel());
        self::assertSame(5, $withParams->getSortOrder());
        self::assertFalse($withParams->isActive());
        self::assertSame('current', $withParams->getClass());
        self::assertSame('<svg></svg>', $withParams->getIcon());
        self::assertSame('Vendor_Module::reports', $withParams->getResource());
        self::assertSame('reports', $withParams->getGroup());
    }

    /**
     * A URL already built for the original item is not carried over to the copy
     *
     * @return void
     */
    public function testCopyBuildsItsOwnUrl(): void
    {
        $item = new MenuItem($this->urlBuilder, route: 'vendor/reports/index');
        $item->getUrl();

        self::assertSame('vendor/reports/index?page=2', $item->withRouteParams(['page' => '2'])->getUrl());
    }
}
