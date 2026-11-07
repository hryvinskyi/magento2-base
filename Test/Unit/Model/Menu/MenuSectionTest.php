<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Menu;

use Hryvinskyi\Base\Api\Menu\MenuGroupInterface;
use Hryvinskyi\Base\Model\Menu\MenuGroup;
use Hryvinskyi\Base\Model\Menu\MenuItem;
use Hryvinskyi\Base\Model\Menu\MenuSection;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\Code\Reader\ClassReader;
use Magento\Framework\ObjectManager\Config\Compiled as CompiledConfig;
use Magento\Framework\ObjectManager\Factory\Compiled as CompiledFactory;
use PHPUnit\Framework\TestCase;

/**
 * A section built the way production mode builds it
 *
 * Production mode creates objects with the compiled object manager. It uses an argument handed to create() only
 * when the argument is set, so an explicit null falls back to what the compilation recorded for the parameter:
 * for a required class-typed parameter, a shared instance of that class. These tests record the section's
 * constructor arguments the way the compilation does and build through the compiled factory, with a shared
 * group planted where a wrongly compiled parameter would pick it up.
 *
 * @covers \Hryvinskyi\Base\Model\Menu\MenuSection
 */
class MenuSectionTest extends TestCase
{
    /**
     * A section created without a group argument has no group
     *
     * @return void
     */
    public function testUntitledSectionBuiltByTheCompiledObjectManagerHasNoGroup(): void
    {
        $columns = [[new MenuItem($this->createMock(UrlInterface::class), label: 'Loose')]];

        $section = $this->compiledFactory()->create(MenuSection::class, ['columns' => $columns]);

        self::assertInstanceOf(MenuSection::class, $section);
        self::assertNull($section->getGroup());
        self::assertSame($columns, $section->getColumns());
    }

    /**
     * A section created with a group keeps that group
     *
     * @return void
     */
    public function testSectionBuiltByTheCompiledObjectManagerKeepsItsGroup(): void
    {
        $group = new MenuGroup('reports', 'Reports', 10);

        $section = $this->compiledFactory()->create(MenuSection::class, ['columns' => [], 'group' => $group]);

        self::assertInstanceOf(MenuSection::class, $section);
        self::assertSame($group, $section->getGroup());
    }

    /**
     * The compiled factory, holding the section's constructor arguments as the compilation records them
     *
     * @return CompiledFactory
     */
    private function compiledFactory(): CompiledFactory
    {
        $arguments = [];
        foreach ((new ClassReader())->getConstructor(MenuSection::class) ?? [] as $parameter) {
            self::assertIsArray($parameter);
            self::assertIsString($parameter[0]);
            self::assertIsBool($parameter[2]);
            $type = is_string($parameter[1]) ? $parameter[1] : null;
            $arguments[$parameter[0]] = $this->compiledArgument($type, $parameter[2], $parameter[3]);
        }
        $sharedInstances = [MenuGroupInterface::class => new MenuGroup('planted', 'Planted', 0)];

        return new CompiledFactory(
            new CompiledConfig(['arguments' => [MenuSection::class => $arguments]]),
            $sharedInstances
        );
    }

    /**
     * One constructor argument as the compilation records it: an optional parameter keeps its default value, a
     * required class-typed one becomes a shared instance of its type, a required scalar one has no value
     *
     * @param string|null $type
     * @param bool $isRequired
     * @param mixed $default
     * @return array<string, mixed>
     */
    private function compiledArgument(?string $type, bool $isRequired, mixed $default): array
    {
        if (!$isRequired) {
            return $default === null ? ['_vn_' => true] : ['_v_' => $default];
        }

        return $type !== null ? ['_i_' => $type] : ['_vn_' => true];
    }
}
