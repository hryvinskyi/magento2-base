<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\Base\Helper;


class ReplaceArrayValue
{
    /**
     * @var mixed value used as replacement.
     */
    public $value;


    /**
     * Constructor.
     * @param mixed $value value used as replacement.
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Restores class state after using `var_export()`.
     *
     * @param array $state
     * @return ReplaceArrayValue
     * @throws \InvalidArgumentException when $state property does not contain `value` parameter
     * @see https://www.php.net/manual/en/function.var-export.php
     * @since 2.0.16
     */
    public static function __set_state($state)
    {
        if (!isset($state['value'])) {
            throw new \InvalidArgumentException('Failed to instantiate class "ReplaceArrayValue". Required parameter "value" is missing');
        }

        return new self($state['value']);
    }
}
