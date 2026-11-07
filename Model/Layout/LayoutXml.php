<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Model\Layout;

/**
 * Holds the current page's merged layout XML for the layout debug output.
 */
class LayoutXml
{
    /**
     * @var string|null
     */
    private ?string $layout = null;

    /**
     * Returns the layout XML recorded for this request.
     *
     * @return string
     * @throws NoSetLayoutException When no layout was recorded yet.
     */
    public function getLayout(): string
    {
        $layout = $this->layout;

        if ($layout === null) {
            throw new NoSetLayoutException();
        }

        return $layout;
    }

    /**
     * Records the layout XML of this request.
     *
     * @param string $layout
     * @return LayoutXml
     */
    public function setLayout(string $layout): LayoutXml
    {
        $this->layout = $layout;

        return $this;
    }
}
