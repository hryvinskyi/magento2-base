<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/ (BSD-3-Clause)
 *
 * Modified for Magento 2
 * @copyright Copyright (c) 2019 Volodymyr Hryvinskyi. All rights reserved.
 * @author Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 * @github <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\Base\Helper;


class Json
{
    /**
     * @var bool|null Enables human-readable output a.k.a. Pretty Print.
     *  This can be useful for debugging during development but is not recommended in a production environment!
     *  In case `prettyPrint` is `null` (default) the `options` passed to `encode` functions will not be changed.
     */
    public static $prettyPrint;

    /**
     * @var bool Avoids objects with zero-indexed keys to be encoded as array
     * `Json::encode((object)['test'])` will be encoded as an object not as an array. This matches the behaviour of `json_encode()`.
     * Defaults too false to avoid any backwards compatibility issues.
     * Enable for single purpose: `Json::$keepObjectType = true;`
     * @see JsonResponseFormatter documentation to enable for all JSON responses
     */
    public static $keepObjectType = false;

    /**
     * List of JSON Error messages assigned to constant names for better handling of version differences
     * @var array
     */
    public static $jsonErrorMessages = [
        'JSON_ERROR_SYNTAX' => 'Syntax error',
        'JSON_ERROR_UNSUPPORTED_TYPE' => 'Type is not supported',
        'JSON_ERROR_DEPTH' => 'The maximum stack depth has been exceeded',
        'JSON_ERROR_STATE_MISMATCH' => 'Invalid or malformed JSON',
        'JSON_ERROR_CTRL_CHAR' => 'Control character error, possibly incorrectly encoded',
        'JSON_ERROR_UTF8' => 'Malformed UTF-8 characters, possibly incorrectly encoded',
    ];

    /**
     * Encodes the given value into a JSON string.
     *
     * The method enhances `json_encode()` by supporting JavaScript expressions.
     * In particular, the method will not encode a JavaScript expression that is
     * represented in terms of a [[JsExpression]] object.
     *
     * Note that data encoded as JSON must be UTF-8 encoded according to the JSON specification.
     * You must ensure strings passed to this method have proper encoding before passing them.
     *
     * @param mixed $value the data to be encoded.
     * @param int $options the encoding options. For more details please refer to
     * <https://www.php.net/manual/en/function.json-encode.php>. Default is `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`.
     * @return string the encoding result.
     * @throws InvalidParamException if there is any encoding error.
     */
    public static function encode($value, $options = 320)
    {
        $expressions = [];
        $value = static::processData($value, $expressions, uniqid('', true));
        set_error_handler(function () {
            static::handleJsonError(JSON_ERROR_SYNTAX);
        }, E_WARNING);

        if (static::$prettyPrint === true) {
            $options |= JSON_PRETTY_PRINT;
        } elseif (static::$prettyPrint === false) {
            $options &= ~JSON_PRETTY_PRINT;
        }

        $json = json_encode($value, $options);
        restore_error_handler();
        static::handleJsonError(json_last_error());
        return $expressions === [] ? $json : strtr($json, $expressions);
    }

    /**
     * Encodes the given value into a JSON string HTML-escaping entities so it is safe to be embedded in HTML code.
     *
     * The method enhances `json_encode()` by supporting JavaScript expressions.
     * In particular, the method will not encode a JavaScript expression that is
     * represented in terms of a [[JsExpression]] object.
     *
     * Note that data encoded as JSON must be UTF-8 encoded according to the JSON specification.
     * You must ensure strings passed to this method have proper encoding before passing them.
     *
     * @param mixed $value the data to be encoded
     * @return string the encoding result
     * @throws InvalidParamException if there is any encoding error
     */
    public static function htmlEncode($value)
    {
        return static::encode($value, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
    }

    /**
     * Decodes the given JSON string into a PHP data structure.
     * @param string $json the JSON string to be decoded
     * @param bool $asArray whether to return objects in terms of associative arrays.
     * @return mixed the PHP data
     * @throws InvalidParamException if there is any decoding error
     */
    public static function decode($json, $asArray = true)
    {
        if (is_array($json)) {
            throw new InvalidParamException('Invalid JSON data.');
        } elseif ($json === null || $json === '') {
            return null;
        }
        $decode = json_decode((string) $json, $asArray);
        static::handleJsonError(json_last_error());
        return $decode;
    }

    /**
     * Handles [[encode()]] and [[decode()]] errors by throwing exceptions with the respective error message.
     *
     * @param int $lastError error code from [json_last_error()](https://www.php.net/manual/en/function.json-last-error.php).
     * @throws InvalidParamException if there is any encoding/decoding error.
     * @since 2.0.6
     */
    protected static function handleJsonError($lastError)
    {
        if ($lastError === JSON_ERROR_NONE) {
            return;
        }

        if (PHP_VERSION_ID >= 50500) {
            throw new InvalidParamException(json_last_error_msg(), $lastError);
        }

        foreach (static::$jsonErrorMessages as $const => $message) {
            if (defined($const) && constant($const) === $lastError) {
                throw new InvalidParamException($message, $lastError);
            }
        }

        throw new InvalidParamException('Unknown JSON encoding/decoding error.');
    }

    /**
     * Pre-processes the data before sending it to `json_encode()`.
     * @param mixed $data the data to be processed
     * @param array $expressions collection of JavaScript expressions
     * @param string $expPrefix a prefix internally used to handle JS expressions
     * @return mixed the processed data
     */
    protected static function processData($data, &$expressions, $expPrefix)
    {
        $revertToObject = false;

        if (is_object($data)) {
            if ($data instanceof \JsonSerializable) {
                return static::processData($data->jsonSerialize(), $expressions, $expPrefix);
            }

            if ($data instanceof \DateTimeInterface) {
                return static::processData((array)$data, $expressions, $expPrefix);
            }

            if ($data instanceof Arrayable) {
                $data = $data->toArray();
            } elseif ($data instanceof \Generator) {
                $_data = [];
                foreach ($data as $name => $value) {
                    $_data[$name] = static::processData($value, $expressions, $expPrefix);
                }
                $data = $_data;
            } elseif ($data instanceof \SimpleXMLElement) {
                $data = (array) $data;

                // Avoid empty elements to be returned as array.
                // Not breaking BC because empty array was always cast to stdClass before.
                $revertToObject = true;
            } else {
                /*
                 * $data type is changed to array here and its elements will be processed further
                 * We must cast $data back to object later to keep intended dictionary type in JSON.
                 * Revert is only done when keepObjectType flag is provided to avoid breaking BC
                 */
                $revertToObject = static::$keepObjectType;

                $result = [];
                foreach ($data as $name => $value) {
                    $result[$name] = $value;
                }
                $data = $result;

                // Avoid empty objects to be returned as array (would break BC without keepObjectType flag)
                if ($data === []) {
                    $revertToObject = true;
                }
            }
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $data[$key] = static::processData($value, $expressions, $expPrefix);
                }
            }
        }

        return $revertToObject ? (object) $data : $data;
    }
}