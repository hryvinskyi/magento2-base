<?php
/**
 * Copyright (c) 2024. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Plugin;

use Hryvinskyi\Base\Model\ViewModelRegistry;
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\TemplateEngine\Php;

class AddViewModelsRegistryVariable
{
    /**
     * @var ViewModelRegistry
     */
    private $viewModelRegistry;

    public function __construct(
        ViewModelRegistry $viewModelRegistry
    ) {
        $this->viewModelRegistry = $viewModelRegistry;
    }

    /**
     * Adds the viewModelRegistry to all template files as $viewModels
     * 
     * @param Php $subject
     * @param BlockInterface $block
     * @param $filename
     * @param mixed[] $dictionary
     * @return mixed[]
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeRender(Php $subject, BlockInterface $block, $filename, array $dictionary = [])
    {
        $dictionary['viewModels'] = $this->viewModelRegistry;

        return [$block, $filename, $dictionary];
    }
}
