<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Block\Adminhtml;

use Hryvinskyi\Base\Api\Menu\MenuItemInterface;
use Hryvinskyi\Base\Block\Adminhtml\Menu;
use Hryvinskyi\Base\Model\Menu\MenuItemFactory;
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
        $this->menuItemFactory = new MenuItemFactory($this->createMock(UrlInterface::class));
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

        $menu = new Menu($this->context, $this->menuItemFactory);

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
        $menu = new Menu($this->context, $this->menuItemFactory, ['menu_title' => $title]);

        self::assertSame($expected, $menu->getMenuTitle());
    }

    /**
     * A configured icon replaces the default one; an empty or non-text icon falls back to the default
     *
     * @return void
     */
    public function testReadsMenuIcon(): void
    {
        $custom = new Menu($this->context, $this->menuItemFactory, ['menu_icon' => '<svg class="custom"></svg>']);
        $empty = new Menu($this->context, $this->menuItemFactory, ['menu_icon' => '']);
        $invalid = new Menu($this->context, $this->menuItemFactory, ['menu_icon' => 42]);

        self::assertSame('<svg class="custom"></svg>', $custom->getMenuIcon());
        self::assertStringContainsString('hamburger-icon', $empty->getMenuIcon());
        self::assertSame($empty->getMenuIcon(), $invalid->getMenuIcon());
    }

    /**
     * Create the block with the given "items" argument
     *
     * @param mixed $items
     * @return Menu
     */
    private function createMenu(mixed $items): Menu
    {
        $menu = new Menu($this->context, $this->menuItemFactory, ['items' => $items]);
        $menu->setNameInLayout('test.menu');

        return $menu;
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
