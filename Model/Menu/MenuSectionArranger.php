<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Model\Menu;

use Hryvinskyi\Base\Api\Menu\GroupedMenuItemInterface;
use Hryvinskyi\Base\Api\Menu\MenuGroupInterface;
use Hryvinskyi\Base\Api\Menu\MenuItemInterface;
use Psr\Log\LoggerInterface;

/**
 * Places each visible menu item into its declared group and splits long sections into even columns
 */
class MenuSectionArranger implements MenuSectionArrangerInterface
{
    /**
     * @param MenuSectionFactory $sectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly MenuSectionFactory $sectionFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function arrange(array $items, array $groups, int $maxItemsPerColumn, string $menuName): array
    {
        if ($maxItemsPerColumn < 1) {
            throw new \InvalidArgumentException(
                sprintf('A menu column holds at least one item, %d given.', $maxItemsPerColumn)
            );
        }

        $declared = $this->sortGroups($groups);
        $grouped = [];
        $untitled = [];
        foreach ($items as $name => $item) {
            $code = $item instanceof GroupedMenuItemInterface ? $item->getGroup() : '';
            if ($code !== '' && isset($declared[$code])) {
                $grouped[$code][] = $item;
                continue;
            }
            if ($code !== '') {
                $this->logger->warning(
                    'Admin menu item names a group the menu does not declare; it is listed after every group.',
                    ['block' => $menuName, 'item' => (string) $name, 'group' => $code]
                );
            }
            $untitled[] = $item;
        }

        $sections = [];
        foreach ($declared as $code => $group) {
            if (isset($grouped[$code])) {
                $sections[] = $this->sectionFactory->create([
                    'columns' => $this->splitIntoColumns($grouped[$code], $maxItemsPerColumn),
                    'group' => $group,
                ]);
            }
        }

        if ($untitled !== []) {
            $sections[] = $this->sectionFactory->create([
                'columns' => $sections === [] ? [$untitled] : $this->splitIntoColumns($untitled, $maxItemsPerColumn),
            ]);
        }

        return $sections;
    }

    /**
     * Index the declared groups by code, in sort order; groups with the same sort order keep the declared order
     *
     * @param list<MenuGroupInterface> $groups
     * @return array<string, MenuGroupInterface>
     */
    private function sortGroups(array $groups): array
    {
        usort(
            $groups,
            fn (MenuGroupInterface $a, MenuGroupInterface $b): int => $a->getSortOrder() <=> $b->getSortOrder()
        );

        $byCode = [];
        foreach ($groups as $group) {
            $byCode[$group->getCode()] ??= $group;
        }

        return $byCode;
    }

    /**
     * Split items into ceil(n / max) consecutive columns of even size
     *
     * Column sizes differ by at most one; the earlier columns take the extra items.
     *
     * @param non-empty-list<MenuItemInterface> $items
     * @param positive-int $maxItemsPerColumn
     * @return list<list<MenuItemInterface>>
     */
    private function splitIntoColumns(array $items, int $maxItemsPerColumn): array
    {
        $columnCount = (int) ceil(count($items) / $maxItemsPerColumn);
        $baseSize = intdiv(count($items), $columnCount);
        $longColumns = count($items) % $columnCount;

        $columns = [];
        $offset = 0;
        for ($column = 0; $column < $columnCount; $column++) {
            $size = $baseSize + ($column < $longColumns ? 1 : 0);
            $columns[] = array_slice($items, $offset, $size);
            $offset += $size;
        }

        return $columns;
    }
}
