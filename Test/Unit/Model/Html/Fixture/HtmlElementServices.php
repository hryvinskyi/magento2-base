<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Html\Fixture;

use Hryvinskyi\Base\Model\Arrays\ArrayInspector;
use Hryvinskyi\Base\Model\Arrays\ArrayKeyNormalizer;
use Hryvinskyi\Base\Model\Arrays\ArrayPathReader;
use Hryvinskyi\Base\Model\Arrays\ObjectToArrayConverter;
use Hryvinskyi\Base\Model\Html\ChoiceListRenderer;
use Hryvinskyi\Base\Model\Html\FormRenderer;
use Hryvinskyi\Base\Model\Html\InputRenderer;
use Hryvinskyi\Base\Model\Html\LinkRenderer;
use Hryvinskyi\Base\Model\Html\ListRenderer;
use Hryvinskyi\Base\Model\Html\OptionReader;
use Hryvinskyi\Base\Model\Html\ResourceTagRenderer;
use Hryvinskyi\Base\Model\Html\TextConverter;

/**
 * Builds the real HTML element services by hand, on the real core services.
 */
class HtmlElementServices
{
    /**
     * @var HtmlCoreServices
     */
    private HtmlCoreServices $core;

    public function __construct()
    {
        $this->core = new HtmlCoreServices();
    }

    /**
     * Builds the option reader.
     *
     * @return OptionReader
     */
    public function optionReader(): OptionReader
    {
        return new OptionReader(new TextConverter());
    }

    /**
     * Builds the resource tag renderer.
     *
     * @return ResourceTagRenderer
     */
    public function resourceTagRenderer(): ResourceTagRenderer
    {
        return new ResourceTagRenderer($this->core->tagRenderer(), new TextConverter());
    }

    /**
     * Builds the link renderer.
     *
     * @return LinkRenderer
     */
    public function linkRenderer(): LinkRenderer
    {
        return new LinkRenderer($this->core->tagRenderer(), new TextConverter());
    }

    /**
     * Builds the input renderer.
     *
     * @return InputRenderer
     */
    public function inputRenderer(): InputRenderer
    {
        return new InputRenderer($this->core->tagRenderer(), new TextConverter(), $this->optionReader());
    }

    /**
     * Builds the form renderer.
     *
     * @return FormRenderer
     */
    public function formRenderer(): FormRenderer
    {
        return new FormRenderer(
            $this->core->tagRenderer(),
            $this->inputRenderer(),
            $this->core->htmlEncoder(),
            $this->optionReader()
        );
    }

    /**
     * Builds the list renderer.
     *
     * @return ListRenderer
     */
    public function listRenderer(): ListRenderer
    {
        return new ListRenderer(
            $this->core->tagRenderer(),
            $this->core->htmlEncoder(),
            new TextConverter(),
            $this->optionReader()
        );
    }

    /**
     * Builds the choice list renderer.
     *
     * @return ChoiceListRenderer
     */
    public function choiceListRenderer(): ChoiceListRenderer
    {
        return new ChoiceListRenderer(
            $this->core->tagRenderer(),
            $this->inputRenderer(),
            $this->core->htmlEncoder(),
            new ObjectToArrayConverter(new ArrayPathReader(new ArrayKeyNormalizer())),
            new ArrayInspector(),
            new TextConverter(),
            $this->optionReader()
        );
    }
}
