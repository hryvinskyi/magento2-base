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

namespace Hryvinskyi\Base\Api\Html;

use Hryvinskyi\Base\Exception\InvalidParamException;

/**
 * Derives names and ids of form inputs from attribute expressions and input names.
 *
 * @api
 */
interface InputNameResolverInterface
{
    /**
     * Returns the attribute name inside an attribute expression, without its array-index prefix and suffix.
     *
     * `[0]content` gives `content`, `dates[0]` gives `dates`, `[0]dates[0]` gives `dates`; a name without prefix
     * or suffix is returned unchanged.
     *
     * @param string $attribute the attribute name or expression
     * @return string the attribute name
     * @throws InvalidParamException when the name contains characters other than word characters, `.` and `+`
     */
    public function getAttributeName(string $attribute): string;

    /**
     * Converts an input name to an element id: `Post[content]` gives `post-content`.
     *
     * The name is lower-cased; brackets, spaces and dots become dashes, and `[]` is dropped.
     *
     * @param string $name the input name
     * @return string the element id
     */
    public function getInputIdByName(string $name): string;
}
