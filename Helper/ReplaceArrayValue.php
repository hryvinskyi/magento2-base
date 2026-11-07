<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\Base\Helper;

/**
 * Marks a value that `ArrayHelper::merge()` puts in place of the former one instead of merging recursively.
 */
class ReplaceArrayValue
{
    /**
     * @var mixed value used as replacement.
     */
    public $value;

    /**
     * Keeps the value that replaces the former one.
     *
     * @param mixed $value value used as replacement.
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Restores class state after using `var_export()`.
     *
     * @param array<string,mixed> $state the exported properties, `value` among them
     * @return ReplaceArrayValue
     * @throws \InvalidArgumentException when $state property does not contain `value` parameter
     * @see https://www.php.net/manual/en/function.var-export.php
     */
    public static function __set_state($state)
    {
        if (!isset($state['value'])) {
            throw new \InvalidArgumentException(
                'Failed to instantiate class "ReplaceArrayValue". Required parameter "value" is missing'
            );
        }

        return new self($state['value']);
    }
}
