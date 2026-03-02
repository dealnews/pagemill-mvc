# PageMill MVC Test Suite

This directory contains unit tests for the PageMill MVC framework.

## Running Tests

Run all tests:
```bash
./vendor/bin/phpunit
```

Run a specific test file:
```bash
./vendor/bin/phpunit tests/ActionAbstractTest.php
```

Run with coverage (requires Xdebug or PCOV):
```bash
./vendor/bin/phpunit --coverage-html coverage/
```

Run with verbose output:
```bash
./vendor/bin/phpunit --testdox
```

## Test Organization

Tests are organized by the class they test, following PSR-4 autoloading:
- `tests/ActionAbstractTest.php` - Tests for `src/ActionAbstract.php`
- More test files to be added as needed

## Writing Tests

All test classes should:
1. Extend `PHPUnit\Framework\TestCase`
2. Use the namespace `PageMill\MVC\Tests`
3. Follow the naming convention `{ClassName}Test`
4. Include PHPDoc comments describing what is tested
5. Use descriptive test method names starting with `test`

Example:
```php
<?php

declare(strict_types=1);

namespace PageMill\MVC\Tests;

use PHPUnit\Framework\TestCase;

class MyClassTest extends TestCase {
    
    public function testSomeBehavior(): void {
        // Arrange
        $instance = new MyClass();
        
        // Act
        $result = $instance->doSomething();
        
        // Assert
        $this->assertEquals('expected', $result);
    }
}
```

## Test Coverage

Current test coverage:

### Core MVC Classes (100% file coverage)
- ✅ **ActionAbstract** - 16 tests, 40 assertions
- ✅ **ControllerAbstract** - 22 tests (19 passing, 3 skipped), 38 assertions
- ✅ **ElementAbstract** - 28 tests, 60 assertions
- ✅ **Environment** - 22 tests, 53 assertions
- ✅ **HTMLElement** - 31 tests, 71 assertions
- ✅ **ModelAbstract** - 21 tests, 58 assertions
- ✅ **ResponderAbstract** - 19 tests, 30 assertions
- ✅ **ViewAbstract** - 22 tests, 45 assertions

### HTML Directory (100% file coverage)
- ✅ **HTML/Document** - 44 tests, 70 assertions
- ✅ **HTML/Assets** - 25 tests (24 passing, 1 skipped), 47 assertions, 1 warning
- ✅ **HTML/Assets/Exception** - 11 tests, 22 assertions
- ✅ **HTML/Assets/Combine** - 15 tests (14 passing, 1 skipped), 29 assertions
- ✅ **HTML/Assets/Injector** - 27 tests, 53 assertions

### Template Directory (100% file coverage)
- ✅ **Template/HTMLAbstract** - 24 tests, 45 assertions

### View Directory (100% file coverage)
- ✅ **View/JSONAbstract** - 16 tests, 32 assertions

### Traits Directory (100% file coverage)
- ✅ **Traits/PropertyMap** - 25 tests, 41 assertions

**Total: 368 tests, 734 assertions, 5 skipped, 1 warning**

### Coverage Statistics
- **Tested Files**: 16 of 16 source files (100%)
- **Core MVC**: 8 of 8 files tested (100%)
- **HTML Directory**: 5 of 5 files tested (100%)
- **Template Directory**: 1 of 1 file tested (100%)
- **View Directory**: 1 of 1 file tested (100%)
- **Traits Directory**: 1 of 1 file tested (100%)
- **Estimated Line Coverage**: ~90% of framework code

### Complete Test Coverage
All 16 source files in the PageMill MVC framework now have comprehensive unit tests!

### Bugs Fixed During Testing

1. **ResponderAbstract** - Added missing `use PageMill\HTTP\HTTP;` import
2. **ResponderAbstract** - Fixed Response class namespace (PageMill\HTTP not PageMill\MVC\HTTP)
3. **Document** - Fixed Headers class namespace (PageMill\HTTP not PageMill\MVC\HTTP)
4. **Combine** - Fixed HTTP imports namespace (PageMill\HTTP not PageMill\MVC\HTTP)
5. **ResponderAbstract** - Documented TypeError bug in content negotiation (false assigned to string property)

### Test Notes

**Skipped Tests:**
- ControllerAbstract: 3 tests require complete HTTP request/response integration
- Combine: 1 test (304 response) calls exit() and cannot be tested without process isolation
- Assets: 1 test (custom handler registration) requires deeper integration setup

**Warnings:**
- Assets: 1 expected warning when testing missing asset behavior with exceptions disabled

## Requirements

- PHP 8.2 or higher
- PHPUnit 10.5 or higher
- All dependencies installed via `composer install`
