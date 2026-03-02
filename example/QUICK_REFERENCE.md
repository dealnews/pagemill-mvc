# Example Application Quick Reference

## File Overview

### Entry Points
- `public/index.php` - HTML interface entry point
- `public/api.php` - JSON API entry point

### Controllers
- `src/Controllers/BlogController.php` - Main request handler
  - Filters input (validates `id` parameter)
  - Runs validation actions
  - Builds models for data

### Actions
- `src/Actions/ValidatePostExistsAction.php` - Validates post ID
  - Sets errors if post not found
  - Returns post data if valid

### Models
- `src/Models/BlogListModel.php` - Fetches all posts
- `src/Models/BlogPostModel.php` - Fetches single post by ID

### Responders
- `src/Responders/BlogResponder.php` - Content negotiation
  - HTML for browsers
  - JSON for API requests
  - Chooses list vs single post view

### Views (HTML)
- `src/Views/BlogListView.php` - Post listing page
- `src/Views/BlogPostView.php` - Single post page

### Views (API)
- `src/Views/BlogJSONView.php` - JSON responses

### Elements
- `src/Elements/PostCardElement.php` - Reusable post card
  - Auto-loads `post-card.css`

### Assets
- `assets/css/blog.css` - Main stylesheet
- `assets/css/post-card.css` - Post card component styles
- `assets/css/post.css` - Single post styles

## Data Flow Example

### Viewing a List (GET /)

```
1. index.php receives request
2. BlogController filters input
3. No ID provided, so no validation action runs
4. BlogListModel fetches all posts
5. BlogResponder checks Accept header
6. BlogListView generates HTML
   - Loads blog.css
   - Uses PostCardElement for each post
   - PostCardElement auto-loads post-card.css
7. HTML output sent to browser
```

### Viewing a Post (GET /?id=1)

```
1. index.php receives request with id=1
2. BlogController filters input (validates id as integer)
3. ValidatePostExistsAction runs
   - Finds post with ID 1
   - Returns post data
4. BlogPostModel fetches full post data
5. BlogResponder chooses BlogPostView
6. BlogPostView generates HTML
   - Sets page title, meta tags
   - Loads blog.css and post.css
7. HTML output sent to browser
```

### API Request (GET /api.php or Accept: application/json)

```
1. api.php receives request OR Accept header is application/json
2. BlogController filters input
3. Actions and Models run same as above
4. BlogResponder chooses BlogJSONView
5. BlogJSONView encodes data as JSON
6. JSON output sent with Content-Type: application/json
```

## Key Patterns Demonstrated

### 1. Input Filtering
```php
protected function getFilters(): array {
    return [
        'id' => ['type' => 'int', 'min' => 1, 'default' => null]
    ];
}
```

### 2. Validation Actions
```php
protected function getRequestActions(): array {
    if (!empty($this->inputs['id'])) {
        return [new ValidatePostExistsAction($this->inputs)];
    }
    return [];
}
```

### 3. Error Checking
```php
protected function buildModels(): array {
    if (!empty($this->inputs['_errors'])) {
        return []; // Validation failed
    }
    // ... build models
}
```

### 4. Property Mapping
```php
// Array data automatically maps to typed properties
$model = new BlogPostModel(['id' => 1]);
// $model->id === 1
```

### 5. Asset Management
```php
// Register location
$this->assets->addLocation('css', [
    'directory' => '/path/to/css',
    'url' => '/css'
]);

// Add assets
$this->assets->add('css', ['blog']);

// Output
$this->assets->link('css');
// <link rel="stylesheet" href="/css/blog.css">
```

### 6. Component Assets
```php
// Element defines assets
public static function getAssets(): array {
    return ['css' => ['post-card']];
}

// View loads element
$this->element_assets->add([PostCardElement::class]);
// post-card.css is automatically added
```

### 7. Content Negotiation
```php
protected function getView(string $content_type): string {
    return match($content_type) {
        'application/json' => BlogJSONView::class,
        default => BlogListView::class
    };
}
```

## Testing the Example

### Manual Testing

```bash
# Start server
cd example/public
php -S localhost:8000

# Test HTML (browser)
open http://localhost:8000/
open http://localhost:8000/?id=1

# Test JSON API
curl http://localhost:8000/api.php
curl http://localhost:8000/api.php?id=1

# Test content negotiation
curl -H "Accept: application/json" http://localhost:8000/
curl -H "Accept: text/html" http://localhost:8000/

# Test validation
curl http://localhost:8000/api.php?id=999
# Returns: {"status":"error","errors":["Blog post with ID 999 not found"]}
```

## Extending the Example

### Add a Search Feature

1. Add filter: `'query' => ['type' => 'string']`
2. Create `SearchBlogAction` to find matching posts
3. Update models to filter by search term
4. Add search form to views

### Add Pagination

1. Add filters: `'page' => ['type' => 'int']`, `'limit' => ['type' => 'int']`
2. Update `BlogListModel` to support pagination
3. Add pagination controls to views

### Add Comments

1. Create `CommentModel` to fetch comments
2. Create `PostCommentAction` for validation
3. Update post view to show comments

### Add Categories

1. Create `CategoryModel`
2. Add category filter to controller
3. Update views to show/filter by category
