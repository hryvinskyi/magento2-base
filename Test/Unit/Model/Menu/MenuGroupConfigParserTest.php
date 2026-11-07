<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Menu;

use Hryvinskyi\Base\Api\Menu\MenuGroupInterfaceFactory;
use Hryvinskyi\Base\Model\Menu\MenuGroup;
use Hryvinskyi\Base\Model\Menu\MenuGroupConfigParser;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hryvinskyi\Base\Model\Menu\MenuGroupConfigParser
 * @covers \Hryvinskyi\Base\Model\Menu\MenuGroup
 */
class MenuGroupConfigParserTest extends TestCase
{
    use BuildsMenuObjectsTrait;

    /**
     * @var MenuGroupInterfaceFactory&MockObject
     */
    private MockObject $groupFactory;

    /**
     * @var list<array<mixed>>
     */
    private array $createdWith = [];

    /**
     * @var MenuGroupConfigParser
     */
    private MenuGroupConfigParser $parser;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->groupFactory = $this->createMock(MenuGroupInterfaceFactory::class);
        $this->groupFactory->method('create')->willReturnCallback(
            function (array $data): MenuGroup {
                $this->createdWith[] = $data;

                return self::menuGroupFrom($data);
            }
        );
        $this->parser = new MenuGroupConfigParser($this->groupFactory);
    }

    /**
     * A configured group exposes its code, label and sort order, and comes from the generated factory
     *
     * @return void
     */
    public function testCreatesGroupThroughTheGeneratedFactory(): void
    {
        $group = $this->parser->create('reports', ['label' => 'Reports', 'sort_order' => '20']);

        self::assertSame('reports', $group->getCode());
        self::assertSame('Reports', $group->getLabel());
        self::assertSame(20, $group->getSortOrder());
        self::assertSame([['code' => 'reports', 'label' => 'Reports', 'sortOrder' => 20]], $this->createdWith);
    }

    /**
     * Without a sort order a group sorts as 0; the code loses surrounding whitespace
     *
     * @return void
     */
    public function testDefaultsSortOrderAndTrimsCode(): void
    {
        $group = $this->parser->create(' settings ', ['label' => 'Settings']);

        self::assertSame('settings', $group->getCode());
        self::assertSame(0, $group->getSortOrder());
    }

    /**
     * A translated phrase is accepted as the label
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
                return 'Reports';
            }
        };

        self::assertSame('Reports', $this->parser->create('reports', ['label' => $label])->getLabel());
    }

    /**
     * Sort orders arrive as numbers or numeric strings
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
        self::assertSame(
            $expected,
            $this->parser->create('reports', ['label' => 'Reports', 'sort_order' => $sortOrder])->getSortOrder()
        );
    }

    /**
     * A malformed group is rejected, naming the offending key, before any group is created
     *
     * @param string $code
     * @param array<string, mixed> $data
     * @param string $key
     * @return void
     */
    #[TestWith(['reports', [], 'label'])]
    #[TestWith(['reports', ['label' => ''], 'label'])]
    #[TestWith(['reports', ['label' => '   '], 'label'])]
    #[TestWith(['reports', ['label' => ['Reports']], 'label'])]
    #[TestWith(['reports', ['label' => 12], 'label'])]
    #[TestWith(['reports', ['label' => 'Reports', 'sort_order' => 'first'], 'sort_order'])]
    #[TestWith(['reports', ['label' => 'Reports', 'sort_order' => true], 'sort_order'])]
    #[TestWith(['', ['label' => 'Reports'], 'code'])]
    #[TestWith(['  ', ['label' => 'Reports'], 'code'])]
    #[TestWith(['a.b', ['label' => 'Reports'], 'code'])]
    #[TestWith(['a b', ['label' => 'Reports'], 'code'])]
    public function testRejectsMalformedGroup(string $code, array $data, string $key): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"' . $key . '"');

        try {
            $this->parser->create($code, $data);
        } finally {
            self::assertSame([], $this->createdWith);
        }
    }
}
