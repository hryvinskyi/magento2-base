<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Model\Menu;

use Hryvinskyi\Base\Api\Menu\MenuGroupFactoryInterface;
use Hryvinskyi\Base\Api\Menu\MenuGroupInterface;
use Hryvinskyi\Base\Api\Menu\MenuGroupInterfaceFactory;

/**
 * Reads a menu group from layout configuration and creates it through the generated group factory
 *
 * Every key is read with its type checked before anything is created, so a typo in layout XML never renders a
 * half-built group.
 */
class MenuGroupConfigParser implements MenuGroupFactoryInterface
{
    /**
     * @param MenuGroupInterfaceFactory $groupFactory
     */
    public function __construct(
        private readonly MenuGroupInterfaceFactory $groupFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function create(string $code, array $data): MenuGroupInterface
    {
        return $this->groupFactory->create([
            'code' => $this->readCode($code),
            'label' => $this->readLabel($data),
            'sortOrder' => $this->readSortOrder($data),
        ]);
    }

    /**
     * Read the group code: letters, digits, "_" and "-" only, without surrounding whitespace
     *
     * Only those characters are allowed so that two codes never give the same heading id.
     *
     * @param string $code
     * @return string
     * @throws \InvalidArgumentException
     */
    private function readCode(string $code): string
    {
        $code = trim($code);

        if ($code === '') {
            throw new \InvalidArgumentException('Menu group "code" must not be empty.');
        }

        if (preg_match('/^[A-Za-z0-9_-]+$/D', $code) !== 1) {
            throw new \InvalidArgumentException(
                sprintf('Menu group "code" may hold only letters, digits, "_" and "-", "%s" given.', $code)
            );
        }

        return $code;
    }

    /**
     * Read the required label: a non-empty string or a translatable phrase
     *
     * @param array<string,mixed> $data
     * @return string
     * @throws \InvalidArgumentException
     */
    private function readLabel(array $data): string
    {
        $value = $data['label'] ?? null;
        $label = match (true) {
            is_string($value) => $value,
            $value instanceof \Stringable => (string) $value,
            default => null,
        };

        if ($label === null || trim($label) === '') {
            throw new \InvalidArgumentException(sprintf(
                'Menu group key "label" must be a non-empty string, %s given.',
                get_debug_type($value)
            ));
        }

        return $label;
    }

    /**
     * Read the sort order; layout XML "number" arguments arrive as numeric strings
     *
     * @param array<string,mixed> $data
     * @return int
     * @throws \InvalidArgumentException
     */
    private function readSortOrder(array $data): int
    {
        $value = $data['sort_order'] ?? 0;

        if (is_int($value)) {
            return $value;
        }

        if (is_float($value) || (is_string($value) && is_numeric($value))) {
            return (int) $value;
        }

        throw new \InvalidArgumentException(sprintf(
            'Menu group key "sort_order" must be a number, %s given.',
            get_debug_type($value)
        ));
    }
}
