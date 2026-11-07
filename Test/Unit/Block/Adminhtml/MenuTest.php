<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Block\Adminhtml;

use Hryvinskyi\Base\Api\Menu\MenuGroupInterfaceFactory;
use Hryvinskyi\Base\Api\Menu\MenuItemInterface;
use Hryvinskyi\Base\Block\Adminhtml\Menu;
use Hryvinskyi\Base\Model\Menu\MenuGroup;
use Hryvinskyi\Base\Model\Menu\MenuGroupConfigParser;
use Hryvinskyi\Base\Model\Menu\MenuItem;
use Hryvinskyi\Base\Model\Menu\MenuItemFactory;
use Hryvinskyi\Base\Model\Menu\MenuSection;
use Hryvinskyi\Base\Model\Menu\MenuSectionArranger;
use Hryvinskyi\Base\Model\Menu\MenuSectionFactory;
use Hryvinskyi\Base\Model\Menu\RoutedMenuItemInterfaceFactory;
use Hryvinskyi\Base\Test\Unit\Model\Menu\BuildsMenuObjectsTrait;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\ObjectManager as AppObjectManager;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\DataObject;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Hryvinskyi\Base\Block\Adminhtml\Menu
 */
class MenuTest extends TestCase
{
    use BuildsMenuObjectsTrait;

    /**
     * @var AuthorizationInterface&MockObject
     */
    private MockObject $authorization;

    /**
     * @var LoggerInterface&MockObject
     */
    private MockObject $logger;

    /**
     * @var Context&MockObject
     */
    private MockObject $context;

    /**
     * @var MenuItemFactory
     */
    private MenuItemFactory $menuItemFactory;

    /**
     * @var MenuGroupConfigParser
     */
    private MenuGroupConfigParser $menuGroupFactory;

    /**
     * @var MenuSectionArranger
     */
    private MenuSectionArranger $sectionArranger;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        // The backend block's parent constructor pulls two helpers from the global object manager.
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->method('get')->willReturn(new DataObject());
        AppObjectManager::setInstance($objectManager);

        $this->authorization = $this->createMock(AuthorizationInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->context = $this->createMock(Context::class);
        $this->context->method('getAuthorization')->willReturn($this->authorization);
        $this->context->method('getLogger')->willReturn($this->logger);
        $urlBuilder = $this->createMock(UrlInterface::class);
        $itemFactory = $this->createMock(RoutedMenuItemInterfaceFactory::class);
        $itemFactory->method('create')->willReturnCallback(
            static fn (array $data): MenuItem => self::menuItemFrom($urlBuilder, $data)
        );
        $this->menuItemFactory = new MenuItemFactory($itemFactory);
        $groupFactory = $this->createMock(MenuGroupInterfaceFactory::class);
        $groupFactory->method('create')->willReturnCallback(
            static fn (array $data): MenuGroup => self::menuGroupFrom($data)
        );
        $this->menuGroupFactory = new MenuGroupConfigParser($groupFactory);
        $sectionFactory = $this->createMock(MenuSectionFactory::class);
        $sectionFactory->method('create')->willReturnCallback(
            static fn (array $data): MenuSection => self::menuSectionFrom($data)
        );
        $this->sectionArranger = new MenuSectionArranger($sectionFactory, $this->logger);
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        (new \ReflectionProperty(AppObjectManager::class, '_instance'))->setValue(null, null);
    }

    /**
     * Allowed items and items without a resource are listed; denied items are not
     *
     * @return void
     */
    public function testListsAllowedItemsAndItemsWithoutResource(): void
    {
        $this->authorization->expects($this->exactly(2))
            ->method('isAllowed')
            ->willReturnMap([
                ['Vendor_Module::allowed', null, true],
                ['Vendor_Module::denied', null, false],
            ]);

        $menu = $this->createMenu([
            'allowed' => ['label' => 'Allowed', 'resource' => 'Vendor_Module::allowed', 'sort_order' => '10'],
            'denied' => ['label' => 'Denied', 'resource' => 'Vendor_Module::denied', 'sort_order' => '20'],
            'open' => ['label' => 'Open', 'sort_order' => '30'],
        ]);

        self::assertSame(['Allowed', 'Open'], $this->labels($menu->getMenuItems()));
        self::assertTrue($menu->hasItems());
    }

    /**
     * Items without a resource never ask the authorization service
     *
     * @return void
     */
    public function testItemsWithoutResourceSkipTheAclCheck(): void
    {
        $this->authorization->expects($this->never())->method('isAllowed');

        $menu = $this->createMenu([
            'one' => ['label' => 'One'],
            'two' => ['label' => 'Two'],
        ]);

        self::assertSame(['One', 'Two'], $this->labels($menu->getMenuItems()));
    }

    /**
     * When every item is denied the menu has nothing to render
     *
     * @return void
     */
    public function testHasNoItemsWhenEveryItemIsDenied(): void
    {
        $this->authorization->method('isAllowed')->willReturn(false);

        $menu = $this->createMenu([
            'a' => ['label' => 'A', 'resource' => 'Vendor_Module::a'],
            'b' => ['label' => 'B', 'resource' => 'Vendor_Module::b'],
        ]);

        self::assertSame([], $menu->getMenuItems());
        self::assertFalse($menu->hasItems());
    }

    /**
     * Items are ordered by sort order; equal sort orders keep their configured order
     *
     * @return void
     */
    public function testSortsItemsBySortOrderKeepingConfiguredOrderForTies(): void
    {
        $menu = $this->createMenu([
            'third' => ['label' => 'Third', 'sort_order' => '30'],
            'first' => ['label' => 'First', 'sort_order' => '10'],
            'second-a' => ['label' => 'Second A', 'sort_order' => '20'],
            'second-b' => ['label' => 'Second B', 'sort_order' => '20'],
            'unsorted' => ['label' => 'Unsorted'],
        ]);

        self::assertSame(
            ['Unsorted', 'First', 'Second A', 'Second B', 'Third'],
            $this->labels($menu->getMenuItems())
        );
    }

    /**
     * The filtered, sorted list is built once per block
     *
     * @return void
     */
    public function testBuildsItemListOnce(): void
    {
        $this->authorization->expects($this->once())->method('isAllowed')->willReturn(true);

        $menu = $this->createMenu(['a' => ['label' => 'A', 'resource' => 'Vendor_Module::a']]);

        self::assertSame($menu->getMenuItems(), $menu->getMenuItems());
        self::assertTrue($menu->hasItems());
    }

    /**
     * A malformed item is logged and skipped; the other items still render
     *
     * @return void
     */
    public function testSkipsAndLogsMalformedItems(): void
    {
        $logged = [];
        $this->logger->expects($this->exactly(3))
            ->method('warning')
            ->willReturnCallback(static function (string $message, array $context) use (&$logged): void {
                $logged[] = $context['item'] ?? null;
            });

        $menu = $this->createMenu([
            'scalar' => 'not an item',
            'bad-label' => ['label' => ['array']],
            'empty-resource' => ['label' => 'Empty resource', 'resource' => ''],
            'good' => ['label' => 'Good'],
        ]);

        self::assertSame(['Good'], $this->labels($menu->getMenuItems()));
        self::assertSame(['scalar', 'bad-label', 'empty-resource'], $logged);
    }

    /**
     * An "items" argument that is not an array is logged and yields an empty menu
     *
     * @return void
     */
    public function testIgnoresItemsArgumentThatIsNotAnArray(): void
    {
        $this->logger->expects($this->once())->method('warning');

        $menu = $this->createMenu('dashboard');

        self::assertSame([], $menu->getMenuItems());
        self::assertFalse($menu->hasItems());
    }

    /**
     * A block without an "items" argument has no items and logs nothing
     *
     * @return void
     */
    public function testHasNoItemsWithoutItemsArgument(): void
    {
        $this->logger->expects($this->never())->method('warning');

        $menu = $this->newMenu([]);

        self::assertFalse($menu->hasItems());
    }

    /**
     * The title is read as text; anything else reads as empty
     *
     * @param mixed $title
     * @param string $expected
     * @return void
     */
    #[TestWith(['My Module', 'My Module'])]
    #[TestWith([null, ''])]
    #[TestWith([['My Module'], ''])]
    public function testReadsMenuTitle(mixed $title, string $expected): void
    {
        $menu = $this->newMenu(['menu_title' => $title]);

        self::assertSame($expected, $menu->getMenuTitle());
    }

    /**
     * A configured icon replaces the default one; an empty or non-text icon falls back to the default
     *
     * @return void
     */
    public function testReadsMenuIcon(): void
    {
        $custom = $this->newMenu(['menu_icon' => '<svg class="custom"></svg>']);
        $empty = $this->newMenu(['menu_icon' => '']);
        $invalid = $this->newMenu(['menu_icon' => 42]);

        self::assertSame('<svg class="custom"></svg>', $custom->getMenuIcon());
        self::assertStringContainsString('hamburger-icon', $empty->getMenuIcon());
        self::assertSame($empty->getMenuIcon(), $invalid->getMenuIcon());
    }

    /**
     * Inactive items stay in the item list, as before, but count as hidden: a menu of inactive items is empty
     *
     * @return void
     */
    public function testInactiveItemsAreListedButNotVisible(): void
    {
        $menu = $this->createMenu([
            'off' => ['label' => 'Off', 'is_active' => false],
            'also-off' => ['label' => 'Also off', 'is_active' => '0'],
        ]);

        self::assertSame(['Off', 'Also off'], $this->labels($menu->getMenuItems()));
        self::assertFalse($menu->hasItems());
        self::assertSame([], $menu->getSections());
    }

    /**
     * Without groups the menu is one untitled section holding one column of the visible items
     *
     * @return void
     */
    public function testMenuWithoutGroupsIsOneUntitledColumnOfVisibleItems(): void
    {
        $menu = $this->createMenu([
            'b' => ['label' => 'B', 'sort_order' => '20'],
            'off' => ['label' => 'Off', 'is_active' => false],
            'a' => ['label' => 'A', 'sort_order' => '10'],
        ]);

        self::assertFalse($menu->isGrouped());
        self::assertSame(['' => [['A', 'B']]], $this->layout($menu->getSections()));
    }

    /**
     * Visible items are arranged into the declared groups; a group whose items are all denied or inactive has no
     * section
     *
     * @return void
     */
    public function testArrangesVisibleItemsIntoDeclaredGroups(): void
    {
        $this->authorization->method('isAllowed')->willReturnMap([
            ['Vendor_Module::audit', null, true],
            ['Vendor_Module::secret', null, false],
        ]);

        $menu = $this->newMenu([
            'groups' => [
                'settings' => ['label' => 'Settings', 'sort_order' => '30'],
                'reports' => ['label' => 'Reports', 'sort_order' => '10'],
                'denied' => ['label' => 'Denied', 'sort_order' => '20'],
                'inactive' => ['label' => 'Inactive', 'sort_order' => '25'],
            ],
            'items' => [
                'general' => ['label' => 'General', 'group' => 'settings', 'sort_order' => '10'],
                'audit' => [
                    'label' => 'Audit',
                    'group' => 'reports',
                    'resource' => 'Vendor_Module::audit',
                    'sort_order' => '20',
                ],
                'sales' => ['label' => 'Sales', 'group' => 'reports', 'sort_order' => '10'],
                'secret' => ['label' => 'Secret', 'group' => 'denied', 'resource' => 'Vendor_Module::secret'],
                'paused' => ['label' => 'Paused', 'group' => 'inactive', 'is_active' => false],
            ],
        ]);

        self::assertTrue($menu->isGrouped());
        self::assertTrue($menu->hasItems());
        self::assertSame(
            ['reports' => [['Sales', 'Audit']], 'settings' => [['General']]],
            $this->layout($menu->getSections())
        );
    }

    /**
     * The column limit comes from the layout; a long group splits into even columns
     *
     * @return void
     */
    public function testReadsTheColumnLimit(): void
    {
        $this->logger->expects($this->never())->method('warning');
        $items = [];
        for ($i = 1; $i <= 5; $i++) {
            $items['item' . $i] = ['label' => 'Item ' . $i, 'group' => 'reports', 'sort_order' => (string) $i];
        }

        $menu = $this->newMenu([
            'groups' => ['reports' => ['label' => 'Reports']],
            'items' => $items,
            'max_items_per_column' => '2',
        ]);

        self::assertSame(
            ['reports' => [['Item 1', 'Item 2'], ['Item 3', 'Item 4'], ['Item 5']]],
            $this->layout($menu->getSections())
        );
    }

    /**
     * Without a column limit, or with a malformed one, a column holds up to six items; a malformed one is logged
     *
     * @param mixed $limit
     * @param int $warnings
     * @return void
     */
    #[TestWith([null, 0])]
    #[TestWith(['0', 1])]
    #[TestWith([-3, 1])]
    #[TestWith(['many', 1])]
    #[TestWith(['2.5', 1])]
    #[TestWith([['6'], 1])]
    public function testFallsBackToSixItemsPerColumn(mixed $limit, int $warnings): void
    {
        $this->logger->expects($this->exactly($warnings))->method('warning');
        $items = [];
        for ($i = 1; $i <= 7; $i++) {
            $items['item' . $i] = ['label' => 'Item ' . $i, 'group' => 'reports', 'sort_order' => (string) $i];
        }

        $menu = $this->newMenu([
            'groups' => ['reports' => ['label' => 'Reports']],
            'items' => $items,
            'max_items_per_column' => $limit,
        ]);
        $menu->setNameInLayout('test.menu');

        self::assertSame([[4, 3]], array_map(
            static fn (MenuSection $section): array => array_map('count', $section->getColumns()),
            $menu->getSections()
        ));
    }

    /**
     * A malformed group is logged and left out; its items close the menu untitled
     *
     * @return void
     */
    public function testSkipsAndLogsMalformedGroups(): void
    {
        $logged = [];
        $this->logger->method('warning')
            ->willReturnCallback(static function (string $message, array $context) use (&$logged): void {
                $group = $context['group'] ?? '?';
                $item = $context['item'] ?? '';
                self::assertIsString($group);
                self::assertIsString($item);
                $logged[] = $group . $item;
            });

        $menu = $this->newMenu([
            'groups' => [
                'scalar' => 'Scalar',
                'nameless' => ['sort_order' => '10'],
                'settings' => ['label' => 'Settings'],
            ],
            'items' => [
                'general' => ['label' => 'General', 'group' => 'settings'],
                'orphan' => ['label' => 'Orphan', 'group' => 'nameless'],
            ],
        ]);
        $menu->setNameInLayout('test.menu');

        self::assertSame(['settings' => [['General']], '' => [['Orphan']]], $this->layout($menu->getSections()));
        self::assertSame(['scalar', 'nameless', 'namelessorphan'], $logged);
    }

    /**
     * A "groups" argument that is not an array is logged and the menu renders without groups
     *
     * @return void
     */
    public function testIgnoresGroupsArgumentThatIsNotAnArray(): void
    {
        $this->logger->expects($this->once())->method('warning');

        $menu = $this->newMenu(['groups' => 'reports', 'items' => ['a' => ['label' => 'A']]]);

        self::assertFalse($menu->isGrouped());
        self::assertSame(['' => [['A']]], $this->layout($menu->getSections()));
    }

    /**
     * The sections are arranged once per block
     *
     * @return void
     */
    public function testArrangesSectionsOnce(): void
    {
        $menu = $this->createMenu(['a' => ['label' => 'A']]);

        self::assertSame($menu->getSections(), $menu->getSections());
    }

    /**
     * A heading id is unique per block and per group, and safe as an HTML id
     *
     * @return void
     */
    public function testBuildsHeadingIdsUniquePerBlockAndGroup(): void
    {
        $first = $this->newMenu([]);
        $first->setNameInLayout('my.module.menu');
        $second = $this->newMenu([]);
        $second->setNameInLayout('other menu');
        $reports = new MenuGroup('reports', 'Reports', 10);
        $settings = new MenuGroup('settings', 'Settings', 20);

        self::assertSame('hryvinskyi-core-menu-my-module-menu-reports', $first->getGroupHeadingId($reports));
        self::assertSame('hryvinskyi-core-menu-my-module-menu-settings', $first->getGroupHeadingId($settings));
        self::assertSame('hryvinskyi-core-menu-other-menu-reports', $second->getGroupHeadingId($reports));
    }

    /**
     * Create the block with the given "items" argument
     *
     * @param mixed $items
     * @return Menu
     */
    private function createMenu(mixed $items): Menu
    {
        $menu = $this->newMenu(['items' => $items]);
        $menu->setNameInLayout('test.menu');

        return $menu;
    }

    /**
     * Create the block with the given layout arguments
     *
     * @param array<string, mixed> $data
     * @return Menu
     */
    private function newMenu(array $data): Menu
    {
        return new Menu($this->context, $this->menuItemFactory, $this->menuGroupFactory, $this->sectionArranger, $data);
    }

    /**
     * Map sections to group code => columns of labels ('' for the untitled section)
     *
     * @param list<MenuSection> $sections
     * @return array<string, list<list<string>>>
     */
    private function layout(array $sections): array
    {
        $layout = [];
        foreach ($sections as $section) {
            $layout[$section->getGroup()?->getCode() ?? ''] = array_map(
                fn (array $column): array => $this->labels($column),
                $section->getColumns()
            );
        }

        return $layout;
    }

    /**
     * Map menu items to their labels
     *
     * @param list<MenuItemInterface> $items
     * @return list<string>
     */
    private function labels(array $items): array
    {
        return array_map(static fn (MenuItemInterface $item): string => $item->getLabel(), $items);
    }
}
