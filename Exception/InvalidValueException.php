<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Exception;

/**
 * Thrown when a value produced by the caller's own code breaks the contract a service relies on, such as an
 * object's `__debugInfo()` that does not return an array.
 *
 * @api
 */
class InvalidValueException extends \UnexpectedValueException
{
}
