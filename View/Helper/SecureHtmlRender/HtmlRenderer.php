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

class HtmlRenderer extends \Magento\Framework\View\Helper\SecureHtmlRender\HtmlRenderer
{
    public function __construct(private readonly Escaper $escaper) {
        parent::__construct($escaper);
    }

    /**
     * Render the tag.
     *
     * @param TagData $tagData
     * @return string
     */
    public function renderTag(TagData $tagData): string
    {
        $content = null;
        if ($tagData->getContent() !== null) {
            $content = $tagData->isTextContent() ? $this->escaper->escapeHtml($tagData->getContent()) : $tagData->getContent();
        }

        return Html::tag($tagData->getTag(), $content, $tagData->getAttributes());
    }
}
