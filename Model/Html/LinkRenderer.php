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

use Hryvinskyi\Base\Api\Html\LinkRendererInterface;
use Hryvinskyi\Base\Api\Html\TagRendererInterface;

/**
 * Renders hyperlinks and images on top of the tag renderer.
 */
class LinkRenderer implements LinkRendererInterface
{
    /**
     * @param TagRendererInterface $tagRenderer renders the tags
     * @param TextConverter $textConverter turns the URLs of a `srcset` array into text
     */
    public function __construct(
        private readonly TagRendererInterface $tagRenderer,
        private readonly TextConverter $textConverter
    ) {
    }

    /**
     * @inheritDoc
     */
    public function a(string $text, ?string $url = null, array $options = []): string
    {
        if ($url !== null) {
            $options['href'] = $url;
        }

        return $this->tagRenderer->tag('a', $text, $options);
    }

    /**
     * @inheritDoc
     */
    public function mailto(string $text, ?string $email = null, array $options = []): string
    {
        $options['href'] = 'mailto:' . ($email ?? $text);

        return $this->tagRenderer->tag('a', $text, $options);
    }

    /**
     * @inheritDoc
     */
    public function img(string $src, array $options = []): string
    {
        $options['src'] = $src;

        if (isset($options['srcset']) && is_array($options['srcset'])) {
            $srcset = [];
            foreach ($options['srcset'] as $descriptor => $url) {
                $srcset[] = $this->textConverter->toText($url) . ' ' . $descriptor;
            }
            $options['srcset'] = implode(',', $srcset);
        }

        if (!isset($options['alt'])) {
            $options['alt'] = '';
        }

        return $this->tagRenderer->tag('img', '', $options);
    }
}
