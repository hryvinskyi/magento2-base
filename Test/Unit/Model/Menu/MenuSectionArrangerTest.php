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
use Hryvinskyi\Base\Model\Menu\MenuSectionArranger;
use Hryvinskyi\Base\Model\Menu\MenuSectionFactory;
use Magento\Backend\Model\UrlInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Hryvinskyi\Base\Model\Menu\MenuSectionArranger
 * @covers \Hryvinskyi\Base\Model\Menu\MenuSection
 */
class MenuSectionArrangerTest extends TestCase
{
    use BuildsMenuObjectsTrait;

    /**
     * @var LoggerInterface&MockObject
     */
    private MockObject $logger;

    /**
     * @var list<array<mixed>>
     */
    private array $createdWith = [];

    /**
     * @var MenuSectionArranger
     */
    private MenuSectionArranger $arranger;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $sectionFactory = $this->createMock(MenuSectionFactory::class);
        $sectionFactory->method('create')->willReturnCallback(
            function (array $data): MenuSection {
                $this->createdWith[] = $data;

                return self::menuSectionFrom($data);
            }
        );
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->arranger = new MenuSectionArranger($sectionFactory, $this->logger);
    }

    /**
     * Groups follow their sort order (ties keep the configured order); items keep the order they are given in
     *
     * @return void
     */
    public function testOrdersGroupsBySortOrderAndKeepsItemOrderInsideGroups(): void
    {
        $sections = $this->arranger->arrange(
            [
                'audit' => $this->item('Audit', 'reports'),
                'general' => $this->item('General', 'settings'),
                'sales' => $this->item('Sales', 'reports'),
                'help' => $this->item('Help', 'help'),
            ],
            [
                $this->group('settings', 30),
                $this->group('help', 10),
                $this->group('reports', 30),
            ],
            6,
            'test.menu'
        );

        self::assertSame(
            ['help' => [['Help']], 'settings' => [['General']], 'reports' => [['Audit', 'Sales']]],
            $this->layout($sections)
        );
    }

    /**
     * A declared group without any item of its own gets no section
     *
     * @return void
     */
    public function testLeavesOutGroupWithoutItems(): void
    {
        $sections = $this->arranger->arrange(
            ['general' => $this->item('General', 'settings')],
            [$this->group('reports', 10), $this->group('settings', 20)],
            6,
            'test.menu'
        );

        self::assertSame(['settings' => [['General']]], $this->layout($sections));
    }

    /**
     * Items naming no group, or a group the menu does not declare, close the menu in an untitled section; each
     * undeclared code is logged once per item with the menu's name
     *
     * @return void
     */
    public function testPutsUngroupedAndUndeclaredItemsIntoATrailingUntitledSection(): void
    {
        $warnings = [];
        $this->logger->expects($this->exactly(2))
            ->method('warning')
            ->willReturnCallback(static function (string $message, array $context) use (&$warnings): void {
                $warnings[] = $context;
            });

        $sections = $this->arranger->arrange(
            [
                'loose' => $this->item('Loose', ''),
                'typo' => $this->item('Typo', 'setings'),
                'general' => $this->item('General', 'settings'),
                'other' => $this->item('Other', 'unknown'),
            ],
            [$this->group('settings', 10)],
            6,
            'test.menu'
        );

        self::assertSame(['settings' => [['General']], '' => [['Loose', 'Typo', 'Other']]], $this->layout($sections));
        self::assertSame(
            [
                ['block' => 'test.menu', 'item' => 'typo', 'group' => 'setings'],
                ['block' => 'test.menu', 'item' => 'other', 'group' => 'unknown'],
            ],
            $warnings
        );
    }

    /**
     * An item that implements only the plain item contract names no group
     *
     * @return void
     */
    public function testItemWithoutGroupContractLandsInTheUntitledSection(): void
    {
        $this->logger->expects($this->never())->method('warning');

        $sections = $this->arranger->arrange(
            ['plain' => $this->plainItem('Plain'), 'general' => $this->item('General', 'settings')],
            [$this->group('settings', 10)],
            6,
            'test.menu'
        );

        self::assertSame(['settings' => [['General']], '' => [['Plain']]], $this->layout($sections));
    }

    /**
     * The untitled section is created without a group argument at all, so the compiled object manager never
     * replaces an explicit null with a shared instance
     *
     * @return void
     */
    public function testCreatesTheUntitledSectionWithoutAGroupArgument(): void
    {
        $this->arranger->arrange(['loose' => $this->item('Loose', '')], [], 6, 'test.menu');

        self::assertCount(1, $this->createdWith);
        self::assertSame(['columns'], array_keys($this->createdWith[0]));
    }

    /**
     * A long group splits into ceil(n / max) columns as even as possible, the earlier columns taking the extra item
     *
     * @param int $itemCount
     * @param int $maxItemsPerColumn
     * @param list<int> $expectedSizes
     * @return void
     */
    #[DataProvider('columnSplits')]
    public function testSplitsALongGroupIntoEvenColumns(
        int $itemCount,
        int $maxItemsPerColumn,
        array $expectedSizes
    ): void {
        $items = [];
        for ($i = 1; $i <= $itemCount; $i++) {
            $items['item' . $i] = $this->item('Item ' . $i, 'settings');
        }

        $sections = $this->arranger->arrange($items, [$this->group('settings', 10)], $maxItemsPerColumn, 'test.menu');

        self::assertCount(1, $sections);
        self::assertSame($expectedSizes, array_map('count', $sections[0]->getColumns()));
        self::assertSame(
            array_map(static fn (MenuItemInterface $item): string => $item->getLabel(), array_values($items)),
            array_map(
                static fn (MenuItemInterface $item): string => $item->getLabel(),
                array_merge(...$sections[0]->getColumns())
            ),
            'Columns keep the items in order, the first column holding the first items'
        );
    }

    /**
     * Item counts, column limits and the resulting column sizes
     *
     * @return array<string, array{int, int, list<int>}>
     */
    public static function columnSplits(): array
    {
        return [
            'one item' => [1, 6, [1]],
            'exactly the limit' => [6, 6, [6]],
            'one over the limit' => [7, 6, [4, 3]],
            'twice the limit' => [12, 6, [6, 6]],
            'thirteen items' => [13, 6, [5, 4, 4]],
            'one item per column' => [3, 1, [1, 1, 1]],
        ];
    }

    /**
     * A menu with groups splits its untitled section into columns like any group
     *
     * @return void
     */
    public function testSplitsTheUntitledSectionOfAGroupedMenu(): void
    {
        $sections = $this->arranger->arrange(
            [
                'general' => $this->item('General', 'settings'),
                'a' => $this->item('A', ''),
                'b' => $this->item('B', ''),
                'c' => $this->item('C', ''),
            ],
            [$this->group('settings', 10)],
            2,
            'test.menu'
        );

        self::assertSame(['settings' => [['General']], '' => [['A', 'B'], ['C']]], $this->layout($sections));
    }

    /**
     * A menu where no item lands in a group is one untitled section with one column, however long it is
     *
     * @return void
     */
    public function testKeepsAMenuWithoutGroupsInOneColumn(): void
    {
        $items = [];
        for ($i = 1; $i <= 13; $i++) {
            $items['item' . $i] = $this->item('Item ' . $i, '');
        }

        $sections = $this->arranger->arrange($items, [$this->group('settings', 10)], 6, 'test.menu');

        self::assertCount(1, $sections);
        self::assertNull($sections[0]->getGroup());
        self::assertSame([13], array_map('count', $sections[0]->getColumns()));
    }

    /**
     * No items, no sections
     *
     * @return void
     */
    public function testArrangesNothingWithoutItems(): void
    {
        self::assertSame([], $this->arranger->arrange([], [$this->group('settings', 10)], 6, 'test.menu'));
    }

    /**
     * The column limit must be positive
     *
     * @return void
     */
    public function testRejectsAColumnLimitBelowOne(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->arranger->arrange(['a' => $this->item('A', '')], [], 0, 'test.menu');
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
                static fn (array $column): array => array_map(
                    static fn (MenuItemInterface $item): string => $item->getLabel(),
                    $column
                ),
                $section->getColumns()
            );
        }

        return $layout;
    }

    /**
     * A menu item naming the given group
     *
     * @param string $label
     * @param string $group
     * @return MenuItem
     */
    private function item(string $label, string $group): MenuItem
    {
        return new MenuItem($this->createMock(UrlInterface::class), label: $label, group: $group);
    }

    /**
     * A menu item that implements only the plain item contract, as a custom item factory's might
     *
     * @param string $label
     * @return MenuItemInterface
     */
    private function plainItem(string $label): MenuItemInterface
    {
        return new class ($label) implements MenuItemInterface {
            /**
             * @param string $label
             */
            public function __construct(private readonly string $label)
            {
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
                return '';
            }

            /**
             * @inheritDoc
             */
            public function getSortOrder(): int
            {
                return 0;
            }

            /**
             * @inheritDoc
             */
            public function isActive(): bool
            {
                return true;
            }

            /**
             * @inheritDoc
             */
            public function getClass(): string
            {
                return '';
            }

            /**
             * @inheritDoc
             */
            public function getIcon(): string
            {
                return '';
            }

            /**
             * @inheritDoc
             */
            public function getResource(): ?string
            {
                return null;
            }
        };
    }

    /**
     * A declared group
     *
     * @param string $code
     * @param int $sortOrder
     * @return MenuGroupInterface
     */
    private function group(string $code, int $sortOrder): MenuGroupInterface
    {
        return new MenuGroup($code, ucfirst($code), $sortOrder);
    }
}
