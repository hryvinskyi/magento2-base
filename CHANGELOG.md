# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
