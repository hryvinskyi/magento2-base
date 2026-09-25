# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
