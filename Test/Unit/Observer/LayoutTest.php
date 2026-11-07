<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Observer;

use Hryvinskyi\Base\Model\Layout\LayoutXml;
use Hryvinskyi\Base\Model\Layout\NoSetLayoutException;
use Hryvinskyi\Base\Observer\Layout;
use Hryvinskyi\Base\Test\Unit\Fake\FixedLayoutDebugConfig;
use Magento\Framework\Event\Observer;
use Magento\Framework\View\Layout as ViewLayout;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hryvinskyi\Base\Observer\Layout
 */
class LayoutTest extends TestCase
{
    /**
     * With the layout debug on, the XML of the layout the event carries is recorded.
     *
     * @return void
     */
    public function testRecordsTheLayoutXmlWhenTheDebugIsOn(): void
    {
        $layoutXml = new LayoutXml();
        $layout = $this->createMock(ViewLayout::class);
        $layout->method('getXmlString')->willReturn('<layout/>');

        (new Layout(new FixedLayoutDebugConfig(true), $layoutXml))->execute(new Observer(['layout' => $layout]));

        self::assertSame('<layout/>', $layoutXml->getLayout());
    }

    /**
     * With the layout debug off, the layout is not even read and nothing is recorded.
     *
     * @return void
     */
    public function testRecordsNothingWhenTheDebugIsOff(): void
    {
        $layoutXml = new LayoutXml();
        $layout = $this->createMock(ViewLayout::class);
        $layout->expects(self::never())->method('getXmlString');

        (new Layout(new FixedLayoutDebugConfig(false), $layoutXml))->execute(new Observer(['layout' => $layout]));

        $this->expectException(NoSetLayoutException::class);
        $layoutXml->getLayout();
    }
}
