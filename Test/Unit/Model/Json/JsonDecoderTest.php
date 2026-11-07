<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Json;

use Hryvinskyi\Base\Exception\InvalidParamException;
use Hryvinskyi\Base\Model\Json\JsonDecoder;
use PHPUnit\Framework\TestCase;

class JsonDecoderTest extends TestCase
{
    /**
     * The decoder under test.
     *
     * @var JsonDecoder
     */
    private JsonDecoder $decoder;

    /**
     * Builds the decoder.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->decoder = new JsonDecoder();
    }

    /**
     * JSON decodes to arrays by default.
     *
     * @return void
     */
    public function testDecodeAsArray(): void
    {
        $this->assertSame(['a' => 1], $this->decoder->decode('{"a":1}'));
    }

    /**
     * JSON objects decode to stdClass objects when arrays are not asked for.
     *
     * @return void
     */
    public function testDecodeAsObject(): void
    {
        $this->assertEquals((object) ['a' => 1], $this->decoder->decode('{"a":1}', false));
    }

    /**
     * Null and an empty string decode to null.
     *
     * @return void
     */
    public function testDecodeEmptyInputAsNull(): void
    {
        $this->assertNull($this->decoder->decode(null));
        $this->assertNull($this->decoder->decode(''));
    }

    /**
     * Malformed JSON is reported with an exception carrying PHP's message and error code.
     *
     * @return void
     */
    public function testDecodeMalformedJsonThrows(): void
    {
        $this->expectException(InvalidParamException::class);
        $this->expectExceptionMessage('Syntax error');
        $this->expectExceptionCode(JSON_ERROR_SYNTAX);

        $this->decoder->decode('{"a":');
    }
}
