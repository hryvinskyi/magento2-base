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

use Hryvinskyi\Base\Api\Html\ResourceTagRendererInterface;
use Hryvinskyi\Base\Api\Html\TagRendererInterface;

/**
 * Renders style and script tags, inline or referring to a file, optionally inside a conditional comment.
 */
class ResourceTagRenderer implements ResourceTagRendererInterface
{
    /**
     * @param TagRendererInterface $tagRenderer renders the tags
     * @param TextConverter $textConverter turns the `condition` option into text
     */
    public function __construct(
        private readonly TagRendererInterface $tagRenderer,
        private readonly TextConverter $textConverter
    ) {
    }

    /**
     * @inheritDoc
     */
    public function style(string $content, array $options = []): string
    {
        return $this->tagRenderer->tag('style', $content, $options);
    }

    /**
     * @inheritDoc
     */
    public function script(string $content, array $options = []): string
    {
        return $this->tagRenderer->tag('script', $content, $options);
    }

    /**
     * @inheritDoc
     */
    public function cssFile(string $url, array $options = []): string
    {
        if (!isset($options['rel'])) {
            $options['rel'] = 'stylesheet';
        }
        $options['href'] = $url;

        if (isset($options['condition'])) {
            $condition = $this->textConverter->toText($options['condition']);
            unset($options['condition']);

            return $this->wrapIntoCondition($this->tagRenderer->tag('link', '', $options), $condition);
        }

        if (isset($options['noscript']) && $options['noscript'] === true) {
            unset($options['noscript']);

            return '<noscript>' . $this->tagRenderer->tag('link', '', $options) . '</noscript>';
        }

        return $this->tagRenderer->tag('link', '', $options);
    }

    /**
     * @inheritDoc
     */
    public function jsFile(string $url, array $options = []): string
    {
        $options['src'] = $url;

        if (isset($options['condition'])) {
            $condition = $this->textConverter->toText($options['condition']);
            unset($options['condition']);

            return $this->wrapIntoCondition($this->tagRenderer->tag('script', '', $options), $condition);
        }

        return $this->tagRenderer->tag('script', '', $options);
    }

    /**
     * Encloses content in a conditional comment.
     *
     * A condition containing `!IE` keeps the content visible to every browser other than IE.
     *
     * @param string $content the HTML to enclose
     * @param string $condition the condition, such as `lt IE 9`
     * @return string the enclosed HTML
     */
    private function wrapIntoCondition(string $content, string $condition): string
    {
        if (strpos($condition, '!IE') !== false) {
            return '<!--[if ' . $condition . ']><!-->' . "\n" . $content . "\n" . '<!--<![endif]-->';
        }

        return '<!--[if ' . $condition . ']>' . "\n" . $content . "\n" . '<![endif]-->';
    }
}
