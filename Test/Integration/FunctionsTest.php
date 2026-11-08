<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Integration;

use Hryvinskyi\Base\Api\Debug\VarDumperInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * The debug functions come with the module's registration and print through the dumper the container configures.
 */
class FunctionsTest extends TestCase
{
    /**
     * The functions exist as soon as the module is registered, with no import.
     *
     * @return void
     */
    public function testTheModuleRegistrationDeclaresTheFunctions(): void
    {
        self::assertTrue(function_exists('hdump'));
        self::assertTrue(function_exists('hdd'));
    }

    /**
     * hdump() prints exactly what the configured dumper gives, plain on the command line.
     *
     * @return void
     */
    public function testHdumpPrintsTheConfiguredDump(): void
    {
        $value = ['name' => 'John', 'items' => [1, 2]];
        $dumper = Bootstrap::getObjectManager()->get(VarDumperInterface::class);
        self::assertInstanceOf(VarDumperInterface::class, $dumper);
        $this->expectOutputString($dumper->dumpAsString($value, 10, false) . PHP_EOL);

        self::assertSame($value, hdump($value));
    }
}
