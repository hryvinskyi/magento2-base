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
use Hryvinskyi\Base\Api\Html\ListRendererInterface;
use Hryvinskyi\Base\Api\Html\TagRendererInterface;

/**
 * Renders unordered and ordered lists on top of the tag renderer.
 */
class ListRenderer implements ListRendererInterface
{
    /**
     * The options that configure the list and are not attributes of its tag.
     */
    private const LIST_OPTION_KEYS = ['tag', 'encode', 'item', 'separator', 'itemOptions'];

    /**
     * @param TagRendererInterface $tagRenderer renders the tags
     * @param HtmlEncoderInterface $htmlEncoder encodes the items
     * @param TextConverter $textConverter turns items and option values into text
     * @param OptionReader $optionReader reads the options that are not attributes
     */
    public function __construct(
        private readonly TagRendererInterface $tagRenderer,
        private readonly HtmlEncoderInterface $htmlEncoder,
        private readonly TextConverter $textConverter,
        private readonly OptionReader $optionReader
    ) {
    }

    /**
     * @inheritDoc
     */
    public function ul(iterable $items, array $options = []): string
    {
        $tag = $this->optionReader->tagName($options, 'ul');
        $encode = (bool)$this->optionReader->value($options, 'encode', true);
        $formatter = $this->optionReader->itemFormatter($options);
        $separator = $this->textConverter->toText($this->optionReader->value($options, 'separator', "\n"));
        $itemOptions = $this->optionReader->arrayValue($options, 'itemOptions');
        $options = array_diff_key($options, array_flip(self::LIST_OPTION_KEYS));

        if ($items === []) {
            return $this->tagRenderer->tag($tag, '', $options);
        }

        $results = [];
        foreach ($items as $index => $item) {
            if ($formatter !== null) {
                $results[] = $this->textConverter->toText($formatter($item, $index));
            } else {
                $text = $this->textConverter->toText($item);
                $results[] = $this->tagRenderer->tag(
                    'li',
                    $encode ? $this->htmlEncoder->encode($text) : $text,
                    $itemOptions
                );
            }
        }

        return $this->tagRenderer->tag($tag, $separator . implode($separator, $results) . $separator, $options);
    }

    /**
     * @inheritDoc
     */
    public function ol(iterable $items, array $options = []): string
    {
        $options['tag'] = 'ol';

        return $this->ul($items, $options);
    }
}
