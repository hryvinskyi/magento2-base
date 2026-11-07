<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Json;

use Hryvinskyi\Base\Exception\InvalidParamException;
use Hryvinskyi\Base\Model\Json\JsonEncoder;
use Hryvinskyi\Base\Test\Unit\Model\Json\Fixture\JsonArrayable;
use Hryvinskyi\Base\Test\Unit\Model\Json\Fixture\JsonObjectWithHiddenState;
use Hryvinskyi\Base\Test\Unit\Model\Json\Fixture\JsonTraversable;
use PHPUnit\Framework\TestCase;

class JsonEncoderTest extends TestCase
{
    /**
     * The encoder under test, with the default object handling.
     *
     * @var JsonEncoder
     */
    private JsonEncoder $encoder;

    /**
     * Builds the encoder.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->encoder = new JsonEncoder();
    }

    /**
     * A plain object encodes as its public properties only.
     *
     * @return void
     */
    public function testEncodePlainObjectAsItsPublicProperties(): void
    {
        $this->assertSame('{"visible":"shown"}', $this->encoder->encode(new JsonObjectWithHiddenState()));
    }

    /**
     * A traversable object encodes as its items, not its properties.
     *
     * @return void
     */
    public function testEncodeTraversableAsItsItems(): void
    {
        $this->assertSame('{"item":"value","nested":{"a":1}}', $this->encoder->encode(new JsonTraversable()));
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

        $this->assertSame('{"a":1,"b":{"c":2}}', $this->encoder->encode($generator));
    }

    /**
     * An arrayable object encodes as its array, objects inside it converted too.
     *
     * @return void
     */
    public function testEncodeArrayableAsItsArray(): void
    {
        $this->assertSame('{"id":7,"nested":{"a":1}}', $this->encoder->encode(new JsonArrayable()));
    }

    /**
     * A JsonSerializable object encodes as what jsonSerialize() returns.
     *
     * @return void
     */
    public function testEncodeJsonSerializableAsItsSerialization(): void
    {
        $value = new class implements \JsonSerializable {
            /**
             * Returns the data to encode.
             *
             * @return array<string, mixed>
             */
            public function jsonSerialize(): array
            {
                return ['serialized' => (object) ['x' => true]];
            }
        };

        $this->assertSame('{"serialized":{"x":true}}', $this->encoder->encode($value));
    }

    /**
     * An object without properties encodes as an empty JSON object.
     *
     * @return void
     */
    public function testEncodeEmptyObjectAsJsonObject(): void
    {
        $this->assertSame('{}', $this->encoder->encode(new \stdClass()));
    }

    /**
     * An object with zero-indexed keys encodes as a list unless the encoder keeps the object type.
     *
     * @return void
     */
    public function testEncodeKeepsObjectTypeOnlyWhenAsked(): void
    {
        $this->assertSame('["test"]', $this->encoder->encode((object) ['test']));

        $this->assertSame('{"0":"test"}', (new JsonEncoder(true))->encode((object) ['test']));
    }

    /**
     * Pretty print is asked for through the options.
     *
     * @return void
     */
    public function testEncodePrettyPrint(): void
    {
        $this->assertSame(
            "{\n    \"a\": 1\n}",
            $this->encoder->encode(['a' => 1], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );
    }

    /**
     * The default options leave slashes and Unicode unescaped.
     *
     * @return void
     */
    public function testEncodeLeavesSlashesAndUnicodeUnescapedByDefault(): void
    {
        $this->assertSame('"a/b ü"', $this->encoder->encode('a/b ü'));
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
            $this->encoder->htmlEncode('<a href="x">&</a>')
        );
    }

    /**
     * A value json_encode() cannot encode is reported with an exception carrying PHP's message and error code.
     *
     * @return void
     */
    public function testEncodeInvalidUtf8Throws(): void
    {
        $this->expectException(InvalidParamException::class);
        $this->expectExceptionMessage('Malformed UTF-8 characters, possibly incorrectly encoded');
        $this->expectExceptionCode(JSON_ERROR_UTF8);

        $this->encoder->encode("\xB1\x31");
    }
}
