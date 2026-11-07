<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/ (BSD-3-Clause)
 *
 * Modified for Magento 2
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Model\Json;

use Hryvinskyi\Base\Api\Data\ArrayableInterface;
use Hryvinskyi\Base\Api\Json\JsonEncoderInterface;
use Hryvinskyi\Base\Exception\InvalidParamException;

/**
 * Encodes PHP values as JSON through `json_encode()`, after converting every object in the value to an array.
 *
 * `json_encode()` is used directly rather than a framework serializer because the flags here are part of the
 * output contract: unescaped slashes and Unicode by default, the `JSON_HEX_*` set for HTML.
 */
class JsonEncoder implements JsonEncoderInterface
{
    /**
     * Flags of the HTML-safe encoding: Unicode stays readable, everything that could end an element or an
     * attribute is escaped.
     */
    private const HTML_SAFE_OPTIONS = JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP
        | JSON_HEX_APOS;

    /**
     * @param bool $keepObjectType whether a plain object or a traversable keeps its JSON object type when its keys
     *     are zero-indexed (`(object) ['a']` encodes as `{"0":"a"}` instead of `["a"]`), as `json_encode()` does
     */
    public function __construct(
        private readonly bool $keepObjectType = false
    ) {
    }

    /**
     * @inheritDoc
     */
    public function encode(mixed $value, int $options = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES): string
    {
        $json = json_encode($this->processData($value), $options);
        $this->assertNoJsonError();
        if ($json === false) {
            throw new InvalidParamException('Unknown JSON encoding/decoding error.');
        }

        return $json;
    }

    /**
     * @inheritDoc
     */
    public function htmlEncode(mixed $value): string
    {
        return $this->encode($value, self::HTML_SAFE_OPTIONS);
    }

    /**
     * Throws when the last `json_encode()` call failed.
     *
     * @return void
     * @throws InvalidParamException with PHP's message and the `json_last_error()` code
     */
    private function assertNoJsonError(): void
    {
        $lastError = json_last_error();
        if ($lastError !== JSON_ERROR_NONE) {
            throw new InvalidParamException(json_last_error_msg(), $lastError);
        }
    }

    /**
     * Converts every object in the data to an array, at any depth, leaving other values as they are.
     *
     * @param mixed $data the data to process
     * @return mixed the processed data
     */
    private function processData(mixed $data): mixed
    {
        if (is_object($data)) {
            return $this->processObject($data);
        }
        if (is_array($data)) {
            return $this->processArray($data);
        }

        return $data;
    }

    /**
     * Converts an object to the array, or the object of public properties, that `json_encode()` is given.
     *
     * An object that is converted through its properties or items stays an object when it has none, so it
     * encodes as `{}`, and when the object type is kept.
     *
     * @param object $data the object to convert
     * @return mixed the processed form of the object
     */
    private function processObject(object $data): mixed
    {
        if ($data instanceof \JsonSerializable) {
            return $this->processData($data->jsonSerialize());
        }
        if ($data instanceof \DateTimeInterface) {
            return $this->processData((array) $data);
        }
        if ($data instanceof ArrayableInterface) {
            return $this->processArray($data->toArray());
        }
        if ($data instanceof \Generator) {
            $items = [];
            foreach (iterator_to_array($data) as $name => $value) {
                $items[$name] = $this->processData($value);
            }

            return $this->processArray($items);
        }
        if ($data instanceof \SimpleXMLElement) {
            // an element without children stays an object, so it encodes as {} rather than []
            return (object) $this->processArray((array) $data);
        }

        $items = $data instanceof \Traversable ? iterator_to_array($data) : get_object_vars($data);
        $processed = $this->processArray($items);

        return $this->keepObjectType || $items === [] ? (object) $processed : $processed;
    }

    /**
     * Processes the arrays and objects among an array's values.
     *
     * @param array<mixed> $data the array to process
     * @return array<mixed> the array with its nested arrays and objects processed
     */
    private function processArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $data[$key] = $this->processData($value);
            }
        }

        return $data;
    }
}
