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

use Hryvinskyi\Base\Api\Html\AttributeRendererInterface;
use Hryvinskyi\Base\Api\Html\TagRendererInterface;

/**
 * Renders tags with a configurable list of void elements, which are rendered self-closed: `<br />`.
 */
class TagRenderer implements TagRendererInterface
{
    /**
     * @param AttributeRendererInterface $attributeRenderer renders the attributes of a start tag
     * @param array<string,bool> $voidElements lower-case element name => whether it is void; an element set to
     *     `false`, or not listed, is rendered with an end tag
     */
    public function __construct(
        private readonly AttributeRendererInterface $attributeRenderer,
        private readonly array $voidElements
    ) {
    }

    /**
     * @inheritDoc
     */
    public function tag(?string $name, string $content = '', array $options = []): string
    {
        if ($name === null) {
            return $content;
        }

        $html = '<' . $name . $this->attributeRenderer->render($options);

        return $this->isVoid($name) ? $html . ' />' : $html . '>' . $content . '</' . $name . '>';
    }

    /**
     * @inheritDoc
     */
    public function beginTag(?string $name, array $options = []): string
    {
        if ($name === null) {
            return '';
        }

        return '<' . $name . $this->attributeRenderer->render($options) . '>';
    }

    /**
     * @inheritDoc
     */
    public function endTag(?string $name): string
    {
        if ($name === null) {
            return '';
        }

        return '</' . $name . '>';
    }

    /**
     * Tells whether the element is rendered self-closed, comparing its name case-insensitively.
     *
     * @param string $name the tag name
     * @return bool whether the element is void
     */
    private function isVoid(string $name): bool
    {
        return ($this->voidElements[strtolower($name)] ?? false) === true;
    }
}
