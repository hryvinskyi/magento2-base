<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Menu;

use Hryvinskyi\Base\Model\Menu\MenuItem;
use Hryvinskyi\Base\Model\Menu\MenuItemFactory;
use Hryvinskyi\Base\Model\Menu\RoutedMenuItemInterfaceFactory;
use Magento\Backend\Model\UrlInterface;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hryvinskyi\Base\Model\Menu\MenuItemFactory
 * @covers \Hryvinskyi\Base\Model\Menu\MenuItem
 */
class MenuItemFactoryTest extends TestCase
{
    use BuildsMenuObjectsTrait;

    /**
     * @var UrlInterface&MockObject
     */
    private MockObject $urlBuilder;

    /**
     * @var RoutedMenuItemInterfaceFactory&MockObject
     */
    private MockObject $itemFactory;

    /**
     * @var list<array<mixed>>
     */
    private array $createdWith = [];

    /**
     * @var MenuItemFactory
     */
    private MenuItemFactory $factory;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->urlBuilder = $this->createMock(UrlInterface::class);
        $this->itemFactory = $this->createMock(RoutedMenuItemInterfaceFactory::class);
        $this->itemFactory->method('create')->willReturnCallback(
            function (array $data): MenuItem {
                $this->createdWith[] = $data;

                return self::menuItemFrom($this->urlBuilder, $data);
            }
        );
        $this->factory = new MenuItemFactory($this->itemFactory);
    }

    /**
     * A fully configured item exposes every configured value
     *
     * @return void
     */
    public function testCreatesItemFromFullConfiguration(): void
    {
        $this->urlBuilder->expects($this->once())
            ->method('getUrl')
            ->with('adminhtml/system_config/edit', ['section' => 'my_section'])
            ->willReturn('https://admin.test/config/section/my_section/');

        $item = $this->factory->create([
            'label' => 'Settings',
            'route' => 'adminhtml/system_config/edit',
            'route_params' => ['section' => 'my_section'],
            'sort_order' => '20',
            'is_active' => true,
            'class' => 'settings-item',
            'icon' => '<svg></svg>',
            'resource' => 'Vendor_Module::config',
            'group' => 'settings',
        ]);

        self::assertInstanceOf(MenuItem::class, $item);
        self::assertSame('Settings', $item->getLabel());
        $item->getUrl();
        self::assertSame('https://admin.test/config/section/my_section/', $item->getUrl());
        self::assertSame(20, $item->getSortOrder());
        self::assertTrue($item->isActive());
        self::assertSame('settings-item', $item->getClass());
        self::assertSame('<svg></svg>', $item->getIcon());
        self::assertSame('Vendor_Module::config', $item->getResource());
        self::assertSame('settings', $item->getGroup());
    }

    /**
     * The item comes from the generated factory, which receives only scalar values: the route parameters reach
     * the item afterwards, so no layout array is ever interpreted by the object manager
     *
     * @return void
     */
    public function testObtainsTheItemFromTheGeneratedFactoryWithScalarArgumentsOnly(): void
    {
        $this->factory->create([
            'label' => 'Reports',
            'route' => 'vendor/reports/index',
            'route_params' => ['_query' => ['instance' => 'x']],
            'sort_order' => 10,
            'group' => 'reports',
        ]);

        self::assertSame(
            [[
                'label' => 'Reports',
                'route' => 'vendor/reports/index',
                'sortOrder' => 10,
                'isActive' => true,
                'cssClass' => '',
                'icon' => '',
                'resource' => null,
                'group' => 'reports',
            ]],
            $this->createdWith
        );
    }

    /**
     * Route parameters holding keys the object manager would interpret still reach the URL unchanged
     *
     * @return void
     */
    public function testRouteParametersReachTheUrlUnchanged(): void
    {
        $this->urlBuilder->expects($this->once())
            ->method('getUrl')
            ->with('vendor/reports/index', ['_query' => ['instance' => 'x', 'argument' => 'y']])
            ->willReturn('https://admin.test/vendor/reports/?instance=x&argument=y');

        $item = $this->factory->create([
            'route' => 'vendor/reports/index',
            'route_params' => ['_query' => ['instance' => 'x', 'argument' => 'y']],
        ]);

        self::assertSame('https://admin.test/vendor/reports/?instance=x&argument=y', $item->getUrl());
    }

    /**
     * An empty configuration yields the documented defaults, no ACL resource and no group
     *
     * @return void
     */
    public function testAppliesDefaultsForMissingKeys(): void
    {
        $item = $this->factory->create([]);

        self::assertInstanceOf(MenuItem::class, $item);
        self::assertSame('', $item->getLabel());
        self::assertSame(0, $item->getSortOrder());
        self::assertTrue($item->isActive());
        self::assertSame('', $item->getClass());
        self::assertSame('', $item->getIcon());
        self::assertNull($item->getResource());
        self::assertSame('', $item->getGroup());
    }

    /**
     * A null resource means "no permission check", like an absent one
     *
     * @return void
     */
    public function testNullResourceMeansNoPermissionCheck(): void
    {
        self::assertNull($this->factory->create(['resource' => null])->getResource());
    }

    /**
     * Surrounding whitespace of the resource id is ignored
     *
     * @return void
     */
    public function testResourceIsTrimmed(): void
    {
        self::assertSame(
            'Vendor_Module::page',
            $this->factory->create(['resource' => "  Vendor_Module::page\n"])->getResource()
        );
    }

    /**
     * The group code is read as text, without surrounding whitespace; null and an empty code mean no group
     *
     * @param string|null $group
     * @param string $expected
     * @return void
     */
    #[TestWith(['reports', 'reports'])]
    #[TestWith([' settings ', 'settings'])]
    #[TestWith(['', ''])]
    #[TestWith([null, ''])]
    public function testReadsGroup(?string $group, string $expected): void
    {
        $item = $this->factory->create(['group' => $group]);

        self::assertInstanceOf(MenuItem::class, $item);
        self::assertSame($expected, $item->getGroup());
    }

    /**
     * Layout "number" arguments arrive as numeric strings; integers and floats are accepted too
     *
     * @param int|float|string $sortOrder
     * @param int $expected
     * @return void
     */
    #[TestWith([15, 15])]
    #[TestWith(['30', 30])]
    #[TestWith(['-5', -5])]
    #[TestWith([7.0, 7])]
    public function testReadsSortOrder(int|float|string $sortOrder, int $expected): void
    {
        self::assertSame($expected, $this->factory->create(['sort_order' => $sortOrder])->getSortOrder());
    }

    /**
     * Boolean flags accept booleans and the usual boolean spellings
     *
     * @param bool|int|string $value
     * @param bool $expected
     * @return void
     */
    #[TestWith([true, true])]
    #[TestWith([false, false])]
    #[TestWith([1, true])]
    #[TestWith([0, false])]
    #[TestWith(['1', true])]
    #[TestWith(['0', false])]
    #[TestWith(['true', true])]
    #[TestWith(['false', false])]
    public function testReadsActiveFlag(bool|int|string $value, bool $expected): void
    {
        self::assertSame($expected, $this->factory->create(['is_active' => $value])->isActive());
    }

    /**
     * A class map keeps the enabled class names, in configured order
     *
     * @return void
     */
    public function testBuildsClassStringFromMap(): void
    {
        $item = $this->factory->create([
            'class' => ['first' => true, 'hidden' => false, 'second' => '1', 'off' => 'false'],
        ]);

        self::assertSame('first second', $item->getClass());
    }

    /**
     * A class map with nothing enabled yields no class
     *
     * @return void
     */
    public function testBuildsEmptyClassStringWhenNothingEnabled(): void
    {
        self::assertSame('', $this->factory->create(['class' => ['a' => false]])->getClass());
    }

    /**
     * A label given as a stringable object (a translated phrase) is converted to a string
     *
     * @return void
     */
    public function testAcceptsStringableLabel(): void
    {
        $label = new class () implements \Stringable {
            /**
             * @inheritDoc
             */
            public function __toString(): string
            {
                return 'Pages';
            }
        };

        self::assertSame('Pages', $this->factory->create(['label' => $label])->getLabel());
    }

    /**
     * A known key holding a value of the wrong type is rejected, naming the key, before any item is created
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    #[TestWith(['label', ['not', 'a', 'string']])]
    #[TestWith(['route', 42])]
    #[TestWith(['icon', false])]
    #[TestWith(['route_params', 'section=foo'])]
    #[TestWith(['sort_order', 'first'])]
    #[TestWith(['sort_order', true])]
    #[TestWith(['is_active', 'maybe'])]
    #[TestWith(['is_active', 1.5])]
    #[TestWith(['class', 12])]
    #[TestWith(['resource', ''])]
    #[TestWith(['resource', '   '])]
    #[TestWith(['resource', ['Vendor_Module::page']])]
    #[TestWith(['group', ['reports']])]
    #[TestWith(['group', 12])]
    #[TestWith(['group', true])]
    public function testRejectsValueOfWrongType(string $key, mixed $value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"' . $key . '"');

        try {
            $this->factory->create([$key => $value]);
        } finally {
            self::assertSame([], $this->createdWith);
        }
    }

    /**
     * Route parameters must be keyed by parameter name
     *
     * @return void
     */
    public function testRejectsRouteParamsWithoutNames(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"route_params"');

        $this->factory->create(['route_params' => ['foo']]);
    }

    /**
     * A class map must map non-empty names to boolean flags
     *
     * @param array<mixed> $classMap
     * @return void
     */
    #[TestWith([['active' => 'sometimes']])]
    #[TestWith([['first', 'second']])]
    #[TestWith([['' => true]])]
    public function testRejectsMalformedClassMap(array $classMap): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"class"');

        $this->factory->create(['class' => $classMap]);
    }
}
