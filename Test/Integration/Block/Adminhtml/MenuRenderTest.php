<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Integration\Block\Adminhtml;

use Hryvinskyi\Base\Block\Adminhtml\Menu;
use Hryvinskyi\Base\Test\Integration\Fake\AllowListAuthorization;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * The admin menu rendered through the real layout, template, escaper and container wiring
 *
 * The block takes its authorization from its context, and without an admin session the backend authorization
 * denies every resource. Each menu is therefore given a context whose authorization allows a fixed list of
 * resources, so an item with a resource is shown or hidden for the reason the test names. The rendered markup is
 * parsed as a browser parses it.
 */
#[AppArea('adminhtml')]
class MenuRenderTest extends TestCase
{
    private const ALLOWED = 'Vendor_Module::allowed';
    private const DENIED = 'Vendor_Module::denied';
    private const ROUTE = 'adminhtml/dashboard/index';
    private const GROUPED = 'hryvinskyi-core-menu--grouped';

    /**
     * Without groups the menu prints one list of its items, in order, without the grouped modifier
     *
     * @return void
     */
    public function testMenuWithoutGroupsPrintsOneList(): void
    {
        $xpath = $this->render([
            'menu_title' => 'My Module',
            'items' => [
                'settings' => ['label' => 'Settings', 'route' => self::ROUTE, 'sort_order' => '20'],
                'dashboard' => ['label' => 'Dashboard', 'route' => self::ROUTE, 'sort_order' => '10'],
                'hidden' => ['label' => 'Hidden', 'route' => self::ROUTE, 'is_active' => false],
                'reports' => [
                    'label' => 'Reports',
                    'route' => self::ROUTE,
                    'sort_order' => '30',
                    'resource' => self::ALLOWED,
                    'class' => 'current',
                ],
            ],
        ]);

        $lists = $this->nodes($xpath, '//div[contains(@class, "hryvinskyi-core-menu")]/ul');
        self::assertCount(1, $lists);
        $list = $lists[0];
        self::assertSame('admin__action-dropdown-menu', $list->getAttribute('class'));
        self::assertSame(
            'button',
            $this->single($xpath, 'preceding-sibling::*[1]', $list)->nodeName,
            'The list is the toggle button\'s next element'
        );
        self::assertSame(['Dashboard', 'Settings', 'Reports'], $this->labels($xpath, './li/a', $list));
        self::assertSame('current', $this->single($xpath, './li[3]', $list)->getAttribute('class'));
        self::assertCount(0, $this->nodes($xpath, '//*[contains(@class, "' . self::GROUPED . '")]'));
        self::assertCount(0, $this->nodes($xpath, '//*[@role="group"]'));
    }

    /**
     * With groups each group is a labelled container holding its items, in group order, and the menu element is
     * the toggle button's next element, marked so a click inside it keeps the dropdown open
     *
     * @return void
     */
    public function testGroupedMenuPrintsLabelledGroupsInOrder(): void
    {
        $xpath = $this->render([
            'groups' => [
                'settings' => ['label' => 'Settings', 'sort_order' => '30'],
                'reports' => ['label' => 'Reports', 'sort_order' => '10'],
                'pages' => ['label' => 'Pages', 'sort_order' => '20'],
            ],
            'items' => [
                'general' => ['label' => 'General', 'route' => self::ROUTE, 'group' => 'settings'],
                'sales' => ['label' => 'Sales', 'route' => self::ROUTE, 'group' => 'reports', 'sort_order' => '1'],
                'about' => ['label' => 'About', 'route' => self::ROUTE, 'group' => 'pages'],
                'audit' => ['label' => 'Audit', 'route' => self::ROUTE, 'group' => 'reports', 'sort_order' => '2'],
                'loose' => ['label' => 'Loose', 'route' => self::ROUTE],
            ],
        ]);

        $button = $this->single($xpath, '//div[contains(@class, "hryvinskyi-core-menu")]/button');
        $menu = $this->single($xpath, 'following-sibling::*[1]', $button);
        self::assertSame('div', $menu->nodeName);
        self::assertSame('dropdown', $menu->getAttribute('data-target'));
        self::assertContains('admin__action-dropdown-menu', explode(' ', $menu->getAttribute('class')));
        self::assertContains(self::GROUPED, explode(' ', $menu->getAttribute('class')));

        $groups = $this->nodes($xpath, './/*[@role="group"]', $menu);
        $headings = [];
        $labels = [];
        foreach ($groups as $group) {
            $headingId = $group->getAttribute('aria-labelledby');
            self::assertNotSame('', $headingId);
            $heading = $this->single($xpath, sprintf('//*[@id="%s"]', $headingId));
            self::assertSame($group, $heading->parentNode, 'The heading is inside its group');
            $headings[] = trim($heading->textContent);
            $labels[] = $this->labels($xpath, './/a', $group);
        }

        self::assertSame(['Reports', 'Pages', 'Settings'], $headings);
        self::assertSame([['Sales', 'Audit'], ['About'], ['General']], $labels);
        self::assertSame(
            ['Sales', 'Audit', 'About', 'General', 'Loose'],
            $this->labels($xpath, './/a', $menu),
            'An item outside every group closes the menu'
        );
        self::assertCount(0, $this->nodes($xpath, '//h1|//h2|//h3|//h4|//h5|//h6'));
    }

    /**
     * A group longer than the column limit is printed as even columns
     *
     * @return void
     */
    public function testLongGroupIsPrintedAsEvenColumns(): void
    {
        $items = [];
        for ($i = 1; $i <= 13; $i++) {
            $items['item' . $i] = [
                'label' => 'Item ' . $i,
                'route' => self::ROUTE,
                'group' => 'settings',
                'sort_order' => (string) $i,
            ];
        }

        $xpath = $this->render([
            'groups' => ['settings' => ['label' => 'Settings']],
            'items' => $items,
            'max_items_per_column' => '6',
        ]);

        $group = $this->single($xpath, '//*[@role="group"]');
        $columns = $this->nodes($xpath, './/ul', $group);
        self::assertSame([5, 4, 4], array_map(
            fn (\DOMElement $column): int => count($this->nodes($xpath, './li', $column)),
            $columns
        ));
        self::assertSame(['Item 1', 'Item 6', 'Item 10'], array_map(
            fn (\DOMElement $column): string => trim($this->single($xpath, './li[1]/a', $column)->textContent),
            $columns
        ));
    }

    /**
     * A group whose only item is denied, or inactive, is not printed at all
     *
     * @return void
     */
    public function testGroupWithoutVisibleItemsIsNotPrinted(): void
    {
        $xpath = $this->render([
            'groups' => [
                'reports' => ['label' => 'Reports', 'sort_order' => '10'],
                'denied' => ['label' => 'Denied', 'sort_order' => '20'],
                'inactive' => ['label' => 'Inactive', 'sort_order' => '30'],
            ],
            'items' => [
                'sales' => ['label' => 'Sales', 'route' => self::ROUTE, 'group' => 'reports',
                    'resource' => self::ALLOWED],
                'secret' => ['label' => 'Secret', 'route' => self::ROUTE, 'group' => 'denied',
                    'resource' => self::DENIED],
                'paused' => ['label' => 'Paused', 'route' => self::ROUTE, 'group' => 'inactive',
                    'is_active' => false],
            ],
        ]);

        $headings = array_map(
            fn (\DOMElement $group): string => trim(
                $this->single($xpath, sprintf('//*[@id="%s"]', $group->getAttribute('aria-labelledby')))->textContent
            ),
            $this->nodes($xpath, '//*[@role="group"]')
        );
        self::assertSame(['Reports'], $headings);
        self::assertSame(['Sales'], $this->labels($xpath, '//*[contains(@class, "' . self::GROUPED . '")]//a'));
    }

    /**
     * Query parameters whose names the object manager would interpret ("instance") still reach the link
     *
     * In developer mode the object manager interprets such keys anywhere in the arguments it is handed, the
     * block's own "data" included. The items are therefore given to the block after it is created, so the test
     * shows that building the items hands no route parameters to the object manager.
     *
     * @return void
     */
    public function testRouteParametersReachTheLinkUnchanged(): void
    {
        $xpath = $this->render([], [
            'items' => [
                'filtered' => [
                    'label' => 'Filtered',
                    'route' => self::ROUTE,
                    'route_params' => ['_query' => ['instance' => 'x']],
                ],
            ],
        ]);

        $href = $this->single($xpath, '//div[contains(@class, "hryvinskyi-core-menu")]//a')->getAttribute('href');
        $query = (string) parse_url($href, PHP_URL_QUERY);
        parse_str($query, $parameters);
        self::assertSame('x', $parameters['instance'] ?? null, $href);
    }

    /**
     * Render a menu block with the given layout arguments and parse its markup
     *
     * @param array<string, mixed> $data Arguments the block is created with, as layout XML gives them
     * @param array<string, mixed> $lateData Arguments set on the block after it is created
     * @return \DOMXPath
     */
    private function render(array $data, array $lateData = []): \DOMXPath
    {
        $objectManager = Bootstrap::getObjectManager();
        $context = $objectManager->create(Context::class, [
            'authorization' => new AllowListAuthorization([self::ALLOWED]),
        ]);
        $layout = $objectManager->get(LayoutInterface::class);
        self::assertInstanceOf(LayoutInterface::class, $layout);
        $block = $layout->createBlock(Menu::class, uniqid('test.menu.', false), [
            'context' => $context,
            'data' => $data,
        ]);
        self::assertInstanceOf(Menu::class, $block);
        $block->addData($lateData);

        $html = $block->toHtml();
        self::assertNotSame('', trim($html));
        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="utf-8"?><body>' . $html . '</body>', LIBXML_NOERROR);

        return new \DOMXPath($document);
    }

    /**
     * The elements an expression selects
     *
     * @param \DOMXPath $xpath
     * @param string $expression
     * @param \DOMNode|null $context
     * @return list<\DOMElement>
     */
    private function nodes(\DOMXPath $xpath, string $expression, ?\DOMNode $context = null): array
    {
        $result = $xpath->query($expression, $context);
        self::assertInstanceOf(\DOMNodeList::class, $result, $expression);
        $elements = [];
        foreach ($result as $node) {
            if ($node instanceof \DOMElement) {
                $elements[] = $node;
            }
        }

        return $elements;
    }

    /**
     * The one element an expression selects
     *
     * @param \DOMXPath $xpath
     * @param string $expression
     * @param \DOMNode|null $context
     * @return \DOMElement
     */
    private function single(\DOMXPath $xpath, string $expression, ?\DOMNode $context = null): \DOMElement
    {
        $elements = $this->nodes($xpath, $expression, $context);
        self::assertCount(1, $elements, $expression);

        return $elements[0];
    }

    /**
     * The trimmed text of the links an expression selects
     *
     * @param \DOMXPath $xpath
     * @param string $expression
     * @param \DOMNode|null $context
     * @return list<string>
     */
    private function labels(\DOMXPath $xpath, string $expression, ?\DOMNode $context = null): array
    {
        return array_map(
            static fn (\DOMElement $link): string => trim($link->textContent),
            $this->nodes($xpath, $expression, $context)
        );
    }
}
