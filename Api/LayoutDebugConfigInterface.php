<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Api;

/**
 * Tells whether the layout debug tools are available to the current visitor.
 *
 * @api
 */
interface LayoutDebugConfigInterface
{
    /**
     * Returns whether the layout debug tools are on for the current visitor.
     *
     * They are on in developer mode only, and there only for a visitor the developer restriction allows
     * (`dev/restrict/allow_ips`; with no list set, every visitor). This is the one rule for Base's own debug tools:
     * each of them asks this question and adds no check of its own.
     *
     * @return bool true when the layout debug is on for this visitor
     */
    public function isEnabledLayoutDebug(): bool;
}
