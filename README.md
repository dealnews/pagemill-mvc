# PageMill MVC

[![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-BSD--3--Clause-green)](LICENSE)
[![Tests](https://img.shields.io/badge/tests-368%20passing-brightgreen)](tests/)
[![Coverage](https://img.shields.io/badge/coverage-100%25%20files-brightgreen)](tests/README.md)

A lightweight, modern PHP MVC framework focused on developer experience and clean architecture. PageMill MVC provides the essential building blocks for web applications without the bloat, making it perfect for developers who want control without complexity.

**Why PageMill MVC?** Built for modern PHP (8.2+), fully type-hinted, extensively tested (368 tests, 100% file coverage), and designed around proven patterns. It's the MVC framework that gets out of your way and lets you build.

## Features

- ✅ **Full MVC Architecture** - Complete separation of concerns with Controllers, Models, Views, and Actions
- 🎨 **Asset Management** - Built-in CSS/JS asset handling with combining, minification, and cache busting
- 📄 **Multiple Response Types** - HTML templates, JSON APIs, and custom formats out of the box
- 🔧 **Component System** - Reusable UI elements with automatic asset injection
- 🎯 **Content Negotiation** - Automatic format detection and response generation based on Accept headers
- 📦 **Property Mapping** - Type-safe array-to-object mapping with validation
- ⚡ **Lightweight** - No bloat, just the essentials you need
- 🧪 **Fully Tested** - 368 tests with comprehensive coverage
- 🔍 **Modern PHP** - Requires PHP 8.2+, fully type-hinted with strict types
- 📚 **Well Documented** - Complete PHPDoc blocks and extensive test examples

## Requirements

- PHP 8.2 or higher
- Composer

## Installation

Install via Composer:

```bash
composer require pagemill/mvc
```

The framework will automatically install its dependencies:
- `pagemill/http` - HTTP request/response handling
- `dealnews/filter` - Input filtering and validation
- `pagemill/accept` - Content-type negotiation

## Quick Start

Here's a minimal example to get you up and running in under 5 minutes:

### 1. Create a Simple Controller

```php
<?php
namespace MyApp;

use PageMill\MVC\ControllerAbstract;

class HomeController extends ControllerAbstract {
    
    protected function getRequestActions(): array {
        return []; // No actions needed for this simple example
    }
    
    protected function getDataActions(): array {
        return []; // No data actions needed
    }
    
    protected function buildModels(): array {
        // Return data to be passed to the view
        return [
            'title' => 'Welcome to PageMill MVC',
            'message' => 'Hello, World!'
        ];
    }
}
```

### 2. Create a View

```php
<?php
namespace MyApp;

use PageMill\MVC\Template\HTMLAbstract;

class HomeView extends HTMLAbstract {
    
    public string $title = '';
    public string $message = '';
    
    protected function prepareDocument(): void {
        $this->document->title = $this->title;
    }
    
    protected function generateHeader(): void {
        echo '<!DOCTYPE html><html><head>';
        echo $this->document->generateHead();
        echo '</head><body>';
    }
    
    protected function generateBody(): void {
        echo '<h1>' . htmlspecialchars($this->title) . '</h1>';
        echo '<p>' . htmlspecialchars($this->message) . '</p>';
    }
    
    protected function generateFooter(): void {
        echo '</body></html>';
    }
}
```

### 3. Create a Responder

```php
<?php
namespace MyApp;

use PageMill\MVC\ResponderAbstract;

class HomeResponder extends ResponderAbstract {
    
    protected function getView(string $content_type): string {
        return HomeView::class;
    }
}
```

### 4. Wire It Up

```php
<?php
require 'vendor/autoload.php';

use PageMill\HTTP\Request;
use PageMill\HTTP\Response;
use MyApp\HomeController;
use MyApp\HomeResponder;

$request = new Request();
$response = new Response();

$controller = new HomeController('/', [], $request);
[$data, $inputs] = $controller->handleRequest();

$responder = new HomeResponder($response);
$responder->respond($data, $inputs);
```

That's it! You now have a working MVC application.

## Core Concepts

PageMill MVC follows a request/response flow through four main components:

```
Request → Controller → Responder → View → Response
              ↓
           Actions
              ↓
           Models
```

### Controllers

Controllers orchestrate the request handling. They:
- Filter and validate input
- Execute actions (validation, business logic)
- Build models (fetch data)
- Return data to responders

```php
class ProductController extends ControllerAbstract {
    
    // Filters clean/validate input
    protected function getFilters(): array {
        return [
            'id' => ['type' => 'int', 'min' => 1]
        ];
    }
    
    // Request actions run first (validation)
    protected function getRequestActions(): array {
        return [
            new ValidateProductExists()
        ];
    }
    
    // Data actions transform data
    protected function getDataActions(): array {
        return [
            new LoadProductReviews()
        ];
    }
    
    // Models provide data
    protected function buildModels(): array {
        return [
            new ProductModel($this->inputs['id']),
            new CategoryModel()
        ];
    }
}
```

### Actions

Actions contain reusable business logic:

```php
class ValidateProductExists extends ActionAbstract {
    
    public int $id = 0;
    
    protected function doAction(array $data): mixed {
        $product = Product::findById($this->id);
        
        if (!$product) {
            $this->errors[] = 'Product not found';
            return null;
        }
        
        return ['product' => $product];
    }
}
```

### Models

Models fetch and prepare data:

```php
class ProductModel extends ModelAbstract {
    
    public int $id = 0;
    
    protected function getData(): array {
        return [
            'product' => $this->loadProductFromDatabase($this->id),
            'related' => $this->loadRelatedProducts($this->id)
        ];
    }
}
```

### Responders

Responders handle content negotiation and choose the appropriate view:

```php
class ProductResponder extends ResponderAbstract {
    
    protected function getAcceptedContentTypes(): array {
        return ['text/html', 'application/json'];
    }
    
    protected function getView(string $content_type): string {
        return match($content_type) {
            'application/json' => ProductJSONView::class,
            default => ProductHTMLView::class
        };
    }
}
```

### Views

Views generate output. HTML views extend `Template\HTMLAbstract`:

```php
class ProductHTMLView extends HTMLAbstract {
    
    public array $product = [];
    public array $related = [];
    
    protected function prepareDocument(): void {
        $this->document->title = $this->product['name'];
        $this->document->canonical = 'https://example.com/products/' . $this->product['id'];
        $this->assets->add('css', ['product']);
        $this->assets->add('js', ['product'], 'footer');
    }
    
    protected function generateHeader(): void {
        echo '<!DOCTYPE html><html><head>';
        echo $this->document->generateHead();
        $this->assets->link('css');
        echo '</head><body>';
    }
    
    protected function generateBody(): void {
        echo '<h1>' . htmlspecialchars($this->product['name']) . '</h1>';
        echo '<p>' . htmlspecialchars($this->product['description']) . '</p>';
        echo '<div class="related-products">';
        foreach ($this->related as $item) {
            echo '<div class="product">' . htmlspecialchars($item['name']) . '</div>';
        }
        echo '</div>';
    }
    
    protected function generateFooter(): void {
        $this->assets->link('js', 'footer');
        echo '</body></html>';
    }
}
```

JSON views extend `View\JSONAbstract`:

```php
class ProductJSONView extends JSONAbstract {
    
    public array $product = [];
    public array $related = [];
    
    protected function getData(): array {
        return [
            'status' => 'success',
            'data' => [
                'product' => $this->product,
                'related' => $this->related
            ]
        ];
    }
}
```

## Asset Management

PageMill MVC includes a powerful asset management system for CSS and JavaScript:

### Basic Asset Usage

```php
// In your view's prepareDocument() method:

// Add CSS files
$this->assets->add('css', ['normalize', 'main', 'components']);

// Add JS files to different groups
$this->assets->add('js', ['app'], 'header');
$this->assets->add('js', ['analytics'], 'footer');

// In generateHeader():
$this->assets->link('css'); // Outputs <link> tags

// In generateFooter():
$this->assets->link('js', 'footer'); // Outputs <script> tags
```

### Asset Locations

Configure where assets are stored:

```php
$assets = Assets::init();

$assets->addLocation('css', [
    'directory' => '/var/www/public/css',
    'url' => '/css'
]);

$assets->addLocation('js', [
    'directory' => '/var/www/public/js',
    'url' => '/js'
]);
```

### Inline Assets

Embed assets directly in HTML:

```php
// Inline critical CSS
$this->assets->inline('css', 'critical');

// Output: <style>/* contents of critical.css */</style>
```

### Combined Assets

Use the `Combine` class to serve multiple assets as one file:

```php
use PageMill\MVC\HTML\Assets\Combine;

$combine = new Combine($assets, $request, $response);
$combine->combine('css'); // Combines all requested CSS files
```

URL format: `/combine.php?css=normalize,main,app&v=12345`

### Element Assets

Automatically load assets from UI components:

```php
class ButtonElement extends ElementAbstract {
    
    public static function getAssets(?string $class = null): array {
        return [
            'css' => ['button'],
            'js' => ['button-handler']
        ];
    }
}

// In your view:
$this->element_assets->add([ButtonElement::class]);
// Automatically loads button.css and button-handler.js
```

## Document Metadata

Manage page metadata with the Document class:

```php
protected function prepareDocument(): void {
    // Page title
    $this->document->title = 'My Page Title';
    
    // Canonical URL
    $this->document->canonical = 'https://example.com/page';
    
    // Robots directives
    $this->document->robots_index = false;    // noindex
    $this->document->robots_follow = true;    // follow
    $this->document->robots_archive = false;  // noarchive
    
    // Meta tags
    $this->document->addMeta([
        'name' => 'description',
        'content' => 'Page description'
    ]);
    
    $this->document->addMeta([
        'property' => 'og:title',
        'content' => 'My Page Title'
    ]);
    
    // Custom variables for templates
    $this->document->custom_var = 'Some value';
}

// In generateHeader():
echo $this->document->generateHead();
// Outputs: <title>, <meta>, <link rel="canonical">, etc.

// HTTP headers are sent automatically:
$this->document->generateHeaders();
// Sends: X-Robots-Tag, Link rel=canonical, etc.
```

## UI Components (Elements)

Create reusable UI components:

```php
class AlertElement extends ElementAbstract {
    
    public string $message = '';
    public string $type = 'info';
    
    public static function getAssets(?string $class = null): array {
        return [
            'css' => ['alert'],
            'js' => ['alert-dismiss']
        ];
    }
    
    public function generateElement(): void {
        echo '<div class="alert alert-' . htmlspecialchars($this->type) . '">';
        echo htmlspecialchars($this->message);
        echo '<button class="close">×</button>';
        echo '</div>';
    }
}

// Usage:
$alert = new AlertElement(['message' => 'Success!', 'type' => 'success']);
$alert->generateElement();

// Auto-load element assets:
$this->element_assets->add([AlertElement::class]);
```

## Configuration

### Environment Settings

```php
use PageMill\MVC\Environment;

// Enable debug mode
Environment::debug(true);

// Check debug state
if (Environment::debug()) {
    // Show detailed errors
}

// Disable debug mode
Environment::debug(false);
```

### Property Mapping

The PropertyMap trait maps array data to object properties with type validation:

```php
use PageMill\MVC\Traits\PropertyMap;

class MyClass {
    use PropertyMap;
    
    public string $name = '';
    public int $age = 0;
    
    // Optional: Define type constraints
    protected static array $constraints = [
        'email' => ['type' => 'string'],
        'created_at' => ['type' => \DateTime::class]
    ];
    
    public function __construct(array $data) {
        // Maps array keys to properties with type checking
        $this->mapProperties($data);
        
        // Ignore unknown properties
        $this->mapProperties($data, ignore: true);
    }
}
```

## API Reference

### Core Classes

#### ControllerAbstract

**Purpose:** Orchestrates request handling

**Key Methods:**
- `handleRequest(): array` - Processes the request, returns `[$data, $inputs]`
- `getFilters(): array` - Returns input filter definitions
- `getRequestActions(): array` - Returns validation actions
- `getDataActions(): array` - Returns data transformation actions
- `buildModels(): array` - Returns model instances for data fetching

#### ActionAbstract

**Purpose:** Encapsulates reusable business logic

**Key Methods:**
- `doAction(array $data): mixed` - Executes the action logic
- `getErrors(): array` - Returns validation errors

#### ModelAbstract

**Purpose:** Fetches and prepares data

**Key Methods:**
- `getData(): array` - Returns data array (abstract, must implement)

#### ResponderAbstract

**Purpose:** Handles content negotiation and response generation

**Key Methods:**
- `respond(array $data, array $inputs): void` - Generates and sends response
- `acceptable(string $header): bool` - Checks if content type is acceptable
- `getAcceptedContentTypes(): array` - Returns accepted content types
- `getView(string $content_type): string` - Returns view class name for content type

#### ViewAbstract

**Purpose:** Base class for all views

**Properties:**
- `$http_response` - HTTP response object

**Key Methods:**
- `generate(): void` - Generates output (abstract, must implement)

### HTML/Template Classes

#### Template\HTMLAbstract

**Purpose:** Base class for HTML views

**Properties:**
- `$assets` - Asset manager instance
- `$element_assets` - Element asset injector
- `$document` - Document metadata manager

**Key Methods:**
- `generate(): void` - Orchestrates HTML generation
- `prepareDocument(): void` - Set up metadata and assets (abstract)
- `generateHeader(): void` - Output HTML header (abstract)
- `generateBody(): void` - Output main content (abstract)
- `generateFooter(): void` - Output footer and closing tags (abstract)

#### View\JSONAbstract

**Purpose:** Base class for JSON API responses

**Key Methods:**
- `generate(): void` - Outputs JSON (implemented)
- `getData(): array` - Returns data to encode (abstract)

#### HTML\Document

**Purpose:** Manages document metadata

**Properties:**
- `$title` - Page title
- `$canonical` - Canonical URL
- `$robots_index` - Allow indexing (default: true)
- `$robots_follow` - Allow following links (default: true)
- `$robots_archive` - Allow archiving (default: true)

**Key Methods:**
- `addMeta(array $attributes): void` - Add meta tag
- `generateHead(): string` - Generate `<head>` contents
- `generateHeaders(): void` - Send HTTP headers

#### HTML\Assets

**Purpose:** Manages CSS and JavaScript assets

**Key Methods:**
- `init(): Assets` - Get singleton instance
- `addLocation(string $type, array $config): void` - Register asset location
- `add(string $type, array $assets, string $group = 'default'): void` - Add assets
- `link(string $type, string $group = 'default'): void` - Output `<link>` or `<script>` tags
- `inline(string $type, string|array $assets): void` - Output inline CSS/JS
- `setTagTemplate(string $type, string $template): void` - Customize output template

#### HTML\Assets\Injector

**Purpose:** Automatically loads assets from Element classes

**Key Methods:**
- `add(array $elements): void` - Register elements and load their assets
- `inline(string $type, string $group = 'default'): void` - Output inline assets
- `link(string $type, string $group = 'default'): void` - Output linked assets

#### ElementAbstract

**Purpose:** Base class for reusable UI components

**Key Methods:**
- `static getAssets(?string $class = null): array` - Returns required assets
- `generateElement(): void` - Outputs HTML (abstract)

### Utility Classes

#### Environment

**Purpose:** Global configuration manager

**Key Methods:**
- `static debug(?bool $toggle = null): bool` - Get/set debug mode

#### Traits\PropertyMap

**Purpose:** Type-safe property mapping

**Key Methods:**
- `mapProperties(array $inputs, ?bool $ignore = null): void` - Maps array to properties

## Advanced Usage

### Custom Content Types

Support custom response formats:

```php
class ProductResponder extends ResponderAbstract {
    
    protected function getAcceptedContentTypes(): array {
        return ['text/html', 'application/json', 'application/xml'];
    }
    
    protected function getView(string $content_type): string {
        return match($content_type) {
            'application/xml' => ProductXMLView::class,
            'application/json' => ProductJSONView::class,
            default => ProductHTMLView::class
        };
    }
}
```

### Nested Actions

Actions can be composed for complex workflows:

```php
class ProcessOrderAction extends ActionAbstract {
    
    protected function doAction(array $data): mixed {
        // Validate first
        $validateAction = new ValidateOrderAction($this->inputs);
        $result = $validateAction->doAction($data);
        
        if (!empty($validateAction->getErrors())) {
            $this->errors = $validateAction->getErrors();
            return null;
        }
        
        // Then process
        return $this->processPayment($result);
    }
}
```

### Custom Asset Handlers

Create custom asset output formats:

```php
$assets->registerHandler(
    'css',
    'sri', // Custom handler name
    function(string $type, array $assetList) {
        foreach ($assetList as $asset) {
            $hash = hash_file('sha384', $asset['path']);
            echo '<link rel="stylesheet" href="' . $asset['url'] . '" ';
            echo 'integrity="sha384-' . base64_encode($hash) . '" ';
            echo 'crossorigin="anonymous">';
        }
    }
);

$assets->generate('sri', null, 'css');
```

### Error Handling

```php
class MyController extends ControllerAbstract {
    
    protected function getRequestActions(): array {
        return [new ValidateInputAction()];
    }
    
    protected function buildModels(): array {
        // Check if validation failed
        if (!empty($this->inputs['_errors'])) {
            return ['errors' => $this->inputs['_errors']];
        }
        
        return [new MyModel($this->inputs)];
    }
}
```

## Testing

### Running Tests

Run the complete test suite:

```bash
# All tests
./vendor/bin/phpunit

# Specific test file
./vendor/bin/phpunit tests/ControllerAbstractTest.php

# With verbose output
./vendor/bin/phpunit --testdox

# With coverage (requires Xdebug or PCOV)
./vendor/bin/phpunit --coverage-html coverage/
```

### Test Coverage

The framework has comprehensive test coverage:

- **368 tests** with **734 assertions**
- **100% file coverage** (16/16 source files)
- **~90% line coverage**

See [tests/README.md](tests/README.md) for detailed coverage information.

### Writing Tests for Your Application

Example controller test:

```php
use PHPUnit\Framework\TestCase;
use PageMill\HTTP\Request;

class MyControllerTest extends TestCase {
    
    public function testHandleRequestReturnsData(): void {
        $request = $this->createMock(Request::class);
        $controller = new MyController('/path', ['id' => 1], $request);
        
        [$data, $inputs] = $controller->handleRequest();
        
        $this->assertArrayHasKey('product', $data);
        $this->assertEquals(1, $inputs['id']);
    }
}
```

## Architecture & Best Practices

### Request Flow

1. **Request arrives** → Create Request object
2. **Controller instantiated** → Filters input
3. **Request actions execute** → Validation, authorization
4. **Models built** → Data fetched
5. **Data actions execute** → Transform/enrich data
6. **Responder chooses view** → Based on Accept header
7. **View generates output** → HTML, JSON, etc.
8. **Response sent** → Complete

### Separation of Concerns

- **Controllers** - Orchestration only, no business logic
- **Actions** - Business logic, validation, side effects
- **Models** - Data fetching only
- **Views** - Presentation only, no data fetching
- **Responders** - Content negotiation only

### When to Use What

**Use Controllers when:**
- You need to coordinate multiple actions and models
- You're handling HTTP requests

**Use Actions when:**
- You have reusable business logic
- You need validation that can fail
- You're performing side effects (sending email, logging, etc.)

**Use Models when:**
- You're fetching data from a database or API
- You're transforming raw data into a usable format

**Use Elements when:**
- You have reusable UI components
- Components have their own CSS/JS requirements

### Performance Tips

1. **Asset Combining** - Use `Combine` to reduce HTTP requests
2. **Lazy Loading** - Only load what you need when you need it
3. **Caching** - Assets class supports MD5 fingerprinting for cache busting
4. **Inline Critical CSS** - Use `$assets->inline()` for above-the-fold styles

## Troubleshooting

### Common Issues

**Problem:** "Class not found" errors

**Solution:** Run `composer dump-autoload` to regenerate autoload files.

---

**Problem:** Assets not loading

**Solution:** Ensure asset locations are configured correctly:
```php
$assets->addLocation('css', [
    'directory' => __DIR__ . '/public/css',  // Absolute path
    'url' => '/css'  // Web-accessible URL
]);
```

---

**Problem:** Type errors when mapping properties

**Solution:** Ensure your input data types match property types, or use constraints:
```php
protected static array $constraints = [
    'age' => ['type' => 'integer']
];
```

---

**Problem:** "Unknown configuration input" exception

**Solution:** Either add the property to your class, or use ignore mode:
```php
$this->mapProperties($data, ignore: true);
```

## FAQ

**Q: Do I have to use all components?**

A: No! Use what you need. The framework is designed to be modular. You can use just the asset management, just the MVC components, or everything together.

**Q: Can I use this with existing frameworks?**

A: Yes, PageMill MVC can be integrated into existing applications. The components are standalone and don't require a specific application structure.

**Q: How do I handle AJAX requests?**

A: Use JSONAbstract views and let the Responder handle content negotiation automatically based on the Accept header.

**Q: Is this production-ready?**

A: Yes. The framework is fully tested, type-safe, and follows modern PHP best practices.

**Q: What's the performance like?**

A: PageMill MVC is lightweight with minimal overhead. The asset system includes combining and caching for production optimization.

**Q: Can I extend the base classes?**

A: Absolutely! All abstract classes are designed to be extended. That's the whole point.

## Contributing

We welcome contributions! Here's how you can help:

### Reporting Bugs

1. Check if the bug has already been reported in Issues
2. Create a new issue with:
   - Clear description of the problem
   - Steps to reproduce
   - Expected vs actual behavior
   - PHP version and environment details

### Suggesting Features

1. Open an issue describing the feature
2. Explain the use case and benefits
3. Provide examples if possible

### Submitting Pull Requests

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Write tests for your changes
4. Ensure all tests pass (`./vendor/bin/phpunit`)
5. Follow PSR-12 coding standards
6. Commit your changes (`git commit -m 'Add amazing feature'`)
7. Push to the branch (`git push origin feature/amazing-feature`)
8. Open a Pull Request

### Code Style

- Follow PSR-12 coding standards
- Use strict types (`declare(strict_types=1)`)
- Add type hints for all parameters and return types
- Write PHPDoc blocks for all public methods
- Keep methods focused and small
- Write tests for new features

## License

PageMill MVC is open-source software licensed under the [BSD-3-Clause license](LICENSE).

Copyright © 1997-Present DealNews.com, Inc

## Credits

**Maintainer:** DealNews.com, Inc

### Dependencies

- [pagemill/http](https://github.com/pagemill/http) - HTTP request/response handling
- [pagemill/accept](https://github.com/pagemill/accept) - Content-type negotiation
- [dealnews/filter](https://github.com/dealnews/filter) - Input filtering

---

**Built with ❤️ by developers, for developers.**

For questions, issues, or discussions, please use the [GitHub Issues](https://github.com/dealnews/pagemill-mvc/issues) page.
