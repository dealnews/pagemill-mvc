<?php

declare(strict_types=1);

namespace PageMill\MVC\Tests;

use PageMill\MVC\ModelAbstract;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for ModelAbstract
 *
 * Tests the base model functionality including construction, property mapping,
 * and abstract method enforcement.
 */
class ModelAbstractTest extends TestCase {

    /**
     * Tests constructor maps inputs to properties
     */
    public function testConstructorMapsInputsToProperties(): void {
        $inputs = [
            'user_id' => 123,
            'category' => 'electronics',
            'limit' => 10
        ];

        $model = new ConcreteModel($inputs);

        $this->assertEquals(123, $model->user_id);
        $this->assertEquals('electronics', $model->category);
        $this->assertEquals(10, $model->limit);
    }

    /**
     * Tests constructor with empty inputs
     */
    public function testConstructorWithEmptyInputs(): void {
        $model = new ConcreteModel([]);

        $this->assertInstanceOf(ModelAbstract::class, $model);
    }

    /**
     * Tests constructor ignores unknown properties in ignore mode
     */
    public function testConstructorIgnoresUnknownProperties(): void {
        // ModelAbstract calls mapProperties with $ignore=true
        $model = new ConcreteModel([
            'user_id' => 123,
            'unknown_property' => 'value'
        ]);

        $this->assertEquals(123, $model->user_id);
        // Unknown property is ignored, not set
        $this->assertFalse(property_exists($model, 'unknown_property'));
    }

    /**
     * Tests getData must be implemented by child class
     */
    public function testGetDataMustBeImplemented(): void {
        $model = new ConcreteModel(['user_id' => 1]);
        $data = $model->getData();

        $this->assertIsArray($data);
    }

    /**
     * Tests getData returns expected data structure
     */
    public function testGetDataReturnsExpectedStructure(): void {
        $model = new ConcreteModel(['user_id' => 123]);
        $data = $model->getData();

        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('products', $data);
        $this->assertEquals(123, $data['user']['id']);
    }

    /**
     * Tests getData can return empty array
     */
    public function testGetDataCanReturnEmptyArray(): void {
        $model = new EmptyDataModel([]);
        $data = $model->getData();

        $this->assertIsArray($data);
        $this->assertEmpty($data);
    }

    /**
     * Tests model can access mapped properties
     */
    public function testModelCanAccessMappedProperties(): void {
        $model = new PropertyAccessModel(['category' => 'books', 'page' => 2]);
        $data = $model->getData();

        $this->assertArrayHasKey('category', $data);
        $this->assertArrayHasKey('page', $data);
        $this->assertEquals('books', $data['category']);
        $this->assertEquals(2, $data['page']);
    }

    /**
     * Tests model with null property values
     */
    public function testModelWithNullPropertyValues(): void {
        $model = new ConcreteModel(['user_id' => null, 'category' => null]);

        $this->assertNull($model->user_id);
        $this->assertNull($model->category);
    }

    /**
     * Tests model with various data types
     */
    public function testModelWithVariousDataTypes(): void {
        $inputs = [
            'user_id' => 456,
            'category' => 'sports',
            'limit' => 25,
            'enabled' => true,
            'price' => 99.99
        ];

        $model = new VariousTypesModel($inputs);

        $this->assertIsInt($model->user_id);
        $this->assertIsString($model->category);
        $this->assertIsInt($model->limit);
        $this->assertIsBool($model->enabled);
        $this->assertIsFloat($model->price);
    }

    /**
     * Tests PropertyMap trait integration with ignore mode
     */
    public function testPropertyMapTraitIgnoreMode(): void {
        // ModelAbstract calls PropertyMap with ignore=true (second parameter)
        $model = new ConcreteModel([
            'user_id' => 123,
            'invalid_key' => 'value'
        ]);

        // Valid property is set
        $this->assertEquals(123, $model->user_id);
        // Invalid property is ignored
        $this->assertFalse(property_exists($model, 'invalid_key'));
    }

    /**
     * Tests multiple model instances maintain separate state
     */
    public function testMultipleInstancesMaintainSeparateState(): void {
        $model1 = new ConcreteModel(['user_id' => 1, 'category' => 'a']);
        $model2 = new ConcreteModel(['user_id' => 2, 'category' => 'b']);

        $this->assertEquals(1, $model1->user_id);
        $this->assertEquals(2, $model2->user_id);
        $this->assertEquals('a', $model1->category);
        $this->assertEquals('b', $model2->category);
    }

    /**
     * Tests getData returns complex nested structures
     */
    public function testGetDataReturnsComplexStructures(): void {
        $model = new ComplexDataModel(['user_id' => 1]);
        $data = $model->getData();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('metadata', $data);
        $this->assertIsArray($data['user']);
        $this->assertIsArray($data['metadata']);
        $this->assertArrayHasKey('permissions', $data['user']);
    }

    /**
     * Tests model with array property
     */
    public function testModelWithArrayProperty(): void {
        $model = new ArrayPropertyModel([
            'user_id' => 1,
            'filters' => ['status' => 'active', 'type' => 'premium']
        ]);

        $this->assertIsArray($model->filters);
        $this->assertArrayHasKey('status', $model->filters);
        $this->assertEquals('active', $model->filters['status']);
    }

    /**
     * Tests model getData can use constructor inputs
     */
    public function testGetDataUsesConstructorInputs(): void {
        $model = new ConcreteModel(['user_id' => 999]);
        $data = $model->getData();

        // Model uses user_id to fetch data
        $this->assertEquals(999, $data['user']['id']);
    }

    /**
     * Tests model with default property values
     */
    public function testModelWithDefaultPropertyValues(): void {
        $model = new DefaultValuesModel([]);

        $this->assertEquals(10, $model->limit);
        $this->assertEquals(0, $model->offset);
        $this->assertEquals('created', $model->sort);
    }

    /**
     * Tests model property override defaults
     */
    public function testModelPropertyOverrideDefaults(): void {
        $model = new DefaultValuesModel([
            'limit' => 50,
            'sort' => 'updated'
        ]);

        $this->assertEquals(50, $model->limit);
        $this->assertEquals('updated', $model->sort);
        $this->assertEquals(0, $model->offset); // Still default
    }

    /**
     * Tests model can have typed properties
     */
    public function testModelCanHaveTypedProperties(): void {
        $model = new TypedPropertiesModel([
            'page' => 5,
            'search' => 'test query'
        ]);

        $this->assertIsInt($model->page);
        $this->assertIsString($model->search);
    }

    /**
     * Tests getData return type is enforced
     */
    public function testGetDataReturnTypeEnforced(): void {
        $model = new ConcreteModel(['user_id' => 1]);
        $data = $model->getData();

        // PHP will enforce the array return type
        $this->assertIsArray($data);
    }

    /**
     * Tests model with boolean properties
     */
    public function testModelWithBooleanProperties(): void {
        $model = new BooleanPropertiesModel([
            'active' => true,
            'featured' => false
        ]);

        $this->assertTrue($model->active);
        $this->assertFalse($model->featured);
    }

    /**
     * Tests model getData with database simulation
     */
    public function testGetDataWithDatabaseSimulation(): void {
        $model = new DatabaseSimulationModel(['user_id' => 42]);
        $data = $model->getData();

        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('posts', $data);
        $this->assertEquals(42, $data['user']['id']);
        $this->assertIsArray($data['posts']);
        $this->assertNotEmpty($data['posts']);
    }

    /**
     * Tests model getData with API simulation
     */
    public function testGetDataWithAPISimulation(): void {
        $model = new APISimulationModel(['endpoint' => 'users']);
        $data = $model->getData();

        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertEquals('success', $data['status']);
    }
}

/**
 * Concrete model for basic testing
 */
class ConcreteModel extends ModelAbstract {

    public ?int $user_id = null;
    public ?string $category = null;
    public ?int $limit = null;

    public function getData(): array {
        return [
            'user' => [
                'id' => $this->user_id,
                'name' => 'Test User'
            ],
            'products' => []
        ];
    }
}

/**
 * Model that returns empty data
 */
class EmptyDataModel extends ModelAbstract {

    public function getData(): array {
        return [];
    }
}

/**
 * Model that uses properties in getData
 */
class PropertyAccessModel extends ModelAbstract {

    public ?string $category = null;
    public ?int $page = null;

    public function getData(): array {
        return [
            'category' => $this->category,
            'page' => $this->page,
            'items' => ['item1', 'item2']
        ];
    }
}

/**
 * Model with various property types
 */
class VariousTypesModel extends ModelAbstract {

    public ?int $user_id = null;
    public ?string $category = null;
    public ?int $limit = null;
    public ?bool $enabled = null;
    public ?float $price = null;

    public function getData(): array {
        return [
            'user_id' => $this->user_id,
            'category' => $this->category,
            'limit' => $this->limit,
            'enabled' => $this->enabled,
            'price' => $this->price
        ];
    }
}

/**
 * Model with complex nested data
 */
class ComplexDataModel extends ModelAbstract {

    public ?int $user_id = null;

    public function getData(): array {
        return [
            'user' => [
                'id' => $this->user_id,
                'profile' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com'
                ],
                'permissions' => ['read', 'write']
            ],
            'metadata' => [
                'timestamp' => time(),
                'version' => '1.0'
            ]
        ];
    }
}

/**
 * Model with array property
 */
class ArrayPropertyModel extends ModelAbstract {

    public ?int $user_id = null;
    public array $filters = [];

    public function getData(): array {
        return [
            'user_id' => $this->user_id,
            'filters' => $this->filters
        ];
    }
}

/**
 * Model with default property values
 */
class DefaultValuesModel extends ModelAbstract {

    public int $limit = 10;
    public int $offset = 0;
    public string $sort = 'created';

    public function getData(): array {
        return [
            'limit' => $this->limit,
            'offset' => $this->offset,
            'sort' => $this->sort
        ];
    }
}

/**
 * Model with typed properties
 */
class TypedPropertiesModel extends ModelAbstract {

    public ?int $page = null;
    public ?string $search = null;

    public function getData(): array {
        return [
            'page' => $this->page,
            'search' => $this->search
        ];
    }
}

/**
 * Model with boolean properties
 */
class BooleanPropertiesModel extends ModelAbstract {

    public ?bool $active = null;
    public ?bool $featured = null;

    public function getData(): array {
        return [
            'active' => $this->active,
            'featured' => $this->featured
        ];
    }
}

/**
 * Model simulating database access
 */
class DatabaseSimulationModel extends ModelAbstract {

    public ?int $user_id = null;

    public function getData(): array {
        // Simulate fetching from database
        return [
            'user' => [
                'id' => $this->user_id,
                'name' => 'Database User',
                'email' => 'user@example.com'
            ],
            'posts' => [
                ['id' => 1, 'title' => 'Post 1'],
                ['id' => 2, 'title' => 'Post 2']
            ]
        ];
    }
}

/**
 * Model simulating API access
 */
class APISimulationModel extends ModelAbstract {

    public ?string $endpoint = null;

    public function getData(): array {
        // Simulate API call
        return [
            'status' => 'success',
            'data' => [
                'endpoint' => $this->endpoint,
                'results' => ['result1', 'result2']
            ]
        ];
    }
}
