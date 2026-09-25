<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Observer;

use Hryvinskyi\Base\Observer\AddMenuHandleToConfigPage;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Layout\ProcessorInterface;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hryvinskyi\Base\Observer\AddMenuHandleToConfigPage
 */
class AddMenuHandleToConfigPageTest extends TestCase
{
    /**
     * @var RequestInterface&MockObject
     */
    private MockObject $request;

    /**
     * @var ProcessorInterface&MockObject
     */
    private MockObject $update;

    /**
     * @var LayoutInterface&MockObject
     */
    private MockObject $layout;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->request = $this->createMock(RequestInterface::class);
        $this->update = $this->createMock(ProcessorInterface::class);
        $this->layout = $this->createMock(LayoutInterface::class);
        $this->layout->method('getUpdate')->willReturn($this->update);
    }

    /**
     * The configuration page gets a handle named after its section
     *
     * @return void
     */
    public function testAddsSectionHandleOnConfigPage(): void
    {
        $this->request->method('getParam')->with('section')->willReturn('my_section');
        $this->update->expects($this->once())->method('addHandle')->with('system_config_edit_section_my_section');

        $this->execute('adminhtml_system_config_edit', $this->layout);
    }

    /**
     * Other pages are left alone
     *
     * @return void
     */
    public function testIgnoresOtherPages(): void
    {
        $this->request->method('getParam')->willReturn('my_section');
        $this->update->expects($this->never())->method('addHandle');

        $this->execute('catalog_product_edit', $this->layout);
    }

    /**
     * Without a usable section parameter no handle is added
     *
     * @param mixed $section
     * @return void
     */
    #[TestWith([null])]
    #[TestWith([''])]
    #[TestWith([['my_section']])]
    public function testIgnoresMissingSection(mixed $section): void
    {
        $this->request->method('getParam')->willReturn($section);
        $this->update->expects($this->never())->method('addHandle');

        $this->execute('adminhtml_system_config_edit', $this->layout);
    }

    /**
     * An event without a layout is ignored instead of failing
     *
     * @return void
     */
    public function testIgnoresEventWithoutLayout(): void
    {
        $this->request->method('getParam')->willReturn('my_section');
        $this->update->expects($this->never())->method('addHandle');

        $this->execute('adminhtml_system_config_edit', null);
    }

    /**
     * Run the observer for a layout_load_before event with the given payload
     *
     * @param string $fullActionName
     * @param LayoutInterface|null $layout
     * @return void
     */
    private function execute(string $fullActionName, ?LayoutInterface $layout): void
    {
        $event = new Event(['full_action_name' => $fullActionName, 'layout' => $layout]);

        (new AddMenuHandleToConfigPage($this->request))->execute(new Observer(['event' => $event]));
    }
}
