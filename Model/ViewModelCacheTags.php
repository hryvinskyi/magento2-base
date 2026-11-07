<?php
/**
 * Copyright (c) 2024. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Record cache tags from the view models created by the view model registry, so they can be passed to the page cache.
 */
class ViewModelCacheTags
{
    /**
     * @var IdentityInterface[]
     */
    private $viewModels = [];

    /**
     * Returns the cache tags of every view model collected so far.
     *
     * @return string[]
     */
    public function get(): array
    {
        // wait with collecting the identities until they are needed to catch all of them
        return array_unique(array_merge([], ...array_map(function (IdentityInterface $viewModel): array {
            return $viewModel->getIdentities();
        }, $this->viewModels)));
    }

    /**
     * Remembers the view model when it has cache tags.
     *
     * @param ArgumentInterface $viewModel
     * @return void
     */
    public function collectFrom(ArgumentInterface $viewModel): void
    {
        if ($viewModel instanceof IdentityInterface) {
            $this->viewModels[] = $viewModel;
        }
    }
}
