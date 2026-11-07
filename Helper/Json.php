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

/**
 * JSON encoding and decoding that throws an exception on any error.
 */
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
     * `Json::encode((object)['test'])` will be encoded as an object not as an array.
     * This matches the behaviour of `json_encode()`.
     * Defaults to false to avoid any backwards compatibility issues.
     * Enable for single purpose: `Json::$keepObjectType = true;`
     */
    public static $keepObjectType = false;

    /**
     * List of JSON Error messages assigned to constant names for better handling of version differences
     *
     * @var array<string,string>
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
     * Unlike `json_encode()`, objects are first converted to arrays: `JsonSerializable` through
     * `jsonSerialize()`, `Arrayable` through `toArray()`, generators and other traversables through their items,
     * any other object through its public properties.
     *
     * Note that data encoded as JSON must be UTF-8 encoded according to the JSON specification.
     * You must ensure strings passed to this method have proper encoding before passing them.
     *
     * @param mixed $value the data to be encoded.
     * @param int $options the encoding options, see <https://www.php.net/manual/en/function.json-encode.php>.
     *     Default is `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`.
     * @return string the encoding result.
     * @throws InvalidParamException if there is any encoding error.
     */
    public static function encode($value, $options = 320)
    {
        $expressions = [];
        $value = static::processData($value, $expressions, uniqid('', true));
        set_error_handler(function (): bool {
            static::handleJsonError(JSON_ERROR_SYNTAX);

            return true;
        }, E_WARNING);

        if (static::$prettyPrint === true) {
            $options |= JSON_PRETTY_PRINT;
        } elseif (static::$prettyPrint === false) {
            $options &= ~JSON_PRETTY_PRINT;
        }

        $json = json_encode($value, $options);
        restore_error_handler();
        static::handleJsonError(json_last_error());
        if ($json === false) {
            throw new InvalidParamException('Unknown JSON encoding/decoding error.');
        }

        return $expressions === [] ? $json : strtr($json, $expressions);
    }

    /**
     * Encodes the given value into a JSON string HTML-escaping entities so it is safe to be embedded in HTML code.
     *
     * Objects are converted the same way as by encode().
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
        return static::encode(
            $value,
            JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS
        );
    }

    /**
     * Decodes the given JSON string into a PHP data structure.
     *
     * Null and an empty string decode to null; an array is refused with an exception.
     *
     * @param string|array<mixed>|null $json the JSON string to be decoded
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
     * Handles encode() and decode() errors by throwing exceptions with the respective error message.
     *
     * @param int $lastError error code from `json_last_error()`.
     * @return void
     * @throws InvalidParamException if there is any encoding/decoding error.
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
     * Pre-processes the data before sending it to `json_encode()`, converting objects to arrays.
     *
     * @param mixed $data the data to be processed
     * @param array<string,string> $expressions placeholders in the encoded JSON mapped to their replacements
     * @param string $expPrefix a prefix for the placeholders
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
                foreach (iterator_to_array($data) as $name => $value) {
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

                $data = $data instanceof \Traversable ? iterator_to_array($data) : get_object_vars($data);

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
