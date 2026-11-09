<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

namespace Hryvinskyi\Base\View\Helper\SecureHtmlRender;

use Hryvinskyi\Base\Helper\Html;
use Magento\Framework\Escaper;
use Magento\Framework\View\Helper\SecureHtmlRender\TagData;

/**
 * Renders the secure HTML renderer's tags through the module's Html builder.
 */
class HtmlRenderer extends \Magento\Framework\View\Helper\SecureHtmlRender\HtmlRenderer
{
    /**
     * @var Escaper
     */
    private readonly Escaper $htmlEscaper;

    /**
     * @param Escaper $escaper
     */
    public function __construct(Escaper $escaper)
    {
        parent::__construct($escaper);
        $this->htmlEscaper = $escaper;
    }

    /**
     * Render the tag.
     *
     * An attribute whose value is `null` is rendered without a value (`<script data-x>`): Magento's renderer keeps
     * such an attribute too, as `data-x=""`, and templates mark tags with it.
     *
     * @param TagData $tagData
     * @return string
     */
    public function renderTag(TagData $tagData): string
    {
        $content = $tagData->getContent() ?? '';
        if ($tagData->isTextContent()) {
            $escaped = $this->htmlEscaper->escapeHtml($content);
            $content = is_string($escaped) ? $escaped : '';
        }

        return Html::tag($tagData->getTag(), $content, $this->withValuelessAttributes($tagData->getAttributes()));
    }

    /**
     * Mark each attribute given without a value as one the Html builder renders bare.
     *
     * @param array<string|null> $attributes the tag's attributes, `null` for an attribute without a value
     * @return array<string|true>
     */
    private function withValuelessAttributes(array $attributes): array
    {
        return array_map(static fn (?string $value): string|bool => $value ?? true, $attributes);
    }
}
