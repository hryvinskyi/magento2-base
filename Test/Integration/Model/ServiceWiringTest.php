<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Integration\Model;

use Hryvinskyi\Base\Api\Arrays\ArrayFilterInterface;
use Hryvinskyi\Base\Api\Arrays\ArrayHtmlEncoderInterface;
use Hryvinskyi\Base\Api\Arrays\ArrayIndexerInterface;
use Hryvinskyi\Base\Api\Arrays\ArrayInspectorInterface;
use Hryvinskyi\Base\Api\Arrays\ArrayMergerInterface;
use Hryvinskyi\Base\Api\Arrays\ArrayPathReaderInterface;
use Hryvinskyi\Base\Api\Arrays\ArrayPathWriterInterface;
use Hryvinskyi\Base\Api\Arrays\ArraySorterInterface;
use Hryvinskyi\Base\Api\Arrays\ObjectToArrayConverterInterface;
use Hryvinskyi\Base\Api\Data\ReplaceArrayValueInterfaceFactory;
use Hryvinskyi\Base\Api\Data\UnsetArrayValueInterfaceFactory;
use Hryvinskyi\Base\Api\Debug\VarDumperInterface;
use Hryvinskyi\Base\Api\Html\AttributeRendererInterface;
use Hryvinskyi\Base\Api\Html\ChoiceListRendererInterface;
use Hryvinskyi\Base\Api\Html\CssOptionsInterface;
use Hryvinskyi\Base\Api\Html\FormRendererInterface;
use Hryvinskyi\Base\Api\Html\HtmlEncoderInterface;
use Hryvinskyi\Base\Api\Html\InputNameResolverInterface;
use Hryvinskyi\Base\Api\Html\InputRendererInterface;
use Hryvinskyi\Base\Api\Html\JsRegexConverterInterface;
use Hryvinskyi\Base\Api\Html\LinkRendererInterface;
use Hryvinskyi\Base\Api\Html\ListRendererInterface;
use Hryvinskyi\Base\Api\Html\ResourceTagRendererInterface;
use Hryvinskyi\Base\Api\Html\TagRendererInterface;
use Hryvinskyi\Base\Api\Json\JsonDecoderInterface;
use Hryvinskyi\Base\Api\Json\JsonEncoderInterface;
use Hryvinskyi\Base\Api\LayoutDebugConfigInterface;
use Hryvinskyi\Base\Observer\Layout as LayoutObserver;
use Hryvinskyi\Base\Observer\Response as ResponseObserver;
use Hryvinskyi\Base\Plugin\DebugHints;
use Hryvinskyi\Base\Plugin\Layout as LayoutPlugin;
use Magento\Framework\App\State;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Every published service resolved through the real container, each doing the work it is published for
 *
 * The cases are chosen for what only the container supplies: the preferences, the configured constructor arguments
 * and the generated factories, none of which a unit test built by hand exercises.
 */
class ServiceWiringTest extends TestCase
{
    /**
     * Resolves a service through the real container.
     *
     * @template T of object
     * @param class-string<T> $type the interface or class to resolve
     * @return T
     */
    private function service(string $type): object
    {
        $service = Bootstrap::getObjectManager()->get($type);
        self::assertInstanceOf($type, $service);

        return $service;
    }

    /**
     * A void tag closes itself and its attributes take the configured order, whatever order they are given in.
     *
     * @return void
     */
    public function testVoidTagWithAttributesOutOfOrder(): void
    {
        self::assertSame(
            '<input type="text" name="q" />',
            $this->service(TagRendererInterface::class)->tag('input', '', ['name' => 'q', 'type' => 'text'])
        );
    }

    /**
     * Any other tag gets an end tag.
     *
     * @return void
     */
    public function testNonVoidTag(): void
    {
        self::assertSame(
            '<div id="a">text</div>',
            $this->service(TagRendererInterface::class)->tag('div', 'text', ['id' => 'a'])
        );
    }

    /**
     * Attributes expand their configured data attributes and render a repeated class once.
     *
     * @return void
     */
    public function testAttributesExpandDataAndNormaliseClasses(): void
    {
        self::assertSame(
            ' class="a b" data-id="1" aria-label="x"',
            $this->service(AttributeRendererInterface::class)->render([
                'data' => ['id' => 1],
                'aria' => ['label' => 'x'],
                'class' => ['a', 'b', 'a'],
            ])
        );
    }

    /**
     * Adding a class to options already holding a repeated one normalises them, as the attribute renderer does.
     *
     * @return void
     */
    public function testAddCssClassDropsADuplicate(): void
    {
        self::assertSame(
            ['class' => 'a b c'],
            $this->service(CssOptionsInterface::class)->addCssClass(['class' => 'a a b'], 'c')
        );
    }

    /**
     * Markers from the generated factories remove a key and replace a value, whatever data the replacement holds.
     *
     * The replacement looks like an object manager argument on purpose: it must arrive untouched in every mode.
     *
     * @return void
     */
    public function testMergeWithFactoryMadeMarkers(): void
    {
        $replacement = ['argument' => 'x', 'nested' => ['instance' => 'stdClass']];

        self::assertSame(
            ['replaced' => $replacement, 'kept' => [1]],
            $this->service(ArrayMergerInterface::class)->merge(
                ['removed' => 1, 'replaced' => ['a', 'b'], 'kept' => [1]],
                [
                    'removed' => $this->service(UnsetArrayValueInterfaceFactory::class)->create(),
                    'replaced' => $this->service(ReplaceArrayValueInterfaceFactory::class)->create()
                        ->withValue($replacement),
                ]
            )
        );
    }

    /**
     * A value survives a JSON round trip through the encoder and the decoder.
     *
     * @return void
     */
    public function testJsonRoundTrip(): void
    {
        $value = ['name' => 'Tést/1', 'list' => [1, 2.5, true, null]];
        $json = $this->service(JsonEncoderInterface::class)->encode($value);

        self::assertSame('{"name":"Tést/1","list":[1,2.5,true,null]}', $json);
        self::assertSame($value, $this->service(JsonDecoderInterface::class)->decode($json));
    }

    /**
     * A closure exports as its source, which takes the generated reflection factory and the configured file driver.
     *
     * @return void
     */
    public function testExportOfAClosure(): void
    {
        $closure = function () {
            return 2;
        };

        self::assertSame(
            <<<'SOURCE'
function () {
            return 2;
        }
SOURCE,
            $this->service(VarDumperInterface::class)->export($closure)
        );
    }

    /**
     * A highlighted dump escapes the value and reads as the plain dump once its markup is gone.
     *
     * @return void
     */
    public function testHighlightedDump(): void
    {
        $dumper = $this->service(VarDumperInterface::class);
        $highlighted = $dumper->dumpAsString(['tag' => '<b>'], 10, true);

        self::assertStringStartsWith('<pre><code style="color: #000000">', $highlighted);
        self::assertStringNotContainsString('<b>', $highlighted);
        self::assertSame(
            $dumper->dumpAsString(['tag' => '<b>']),
            htmlspecialchars_decode(strip_tags($highlighted), ENT_QUOTES)
        );
    }

    /**
     * A form start carries the form key in a hidden input.
     *
     * @return void
     */
    public function testFormStartWithAFormKey(): void
    {
        self::assertSame(
            '<form action="/save" method="post">' . "\n" . '<input type="hidden" name="form_key" value="abc" />',
            $this->service(FormRendererInterface::class)->beginForm('/save', 'post', 'abc')
        );
    }

    /**
     * The secure HTML renderer takes the module's renderer: a script keeps its end tag, a void element self-closes.
     *
     * @return void
     */
    public function testSecureHtmlRendererTakesTheModuleRenderer(): void
    {
        $renderer = $this->service(SecureHtmlRenderer::class);

        self::assertSame(
            '<script type="text/javascript">let a = 1 < 2;</script>',
            $renderer->renderTag('script', ['type' => 'text/javascript'], 'let a = 1 < 2;', false)
        );
        self::assertSame(
            '<img src="https://example.test/a.png" alt="a" />',
            $renderer->renderTag('img', ['alt' => 'a', 'src' => 'https://example.test/a.png'])
        );
    }

    /**
     * The array read and write services work on a dot path.
     *
     * @return void
     */
    public function testArrayPathReadAndWrite(): void
    {
        $array = $this->service(ArrayPathWriterInterface::class)->setValue([], 'a.b', 1);

        self::assertSame(['a' => ['b' => 1]], $array);
        self::assertSame(1, $this->service(ArrayPathReaderInterface::class)->getValue($array, 'a.b'));
    }

    /**
     * The indexer groups rows and the sorter orders them.
     *
     * @return void
     */
    public function testArrayIndexerAndSorter(): void
    {
        $rows = [['id' => 2, 'kind' => 'x'], ['id' => 1, 'kind' => 'x']];

        self::assertSame(
            ['x' => [2 => $rows[0], 1 => $rows[1]]],
            $this->service(ArrayIndexerInterface::class)->index($rows, 'id', ['kind'])
        );
        self::assertSame(
            [$rows[1], $rows[0]],
            $this->service(ArraySorterInterface::class)->multisort($rows, 'id')
        );
    }

    /**
     * The inspector tells an associative array from a list.
     *
     * @return void
     */
    public function testArrayInspector(): void
    {
        $inspector = $this->service(ArrayInspectorInterface::class);

        self::assertTrue($inspector->isAssociative(['a' => 1]));
        self::assertFalse($inspector->isAssociative([1]));
    }

    /**
     * The filter keeps what its rules name.
     *
     * @return void
     */
    public function testArrayFilter(): void
    {
        self::assertSame(
            ['a' => ['b' => 1]],
            $this->service(ArrayFilterInterface::class)->filter(['a' => ['b' => 1, 'c' => 2], 'd' => 3], ['a.b'])
        );
    }

    /**
     * The converter turns an object into an array of its public properties.
     *
     * @return void
     */
    public function testObjectToArrayConverter(): void
    {
        self::assertSame(
            ['a' => 1],
            $this->service(ObjectToArrayConverterInterface::class)->toArray((object)['a' => 1])
        );
    }

    /**
     * The array encoder encodes values and decodes them back.
     *
     * @return void
     */
    public function testArrayHtmlEncoder(): void
    {
        $encoder = $this->service(ArrayHtmlEncoderInterface::class);
        $encoded = $encoder->htmlEncode(['a' => '<b>']);

        self::assertSame(['a' => '&lt;b&gt;'], $encoded);
        self::assertSame(['a' => '<b>'], $encoder->htmlDecode($encoded));
    }

    /**
     * The encoder encodes double or keeps entities, on the two escapers the container supplies.
     *
     * @return void
     */
    public function testHtmlEncoder(): void
    {
        $encoder = $this->service(HtmlEncoderInterface::class);

        self::assertSame('&lt;a&gt; &amp;amp;', $encoder->encode('<a> &amp;'));
        self::assertSame('&lt;a&gt; &amp;', $encoder->encodeKeepingEntities('<a> &amp;'));
    }

    /**
     * The resolver reads an attribute name with the configured expression.
     *
     * @return void
     */
    public function testInputNameResolver(): void
    {
        self::assertSame(
            'name',
            $this->service(InputNameResolverInterface::class)->getAttributeName('[0]name[1]')
        );
    }

    /**
     * The converter turns a PHP expression into a JavaScript one.
     *
     * @return void
     */
    public function testJsRegexConverter(): void
    {
        self::assertSame('/[a-z]+/', $this->service(JsRegexConverterInterface::class)->convert('([a-z]+)'));
    }

    /**
     * The resource renderer prints a stylesheet link.
     *
     * @return void
     */
    public function testResourceTagRenderer(): void
    {
        self::assertSame(
            '<link href="/a.css" rel="stylesheet" />',
            $this->service(ResourceTagRendererInterface::class)->cssFile('/a.css')
        );
    }

    /**
     * The link renderer prints an anchor.
     *
     * @return void
     */
    public function testLinkRenderer(): void
    {
        self::assertSame(
            '<a href="/a">Text</a>',
            $this->service(LinkRendererInterface::class)->a('Text', '/a')
        );
    }

    /**
     * The input renderer prints a text input.
     *
     * @return void
     */
    public function testInputRenderer(): void
    {
        self::assertSame(
            '<input type="text" name="q" value="1" />',
            $this->service(InputRendererInterface::class)->textInput('q', '1')
        );
    }

    /**
     * The choice list renderer prints a select with the selected option.
     *
     * @return void
     */
    public function testChoiceListRenderer(): void
    {
        self::assertSame(
            '<select name="c">' . "\n"
            . '<option value="a">A</option>' . "\n"
            . '<option value="b" selected>B</option>' . "\n"
            . '</select>',
            $this->service(ChoiceListRendererInterface::class)->dropDownList('c', 'b', ['a' => 'A', 'b' => 'B'])
        );
    }

    /**
     * The list renderer prints an unordered list.
     *
     * @return void
     */
    public function testListRenderer(): void
    {
        self::assertSame(
            "<ul>\n<li>a</li>\n</ul>",
            $this->service(ListRendererInterface::class)->ul(['a'])
        );
    }

    /**
     * With no developer restriction set, the layout debug answer follows the application mode.
     *
     * @return void
     */
    public function testLayoutDebugConfigFollowsTheApplicationMode(): void
    {
        self::assertSame(
            $this->service(State::class)->getMode() === State::MODE_DEVELOPER,
            $this->service(LayoutDebugConfigInterface::class)->isEnabledLayoutDebug()
        );
    }

    /**
     * The classes reading the layout debug answer.
     *
     * @return array<string, array{class-string}>
     */
    public static function layoutDebugConsumerProvider(): array
    {
        return [
            'layout observer' => [LayoutObserver::class],
            'response observer' => [ResponseObserver::class],
            'layout plugin' => [LayoutPlugin::class],
            'debug hints plugin' => [DebugHints::class],
        ];
    }

    /**
     * Each class reading the layout debug answer is built by the container.
     *
     * @param class-string $type the class
     * @return void
     */
    #[DataProvider('layoutDebugConsumerProvider')]
    public function testLayoutDebugConsumersResolve(string $type): void
    {
        self::assertInstanceOf($type, $this->service($type));
    }
}
