# Magento 2 Base Module

A comprehensive Magento 2 base module providing:
- **Injected services**: Role interfaces for array manipulation, HTML generation, JSON handling, and debugging, ported from Yii2
- **ViewModel Registry**: Global access to view models in templates without layout XML configuration
- **Layout Debugging**: Developer mode tools for layout XML and block structure inspection

## Installation Guide

```bash
composer require hryvinskyi/magento2-base
bin/magento module:enable Hryvinskyi_Base
bin/magento setup:upgrade
```

## Table of Contents

1. [Core Features](#core-features)
   - [ViewModel Registry](#1-viewmodel-registry-viewmodels)
   - [Layout Debugging](#2-layout-debugging-developer-mode-only)
   - [Extensible Admin Menu](#3-extensible-admin-menu)
2. [Services](#services)
   - [Arrays](#arrays)
   - [HTML](#html)
   - [JSON](#json)
   - [VarDumper](#vardumper)
3. [Upgrading from 2.x](#upgrading-from-2x)
4. [Requirements](#requirements)
5. [License](#license)

## Core Features

### 1. ViewModel Registry (`$viewModels`)

Access any view model directly in templates without declaring them in layout XML. The `$viewModels` variable is automatically available in all `.phtml` templates.

**Usage in templates:**
```php
<?php
/** @var Magento\Framework\View\Element\Template $block */
/** @var Hryvinskyi\Base\Model\ViewModelRegistry $viewModels */

// Get any view model by class name
$productViewModel = $viewModels->require(\Vendor\Module\ViewModel\Product::class);
$categoryViewModel = $viewModels->require(\Vendor\Module\ViewModel\Category::class);

// For ESI blocks with TTL, pass the block reference for proper cache handling
$esiViewModel = $viewModels->require(\Vendor\Module\ViewModel\Esi::class, $block);

// Use the view model
echo $productViewModel->getProductName();
?>
```

**How it works:**
- Automatically injected into all PHP template engines via `di.xml`
- Collects cache tags from view models implementing `IdentityInterface`
- Properly handles Varnish ESI blocks to prevent incorrect cache purging
- Validates that requested classes implement `ArgumentInterface`

**Cache handling for ESI blocks:**
```php
// Main page - cache tags collected normally
$viewModel = $viewModels->require(MyViewModel::class);

// ESI block (ttl="300") - pass $block to prevent main page cache pollution
$viewModel = $viewModels->require(MyViewModel::class, $block);
```

### 2. Layout Debugging (Developer Mode Only)

Automatic layout debugging tools enabled when Magento is in developer mode.

**Features:**
- Visual block hierarchy display
- Layout XML structure viewer
- Block rendering time tracking
- Cache status indicators

**Automatically enabled when:**
```bash
bin/magento deploy:mode:set developer
```

**Configuration:**
The debugging information is controlled by `Hryvinskyi\Base\Api\LayoutDebugConfigInterface`:
- Only works in developer mode (`State::MODE_DEVELOPER`), and there only for a visitor the developer restriction
  (`dev/restrict/allow_ips`) allows; with no list set, every visitor
- Injected into responses via event observers
- No configuration needed - works out of the box

**What you get:**
- Block nesting and parent-child relationships
- Template file locations with full paths
- Block class names and cache keys
- Layout handle processing order
- Visual hierarchy with indentation

### 3. Extensible Admin Menu

A reusable dropdown menu component for Magento admin pages that can be configured entirely via layout XML. Perfect for creating navigation menus in custom admin modules.

**Features:**
- Fully configurable via layout XML (no PHP code required)
- Route-based URL generation with parameters support
- Sortable menu items with sort_order
- Custom CSS classes and icons per item
- Per-item ACL resource: items the current admin user may not open are hidden
- Optional titled groups shown side by side, with long groups split into columns
- Translatable labels
- Reusable across multiple modules

**Basic Usage in Layout XML:**

```xml
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <head>
        <css src="Hryvinskyi_Base::css/styles.css"/>
    </head>
    <body>
        <referenceBlock name="page.title">
            <block class="Hryvinskyi\Base\Block\Adminhtml\Menu" name="my.module.menu">
                <arguments>
                    <argument name="menu_title" xsi:type="string" translate="true">My Module</argument>
                    <argument name="items" xsi:type="array">
                        <item name="dashboard" xsi:type="array">
                            <item name="label" xsi:type="string" translate="true">Dashboard</item>
                            <item name="route" xsi:type="string">mymodule/dashboard/index</item>
                            <item name="sort_order" xsi:type="number">10</item>
                        </item>
                        <item name="settings" xsi:type="array">
                            <item name="label" xsi:type="string" translate="true">Settings</item>
                            <item name="route" xsi:type="string">adminhtml/system_config/edit</item>
                            <item name="route_params" xsi:type="array">
                                <item name="section" xsi:type="string">mymodule</item>
                            </item>
                            <item name="sort_order" xsi:type="number">20</item>
                        </item>
                    </argument>
                </arguments>
            </block>
        </referenceBlock>
    </body>
</page>
```

**Creating a Reusable Menu Handle:**

Create a shared layout handle file (e.g., `mymodule_menu.xml`) to avoid duplication:

```xml
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <head>
        <css src="Hryvinskyi_Base::css/styles.css"/>
    </head>
    <body>
        <referenceBlock name="page.title">
            <block class="Hryvinskyi\Base\Block\Adminhtml\Menu" name="mymodule.menu">
                <arguments>
                    <argument name="menu_title" xsi:type="string" translate="true">My Module</argument>
                    <argument name="items" xsi:type="array">
                        <!-- Define your menu items here -->
                    </argument>
                </arguments>
            </block>
        </referenceBlock>
    </body>
</page>
```

Then include it in your page layouts:

```xml
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <update handle="mymodule_menu"/>
    <body>
        <referenceContainer name="content">
            <uiComponent name="mymodule_listing"/>
        </referenceContainer>
    </body>
</page>
```

**Menu Item Configuration Options:**

| Option | Type | Required | Description |
|--------|------|----------|-------------|
| `label` | string | Yes | Menu item text (supports translation with `translate="true"`) |
| `route` | string | Yes | Magento route path (e.g., `module/controller/action`) |
| `route_params` | array | No | Route parameters (e.g., `section`, `id`) |
| `sort_order` | number | No | Order of items (default: 0, lower = first) |
| `class` | string or array | No | CSS class(es) for the menu item, or a map of class name to boolean flag |
| `icon` | string | No | SVG or HTML icon content |
| `is_active` | boolean | No | Show/hide item (default: true) |
| `resource` | string | No | ACL resource id the admin user needs to see the item (since 2.2.0) |
| `group` | string | No | Code of the group the item is listed in (since 2.3.0) |

An item that holds a value of the wrong type (for example an array as `label`, or an empty `resource`) is left out
of the menu and logged as a warning; the rest of the menu still renders.

**Per-item Permissions (`resource`):**

Give an item the ACL resource that guards the page it links to. The menu checks it with
`Magento\Framework\AuthorizationInterface::isAllowed()` and does not render the item for an admin role that lacks
it, so a restricted user never sees a link that ends on "Sorry, you need permissions to view this content".
Items without `resource` are shown to everyone who can open the page, exactly as in 2.1. When no item is left, the
menu renders nothing.

```xml
<item name="settings" xsi:type="array">
    <item name="label" xsi:type="string" translate="true">Settings</item>
    <item name="route" xsi:type="string">adminhtml/system_config/edit</item>
    <item name="route_params" xsi:type="array">
        <item name="section" xsi:type="string">mymodule</item>
    </item>
    <item name="resource" xsi:type="string">Vendor_MyModule::config</item>
    <item name="sort_order" xsi:type="number">20</item>
</item>
```

The per-item check hides links; it does not protect the pages. Each controller still declares its own
`ADMIN_RESOURCE`.

**Groups and Columns (since 2.3.0):**

A long menu reads better in titled groups. Declare the groups in the `groups` argument (code → `label` and
`sort_order`) and name a group in each item's `group` key:

```xml
<block class="Hryvinskyi\Base\Block\Adminhtml\Menu" name="my.module.menu">
    <arguments>
        <argument name="menu_title" xsi:type="string" translate="true">My Module</argument>
        <argument name="groups" xsi:type="array">
            <item name="reports" xsi:type="array">
                <item name="label" xsi:type="string" translate="true">Reports</item>
                <item name="sort_order" xsi:type="number">10</item>
            </item>
            <item name="settings" xsi:type="array">
                <item name="label" xsi:type="string" translate="true">Settings</item>
                <item name="sort_order" xsi:type="number">20</item>
            </item>
        </argument>
        <argument name="max_items_per_column" xsi:type="number">6</argument>
        <argument name="items" xsi:type="array">
            <item name="sales" xsi:type="array">
                <item name="label" xsi:type="string" translate="true">Sales</item>
                <item name="route" xsi:type="string">mymodule/reports/sales</item>
                <item name="group" xsi:type="string">reports</item>
                <item name="sort_order" xsi:type="number">10</item>
            </item>
            <item name="general" xsi:type="array">
                <item name="label" xsi:type="string" translate="true">General</item>
                <item name="route" xsi:type="string">adminhtml/system_config/edit</item>
                <item name="route_params" xsi:type="array">
                    <item name="section" xsi:type="string">mymodule</item>
                </item>
                <item name="group" xsi:type="string">settings</item>
                <item name="resource" xsi:type="string">Vendor_MyModule::config</item>
            </item>
        </argument>
    </arguments>
</block>
```

| Argument | Type | Description |
|----------|------|-------------|
| `groups` | array | Group code → `label` (required, translatable) and `sort_order` (number, default 0) |
| `max_items_per_column` | number | Most items in one column of a group (default 6) |

- Groups are shown side by side in `sort_order` (equal sort orders keep the declared order), each under its
  heading; a group wraps to a new row when the screen is too narrow, and below 768 px everything stacks.
- Inside a group, items keep their `sort_order`. A group none of whose items the admin may see (denied by
  `resource`, or `is_active` false) is not shown.
- A group longer than `max_items_per_column` is split into even columns, the first columns taking the extra item:
  with the default of 6, 7 items become 4 + 3 and 13 items 5 + 4 + 4.
- Items without `group`, or naming a group the menu does not declare, are listed after every group without a
  heading. An undeclared code is logged as a warning on every page that renders the menu, so fix it rather than
  leave it.
- A menu without `groups`, or whose items name none, is the single list it always was.
- Any module can add its own entries to another module's groups from its own layout file by naming the group's
  code; only the menu's owner declares the groups.
- A malformed group (no label, a value of the wrong type) or a malformed `max_items_per_column` is logged and
  ignored, like a malformed item.

**Custom Menu Icon:**

```xml
<argument name="menu_icon" xsi:type="string"><![CDATA[
    <svg class="hamburger-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
    </svg>
]]></argument>
```

**Menu Item with Icon:**

```xml
<item name="reports" xsi:type="array">
    <item name="label" xsi:type="string" translate="true">Reports</item>
    <item name="route" xsi:type="string">mymodule/reports/index</item>
    <item name="sort_order" xsi:type="number">30</item>
    <item name="icon" xsi:type="string"><![CDATA[
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24">
            <path d="M3 3v18h18v-18h-18zm16 16h-14v-14h14v14z"/>
        </svg>
    ]]></item>
</item>
```

## Services

Base publishes its utilities as small role interfaces under `Api/`, implemented under `Model/` and wired in
`etc/di.xml`. Each interface answers one question, so a class depends on the role it uses and nothing else. The
interfaces are `@api`; the services are stateless and shared; no public method changes an argument by reference.

Inject the interface you need:

```php
use Hryvinskyi\Base\Api\Arrays\ArrayPathReaderInterface;
use Hryvinskyi\Base\Api\Html\HtmlEncoderInterface;
use Hryvinskyi\Base\Api\Html\TagRendererInterface;

class ProductBadge
{
    public function __construct(
        private readonly ArrayPathReaderInterface $arrayPathReader,
        private readonly HtmlEncoderInterface $htmlEncoder,
        private readonly TagRendererInterface $tagRenderer
    ) {
    }

    public function render(array $product): string
    {
        $label = (string)$this->arrayPathReader->getValue($product, 'badge.label', 'New');

        return $this->tagRenderer->tag('span', $this->htmlEncoder->encode($label), ['class' => 'badge']);
    }
}
```

### Arrays

| Interface (`Hryvinskyi\Base\Api\Arrays\`) | Methods |
|-------------------------------------------|---------|
| `ArrayPathReaderInterface` | `getValue($source, $key, $default)` reads a dot path; `keyExists($key, $array, $caseSensitive)` |
| `ArrayPathWriterInterface` | `setValue($array, $path, $value)`, `remove($array, $key)`, `removeValue($array, $value)`, `valuesIdenticalTo($array, $value)`; each returns an array |
| `ArrayMergerInterface` | `merge($first, ...$others)` merges recursively |
| `ArrayIndexerInterface` | `index($array, $key, $groups)`, `getColumn($array, $name, $keepKeys)`, `map($array, $from, $to, $group)` |
| `ArraySorterInterface` | `multisort($array, $key, $direction, $sortFlag)` returns the sorted rows |
| `ArrayFilterInterface` | `filter($array, $filters)` keeps or drops dot paths |
| `ArrayInspectorInterface` | `isAssociative()`, `isIndexed()`, `isIn()`, `isTraversable()`, `isSubset()` |
| `ArrayHtmlEncoderInterface` | `htmlEncode($data, $valuesOnly)`, `htmlDecode($data, $valuesOnly)` |
| `ObjectToArrayConverterInterface` | `toArray($object, $properties, $recursive)` |

`Api\Data\ArrayableInterface` lets an object describe its own array form. Two markers steer a merge: an
`Api\Data\UnsetArrayValueInterface` removes a key, an `Api\Data\ReplaceArrayValueInterface` replaces a value instead of
merging into it. Both come from their generated factories, and a replacement carries its value through `withValue()`:

```php
$merged = $this->arrayMerger->merge(
    ['items' => ['a', 'b'], 'debug' => true],
    [
        'items' => $this->replaceArrayValueFactory->create()->withValue(['c', 'd']),
        'debug' => $this->unsetArrayValueFactory->create(),
    ]
);
// ['items' => ['c', 'd']]
```

### HTML

| Interface (`Hryvinskyi\Base\Api\Html\`) | Methods |
|-----------------------------------------|---------|
| `HtmlEncoderInterface` | `encode()`, `encodeKeepingEntities()`, `decode()` |
| `AttributeRendererInterface` | `render($attributes)` |
| `TagRendererInterface` | `tag($name, $content, $options)`, `beginTag()`, `endTag()` |
| `CssOptionsInterface` | `addCssClass()`, `removeCssClass()`, `addCssStyle()`, `removeCssStyle()`, each returning the new options; `cssStyleFromArray()`, `cssStyleToArray()` |
| `InputNameResolverInterface` | `getAttributeName()`, `getInputIdByName()` |
| `JsRegexConverterInterface` | `convert($regexp)` |
| `ResourceTagRendererInterface` | `style()`, `script()`, `cssFile()`, `jsFile()` |
| `LinkRendererInterface` | `a()`, `mailto()`, `img()` |
| `FormRendererInterface` | `beginForm()`, `endForm()`, `label()`, `button()`, `submitButton()`, `resetButton()`, `textarea()` |
| `InputRendererInterface` | `input()`, `buttonInput()`, `submitInput()`, `resetInput()`, `textInput()`, `hiddenInput()`, `passwordInput()`, `fileInput()`, `radio()`, `checkbox()` |
| `ChoiceListRendererInterface` | `dropDownList()`, `listBox()`, `checkboxList()`, `radioList()`, `renderSelectOptions()` |
| `ListRendererInterface` | `ul()`, `ol()` |

The tag rendering keeps a fixed attribute order, renders `data`, `aria`, `class` and `style` arrays, skips `null`
attributes and closes void elements with ` />`. The order and the lists are required `etc/di.xml` arguments with no
default in the classes, so a project changes them there. An array merge can override an item but not remove it, so a void element is switched off with `false`:

```xml
<type name="Hryvinskyi\Base\Model\Html\TagRenderer">
    <arguments>
        <argument name="voidElements" xsi:type="array">
            <item name="command" xsi:type="boolean">false</item>
        </argument>
    </arguments>
</type>
```

The class normalisation switch is read in two places: set `normalizeClassAttribute` to the same value on
`Model\Html\AttributeRenderer` and `Model\Html\CssOptions`.

### JSON

`Api\Json\JsonEncoderInterface::encode($value, $options)` and `htmlEncode($value)`, the latter safe inside an HTML
attribute; `Api\Json\JsonDecoderInterface::decode($json, $asArray)`. Malformed input throws
`Hryvinskyi\Base\Exception\InvalidParamException` with PHP's message and `json_last_error()` code; `null` and an empty
string decode to `null`.

```php
$json = $this->jsonEncoder->encode(['name' => 'John'], JSON_PRETTY_PRINT);
$safe = $this->jsonEncoder->htmlEncode(['alert' => '<script>alert("xss")</script>']);
$data = $this->jsonDecoder->decode($json);
```

### VarDumper

To look at a value while debugging, call `hdump($var, $depth, $highlight)` or `hdd(...)` from anywhere, a template
included, with no import: they print Base's dump (below), `hdump()` returns the value and `hdd()` stops the script.
Highlighting defaults to on everywhere but the command line. Symfony's `dump()` and `dd()` work too: Base requires
`symfony/var-dumper`.
They are debugging tools with no check of their own: unlike the layout debug tools they print in production mode too, to
every visitor, so one left in a template ends up in the page cache.

For a dump the code keeps, such as a log entry or a report,
`Api\Debug\VarDumperInterface::dumpAsString($var, $depth, $highlight)` returns a readable dump that survives circular
references; with `$highlight` it is HTML with every token escaped, so a dump of visitor data is safe to print.
`export($var)` returns a PHP expression: objects as `unserialize('…')`, closures as their source.

```php
$logger->debug($this->varDumper->dumpAsString($data, 5));
$code = $this->varDumper->export(['name' => 'John', 'items' => [1, 2, 3]]);
```

## Upgrading from 2.x

3.0 removes every static class in `Helper/`. Inject the interface of the table below instead of calling the static; the
`Api\…` names are under `Hryvinskyi\Base\`. Nothing replaces `ConsoleHelper`, which had no consumers. A package that
still calls a static cannot load against 3.0, so `composer.json` declares a `conflict` with the released versions of
`hryvinskyi/magento2-head-tag-manager`, `-page-speed`, `-page-speed-js-merge` and `-error-reporting` that do.

### Classes

| 2.x | 3.0 |
|-----|-----|
| `Helper\Config::isEnabledLayoutDebug()` | `Api\LayoutDebugConfigInterface::isEnabledLayoutDebug()`, which also requires a visitor the developer restriction allows |
| `Helper\Arrayable` | `Api\Data\ArrayableInterface`, with typed signatures: `fields(): array`, `extraFields(): array`, `toArray(array $fields = [], array $expand = [], bool $recursive = true): array` |
| `new Helper\UnsetArrayValue()` | `Api\Data\UnsetArrayValueInterfaceFactory::create()` |
| `new Helper\ReplaceArrayValue($value)` | `Api\Data\ReplaceArrayValueInterfaceFactory::create()->withValue($value)` |
| `Helper\InvalidParamException` (a `\BadMethodCallException`) | `Exception\InvalidParamException`, now an `\InvalidArgumentException`: a `catch (\BadMethodCallException)` no longer sees it. `getName()` is gone |
| `Helper\InvalidValueException` | `Exception\InvalidValueException`, still an `\UnexpectedValueException`. `getName()` is gone |
| `Helper\ConsoleHelper` | removed |

The markers have no `__set_state()`.

### Json

| 2.x | 3.0 |
|-----|-----|
| `Json::encode($value, $options)` | `Api\Json\JsonEncoderInterface::encode()` |
| `Json::htmlEncode($value)` | `Api\Json\JsonEncoderInterface::htmlEncode()` |
| `Json::decode($json, $asArray)` | `Api\Json\JsonDecoderInterface::decode()`, which takes `?string` |
| `Json::$prettyPrint` | pass `JSON_PRETTY_PRINT` in `$options` |
| `Json::$keepObjectType` | the `keepObjectType` constructor argument of `Model\Json\JsonEncoder`, set in `di.xml` |
| `Json::$jsonErrorMessages` | removed; the exception carries PHP's `json_last_error_msg()` |

### VarDumper

| 2.x | 3.0 |
|-----|-----|
| `VarDumper::dump($var, $depth, $highlight)` | `hdump($var, $depth, $highlight)`: global, no import; it prints and returns the value. `hdd()` prints and stops. Symfony's `dump()`/`dd()` also work |
| `VarDumper::dumpAsString($var, $depth, $highlight)` | `Api\Debug\VarDumperInterface::dumpAsString()` |
| `VarDumper::export($var)` | `Api\Debug\VarDumperInterface::export()` |

The highlighted form no longer comes from `highlight_string()`: it is `<pre><code style="color: #000000">` with one
coloured span per token. Its text equals the plain dump and every token is escaped.

### ArrayHelper

| 2.x | 3.0 |
|-----|-----|
| `ArrayHelper::getValue($array, $key, $default)` | `Api\Arrays\ArrayPathReaderInterface::getValue()` |
| `ArrayHelper::keyExists($key, $array, $caseSensitive)` | `Api\Arrays\ArrayPathReaderInterface::keyExists()` |
| `ArrayHelper::setValue(&$array, $path, $value)` | `$array = Api\Arrays\ArrayPathWriterInterface::setValue($array, $path, $value)`; a `null` path is gone, use the value itself |
| `$old = ArrayHelper::remove(&$array, $key, $default)` | read first, `$old = array_key_exists($key, $array) ? $array[$key] : $default`, then `$array = ArrayPathWriterInterface::remove($array, $key)`. Not `getValue()`, which falls through to a dot path when the literal key is absent. A `null` array is a `TypeError` |
| `$old = ArrayHelper::removeValue(&$array, $value)` | read first, `$old = ArrayPathWriterInterface::valuesIdenticalTo($array, $value)`, then `$array = ArrayPathWriterInterface::removeValue($array, $value)`. A `null` array is a `TypeError` |
| `ArrayHelper::merge($a, $b, ...)` | `Api\Arrays\ArrayMergerInterface::merge()`; the first argument is an array and the others arrays or objects, so a scalar or `null` is a `TypeError` instead of being skipped |
| `ArrayHelper::index($array, $key, $groups)` | `Api\Arrays\ArrayIndexerInterface::index()`; a lone Closure as `$groups` is refused, wrap it in a list |
| `ArrayHelper::getColumn($array, $name, $keepKeys)` | `Api\Arrays\ArrayIndexerInterface::getColumn()` |
| `ArrayHelper::map($array, $from, $to, $group)` | `Api\Arrays\ArrayIndexerInterface::map()` |
| `ArrayHelper::multisort(&$array, $key, $direction, $sortFlag)` | `$array = Api\Arrays\ArraySorterInterface::multisort($array, $key, $direction, $sortFlag)` |
| `ArrayHelper::filter($array, $filters)` | `Api\Arrays\ArrayFilterInterface::filter()` |
| `ArrayHelper::toArray($object, $properties, $recursive)` | `Api\Arrays\ObjectToArrayConverterInterface::toArray()` |
| `ArrayHelper::htmlEncode($data, $valuesOnly, $charset)` | `Api\Arrays\ArrayHtmlEncoderInterface::htmlEncode($data, $valuesOnly)`, always UTF-8 |
| `ArrayHelper::htmlDecode($data, $valuesOnly)` | `Api\Arrays\ArrayHtmlEncoderInterface::htmlDecode()` |
| `ArrayHelper::isAssociative()`, `isIndexed()`, `isIn()`, `isTraversable()`, `isSubset()` | the same methods of `Api\Arrays\ArrayInspectorInterface` |

### Html

| 2.x | 3.0 |
|-----|-----|
| `Html::encode($content)` | `Api\Html\HtmlEncoderInterface::encode()` |
| `Html::encode($content, false)` | `Api\Html\HtmlEncoderInterface::encodeKeepingEntities()` |
| `Html::decode($content)` | `Api\Html\HtmlEncoderInterface::decode()` |
| `Html::renderTagAttributes($attributes)` | `Api\Html\AttributeRendererInterface::render()` |
| `Html::tag()`, `beginTag()`, `endTag()` | the same methods of `Api\Html\TagRendererInterface`; the name is `?string`, `false` is no longer accepted |
| `Html::addCssClass(&$options, $class)` | `$options = Api\Html\CssOptionsInterface::addCssClass($options, $class)` |
| `Html::removeCssClass(&$options, $class)` | `$options = Api\Html\CssOptionsInterface::removeCssClass($options, $class)` |
| `Html::addCssStyle(&$options, $style, $overwrite)` | `$options = Api\Html\CssOptionsInterface::addCssStyle($options, $style, $overwrite)` |
| `Html::removeCssStyle(&$options, $properties)` | `$options = Api\Html\CssOptionsInterface::removeCssStyle($options, $properties)` |
| `Html::cssStyleFromArray()`, `cssStyleToArray()` | the same methods of `Api\Html\CssOptionsInterface` |
| `Html::getAttributeName()`, `getInputIdByName()` | the same methods of `Api\Html\InputNameResolverInterface` |
| `Html::escapeJsRegularExpression($regexp)` | `Api\Html\JsRegexConverterInterface::convert()` |
| `Html::style()`, `script()`, `cssFile()`, `jsFile()` | the same methods of `Api\Html\ResourceTagRendererInterface` |
| `Html::a()`, `mailto()`, `img()` | the same methods of `Api\Html\LinkRendererInterface` |
| `Html::beginForm()`, `endForm()`, `label()`, `button()`, `submitButton()`, `resetButton()`, `textarea()` | the same methods of `Api\Html\FormRendererInterface` |
| `Html::input()`, `buttonInput()`, `submitInput()`, `resetInput()`, `textInput()`, `hiddenInput()`, `passwordInput()`, `fileInput()`, `radio()`, `checkbox()` | the same methods of `Api\Html\InputRendererInterface` |
| `Html::dropDownList()`, `listBox()`, `checkboxList()`, `radioList()` | the same methods of `Api\Html\ChoiceListRendererInterface` |
| `Html::renderSelectOptions($selection, $items, &$tagOptions)` | `Api\Html\ChoiceListRendererInterface::renderSelectOptions()`, which leaves its options alone: a caller rendering its own `<select>` strips `ChoiceListRendererInterface::SELECT_OPTION_KEYS` from them |
| `Html::ul()`, `ol()` | the same methods of `Api\Html\ListRendererInterface`; the items are `iterable`, so `null` is a `TypeError` |
| `Html::$attributeOrder`, `Html::$dataAttributes` | the `attributeOrder` and `dataAttributes` arguments of `Model\Html\AttributeRenderer` in `di.xml` |
| `Html::$normalizeClassAttribute` | the `normalizeClassAttribute` argument of both `Model\Html\AttributeRenderer` and `Model\Html\CssOptions`, with the same value |
| `Html::$voidElements` | the `voidElements` argument of `Model\Html\TagRenderer` in `di.xml` |
| `Html::$attributeRegex` | the `attributeRegex` argument of `Model\Html\InputNameResolver` in `di.xml` |

The services declare `strict_types`, so an `item` callback of a list receives the raw key: an `int` for a numeric key.
A callback that types the key `string` has to be widened.

## Requirements

- PHP >= 8.1 with the tokenizer extension
- Magento 2.x (2.4+ recommended)

## License

This module contains code from two sources with dual licensing:

### Yii2 Framework Code (BSD-3-Clause License)

The array, HTML, JSON and dump services (`Model/Arrays`, `Model/Html`, `Model/Json`, `Model/Debug` and their interfaces) are derived from the [Yii2 Framework](https://www.yiiframework.com/) and are licensed under the BSD-3-Clause License.

- **Copyright**: © 2008 Yii Software LLC
- **License**: BSD-3-Clause
- **License File**: See [LICENSE-YII](LICENSE-YII) for the full license text
- **Original Source**: https://github.com/yiisoft/yii2

### Magento 2 Modifications (MIT License)

Modifications, adaptations for Magento 2 compatibility, and original features (`ViewModelRegistry`, `ViewModelCacheTags`, layout debugging, etc.) are licensed under the MIT License.

- **Copyright**: © 2019-2024 Volodymyr Hryvinskyi
- **License**: MIT
- **License File**: See [LICENSE](LICENSE) for the full license text

### Usage Rights

You are free to use, modify, and distribute this module under the terms of both licenses. The BSD-3-Clause license applies to the Yii2-derived code, and the MIT license applies to the Magento 2 adaptations and original features.

[![FOSSA Status](https://app.fossa.com/api/projects/git%2Bgithub.com%2Fhryvinskyi%2Fmagento2-base.svg?type=large)](https://app.fossa.com/projects/git%2Bgithub.com%2Fhryvinskyi%2Fmagento2-base?ref=badge_large)
