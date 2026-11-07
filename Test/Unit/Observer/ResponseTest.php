<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Observer;

use Hryvinskyi\Base\Model\Layout\LayoutXml;
use Hryvinskyi\Base\Observer\Response;
use Hryvinskyi\Base\Test\Unit\Fake\FixedLayoutDebugConfig;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\ManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hryvinskyi\Base\Observer\Response
 */
class ResponseTest extends TestCase
{
    /**
     * @var HttpRequest&MockObject
     */
    private MockObject $request;

    /**
     * @var HttpResponse&MockObject
     */
    private MockObject $response;

    /**
     * @var ManagerInterface&MockObject
     */
    private MockObject $messageManager;

    /**
     * @var LayoutXml
     */
    private LayoutXml $layoutXml;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->request = $this->createMock(HttpRequest::class);
        $this->response = $this->createMock(HttpResponse::class);
        $this->messageManager = $this->createMock(ManagerInterface::class);
        $this->layoutXml = new LayoutXml();
    }

    /**
     * A request carrying "xml" with the debug on is answered with the recorded layout as an XML document.
     *
     * @return void
     */
    public function testAnswersWithTheLayoutXml(): void
    {
        $this->request->method('getParam')->with('xml')->willReturn('1');
        $this->layoutXml->setLayout('<layout><block/></layout>');
        $this->response->expects(self::once())->method('setHeader')->with('Content-type', 'application/xml', true);
        $this->response->expects(self::once())->method('setContent')->with(
            '<?xml version="1.0" encoding="UTF-8" ?>'
            . '<layout xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><block/></layout>'
        );
        $this->messageManager->expects(self::never())->method('addErrorMessage');

        $this->execute(true);
    }

    /**
     * A request without "xml" keeps its response.
     *
     * @return void
     */
    public function testLeavesAResponseWhoseRequestDoesNotAskForTheXml(): void
    {
        $this->request->method('getParam')->with('xml')->willReturn(null);
        $this->layoutXml->setLayout('<layout/>');
        $this->response->expects(self::never())->method('setContent');

        $this->execute(true);
    }

    /**
     * With the debug off a request carrying "xml" keeps its response.
     *
     * @return void
     */
    public function testLeavesTheResponseWhenTheDebugIsOff(): void
    {
        $this->request->method('getParam')->with('xml')->willReturn('1');
        $this->layoutXml->setLayout('<layout/>');
        $this->response->expects(self::never())->method('setContent');

        $this->execute(false);
    }

    /**
     * A page that recorded no layout is reported through a message and keeps its content.
     *
     * @return void
     */
    public function testReportsAPageThatRecordedNoLayout(): void
    {
        $this->request->method('getParam')->with('xml')->willReturn('1');
        $this->response->expects(self::never())->method('setContent');
        $this->messageManager->expects(self::once())->method('addErrorMessage');

        $this->execute(true);
    }

    /**
     * Runs the observer with the debug switch.
     *
     * @param bool $debugEnabled whether the layout debug is on
     * @return void
     */
    private function execute(bool $debugEnabled): void
    {
        $observer = new Response(
            $this->layoutXml,
            new FixedLayoutDebugConfig($debugEnabled),
            $this->messageManager
        );

        $observer->execute(new Observer(['request' => $this->request, 'response' => $this->response]));
    }
}
