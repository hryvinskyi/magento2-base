<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\Base\Test\Unit\Helpers;

use Hryvinskyi\Base\Helper\InvalidParamException;
use Hryvinskyi\Base\Helper\Json;
use Hryvinskyi\Base\Test\Unit\Helpers\Fixture\JsonObjectWithHiddenState;
use Hryvinskyi\Base\Test\Unit\Helpers\Fixture\JsonTraversable;
use PHPUnit\Framework\TestCase;

class JsonTest extends TestCase
{
    /**
     * Restores the static encoding switches a test may change.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Json::$keepObjectType = false;
        Json::$prettyPrint = null;
    }

    /**
     * A plain object encodes as its public properties only.
     *
     * @return void
     */
    public function testEncodePlainObjectAsItsPublicProperties(): void
    {
        $this->assertSame('{"visible":"shown"}', Json::encode(new JsonObjectWithHiddenState()));
    }

    /**
     * A traversable object encodes as its items, not its properties.
     *
     * @return void
     */
    public function testEncodeTraversableAsItsItems(): void
    {
        $this->assertSame('{"item":"value","nested":{"a":1}}', Json::encode(new JsonTraversable()));
    }

    /**
     * A generator encodes as the items it yields, objects among them converted too.
     *
     * @return void
     */
    public function testEncodeGeneratorAsItsItems(): void
    {
        $generator = (static function (): \Generator {
            yield 'a' => 1;
            yield 'b' => (object) ['c' => 2];
        })();

        $this->assertSame('{"a":1,"b":{"c":2}}', Json::encode($generator));
    }

    /**
     * An object without properties encodes as an empty JSON object.
     *
     * @return void
     */
    public function testEncodeEmptyObjectAsJsonObject(): void
    {
        $this->assertSame('{}', Json::encode(new \stdClass()));
    }

    /**
     * An object with zero-indexed keys encodes as a list unless the object type is kept.
     *
     * @return void
     */
    public function testEncodeKeepsObjectTypeOnlyWhenAsked(): void
    {
        $this->assertSame('["test"]', Json::encode((object) ['test']));

        Json::$keepObjectType = true;

        $this->assertSame('{"0":"test"}', Json::encode((object) ['test']));
    }

    /**
     * Pretty print can be forced on through the static switch.
     *
     * @return void
     */
    public function testEncodePrettyPrint(): void
    {
        Json::$prettyPrint = true;

        $this->assertSame("{\n    \"a\": 1\n}", Json::encode(['a' => 1]));
    }

    /**
     * The HTML-safe encoding escapes tags, quotes and ampersands.
     *
     * @return void
     */
    public function testHtmlEncodeEscapesHtml(): void
    {
        $this->assertSame(
            '"\\u003Ca href=\\u0022x\\u0022\\u003E\\u0026\\u003C\\/a\\u003E"',
            Json::htmlEncode('<a href="x">&</a>')
        );
    }

    /**
     * A value json_encode() cannot encode is reported with an exception.
     *
     * @return void
     */
    public function testEncodeInvalidUtf8Throws(): void
    {
        $this->expectException(InvalidParamException::class);

        Json::encode("\xB1\x31");
    }

    /**
     * JSON decodes to arrays by default.
     *
     * @return void
     */
    public function testDecodeAsArray(): void
    {
        $this->assertSame(['a' => 1], Json::decode('{"a":1}'));
    }

    /**
     * Null and an empty string decode to null.
     *
     * @return void
     */
    public function testDecodeEmptyInputAsNull(): void
    {
        $this->assertNull(Json::decode(null));
        $this->assertNull(Json::decode(''));
    }

    /**
     * An array is not JSON and is refused.
     *
     * @return void
     */
    public function testDecodeArrayThrows(): void
    {
        $this->expectException(InvalidParamException::class);

        Json::decode([]);
    }

    /**
     * Malformed JSON is reported with an exception.
     *
     * @return void
     */
    public function testDecodeMalformedJsonThrows(): void
    {
        $this->expectException(InvalidParamException::class);

        Json::decode('{"a":');
    }
}
