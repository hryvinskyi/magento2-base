# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.0] - 2026-11-07

The static helpers become injected services. See "Upgrading from 2.x" in the README for the table of every old call.

### Added
- Role interfaces under `Api/`, each `@api`, implemented under `Model/` and wired in `etc/di.xml`: `Api\Json`
  (`JsonEncoderInterface`, `JsonDecoderInterface`), `Api\Debug\VarDumperInterface`, `Api\Arrays`
  (`ArrayPathReaderInterface`, `ArrayPathWriterInterface`, `ArrayMergerInterface`, `ArrayIndexerInterface`,
  `ArraySorterInterface`, `ArrayInspectorInterface`, `ArrayFilterInterface`, `ObjectToArrayConverterInterface`,
  `ArrayHtmlEncoderInterface`), `Api\Html` (`HtmlEncoderInterface`, `AttributeRendererInterface`,
  `TagRendererInterface`, `CssOptionsInterface`, `InputNameResolverInterface`, `JsRegexConverterInterface`,
  `ResourceTagRendererInterface`, `LinkRendererInterface`, `FormRendererInterface`, `InputRendererInterface`,
  `ChoiceListRendererInterface`, `ListRendererInterface`), `Api\Data` (`ArrayableInterface`,
  `UnsetArrayValueInterface`, `ReplaceArrayValueInterface`) and `Api\LayoutDebugConfigInterface`.
- `Html::$attributeOrder`, `$dataAttributes`, `$normalizeClassAttribute`, `$voidElements` and `$attributeRegex` are
  required constructor arguments set in `etc/di.xml`, so a project changes them there. The classes hold no default:
  `etc/di.xml` is their one source, a mistyped argument fails in the container, and code building one of these classes
  by hand passes them.
- `conflict` with the released versions of head-tag-manager (<3.0), page-speed and page-speed-js-merge (<1.0.8) and
  error-reporting (<1.1), which call the removed statics.

### Removed
- `Helper\Html`: replaced by the `Api\Html` services.
- `Helper\ArrayHelper`: replaced by the `Api\Arrays` services.
- `Helper\Json`: replaced by `Api\Json\JsonEncoderInterface` and `JsonDecoderInterface`. `$prettyPrint` and
  `$jsonErrorMessages` are gone: pass `JSON_PRETTY_PRINT`, and read PHP's message on the exception.
- `Helper\VarDumper`: replaced by `Api\Debug\VarDumperInterface`. `dump()` is replaced by the global
  `dump()` and `dd()` of `symfony/var-dumper`, now a declared requirement, which work anywhere with no import.
- `Helper\Config`: replaced by `Api\LayoutDebugConfigInterface`.
- `Helper\Arrayable`: replaced by `Api\Data\ArrayableInterface`, with typed signatures.
- `Helper\ReplaceArrayValue`, `Helper\UnsetArrayValue`: replaced by `Api\Data\ReplaceArrayValueInterface` and
  `UnsetArrayValueInterface`, created by their generated factories, without `__set_state()`. A replacement carries its
  value through `withValue()`.
- `Helper\ConsoleHelper`, with no replacement.
- `Helper\InvalidParamException`, `Helper\InvalidValueException`: moved to `Exception\`.

### Changed
- `InvalidParamException` extends `\InvalidArgumentException` (it was a `\BadMethodCallException`);
  `InvalidValueException` still extends `\UnexpectedValueException`. Neither has `getName()` any more.
- No public method changes an argument by reference. `ArrayHelper::setValue()`, `remove()`, `removeValue()` and `multisort()`
  and the `Html` CSS option methods return the new value. What `remove()` returned is read first with a literal key
  read (`array_key_exists`, not `getValue()`, which falls through to a dot path); what `removeValue()` returned comes
  from `valuesIdenticalTo()`.
- `renderSelectOptions()` leaves its options alone: a caller rendering its own `<select>` strips
  `ChoiceListRendererInterface::SELECT_OPTION_KEYS`.
- `setValue()` has no `null` path, `htmlEncode()` has no charset, `index()` no longer accepts a lone Closure as
  `$groups`.
- `remove()` and `removeValue()` take an array: `null` is a `TypeError`, where 2.x left it alone and returned the
  default.
- `merge()` takes an array first and an array or object after it: a scalar or `null` is a `TypeError`, where 2.x
  skipped it.
- `Html::encode($x, false)` is `HtmlEncoderInterface::encodeKeepingEntities()`. Both encoders take what `Html::encode()`
  took: `string|int|float|bool|\Stringable|null`, cast to a string, so `null` gives `''`.
- `tag()`, `beginTag()` and `endTag()` take `?string`; `false` is no longer accepted. `ul()` and `ol()` take `iterable`,
  so `null` is a `TypeError`.
- `Json::decode()` takes `?string`; `keepObjectType` is a constructor argument of `Model\Json\JsonEncoder`.
- The highlighted dump no longer comes from `highlight_string()`: `<pre><code style="color: #000000">` with one
  coloured span per token, the same text, every token escaped.
- The services declare `strict_types`: an `item` callback receives the raw key, an `int` for a numeric key, so a
  callback typed `string` needs widening.
- Requires `magento/module-developer` and `magento/module-store`, which `Plugin\DebugHints` uses,
  `magento/module-page-cache`, which `Model\ViewModelRegistry` uses, `magento/module-config`, which
  `etc/adminhtml/system.xml` uses, and `ext-tokenizer`, which the closure export and the highlighted dump use; sequences
  `Magento_Config`, `Magento_Developer`, `Magento_PageCache` and `Magento_Store`.
- `Plugin\Layout` and `Plugin\DebugHints` take `Magento\Framework\App\RequestInterface` instead of the concrete HTTP
  request.
- `phpstan.neon.dist` analyses `Exception/` and holds the code to the PHP 8.1 floor (`phpVersion: 80100`).

### Fixed
- `LayoutDebugConfigInterface::isEnabledLayoutDebug()` also requires a visitor the developer restriction allows, so
  `?xml=1`, the recorded layout and the page cache skip follow the rule the template hints already had: on a
  developer-mode host, a visitor outside the allow-list no longer gets them.
- `Plugin\DebugHints` reads the current store only when it is about to show hints, not on every template engine
  creation.

## [2.3.1] - 2026-11-07

### Fixed
- `VarDumper::export()` of a multi-line closure no longer doubles every line break of its source.
- `VarDumper::dump()`/`dumpAsString()` with highlighting no longer print the `<?php` tag on PHP 8.3 and later.
- `ArrayHelper::filter()`: a rule whose path runs through a value that is not an array matches nothing, as
  documented, instead of throwing a `TypeError`; the same for `!` exclude rules.
- `ArrayHelper::toArray()`: a property mapping with an integer key and a Closure value stores the Closure's result
  instead of throwing.
- `Model\Layout\LayoutXml` starts without a layout (`null`) rather than relying on an untyped property, and
  `NoSetLayoutException` builds its default message with `__()`.

### Changed
- `Html` refuses a value it cannot print as text (an array, a resource, an object without `__toString()`) as a list
  item, a label, an `item` callback's result or an attribute value with `InvalidParamException`; such a value used
  to print as "Array" with a warning or end in an `Error`. A non-callable `item` option of `checkboxList()`,
  `radioList()` or `ul()` throws `InvalidParamException` instead of a `TypeError`. An option that must be an array
  (`labelOptions`, `itemOptions`, `groups`, an option's attributes, the prompt's options) is ignored when it is not.
- `ArrayHelper::index()` and `map()` reject a key or group value that is not a scalar or null with an explicit
  `TypeError` message; a float group key is cast to an integer without PHP 8.1's precision deprecation.
- `ArrayHelper::merge()` skips an argument that is neither an array nor an object (it used to warn), and throws
  `InvalidParamException` when a Traversable yields a key that is neither an integer nor a string.
- `ConsoleHelper::prompt()` no longer reads `UnsetArrayValue`/`ReplaceArrayValue` markers in its options;
  `ConsoleHelper::streamSupportsAnsiColors()` returns false for a closed stream instead of throwing.
- Every public method of the helpers carries exact phpdoc types (no native types were added, so callers and
  subclasses keep working), the Yii-only documentation (`Url::to()`, CSRF, `Yii::$app`) is gone, and
  `phpstan.neon.dist` covers the whole module at level 10. New unit tests cover `ConsoleHelper`, `Json`,
  `LayoutXml`, `ViewModelRegistry` and the secure HTML renderer.

## [2.3.0] - 2026-11-07

### Added
- Admin menu groups. The new `groups` layout argument declares titled groups (code → `label`, `sort_order`), and an
  item joins one with the new `group` key. Groups are listed by sort order, each under its heading, side by side;
  a group none of whose items the admin may see (denied by ACL or `is_active` false) is not shown. Items naming no
  group, or a group the menu does not declare, follow every group untitled; an undeclared code is logged as a
  warning on each page that renders the menu. A menu that declares no group, or whose items name none, renders
  the same single list as before.
- Columns for long groups: the new `max_items_per_column` layout argument (default 6) splits a longer group into
  even columns (13 items → 5, 4, 4). Under 768 px the groups and columns stack.
- `Api\Menu\MenuGroupInterface` and `Api\Menu\MenuGroupFactoryInterface` (creates a group from layout
  configuration; a group without a label, or with a value of the wrong type, is skipped and logged).
- `Api\Menu\GroupedMenuItemInterface` (`getGroup()`), implemented by the built-in menu item. `MenuItemInterface` is
  unchanged: existing implementations keep working and are listed ungrouped.
- Module `phpunit.xml.dist` (unit suite, with a bootstrap that generates `…Factory` stubs on demand) and
  `phpstan.neon.dist` (level 10, the menu code and its tests); integration tests rendering the menu through the
  real layout.

### Changed
- Menu items are created through Magento's generated factory instead of `new`; `MenuItemFactory` only validates the
  configuration. The item factory receives scalar values only and the route parameters reach the item after it is
  created. (The block's own layout `data`, `items` included, still goes through the object manager when the layout
  builds the block, as for every block.) `MenuItem` no longer takes `routeParams` in its constructor (use `withRouteParams()`) and takes
  `group` as its last parameter.
- An item with `is_active` false is left out by the block rather than by the template. `getMenuItems()` still returns
  the allowed items, inactive ones included; `hasItems()` now counts only the items shown, so a menu whose items
  are all inactive renders nothing instead of an empty dropdown.
- The block's constructor takes the group factory and the section arranger before `$data`. Neither the block nor
  `MenuItem` is `@api`: a subclass or a direct caller of either constructor must follow these signatures.
- A group code is letters, digits, `_` and `-`; any other code is rejected (the group is skipped and logged), so two
  codes can never give the same heading id.

### Removed
- A stylesheet rule that could never match (the menu is not inside the toggle button).

### Fixed
- `Html::renderTagAttributes()` (and every tag `Html` builds) again leaves out an attribute whose value is `null`, as
  documented. Since 2.1.11 such an attribute was printed bare: `Html::textInput('q')` gave
  `<input type="text" name="q" value />` and `Html::label('Name')` gave `<label for>`. Use `true` for a boolean
  attribute.
- The `Html`, `ArrayHelper` and `VarDumper` unit tests run on PHPUnit 10 and assert this module's own output (void
  elements closed with ` />`, `beginForm()` printing the method as given, `a()` printing the URL as given).

## [2.2.0] - 2026-09-25

### Added
- Admin menu: per-item `resource` key. An item whose ACL resource the current admin user is not allowed is not
  rendered; items without `resource` behave as before. `hasItems()` counts only the items left after the check, so
  a menu with no permitted item renders nothing.
- `MenuItemInterface::getResource(): ?string`. Custom implementations of the interface must add it.

### Changed
- Admin menu items are read with their types checked. An item holding a value of the wrong type is skipped and
  logged as a warning instead of failing with a `TypeError`. `MenuItemFactoryInterface::create()` documents the
  `\InvalidArgumentException` it throws for such an item.
- Boolean item flags (`is_active`, the values of a `class` map) accept `"true"`/`"false"`/`"1"`/`"0"`; the string
  `"false"` no longer counts as true.
- `composer.json` declares `php >=8.1` (the code already needs readonly properties), `magento/framework ^103.0`
  and `magento/module-backend ^102.0`; `module.xml` sequences `Magento_Backend`.

### Fixed
- `menu.phtml`: the inline style tag is printed through an explicit `@noEscape` echo.
- `AddMenuHandleToConfigPage` reads the event's `full_action_name` and `layout` with their types checked.

## [2.1.12] - 2026-06-04

### Fixed
- PHP 8.5 compatibility: use explicit nullable type for the `$block` parameter in `ViewModelRegistry::require()` (`AbstractBlock $block = null` → `?AbstractBlock $block = null`). Implicitly marking a typed parameter as nullable is deprecated since PHP 8.4 and emits a deprecation notice on PHP 8.5.

## [2.1.7] - 2026-02-04

### Fixed
- Renamed `$class` parameter to `$cssClass` in `MenuItem` constructor to avoid PHP reserved keyword conflict that caused DI compilation failure

## [2.1.6] - 2026-02-02

### Added
- **Admin Menu Component** - A reusable dropdown menu block for Magento admin pages
  - `Hryvinskyi\Base\Block\Adminhtml\Menu` - Block class with layout XML configuration support
  - `Hryvinskyi\Base\Api\Menu\MenuItemInterface` - Interface for menu items
  - `Hryvinskyi\Base\Api\Menu\MenuItemFactoryInterface` - Factory interface for creating menu items
  - `Hryvinskyi\Base\Model\Menu\MenuItem` - Menu item implementation with route-based URL generation
  - `Hryvinskyi\Base\Model\Menu\MenuItemFactory` - Factory for creating menu items from array configuration
  - `view/adminhtml/templates/menu.phtml` - Admin menu template
- Menu items support:
  - Route-based URL generation with parameters
  - Sortable items via `sort_order`
  - Custom CSS classes per item
  - SVG/HTML icons per item
  - Translatable labels
  - Active/inactive state

### Changed
- Admin CSS styles now include menu component styles in `view/adminhtml/web/css/styles.css`

## [2.1.5] - Previous Release

### Features
- ViewModel Registry (`$viewModels`) - Global access to view models in templates
- Layout Debugging tools for developer mode
- Yii2 Framework Helpers:
  - ArrayHelper - Array manipulation utilities
  - Html - HTML generation helper
  - Json - JSON encoding/decoding utilities
  - VarDumper - Variable dumping for debugging
- ConsoleHelper - ANSI terminal control for CLI commands
