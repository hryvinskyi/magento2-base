<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Layout;

use Hryvinskyi\Base\Model\Layout\LayoutXml;
use Hryvinskyi\Base\Model\Layout\NoSetLayoutException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hryvinskyi\Base\Model\Layout\LayoutXml
 * @covers \Hryvinskyi\Base\Model\Layout\NoSetLayoutException
 */
class LayoutXmlTest extends TestCase
{
    public function testReturnsTheRecordedLayout(): void
    {
        $layoutXml = new LayoutXml();
        $layoutXml->setLayout('<layout><block name="a"/></layout>');

        self::assertSame('<layout><block name="a"/></layout>', $layoutXml->getLayout());
    }

    public function testAnEmptyRecordedLayoutIsStillALayout(): void
    {
        $layoutXml = new LayoutXml();
        $layoutXml->setLayout('');

        self::assertSame('', $layoutXml->getLayout());
    }

    public function testAskingBeforeTheLayoutWasRecordedFails(): void
    {
        $this->expectException(NoSetLayoutException::class);
        $this->expectExceptionMessage('Layout was not set');

        (new LayoutXml())->getLayout();
    }

    public function testTheExceptionKeepsAGivenMessage(): void
    {
        $exception = new NoSetLayoutException(__('No layout for %1', 'cms_index_index'));

        self::assertSame('No layout for cms_index_index', $exception->getMessage());
    }
}
