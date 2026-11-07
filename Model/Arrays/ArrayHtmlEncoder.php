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

use Hryvinskyi\Base\Api\Arrays\ArrayHtmlEncoderInterface;
use Magento\Framework\ZendEscaper;

/**
 * Encodes or decodes the HTML special characters of every string in a nested array.
 */
class ArrayHtmlEncoder implements ArrayHtmlEncoderInterface
{
    /**
     * @param ZendEscaper $escaper encodes a string with quotes, double encoding and UTF-8 substitution
     */
    public function __construct(
        private readonly ZendEscaper $escaper
    ) {
    }

    /**
     * @inheritdoc
     */
    public function htmlEncode(array $data, bool $valuesOnly = true): array
    {
        $encoded = [];
        foreach ($data as $key => $value) {
            if (!$valuesOnly && is_string($key)) {
                $key = $this->escaper->escapeHtml($key);
            }
            $encoded[$key] = match (true) {
                is_string($value) => $this->escaper->escapeHtml($value),
                is_array($value) => $this->htmlEncode($value, $valuesOnly),
                default => $value,
            };
        }

        return $encoded;
    }

    /**
     * @inheritdoc
     */
    public function htmlDecode(array $data, bool $valuesOnly = true): array
    {
        $decoded = [];
        foreach ($data as $key => $value) {
            if (!$valuesOnly && is_string($key)) {
                $key = htmlspecialchars_decode($key, ENT_QUOTES | ENT_SUBSTITUTE);
            }
            $decoded[$key] = match (true) {
                is_string($value) => htmlspecialchars_decode($value, ENT_QUOTES | ENT_SUBSTITUTE),
                is_array($value) => $this->htmlDecode($value, $valuesOnly),
                default => $value,
            };
        }

        return $decoded;
    }
}
