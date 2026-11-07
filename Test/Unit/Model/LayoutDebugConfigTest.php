<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model;

use Hryvinskyi\Base\Model\LayoutDebugConfig;
use Magento\Developer\Helper\Data as DevHelper;
use Magento\Framework\App\State;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hryvinskyi\Base\Model\LayoutDebugConfig
 */
class LayoutDebugConfigTest extends TestCase
{
    /**
     * The layout debug is on only in developer mode, and there only for a visitor allowed to see developer output.
     *
     * @param string $mode the application mode
     * @param bool $devAllowed whether the visitor may see developer output
     * @param bool $expected whether the layout debug is on
     * @return void
     */
    #[TestWith([State::MODE_DEVELOPER, true, true])]
    #[TestWith([State::MODE_DEVELOPER, false, false])]
    #[TestWith([State::MODE_PRODUCTION, true, false])]
    #[TestWith([State::MODE_PRODUCTION, false, false])]
    #[TestWith([State::MODE_DEFAULT, true, false])]
    public function testDeveloperModeAndAnAllowedVisitorEnableTheLayoutDebug(
        string $mode,
        bool $devAllowed,
        bool $expected
    ): void {
        $state = $this->createMock(State::class);
        $state->method('getMode')->willReturn($mode);
        $devHelper = $this->createMock(DevHelper::class);
        $devHelper->method('isDevAllowed')->willReturn($devAllowed);

        self::assertSame($expected, (new LayoutDebugConfig($state, $devHelper))->isEnabledLayoutDebug());
    }

    /**
     * Outside developer mode the answer costs no look at the visitor.
     *
     * @return void
     */
    public function testTheVisitorIsNotCheckedOutsideDeveloperMode(): void
    {
        $state = $this->createMock(State::class);
        $state->method('getMode')->willReturn(State::MODE_PRODUCTION);
        $devHelper = $this->createMock(DevHelper::class);
        $devHelper->expects(self::never())->method('isDevAllowed');

        self::assertFalse((new LayoutDebugConfig($state, $devHelper))->isEnabledLayoutDebug());
    }
}
