<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Html\Fixture;

use Hryvinskyi\Base\Model\Html\AttributeRenderer;
use Hryvinskyi\Base\Model\Html\CssOptions;
use Hryvinskyi\Base\Model\Html\HtmlEncoder;
use Hryvinskyi\Base\Model\Html\InputNameResolver;
use Hryvinskyi\Base\Model\Html\TagRenderer;
use Hryvinskyi\Base\Model\Html\TextConverter;
use Hryvinskyi\Base\Model\Json\JsonEncoder;
use Magento\Framework\Escaper;
use Magento\Framework\ZendEscaper;

/**
 * Builds the real HTML core services by hand. The services take these lists as required constructor arguments, so a
 * test either passes its own or gets the ones below.
 */
class HtmlCoreServices
{
    /**
     * The preferred order of attributes in a tag.
     */
    public const ATTRIBUTE_ORDER = [
        'type',
        'id',
        'class',
        'name',
        'value',

        'href',
        'src',
        'srcset',
        'form',
        'action',
        'method',

        'selected',
        'checked',
        'readonly',
        'disabled',
        'multiple',

        'size',
        'maxlength',
        'width',
        'height',
        'rows',
        'cols',

        'alt',
        'title',
        'rel',
        'media',
    ];

    /**
     * Attributes whose array value is expanded into one `name-key` attribute per element.
     */
    public const DATA_ATTRIBUTES = ['aria', 'data', 'data-ng', 'ng'];

    /**
     * Void elements of HTML.
     */
    public const VOID_ELEMENTS = [
        'area' => true,
        'base' => true,
        'br' => true,
        'col' => true,
        'command' => true,
        'embed' => true,
        'hr' => true,
        'img' => true,
        'input' => true,
        'keygen' => true,
        'link' => true,
        'meta' => true,
        'param' => true,
        'source' => true,
        'track' => true,
        'wbr' => true,
    ];

    /**
     * An optional prefix ending in `]`, the attribute name, and an optional suffix starting with `[`.
     */
    public const ATTRIBUTE_REGEX = '/(^|.*\])([\w\.\+]+)(\[.*|$)/u';

    /**
     * Builds the HTML encoder on the framework's two escapers.
     *
     * @return HtmlEncoder
     */
    public function htmlEncoder(): HtmlEncoder
    {
        return new HtmlEncoder(new ZendEscaper(), new Escaper());
    }

    /**
     * Builds the CSS options service.
     *
     * @param bool $normalizeClassAttribute whether merging classes removes duplicates
     * @return CssOptions
     */
    public function cssOptions(bool $normalizeClassAttribute = true): CssOptions
    {
        return new CssOptions(new TextConverter(), $normalizeClassAttribute);
    }

    /**
     * Builds the attribute renderer.
     *
     * @param array<string> $attributeOrder attribute names rendered first
     * @param array<string> $dataAttributes attribute names expanded into `name-key` attributes
     * @param bool $normalizeClassAttribute whether duplicate classes are removed, for rendering and merging alike
     * @return AttributeRenderer
     */
    public function attributeRenderer(
        array $attributeOrder = self::ATTRIBUTE_ORDER,
        array $dataAttributes = self::DATA_ATTRIBUTES,
        bool $normalizeClassAttribute = true
    ): AttributeRenderer {
        return new AttributeRenderer(
            $this->htmlEncoder(),
            new JsonEncoder(),
            $this->cssOptions($normalizeClassAttribute),
            new TextConverter(),
            $attributeOrder,
            $dataAttributes,
            $normalizeClassAttribute
        );
    }

    /**
     * Builds the tag renderer on an attribute renderer with the lists above.
     *
     * @param array<string, bool> $voidElements element name => whether it is void
     * @return TagRenderer
     */
    public function tagRenderer(array $voidElements = self::VOID_ELEMENTS): TagRenderer
    {
        return new TagRenderer($this->attributeRenderer(), $voidElements);
    }

    /**
     * Builds the input name resolver.
     *
     * @param string $attributeRegex validates an attribute expression; its second capture group is the name
     * @return InputNameResolver
     */
    public function inputNameResolver(string $attributeRegex = self::ATTRIBUTE_REGEX): InputNameResolver
    {
        return new InputNameResolver($attributeRegex);
    }
}
