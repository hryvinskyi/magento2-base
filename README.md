# Magento 2 Base Module

A comprehensive Magento 2 base module providing:
- **Yii2 Framework Helpers**: Battle-tested utility classes for array manipulation, HTML generation, JSON handling, and debugging
- **ViewModel Registry**: Global access to view models in templates without layout XML configuration
- **Console Utilities**: ANSI terminal control, progress bars, and formatted CLI output
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
2. [Yii2 Framework Helpers](#yii2-framework-helper-classes)
   - [ArrayHelper](#arrayhelper)
   - [Html](#html)
   - [Json](#json)
   - [VarDumper](#vardumper)
3. [Console Utilities](#console-utilities-consolehelper)
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
The debugging information is controlled by `Hryvinskyi\Base\Helper\Config`:
- Only works in developer mode (`State::MODE_DEVELOPER`)
- Injected into responses via event observers
- No configuration needed - works out of the box

**What you get:**
- Block nesting and parent-child relationships
- Template file locations with full paths
- Block class names and cache keys
- Layout handle processing order
- Visual hierarchy with indentation

## Yii2 Framework Helper Classes

This module provides proven, battle-tested helper classes from the Yii2 framework, adapted for Magento 2.

### ArrayHelper

Comprehensive array manipulation utilities with 30+ methods for working with arrays and objects.

**Key Methods:**

| Method | Description |
|--------|-------------|
| `getValue($array, $key, $default)` | Retrieve nested values using dot notation (`user.address.street`) |
| `setValue(&$array, $key, $value)` | Set nested values using dot notation |
| `remove(&$array, $key, $default)` | Remove and return array element |
| `merge($a, $b)` | Recursively merge arrays with smart key handling |
| `index($array, $key)` | Index/group arrays by specified keys |
| `getColumn($array, $name)` | Extract column values from multidimensional arrays |
| `map($array, $from, $to, $group)` | Build key-value maps from arrays |
| `filter($array, $filters)` | Filter array using dot notation rules |
| `multisort(&$array, $key, $direction)` | Sort by multiple keys with different directions |
| `toArray($object, $properties, $recursive)` | Convert objects to arrays with property mapping |
| `isAssociative($array, $allStrings)` | Check if array is associative |
| `isIndexed($array, $consecutive)` | Check if array is indexed |
| `htmlEncode($data, $valuesOnly, $charset)` | Encode HTML entities recursively |
| `htmlDecode($data, $valuesOnly)` | Decode HTML entities recursively |
| `keyExists($key, $array, $caseSensitive)` | Check key existence with case options |
| `isIn($needle, $haystack, $strict)` | Check value membership |
| `isSubset($needles, $haystack, $strict)` | Check if all values exist |

**Advanced Features:**
- **UnsetArrayValue**: Mark array values for removal during merge
- **ReplaceArrayValue**: Force replacement instead of recursive merge

**Usage Examples:**

```php
use Hryvinskyi\Base\Helper\ArrayHelper;

// Dot notation access
$username = ArrayHelper::getValue($_POST, 'user.profile.username', 'guest');
ArrayHelper::setValue($config, 'database.host', 'localhost');

// Array merging with smart handling
$merged = ArrayHelper::merge(
    ['items' => ['apple', 'banana']],
    ['items' => ['cherry']]
);
// Result: ['items' => ['apple', 'banana', 'cherry']]

// Indexing and grouping
$users = [
    ['id' => 1, 'name' => 'John', 'role' => 'admin'],
    ['id' => 2, 'name' => 'Jane', 'role' => 'user'],
    ['id' => 3, 'name' => 'Bob', 'role' => 'admin'],
];

$indexed = ArrayHelper::index($users, 'id');
// Result: [1 => [...], 2 => [...], 3 => [...]]

$grouped = ArrayHelper::index($users, null, 'role');
// Result: ['admin' => [[...], [...]], 'user' => [[...]]]

// Column extraction
$ids = ArrayHelper::getColumn($users, 'id');
// Result: [1, 2, 3]

// Multi-key sorting
ArrayHelper::multisort($users, ['role', 'name'], [SORT_ASC, SORT_DESC]);

// Object conversion with custom property mapping
$array = ArrayHelper::toArray($object, [
    'id',
    'username' => 'name',
    'fullName' => function($obj) { return $obj->firstName . ' ' . $obj->lastName; }
]);

// Filtering with conditions
$filtered = ArrayHelper::filter($users, [
    'id' => [1, 2],  // Include only these IDs
    'role' => 'admin'  // Only admins
]);

// Map creation
$nameMap = ArrayHelper::map($users, 'id', 'name');
// Result: [1 => 'John', 2 => 'Jane', 3 => 'Bob']

// Force replacement in merge
use Hryvinskyi\Base\Helper\ReplaceArrayValue;
$merged = ArrayHelper::merge(
    ['items' => ['a', 'b']],
    ['items' => new ReplaceArrayValue(['c', 'd'])]
);
// Result: ['items' => ['c', 'd']] (replaced, not merged)
```

### Html

HTML generation helper with 40+ methods for programmatic HTML creation and manipulation.

**Element Generation:**
- `tag($name, $content, $options)` - Generate any HTML tag
- `a($text, $url, $options)` - Hyperlinks
- `mailto($text, $email, $options)` - Email links
- `img($src, $options)` - Images
- `label($content, $for, $options)` - Labels
- `button($content, $options)` - Buttons
- `submitButton($content, $options)` - Submit buttons
- `resetButton($content, $options)` - Reset buttons

**Form Generation:**
- `beginForm($action, $method, $formKey, $options)` - Form opening with CSRF
- `endForm()` - Form closing
- `input($type, $name, $value, $options)` - Generic input
- `textInput($name, $value, $options)` - Text input
- `hiddenInput($name, $value, $options)` - Hidden input
- `passwordInput($name, $value, $options)` - Password input
- `fileInput($name, $value, $options)` - File input
- `textarea($name, $value, $options)` - Textarea
- `radio($name, $checked, $options)` - Radio button
- `checkbox($name, $checked, $options)` - Checkbox

**Lists and Selections:**
- `dropDownList($name, $selection, $items, $options)` - Dropdown select
- `listBox($name, $selection, $items, $options)` - Multi-select list
- `checkboxList($name, $selection, $items, $options)` - Checkbox list
- `radioList($name, $selection, $items, $options)` - Radio button list
- `ul($items, $options)` - Unordered list
- `ol($items, $options)` - Ordered list

**Asset Tags:**
- `cssFile($url, $options)` - CSS link tag with IE conditional support
- `jsFile($url, $options)` - JavaScript script tag with IE conditional support

**CSS/Style Manipulation:**
- `addCssClass(&$options, $class)` - Add CSS class(es)
- `removeCssClass(&$options, $class)` - Remove CSS class(es)
- `addCssStyle(&$options, $style, $overwrite)` - Add inline styles
- `removeCssStyle(&$options, $properties)` - Remove inline styles

**Utilities:**
- `encode($content, $doubleEncode)` - HTML entity encoding
- `decode($content)` - HTML entity decoding
- `renderTagAttributes($attributes)` - Render attribute string with data-* support

**Usage Examples:**

```php
use Hryvinskyi\Base\Helper\Html;

// Generate links
echo Html::a('Visit site', 'https://example.com', ['class' => 'external-link', 'target' => '_blank']);
echo Html::mailto('Contact us', 'support@example.com');

// Create forms
echo Html::beginForm('/checkout/submit', 'post', $formKey, ['id' => 'checkout-form']);
echo Html::textInput('email', '', ['class' => 'form-control', 'required' => true]);
echo Html::passwordInput('password', '', ['class' => 'form-control']);
echo Html::textarea('comments', '', ['rows' => 5, 'class' => 'form-control']);
echo Html::submitButton('Submit Order', ['class' => 'btn btn-primary']);
echo Html::endForm();

// Dropdowns and lists
echo Html::dropDownList('country', 'US', [
    'US' => 'United States',
    'CA' => 'Canada',
    'UK' => 'United Kingdom'
], ['class' => 'country-select']);

// Checkbox/radio lists
echo Html::checkboxList('features', ['wifi', 'parking'], [
    'wifi' => 'WiFi',
    'parking' => 'Parking',
    'pool' => 'Swimming Pool'
]);

// Lists
echo Html::ul(['Apple', 'Banana', 'Cherry'], [
    'class' => 'fruit-list',
    'item' => function($item, $index) {
        return Html::tag('span', $item, ['data-index' => $index]);
    }
]);

// CSS class manipulation
$options = ['class' => 'btn'];
Html::addCssClass($options, 'btn-primary btn-lg');
Html::removeCssClass($options, 'btn-lg');
// Result: ['class' => 'btn btn-primary']

// Style manipulation
$options = [];
Html::addCssStyle($options, 'color: red');
Html::addCssStyle($options, ['font-size' => '14px', 'margin' => '10px']);
// Result: ['style' => 'color: red; font-size: 14px; margin: 10px;']

// Asset tags with IE conditional comments
echo Html::cssFile('/css/style.css', ['media' => 'screen']);
echo Html::cssFile('/css/ie8.css', ['condition' => 'lt IE 9']);
echo Html::jsFile('/js/script.js', ['async' => true]);

// Custom tags with data attributes
echo Html::tag('div', 'Content', [
    'class' => 'container',
    'data-module' => 'carousel',
    'data-options' => ['autoplay' => true, 'delay' => 3000]
]);
```

### Json

JSON encoding/decoding utilities with enhanced error handling and features beyond native PHP JSON functions.

**Features:**
- Enhanced error handling with descriptive messages
- Pretty print support via static property
- Object type preservation control
- HTML-safe encoding for embedding in attributes
- Circular reference detection

**Methods:**
- `encode($value, $options)` - Encode with error handling
- `htmlEncode($value)` - HTML-safe encoding (escapes quotes, tags, amp, apos)
- `decode($json, $asArray)` - Decode with error handling

**Properties:**
- `$prettyPrint` - Enable/disable pretty printing (null = use encode options)
- `$keepObjectType` - Avoid objects with zero-indexed keys being encoded as arrays

**Usage Examples:**

```php
use Hryvinskyi\Base\Helper\Json;

// Basic encoding
$json = Json::encode(['name' => 'John', 'age' => 30]);

// Pretty printing
Json::$prettyPrint = true;
$prettyJson = Json::encode(['name' => 'John', 'age' => 30]);
/*
{
    "name": "John",
    "age": 30
}
*/

// HTML-safe encoding for embedding in attributes
$safeJson = Json::htmlEncode(['alert' => '<script>alert("xss")</script>']);
echo '<div data-config="' . $safeJson . '">'; // Safely embedded

// Object type preservation
Json::$keepObjectType = true;
$json = Json::encode((object)['test']);
// Result: {"0":"test"} instead of ["test"]

// Decoding with error handling
try {
    $data = Json::decode($jsonString);
} catch (\Hryvinskyi\Base\Helper\InvalidParamException $e) {
    // Handle JSON decode error
    echo "Invalid JSON: " . $e->getMessage();
}

// Decode as object instead of array
$object = Json::decode($jsonString, false);
```

### VarDumper

Advanced variable dumping for debugging with syntax highlighting and circular reference handling.

**Features:**
- Enhanced var_dump with better formatting
- Handles circular references safely
- Configurable depth limits
- Syntax highlighting support
- Export variables as valid PHP code
- Support for complex objects and closures

**Methods:**
- `dump($var, $depth, $highlight)` - Display variable contents
- `dumpAsString($var, $depth, $highlight)` - Get dump as string
- `export($var)` - Export as executable PHP code
- `exportClosure($closure)` - Export closure source code

**Usage Examples:**

```php
use Hryvinskyi\Base\Helper\VarDumper;

// Basic dumping
VarDumper::dump($complexObject);

// With depth limit and syntax highlighting
VarDumper::dump($deeplyNestedArray, 10, true);

// Get dump as string for logging
$debugInfo = VarDumper::dumpAsString($data, 5);
$logger->debug($debugInfo);

// Export as PHP code
$code = VarDumper::export(['name' => 'John', 'items' => [1, 2, 3]]);
// Result: "['name' => 'John', 'items' => [1, 2, 3,],]"

// Export complex objects (uses serialize/unserialize)
$code = VarDumper::export($product);
// Result: "unserialize('...')"

// Export closures (extracts source code)
$closure = function($x) { return $x * 2; };
$code = VarDumper::export($closure);
// Result: "function($x) { return $x * 2; }"

// Handle circular references safely
$a = ['x' => 1];
$a['self'] = &$a;
VarDumper::dump($a); // Safely handles the circular reference
```

## Console Utilities (ConsoleHelper)

Comprehensive ANSI terminal control for CLI commands with 30+ methods for creating rich console applications.

**Categories:**
- **Cursor Control**: Move, position, show/hide cursor
- **Screen Control**: Clear screen, scroll, save/restore positions
- **Colors & Formatting**: Foreground/background colors, text styles
- **Input/Output**: Styled output, user input, prompts
- **Progress Bars**: Visual progress indicators with ETA
- **Utilities**: Screen size detection, text wrapping, platform detection

### Cursor Control

```php
use Hryvinskyi\Base\Helper\ConsoleHelper;

ConsoleHelper::moveCursorUp(2);           // Move cursor up 2 rows
ConsoleHelper::moveCursorDown(1);         // Move cursor down 1 row
ConsoleHelper::moveCursorForward(5);      // Move cursor right 5 columns
ConsoleHelper::moveCursorBackward(3);     // Move cursor left 3 columns
ConsoleHelper::moveCursorNextLine(2);     // Move to beginning of 2nd line down
ConsoleHelper::moveCursorPrevLine(1);     // Move to beginning of 1 line up
ConsoleHelper::moveCursorTo(10, 5);       // Move to column 10, row 5
ConsoleHelper::saveCursorPosition();      // Save current position
ConsoleHelper::restoreCursorPosition();   // Restore saved position
ConsoleHelper::hideCursor();              // Hide cursor
ConsoleHelper::showCursor();              // Show cursor
```

### Screen Control

```php
ConsoleHelper::clearScreen();             // Clear entire screen
ConsoleHelper::clearScreenBeforeCursor(); // Clear from cursor to top
ConsoleHelper::clearScreenAfterCursor();  // Clear from cursor to bottom
ConsoleHelper::clearLine();               // Clear current line
ConsoleHelper::clearLineBeforeCursor();   // Clear line before cursor
ConsoleHelper::clearLineAfterCursor();    // Clear line after cursor
ConsoleHelper::scrollUp(3);               // Scroll up 3 lines
ConsoleHelper::scrollDown(2);             // Scroll down 2 lines
```

### Colors & Formatting

**Color Constants:**
```php
// Foreground colors
ConsoleHelper::FG_BLACK, FG_RED, FG_GREEN, FG_YELLOW
ConsoleHelper::FG_BLUE, FG_PURPLE, FG_CYAN, FG_GREY

// Background colors
ConsoleHelper::BG_BLACK, BG_RED, BG_GREEN, BG_YELLOW
ConsoleHelper::BG_BLUE, BG_PURPLE, BG_CYAN, BG_GREY

// Text styles
ConsoleHelper::BOLD, ITALIC, UNDERLINE, BLINK
ConsoleHelper::NEGATIVE, CONCEALED, CROSSED_OUT
ConsoleHelper::FRAMED, ENCIRCLED, OVERLINED
```

**Usage:**
```php
// Apply formatting
ConsoleHelper::beginAnsiFormat([ConsoleHelper::FG_RED, ConsoleHelper::BOLD]);
echo "Error message";
ConsoleHelper::endAnsiFormat();

// Format string
$formatted = ConsoleHelper::ansiFormat('Warning', [ConsoleHelper::FG_YELLOW, ConsoleHelper::BOLD]);

// xterm 256 colors
$fgColor = ConsoleHelper::xtermFgColor(208); // Orange
$bgColor = ConsoleHelper::xtermBgColor(235); // Dark gray
echo ConsoleHelper::ansiFormat('Text', [$fgColor, $bgColor]);

// Color codes in strings (irssi-style)
$text = ConsoleHelper::renderColoredString('%R[ERROR]%n %yWarning message', true);
// %R = red bold, %n = reset, %y = yellow

// Convert ANSI to HTML
$html = ConsoleHelper::ansiToHtml($ansiString);

// Strip ANSI codes
$plain = ConsoleHelper::stripAnsiFormat($ansiString);
$length = ConsoleHelper::ansiStrlen($ansiString); // Length without codes
```

### Input/Output

```php
// Output
ConsoleHelper::stdout("Message");          // Print to STDOUT
ConsoleHelper::stderr("Error");            // Print to STDERR
ConsoleHelper::output("Line");             // Print line to STDOUT
ConsoleHelper::error("Error");             // Print line to STDERR

// Input
$input = ConsoleHelper::stdin();           // Read from STDIN
$input = ConsoleHelper::input("Name: ");   // Prompt and read

// Prompt with validation
$email = ConsoleHelper::prompt("Email: ", [
    'required' => true,
    'pattern' => '/^[^@]+@[^@]+$/',
    'error' => 'Invalid email format'
]);

$age = ConsoleHelper::prompt("Age: ", [
    'default' => 18,
    'validator' => function($input, &$error) {
        if ($input < 18) {
            $error = "Must be 18 or older";
            return false;
        }
        return true;
    }
]);

// Confirmation
if (ConsoleHelper::confirm("Delete all files?", false)) {
    // User confirmed
}

// Selection menu
$choice = ConsoleHelper::select("Choose action:", [
    'c' => 'Create',
    'r' => 'Read',
    'u' => 'Update',
    'd' => 'Delete'
]);
```

### Progress Bars

```php
// Simple progress bar
ConsoleHelper::startProgress(0, 1000);
for ($i = 1; $i <= 1000; $i++) {
    usleep(1000);
    ConsoleHelper::updateProgress($i, 1000);
}
ConsoleHelper::endProgress();

// With prefix
ConsoleHelper::startProgress(0, 100, 'Processing: ');
// ... update progress ...
ConsoleHelper::endProgress("Done!\n");

// Git-style (status only, no bar)
ConsoleHelper::startProgress(0, 1000, 'Counting objects: ', false);
// ... update progress ...
ConsoleHelper::endProgress("done.\n");

// Custom width
ConsoleHelper::startProgress(0, 100, '', 0.5); // 50% of screen width
ConsoleHelper::startProgress(0, 100, '', 80);  // 80 characters wide
```

### Utilities

```php
// Platform detection
if (ConsoleHelper::isRunningOnWindows()) {
    // Windows-specific code
}

// ANSI support detection
if (ConsoleHelper::streamSupportsAnsiColors(STDOUT)) {
    // Use colored output
}

// Screen size
list($width, $height) = ConsoleHelper::getScreenSize();
list($width, $height) = ConsoleHelper::getScreenSize(true); // Force refresh

// Text wrapping with indentation
$wrapped = ConsoleHelper::wrapText($longText, 4);
/*
Lorem ipsum
    dolor sit
    amet.
*/

// Escape color codes
$escaped = ConsoleHelper::escape("String with %y color codes");
```

### Complete CLI Example

```php
use Hryvinskyi\Base\Helper\ConsoleHelper;

class MyCommand extends \Symfony\Component\Console\Command\Command
{
    protected function execute($input, $output)
    {
        // Header
        ConsoleHelper::output(ConsoleHelper::ansiFormat('=== Deployment Script ===', [
            ConsoleHelper::FG_GREEN,
            ConsoleHelper::BOLD
        ]));

        // Confirmation
        if (!ConsoleHelper::confirm("Deploy to production?", false)) {
            ConsoleHelper::output(ConsoleHelper::ansiFormat('Cancelled', [ConsoleHelper::FG_YELLOW]));
            return 0;
        }

        // Progress
        $tasks = ['compile', 'test', 'deploy', 'cleanup'];
        ConsoleHelper::startProgress(0, count($tasks), 'Progress: ');

        foreach ($tasks as $i => $task) {
            sleep(1);
            ConsoleHelper::updateProgress($i + 1, count($tasks));
        }

        ConsoleHelper::endProgress(ConsoleHelper::ansiFormat('✓ Completed!', [
            ConsoleHelper::FG_GREEN
        ]) . "\n");

        return 0;
    }
}
```

## Requirements

- PHP >= 8.0
- Magento 2.x (2.4+ recommended)

## License

This module contains code from two sources with dual licensing:

### Yii2 Framework Helpers (BSD-3-Clause License)

The helper classes (`ArrayHelper`, `Html`, `Json`, `VarDumper`, `ConsoleHelper`) are derived from the [Yii2 Framework](https://www.yiiframework.com/) and are licensed under the BSD-3-Clause License.

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
