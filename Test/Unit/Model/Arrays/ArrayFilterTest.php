<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Arrays;

use Hryvinskyi\Base\Model\Arrays\ArrayFilter;
use PHPUnit\Framework\TestCase;

class ArrayFilterTest extends TestCase
{
    /**
     * @var ArrayFilter the service under test, built with its real collaborators
     */
    private ArrayFilter $filter;

    protected function setUp(): void
    {
        $this->filter = new ArrayFilter();
    }

    public function testFilter(): void
    {
        $array = [
            'A' => [
                'B' => 1,
                'C' => 2,
            ],
            'G' => 1,
        ];
        $this->assertEquals($this->filter->filter($array, ['A']), [
            'A' => [
                'B' => 1,
                'C' => 2,
            ],
        ]);
        $this->assertEquals($this->filter->filter($array, ['A.B']), [
            'A' => [
                'B' => 1,
            ],
        ]);
        $this->assertEquals($this->filter->filter($array, ['A', '!A.B']), [
            'A' => [
                'C' => 2,
            ],
        ]);
        $this->assertEquals($this->filter->filter($array, ['!A.B', 'A']), [
            'A' => [
                'C' => 2,
            ],
        ]);
        $this->assertEquals($this->filter->filter($array, ['A', 'G']), [
            'A' => [
                'B' => 1,
                'C' => 2,
            ],
            'G' => 1,
        ]);
        $this->assertEquals($this->filter->filter($array, ['X']), []);
        $this->assertEquals($this->filter->filter($array, ['X.Y']), []);
        $this->assertEquals($this->filter->filter($array, ['A.X']), []);
    }

    public function testFilterIgnoresRulesThatAreNeitherStringsNorIntegers(): void
    {
        $this->assertSame([0 => 'a', 'B' => 1], $this->filter->filter(['a', 'B' => 1, 'C' => 2], [0, 'B', null, 1.5]));
    }

    public function testFilterMatchesNothingThroughAValueThatIsNotAnArray(): void
    {
        $this->assertSame([], $this->filter->filter(['G' => 1], ['G.H']));
    }
}
