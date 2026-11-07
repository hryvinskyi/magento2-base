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
     * @param TagData $tagData
     * @return string
     */
    public function renderTag(TagData $tagData): string
    {
        $content = '';
        if ($tagData->getContent() !== null) {
            $content = $tagData->isTextContent()
                ? $this->htmlEscaper->escapeHtml($tagData->getContent())
                : $tagData->getContent();
        }

        return Html::tag($tagData->getTag(), $content, $tagData->getAttributes());
    }
}
