<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Model\Layout;

/**
 * Class Xml
 */
class LayoutXml
{
    /**
     * @var string
     */
    private $layout;

    /**
     * @return string
     * @throws NoSetLayoutException
     */
    public function getLayout(): string
    {
        $layout = $this->layout;

        if($layout === null) {
            throw new NoSetLayoutException();
        }

        return $layout;
    }

    /**
     * @param string $layout
     *
     * @return LayoutXml
     */
    public function setLayout(string $layout): LayoutXml
    {
        $this->layout = $layout;

        return $this;
    }
}