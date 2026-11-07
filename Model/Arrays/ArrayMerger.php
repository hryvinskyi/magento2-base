<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/ (BSD-3-Clause)
 *
 * Modified for Magento 2
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Model\Arrays;

use Hryvinskyi\Base\Api\Arrays\ArrayInspectorInterface;
use Hryvinskyi\Base\Api\Arrays\ArrayMergerInterface;
use Hryvinskyi\Base\Api\Data\ReplaceArrayValueInterface;
use Hryvinskyi\Base\Api\Data\UnsetArrayValueInterface;
use Hryvinskyi\Base\Exception\InvalidParamException;

/**
 * Merges arrays recursively, the later ones winning, with markers that unset or replace a key.
 */
class ArrayMerger implements ArrayMergerInterface
{
    /**
     * @param ArrayInspectorInterface $inspector tells a source merged entry by entry from an object merged by its
     * public properties
     */
    public function __construct(
        private readonly ArrayInspectorInterface $inspector
    ) {
    }

    /**
     * @inheritdoc
     */
    public function merge(array $first, array|object ...$others): array
    {
        $result = $first;
        foreach ($others as $next) {
            $entries = $this->inspector->isTraversable($next) ? $next : get_object_vars($next);
            foreach ($entries as $key => $value) {
                if (!is_int($key) && !is_string($key)) {
                    throw new InvalidParamException('Only integer and string keys can be merged.');
                }
                $result = $this->mergeEntry($result, $key, $value);
            }
        }

        return $result;
    }

    /**
     * Merges one entry into the result.
     *
     * The two markers are the closed set of instructions a merged array can carry; every other value is data.
     *
     * @param array<array-key,mixed> $result the array merged so far
     * @param int|string $key the key of the entry
     * @param mixed $value the value of the entry
     * @return array<array-key,mixed> the array with the entry merged
     */
    private function mergeEntry(array $result, int|string $key, mixed $value): array
    {
        if ($value instanceof UnsetArrayValueInterface) {
            unset($result[$key]);
            return $result;
        }

        if ($value instanceof ReplaceArrayValueInterface) {
            $result[$key] = $value->getValue();
            return $result;
        }

        if (is_int($key)) {
            if (array_key_exists($key, $result)) {
                $result[] = $value;
                return $result;
            }
            $result[$key] = $value;
            return $result;
        }

        if (is_array($value) && isset($result[$key]) && is_array($result[$key])) {
            $result[$key] = $this->merge($result[$key], $value);
            return $result;
        }

        $result[$key] = $value;

        return $result;
    }
}
