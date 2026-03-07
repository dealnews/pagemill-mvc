# Example Blog Application

This directory contains a complete example application demonstrating PageMill MVC features.

## What's Included

This is a simple blog application that demonstrates:

- **HTML Views** - Traditional server-rendered pages
- **JSON API** - RESTful API endpoints
- **Asset Management** - CSS/JS loading and organization
- **Validation** - Input validation with Actions
- **Data Fetching** - Models for loading blog posts
- **UI Components** - Reusable Elements with auto-loaded assets
- **Content Negotiation** - Automatic format selection based on Accept header

## Directory Structure

```
example/
├── README.md           # This file
├── public/             # Web-accessible files
│   ├── index.php       # Entry point for HTML
│   └── api.php         # Entry point for API
├── src/                # Application code
│   ├── Controllers/    # Request orchestration
│   ├── Actions/        # Business logic & validation
│   ├── Models/         # Data fetching
│   ├── Responders/     # Content negotiation
│   ├── Views/          # Output generation
│   └── Elements/       # UI components
└── assets/             # Static assets
    ├── css/            # Stylesheets
    └── js/             # JavaScript
```

## Quick Start

### 1. Install Dependencies

From the repository root:

```bash
composer install
```

### 2. Run the Example

Using PHP's built-in server:

```bash
cd example/public
php -S localhost:8000
```

### 3. Try It Out

**HTML Interface:**
- http://localhost:8000/ - Blog post listing
- http://localhost:8000/?id=1 - Single post view

**JSON API:**
- http://localhost:8000/api.php - All posts as JSON
- http://localhost:8000/api.php?id=1 - Single post as JSON

**Content Negotiation:**
```bash
# Get HTML
curl http://localhost:8000/

# Get JSON by Accept header
curl -H "Accept: application/json" http://localhost:8000/
```

## Code Walkthrough

### Controllers (`src/Controllers/`)

Controllers coordinate the request flow:

```php
BlogController
├── getFilters()        → Validates 'id' parameter
├── getRequestActions() → Checks if post exists
├── buildModels()       → Loads blog posts
└── Returns [$data, $inputs] for responder
```

### Actions (`src/Actions/`)

Actions contain validation and business logic:

- `ValidatePostExistsAction` - Ensures requested post ID is valid

### Models (`src/Models/`)

Models fetch data:

- `BlogPostModel` - Loads individual post
- `BlogListModel` - Loads all posts

### Responders (`src/Responders/`)

Responders handle content negotiation:

- `BlogResponder` - Chooses between HTML or JSON view

### Views (`src/Views/`)

Views generate output:

- `BlogListView` - HTML listing page
- `BlogPostView` - HTML single post page
- `BlogJSONView` - JSON API response

### Elements (`src/Elements/`)

Reusable UI components:

- `PostCardElement` - Blog post card with auto-loaded CSS

## Features Demonstrated

### 1. Property Mapping

All classes use the PropertyMap trait to automatically populate properties from arrays:

```php
$model = new BlogPostModel(['id' => 1]);
// $model->id is now 1
```

### 2. Type Safety

All properties are typed, and the PropertyMap trait validates types:

```php
public int $id = 0;        // Must be integer
public string $title = ''; // Must be string
```

### 3. Actions for Validation

Business logic is separate from controllers:

```php
// In controller
protected function getRequestActions(): array {
    return [new ValidatePostExistsAction($this->inputs)];
}

// Action checks for errors
if (!empty($this->inputs['_errors'])) {
    // Handle validation failure
}
```

### 4. Content Negotiation

One controller, multiple response formats:

```php
// Responder chooses view based on Accept header
protected function getView(string $content_type): string {
    return match($content_type) {
        'application/json' => BlogJSONView::class,
        default => BlogListView::class
    };
}
```

### 5. Asset Management

CSS and JS are managed centrally:

```php
// In view's prepareDocument()
$this->assets->add('css', ['blog', 'syntax-highlighting']);
$this->assets->add('js', ['comments'], 'footer');

// In generateHeader()
$this->assets->link(null, 'css');

// In generateFooter()
$this->assets->link('footer', 'js');
```

### 6. UI Components

Reusable elements with automatic asset loading:

```php
// Element defines its assets
public static function getAssets(): array {
    return ['css' => ['post-card']];
}

// View loads element assets
$this->element_assets->add([PostCardElement::class]);
// post-card.css is automatically loaded
```

### 7. Document Metadata

SEO-friendly metadata management:

```php
$this->document->title = 'Blog Post Title';
$this->document->canonical = 'https://example.com/post/1';
$this->document->addMeta([
    'name' => 'description',
    'content' => 'Post description'
]);
```

## Extending the Example

### Add a New Feature

1. **Add a comment form:**
   - Create `PostCommentAction` for validation
   - Add to controller's request actions
   - Update view to show form

2. **Add category filtering:**
   - Add `category` to getFilters()
   - Create `BlogCategoryModel`
   - Update views to show categories

3. **Add XML API:**
   - Create `BlogXMLView extends ViewAbstract`
   - Add to responder's accepted content types
   - Return XML view for `application/xml`

## Testing

The example code follows the same patterns as the framework. To test:

```bash
# Copy test structure from framework
mkdir -p tests/Controllers tests/Actions tests/Models

# Write tests following framework patterns
phpunit tests/
```

## Production Considerations

This is a simplified example. For production:

1. **Database** - Replace array data with real database
2. **Routing** - Add proper URL routing
3. **Error Handling** - Add try/catch and error views
4. **Authentication** - Add user authentication actions
5. **Asset Compilation** - Use asset pipeline for minification
6. **Caching** - Cache model data and rendered views
7. **Security** - Add CSRF protection, XSS prevention
8. **Logging** - Add error and access logging

## Learn More

- See [README.md](../README.md) for full framework documentation
- See [AGENTS.md](../AGENTS.md) for coding standards and patterns
- See [tests/](../tests/) for comprehensive test examples
