<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Model\Menu;

use Hryvinskyi\Base\Api\Menu\MenuItemFactoryInterface;
use Hryvinskyi\Base\Api\Menu\MenuItemInterface;
use Magento\Backend\Model\UrlInterface;

/**
 * Factory for creating menu items from array configuration
 *
 * Every known key is read with its type checked: a missing key takes its default, a key holding a value of
 * the wrong type makes the whole item invalid, so a typo in layout XML never renders a half-built item.
 */
class MenuItemFactory implements MenuItemFactoryInterface
{
    /**
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        private readonly UrlInterface $urlBuilder
    ) {
    }

    /**
     * @inheritDoc
     */
    public function create(array $data): MenuItemInterface
    {
        return new MenuItem(
            urlBuilder: $this->urlBuilder,
            label: $this->readString($data, 'label'),
            route: $this->readString($data, 'route'),
            routeParams: $this->readRouteParams($data),
            sortOrder: $this->readSortOrder($data),
            isActive: $this->readFlag($data, 'is_active', true),
            cssClass: $this->readClass($data),
            icon: $this->readString($data, 'icon'),
            resource: $this->readResource($data)
        );
    }

    /**
     * Read an optional string value; an absent or null value yields an empty string
     *
     * @param array<string,mixed> $data
     * @param string $key
     * @return string
     * @throws \InvalidArgumentException
     */
    private function readString(array $data, string $key): string
    {
        $value = $data[$key] ?? '';

        if (is_string($value)) {
            return $value;
        }

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        throw $this->invalid($key, 'a string', $value);
    }

    /**
     * Read the route parameters: a map of parameter name to value
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     * @throws \InvalidArgumentException
     */
    private function readRouteParams(array $data): array
    {
        $value = $data['route_params'] ?? [];

        if (!is_array($value)) {
            throw $this->invalid('route_params', 'an array', $value);
        }

        $params = [];
        foreach ($value as $name => $paramValue) {
            if (!is_string($name)) {
                throw new \InvalidArgumentException(
                    sprintf('Menu item key "route_params" must use parameter names as keys, %d given.', $name)
                );
            }
            $params[$name] = $paramValue;
        }

        return $params;
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

        throw $this->invalid('sort_order', 'a number', $value);
    }

    /**
     * Read a boolean flag; accepts booleans and the usual boolean spellings ("1", "0", "true", "false", …)
     *
     * @param array<string,mixed> $data
     * @param string $key
     * @param bool $default
     * @return bool
     * @throws \InvalidArgumentException
     */
    private function readFlag(array $data, string $key, bool $default): bool
    {
        $value = $data[$key] ?? $default;
        $flag = $this->toFlag($value);

        if ($flag === null) {
            throw $this->invalid($key, 'a boolean', $value);
        }

        return $flag;
    }

    /**
     * Read the CSS class: either a class string or a map of class name to an "enabled" flag
     *
     * @param array<string,mixed> $data
     * @return string
     * @throws \InvalidArgumentException
     */
    private function readClass(array $data): string
    {
        $value = $data['class'] ?? '';

        if (is_string($value)) {
            return $value;
        }

        if (!is_array($value)) {
            throw $this->invalid('class', 'a string or a map of class name to flag', $value);
        }

        $enabledClasses = [];
        foreach ($value as $className => $isEnabled) {
            $flag = $this->toFlag($isEnabled);
            if (!is_string($className) || $className === '' || $flag === null) {
                throw new \InvalidArgumentException(
                    'Menu item key "class" must map non-empty class names to boolean flags.'
                );
            }
            if ($flag) {
                $enabledClasses[] = $className;
            }
        }

        return implode(' ', $enabledClasses);
    }

    /**
     * Read the ACL resource id; absent or null means the item has no permission check of its own
     *
     * @param array<string,mixed> $data
     * @return string|null
     * @throws \InvalidArgumentException
     */
    private function readResource(array $data): ?string
    {
        $value = $data['resource'] ?? null;

        if ($value === null) {
            return null;
        }

        if (!is_string($value) || trim($value) === '') {
            throw $this->invalid('resource', 'a non-empty ACL resource id', $value);
        }

        return trim($value);
    }

    /**
     * Convert a boolean-like value to a boolean, or null when it is not boolean-like
     *
     * @param mixed $value
     * @return bool|null
     */
    private function toFlag(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_string($value)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        return null;
    }

    /**
     * Build the exception for a key holding a value of the wrong type
     *
     * @param string $key
     * @param string $expected
     * @param mixed $value
     * @return \InvalidArgumentException
     */
    private function invalid(string $key, string $expected, mixed $value): \InvalidArgumentException
    {
        return new \InvalidArgumentException(
            sprintf('Menu item key "%s" must be %s, %s given.', $key, $expected, get_debug_type($value))
        );
    }
}
