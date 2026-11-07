<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

namespace Hryvinskyi\Base\View\Helper\SecureHtmlRender;

use Hryvinskyi\Base\Api\Html\TagRendererInterface;
use Magento\Framework\Escaper;
use Magento\Framework\View\Helper\SecureHtmlRender\TagData;

/**
 * Renders the secure HTML renderer's tags through the module's tag renderer, so void elements self-close.
 */
class HtmlRenderer extends \Magento\Framework\View\Helper\SecureHtmlRender\HtmlRenderer
{
    /**
     * @var Escaper
     */
    private readonly Escaper $htmlEscaper;

    /**
     * @param Escaper $escaper escapes text content, and is handed to the parent for event handlers
     * @param TagRendererInterface $tagRenderer renders the tag with its attributes
     */
    public function __construct(
        Escaper $escaper,
        private readonly TagRendererInterface $tagRenderer
    ) {
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

        return $this->tagRenderer->tag($tagData->getTag(), $content, $tagData->getAttributes());
    }
}
