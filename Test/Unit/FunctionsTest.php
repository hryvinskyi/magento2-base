<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit;

use Composer\Autoload\ClassLoader;
use Hryvinskyi\Base\Api\Debug\VarDumperInterface;
use Hryvinskyi\Base\Test\Unit\Fake\RecordingVarDumper;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * The global debug functions: they find Base's dumper wherever Magento has started, and fall back to plain text
 * before it has.
 */
class FunctionsTest extends TestCase
{
    /**
     * @var ObjectManagerInterface|null the object manager the test found, put back afterwards
     */
    private ?ObjectManagerInterface $previous = null;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $instance = new \ReflectionProperty(ObjectManager::class, '_instance');
        $value = $instance->getValue();
        $this->previous = $value instanceof ObjectManagerInterface ? $value : null;
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        (new \ReflectionProperty(ObjectManager::class, '_instance'))->setValue(null, $this->previous);
    }

    /**
     * Installs an object manager that hands out the given dumper.
     *
     * @param VarDumperInterface $dumper the dumper the functions should find
     * @return void
     */
    private function useDumper(VarDumperInterface $dumper): void
    {
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->method('get')->with(VarDumperInterface::class)->willReturn($dumper);
        ObjectManager::setInstance($objectManager);
    }

    /**
     * hdump() prints Base's dump and hands the value back, so it can wrap an expression.
     *
     * @return void
     */
    public function testHdumpPrintsBasesDumpAndReturnsTheValue(): void
    {
        $dumper = new RecordingVarDumper();
        $this->useDumper($dumper);
        $this->expectOutputString('<dump>' . PHP_EOL);

        self::assertSame(['a' => 1], hdump(['a' => 1], 3));
        self::assertSame([['var' => ['a' => 1], 'depth' => 3, 'highlight' => false]], $dumper->calls);
    }

    /**
     * On the command line the dump is plain text unless highlighting is asked for.
     *
     * @return void
     */
    public function testHighlightingIsUsedWhenAskedFor(): void
    {
        $dumper = new RecordingVarDumper();
        $this->useDumper($dumper);
        $this->expectOutputString('<dump>' . PHP_EOL);

        hdump('x', 10, true);

        self::assertTrue($dumper->calls[0]['highlight']);
    }

    /**
     * Before Magento has started there is no dumper to ask: the value is still printed, as plain text.
     *
     * @return void
     */
    public function testBeforeMagentoStartsTheValueIsPrintedAsPlainText(): void
    {
        (new \ReflectionProperty(ObjectManager::class, '_instance'))->setValue(null, null);
        $this->expectOutputString(print_r(['a' => 1], true) . PHP_EOL);

        self::assertSame(['a' => 1], hdump(['a' => 1]));
    }

    /**
     * hdd() prints the dump and stops the script with a failure code.
     *
     * @return void
     */
    public function testHddPrintsAndStops(): void
    {
        $classLoader = (new \ReflectionClass(ClassLoader::class))->getFileName();
        self::assertIsString($classLoader);
        $script = 'require ' . var_export(dirname($classLoader, 2) . '/autoload.php', true) . ';'
            . 'require_once ' . var_export(dirname(__DIR__, 2) . '/functions.php', true) . ';'
            . 'hdd(["a" => 1]); echo "after";';
        $process = new Process([PHP_BINARY, '-r', $script]);
        $process->run();

        self::assertSame(1, $process->getExitCode());
        self::assertStringContainsString('[a] => 1', $process->getOutput());
        self::assertStringNotContainsString('after', $process->getOutput());
    }
}
