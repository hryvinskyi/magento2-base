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

use Hryvinskyi\Base\Api\Html\InputNameResolverInterface;
use Hryvinskyi\Base\Exception\InvalidParamException;

/**
 * Resolves attribute names with a configurable regular expression and input ids by character replacement.
 */
class InputNameResolver implements InputNameResolverInterface
{
    /**
     * @param string $attributeRegex validates an attribute expression; its second capture group is the name
     */
    public function __construct(
        private readonly string $attributeRegex
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getAttributeName(string $attribute): string
    {
        if (preg_match($this->attributeRegex, $attribute, $matches) === 1 && isset($matches[2])) {
            return $matches[2];
        }

        throw new InvalidParamException('Attribute name must contain word characters only.');
    }

    /**
     * @inheritDoc
     */
    public function getInputIdByName(string $name): string
    {
        return str_replace(
            ['[]', '][', '[', ']', ' ', '.', '--'],
            ['', '-', '-', '', '-', '-', '-'],
            mb_strtolower($name, 'UTF-8')
        );
    }
}
