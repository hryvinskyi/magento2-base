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

namespace Hryvinskyi\Base\Model\Html;

use Hryvinskyi\Base\Api\Html\HtmlEncoderInterface;
use Magento\Framework\Escaper;
use Magento\Framework\ZendEscaper;

/**
 * Encodes through the framework's escapers: the Laminas escaper always encodes existing entities again, the
 * framework escaper (called without allowed tags) leaves them as they are.
 */
class HtmlEncoder implements HtmlEncoderInterface
{
    /**
     * @param ZendEscaper $doubleEncodingEscaper encodes with `ENT_QUOTES | ENT_SUBSTITUTE`, entities included
     * @param Escaper $entityKeepingEscaper encodes with `ENT_QUOTES | ENT_SUBSTITUTE`, existing entities kept
     */
    public function __construct(
        private readonly ZendEscaper $doubleEncodingEscaper,
        private readonly Escaper $entityKeepingEscaper
    ) {
    }

    /**
     * @inheritDoc
     */
    public function encode(string|int|float|bool|\Stringable|null $content): string
    {
        return $this->doubleEncodingEscaper->escapeHtml((string)$content);
    }

    /**
     * @inheritDoc
     */
    public function encodeKeepingEntities(string|int|float|bool|\Stringable|null $content): string
    {
        return $this->entityKeepingEscaper->escapeHtml((string)$content);
    }

    /**
     * @inheritDoc
     */
    public function decode(string $content): string
    {
        return htmlspecialchars_decode($content, ENT_QUOTES);
    }
}
