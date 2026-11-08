<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Fake;

use Hryvinskyi\Base\Api\Debug\VarDumperInterface;

/**
 * A dumper that answers with a marker and remembers what it was asked
 */
class RecordingVarDumper implements VarDumperInterface
{
    /**
     * @var list<array{var: mixed, depth: int, highlight: bool}>
     */
    public array $calls = [];

    /**
     * @inheritDoc
     */
    public function dumpAsString(mixed $var, int $depth = 10, bool $highlight = false): string
    {
        $this->calls[] = ['var' => $var, 'depth' => $depth, 'highlight' => $highlight];

        return '<dump>';
    }

    /**
     * @inheritDoc
     */
    public function export(mixed $var): string
    {
        return '<export>';
    }
}
