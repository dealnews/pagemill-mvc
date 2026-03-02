# AI Coding Assistant Context

This file provides essential context for AI coding assistants working with the PageMill MVC codebase. Read this before making changes or answering questions about the code.

## Project Overview

PageMill MVC is a lightweight PHP MVC framework (16 source files, ~2000 LOC). It provides:

- **MVC architecture** - Controllers orchestrate, Models fetch data, Views render output
- **Asset management** - CSS/JS handling with combining, cache busting, and auto-loading from UI components
- **Content negotiation** - Automatic format detection (HTML/JSON/XML) based on Accept headers
- **Component system** - Reusable UI elements with automatic asset injection
- **Type-safe property mapping** - Array-to-object mapping with validation

**Primary use case:** Building web applications and APIs that need clean separation of concerns without framework bloat.

**Key differentiators:** Fully type-hinted, extensively tested (368 tests, 100% file coverage), modern PHP 8.2+, no magic—explicit and predictable.

## Architecture & Design

### Component Structure

```
Request → Controller → Actions → Models → Responder → View → Response
```

**Flow:**
1. **Controller** receives request, filters input, coordinates workflow
2. **Request Actions** execute first (validation, authorization) - can abort flow
3. **Models** fetch data from database/API
4. **Data Actions** transform/enrich data after models
5. **Responder** negotiates content type, selects appropriate View
6. **View** generates output (HTML/JSON/XML/etc.)

### Directory Layout

```
src/
├── ActionAbstract.php          # Business logic container
├── ControllerAbstract.php      # Request orchestrator
├── ModelAbstract.php           # Data fetcher
├── ResponderAbstract.php       # Content negotiator
├── ViewAbstract.php            # Output generator base
├── ElementAbstract.php         # UI component base
├── HTMLElement.php             # Concrete HTML element
├── Environment.php             # Global config (debug mode)
├── HTML/
│   ├── Document.php            # Metadata manager (title, meta tags)
│   └── Assets/
│       ├── Assets.php          # CSS/JS manager (762 lines, most complex)
│       ├── Combine.php         # Asset combination handler
│       ├── Injector.php        # Auto-loads assets from Elements
│       └── Exception.php       # Asset-specific exception
├── Template/
│   └── HTMLAbstract.php        # HTML view base (has prepareDocument flow)
├── View/
│   └── JSONAbstract.php        # JSON view base (auto-encodes getData())
└── Traits/
    └── PropertyMap.php         # Type-safe array→object mapper
```

### Key Design Patterns

**Separation of Concerns:**
- Controllers never fetch data directly—they build Models
- Actions contain all business logic—Controllers just orchestrate
- Views never fetch data—they receive it via constructor
- Models never validate—that's Actions' job

**Property Mapping (used everywhere):**
```php
// Data arrays are mapped to typed properties automatically
class MyModel extends ModelAbstract {
    public int $user_id = 0;  // Maps from ['user_id' => 123]
    public string $name = ''; // Maps from ['name' => 'John']
}
```

**Asset Management Pattern:**
```php
// 1. Assets registered in prepareDocument()
protected function prepareDocument(): void {
    $this->assets->add('css', ['main', 'components']);
    $this->element_assets->add([ButtonElement::class]); // Auto-loads button.css
}

// 2. Output in generateHeader()/generateFooter()
$this->assets->link('css');  // <link rel="stylesheet" href="/css/main.css">
$this->assets->link('js', 'footer'); // Group-based output
```

## Coding Standards

We follow DealNews PHP conventions. These differ from PSR-12 in specific ways:

### Bracing Style (1TBS)

```php
// ✅ CORRECT - Opening brace on same line (1TBS)
if ($condition) {
    doSomething();
}

// ❌ WRONG - Opening brace on new line (Allman style)
if ($condition)
{
    doSomething();
}
```

### Variable Naming

```php
// ✅ CORRECT - snake_case for variables and properties
protected string $user_name = '';
public int $product_id = 0;
protected array $asset_locations = [];

// ❌ WRONG - camelCase
protected string $userName = '';
public int $productId = 0;
```

### Visibility Defaults

```php
// ✅ CORRECT - Protected by default, public only when necessary
class MyClass {
    protected string $internal_data = '';  // Not part of public API
    public string $user_name = '';         // Explicitly public (PropertyMap needs this)
    
    protected function helper(): void {}   // Internal only
    public function publicAPI(): void {}   // Part of contract
}
```

### Return Points

```php
// ✅ PREFERRED - Single return point
protected function calculate(int $value): int {
    $result = 0;
    
    if ($value > 0) {
        $result = $value * 2;
    } else {
        $result = 0;
    }
    
    return $result;
}

// ⚠️ ACCEPTABLE - Early returns for error conditions only
protected function validate(array $data): bool {
    if (empty($data)) {
        return false;  // Guard clause OK
    }
    
    // ... rest of logic
    return $result;
}
```

### Class-Based API

```php
// ✅ CORRECT - Everything is a class method
class Assets {
    public function add(string $type, array $assets): void {}
}

// ❌ WRONG - No bare functions in this codebase
function add_asset(string $type, array $assets): void {}
```

### PHPDoc Coverage

**Every public method and property must have complete PHPDoc:**

```php
/**
 * Adds assets to the collection
 *
 * Registers assets for later output. Assets are organized by type and group,
 * allowing fine-grained control over loading order and location.
 *
 * @param string $type Asset type (css, js, or custom)
 * @param array<int, string> $assets List of asset filenames
 * @param string $group Group name for organizational purposes
 * @return void
 * @throws \LogicException If asset type has no templates defined
 */
public function add(string $type, array $assets, string $group = 'default'): void {
```

**What to include:**
- Brief one-line summary
- Detailed description with context (optional for simple getters/setters)
- Common use cases or examples in description
- All `@param` with types and descriptions
- `@return` with type and description
- `@throws` for any exceptions
- Array shapes with `array<key, value>` notation
- `@var` for all properties

### Type Hints & Strict Types

```php
// ✅ Every file starts with this
declare(strict_types=1);

// ✅ All parameters and returns are typed
public function calculate(int $value, string $name): array {
    return ['result' => $value];
}

// ✅ Properties are typed
protected int $count = 0;
protected ?string $optional_name = null;  // Nullable when needed
```

## Build & Test

### Running Tests

```bash
# Full suite (368 tests, ~1 second)
./vendor/bin/phpunit

# Specific test file
./vendor/bin/phpunit tests/HTML/AssetsTest.php

# Readable output
./vendor/bin/phpunit --testdox

# With coverage (requires Xdebug)
./vendor/bin/phpunit --coverage-html coverage/
```

### Test Organization

- One test file per source file (e.g., `Assets.php` → `AssetsTest.php`)
- Test files mirror src/ structure: `src/HTML/Assets.php` → `tests/HTML/AssetsTest.php`
- All tests use namespace `PageMill\MVC\Tests\*`
- Mock classes at bottom of test file (e.g., `ConcreteView extends ViewAbstract`)

### What Gets Tested

**Unit tests verify:**
- Constructor behavior (property mapping, dependency injection)
- Method return values and side effects
- Error conditions and exceptions
- Type validation (PropertyMap trait)
- Abstract method requirements
- Edge cases (empty arrays, null values, zero values)
- Multiple instances maintain separate state

**Integration tests verify:**
- Method call order (prepareDocument → generateHeader → generateBody → generateFooter)
- Data flow between components
- Mock expectations (e.g., HTTP headers sent)

### Coverage

Current: **100% file coverage** (16/16 files), **~90% line coverage**, **5 skipped tests**

Skipped tests are documented with reasons:
- `ControllerAbstract`: 3 tests require full HTTP stack
- `Combine`: 1 test calls `exit()`, needs process isolation
- `Assets`: 1 test for custom handlers is complex integration

## Common Patterns

### PropertyMap Trait Usage

Used by: ActionAbstract, ControllerAbstract, ElementAbstract, ModelAbstract, ViewAbstract

```php
// In constructor, maps array to properties
public function __construct(array $data) {
    $this->mapProperties($data);          // Strict - throws on unknown
    $this->mapProperties($data, true);    // Ignore mode - skips unknown
}

// Type validation happens automatically
public string $name = '';               // Empty string = type is "string"
public int $age = 0;                    // Zero = type is "integer"
public ?string $email = null;           // Null = no type checking by trait (but PHP enforces)

// Optional: Define constraints for stricter control
protected static array $constraints = [
    'email' => ['type' => 'string'],
    'age' => ['type' => 'integer']
];
```

### Abstract Method Pattern

All abstract classes follow this pattern:

```php
abstract class SomeAbstract {
    
    // Constructor does setup
    public function __construct(array $data) {
        $this->mapProperties($data, true);
    }
    
    // Public method orchestrates
    public function mainMethod(): void {
        $this->stepOne();     // Calls abstract methods
        $this->stepTwo();     // in defined order
    }
    
    // Child classes implement these
    abstract protected function stepOne(): void;
    abstract protected function stepTwo(): void;
}
```

### Singleton Pattern for Managers

Assets and Document use singletons:

```php
// Get singleton instance
$assets = Assets::init();
$document = Document::init();

// Or inject custom instance (for testing)
$view = new MyView($data, $inputs, $response, $custom_assets);
```

### Asset Naming Convention

**Important:** Asset names do NOT include file extensions. The class adds them.

```php
// ✅ CORRECT
$assets->add('css', ['main', 'components']);  // Loads main.css, components.css

// ❌ WRONG
$assets->add('css', ['main.css', 'components.css']);  // Looks for main.css.css
```

### Error Handling in Actions

```php
class ValidateAction extends ActionAbstract {
    
    protected function doAction(array $data): mixed {
        if (!$this->isValid()) {
            $this->errors[] = 'Validation failed';
            return null;  // Signal failure
        }
        
        return ['validated' => true];  // Signal success with data
    }
}

// Controllers check for errors
if (!empty($this->inputs['_errors'])) {
    // Handle error case
}
```

## Testing Strategy

### Test File Structure

```php
declare(strict_types=1);

namespace PageMill\MVC\Tests\Namespace;

use PHPUnit\Framework\TestCase;

/**
 * Tests for ClassName
 *
 * Brief description of what's being tested
 */
class ClassNameTest extends TestCase {
    
    /**
     * Test specific behavior in detail
     */
    public function testSpecificBehavior(): void {
        // Arrange
        $obj = new ClassName(['param' => 'value']);
        
        // Act
        $result = $obj->method();
        
        // Assert
        $this->assertEquals('expected', $result);
    }
}

// Mock/test double classes at bottom
class ConcreteClassName extends AbstractClass {
    // Minimal implementation for testing
}
```

### Mocking Strategy

**Mock external dependencies:**
```php
// ✅ Mock HTTP objects (external to MVC framework)
$response = $this->createMock(Response::class);
$request = $this->createMock(Request::class);

// ✅ Mock framework classes when testing integration
$assets = $this->createMock(Assets::class);
$assets->expects($this->once())->method('add')->with('css', ['test']);
```

**Use real instances for:**
- Classes under test
- Simple data structures
- Framework components being tested together

### Common Test Patterns

**Testing abstract classes:**
```php
// Create concrete implementation at bottom of test file
$concrete = new ConcreteTestClass($data);
$result = $concrete->publicMethod();
$this->assertEquals('expected', $result);
```

**Testing method call order:**
```php
class OrderTracker extends AbstractClass {
    private array $calls = [];
    
    protected function stepOne(): void {
        $this->calls[] = 'stepOne';
    }
    
    public function getCalls(): array {
        return $this->calls;
    }
}

$tracker = new OrderTracker();
$tracker->generate();
$this->assertEquals(['stepOne', 'stepTwo'], $tracker->getCalls());
```

**Testing output:**
```php
ob_start();
$view->generate();
$output = ob_get_clean();
$this->assertStringContainsString('expected', $output);
```

## Gotchas & Edge Cases

### Asset Names Don't Include Extensions

The Assets class automatically appends `.css` or `.js` based on type:

```php
$assets->add('css', ['main']);        // ✅ Loads main.css
$assets->add('css', ['main.css']);    // ❌ Looks for main.css.css
```

**Why:** Allows same asset name across types: `['button']` can be both button.css and button.js.

### PropertyMap Type Checking is Runtime Only

The trait checks types at runtime, but doesn't override PHP's native type system:

```php
public string $name = '';

// Trait checks: "Is value a string?" ✅
// PHP checks: "Is value a string?" ✅
$this->mapProperties(['name' => 'John']);

// Trait checks: "Property is null, skip check" ✅
// PHP checks: "Not a string!" ❌ TypeError
public string $name = '';
$this->mapProperties(['name' => null]);  // Fails PHP type check

// Fix: Use nullable type
public ?string $name = null;
```

### Template Method Pattern Call Order Matters

`HTMLAbstract::generate()` calls methods in specific order:

```php
public function generate(): void {
    $this->prepareDocument();        // 1. Setup (add assets, set title)
    $this->document->generateHeaders(); // 2. Send HTTP headers
    $this->generateHeader();         // 3. Output HTML header
    $this->generateBody();           // 4. Output content
    $this->generateFooter();         // 5. Output footer
}
```

**Don't output in prepareDocument()** - HTTP headers haven't been sent yet.

### Assets Singleton State Persists

`Assets::init()` returns the same instance every time:

```php
$assets1 = Assets::init();
$assets1->add('css', ['main']);

$assets2 = Assets::init();
// $assets2 has the same 'main' asset - same instance!

// For tests: inject fresh instance
$view = new View($data, $inputs, $response, new Assets());
```

### Array Merging is Recursive

PropertyMap merges arrays recursively:

```php
public array $config = ['theme' => 'dark', 'lang' => 'en'];

$this->mapProperties(['config' => ['theme' => 'light']]);

// Result: ['theme' => 'light', 'lang' => 'en']  // Merged!
// Not:    ['theme' => 'light']                    // Not replaced
```

### Combine Class Calls exit()

`HTML\Assets\Combine::combine()` calls `exit()` for 304 and 404 responses:

```php
// This terminates the script
$combine->combine('css');  // May call exit()

// Can't test without process isolation
// Test is marked as skipped with reason documented
```

## Making Changes

### Adding a New Feature

**1. Write tests first (TDD approach):**
```bash
# Create test file
touch tests/NewFeatureTest.php

# Write failing tests
./vendor/bin/phpunit tests/NewFeatureTest.php
```

**2. Implement the feature:**
- Follow coding standards above (1TBS, snake_case, protected default)
- Add complete PHPDoc blocks
- Use strict types: `declare(strict_types=1);`
- Type hint everything: parameters, returns, properties

**3. Run full test suite:**
```bash
./vendor/bin/phpunit
```

**4. Update documentation:**
- Add to README.md if public API
- Update PHPDoc blocks
- Add code examples to tests

### Fixing a Bug

**1. Write a failing test that reproduces the bug:**
```php
public function testBugReproduction(): void {
    $obj = new ClassWithBug();
    $result = $obj->buggyMethod();
    $this->assertEquals('expected', $result);  // Fails
}
```

**2. Fix the bug:**
- Minimal change to fix issue
- Don't refactor unrelated code
- Maintain backward compatibility if possible

**3. Verify fix:**
```bash
./vendor/bin/phpunit tests/ClassWithBugTest.php
```

**4. Check for regressions:**
```bash
./vendor/bin/phpunit  # Full suite
```

### Modifying Existing Code

**Before changing:**
1. Check if tests exist: `ls tests/*NameTest.php`
2. Run existing tests: `./vendor/bin/phpunit tests/NameTest.php`
3. Understand what tests verify

**While changing:**
1. Keep tests passing (green → red → green)
2. Update tests if behavior changes intentionally
3. Add tests for new code paths

**After changing:**
1. Run full suite: `./vendor/bin/phpunit`
2. Update PHPDoc if signature changed
3. Update README.md if public API changed

### Adding a New Abstract Class

Follow the established pattern:

```php
declare(strict_types=1);

namespace PageMill\MVC;

use PageMill\MVC\Traits\PropertyMap;

/**
 * Brief description
 *
 * Longer description explaining purpose, use cases, and patterns.
 *
 * @author      Your Name <email@example.com>
 * @copyright   1997-Present DealNews.com, Inc
 * @package     PageMill\MVC
 */
abstract class NewAbstract {
    use PropertyMap;
    
    /**
     * Property description
     *
     * @var type
     */
    protected $property = default;
    
    /**
     * Constructor description
     *
     * @param array<string, mixed> $data Description
     */
    public function __construct(array $data) {
        $this->mapProperties($data, true);
    }
    
    /**
     * Public orchestration method
     *
     * @return void
     */
    public function publicMethod(): void {
        $this->abstractMethod();
    }
    
    /**
     * Child classes must implement
     *
     * @return void
     */
    abstract protected function abstractMethod(): void;
}
```

### Namespace and Autoloading

- All classes are in `PageMill\MVC\*` namespace
- PSR-4 autoloading: `PageMill\MVC\HTML\Assets` → `src/HTML/Assets.php`
- Tests mirror structure: `PageMill\MVC\Tests\HTML\AssetsTest`
- No need to require files manually - Composer handles it

### When in Doubt

1. **Look at existing code** - Consistency is key
2. **Check the tests** - They show intended usage
3. **Read PHPDoc blocks** - They explain the "why"
4. **Run tests early and often** - Fast feedback loop
5. **Keep changes small** - Easier to review and test

---

**Remember:** This codebase values explicitness over magic, predictability over cleverness, and tests over documentation. When both approaches work, choose the one that's easier to test and understand six months from now.
