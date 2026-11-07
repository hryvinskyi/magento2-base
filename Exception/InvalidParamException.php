<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Exception;

/**
 * Thrown when a value handed to one of the module's services cannot be used: malformed JSON, a value with no text
 * form, a callback that is not callable, a key of a type an array cannot hold.
 *
 * @api
 */
class InvalidParamException extends \InvalidArgumentException
{
}
