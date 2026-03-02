<?php

declare(strict_types=1);

namespace PageMill\MVC\Tests\Traits;

use PageMill\MVC\Traits\PropertyMap;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Traits\PropertyMap
 *
 * PropertyMap trait provides functionality to map array keys to object
 * properties with type validation support.
 */
class PropertyMapTest extends TestCase {
    
    /**
     * Test mapProperties maps values to existing properties
     */
    public function testMapPropertiesToExistingProperties(): void {
        $obj = new SimplePropertyMapClass();
        $obj->mapPropertiesPublic(['name' => 'Test', 'age' => 25]);
        
        $this->assertEquals('Test', $obj->name);
        $this->assertEquals(25, $obj->age);
    }
    
    /**
     * Test mapProperties with empty array does nothing
     */
    public function testMapPropertiesWithEmptyArray(): void {
        $obj = new SimplePropertyMapClass();
        $obj->name = 'Original';
        $obj->mapPropertiesPublic([]);
        
        $this->assertEquals('Original', $obj->name);
    }
    
    /**
     * Test mapProperties throws exception for unknown property
     */
    public function testMapPropertiesThrowsExceptionForUnknownProperty(): void {
        $obj = new SimplePropertyMapClass();
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown configuration input');
        
        $obj->mapPropertiesPublic(['unknown' => 'value']);
    }
    
    /**
     * Test mapProperties ignores unknown properties when ignore is true
     */
    public function testMapPropertiesIgnoresUnknownWhenIgnoreTrue(): void {
        $obj = new SimplePropertyMapClass();
        $obj->mapPropertiesPublic(['name' => 'Test', 'unknown' => 'value'], true);
        
        $this->assertEquals('Test', $obj->name);
    }
    
    /**
     * Test type validation with string property
     */
    public function testTypeValidationWithString(): void {
        $obj = new SimplePropertyMapClass();
        $obj->name = 'Original';
        
        // Valid string
        $obj->mapPropertiesPublic(['name' => 'NewName']);
        $this->assertEquals('NewName', $obj->name);
        
        // Invalid type should throw exception
        $this->expectException(\InvalidArgumentException::class);
        $obj->mapPropertiesPublic(['name' => 123]);
    }
    
    /**
     * Test type validation with integer property
     */
    public function testTypeValidationWithInteger(): void {
        $obj = new SimplePropertyMapClass();
        $obj->age = 30;
        
        // Valid integer
        $obj->mapPropertiesPublic(['age' => 25]);
        $this->assertEquals(25, $obj->age);
        
        // Invalid type should throw exception
        $this->expectException(\InvalidArgumentException::class);
        $obj->mapPropertiesPublic(['age' => 'twenty']);
    }
    
    /**
     * Test type validation with boolean property
     */
    public function testTypeValidationWithBoolean(): void {
        $obj = new TypedPropertyMapClass();
        $obj->enabled = true;
        
        // Valid boolean
        $obj->mapPropertiesPublic(['enabled' => false]);
        $this->assertFalse($obj->enabled);
        
        // Invalid type should throw exception
        $this->expectException(\InvalidArgumentException::class);
        $obj->mapPropertiesPublic(['enabled' => 1]);
    }
    
    /**
     * Test null property values skip trait type checking
     */
    public function testNullPropertyValuesSkipTraitTypeChecking(): void {
        $obj = new SimplePropertyMapClass();
        
        // When property is initially null, trait doesn't enforce type checking
        // (line 59: elseif (!is_null($this->$var)))
        $obj->mapPropertiesPublic(['email' => 'test@example.com']);
        $this->assertEquals('test@example.com', $obj->email);
        
        // Once set to a string, further assignments must be strings
        $obj->email = 'original@example.com';
        $obj->mapPropertiesPublic(['email' => 'updated@example.com']);
        $this->assertEquals('updated@example.com', $obj->email);
    }
    
    /**
     * Test constraints override property type checking
     */
    public function testConstraintsOverridePropertyType(): void {
        $obj = new ConstrainedPropertyMapClass();
        
        // Constraint enforces string type
        $obj->mapPropertiesPublic(['username' => 'john']);
        $this->assertEquals('john', $obj->username);
        
        // Should throw exception for wrong type
        $this->expectException(\InvalidArgumentException::class);
        $obj->mapPropertiesPublic(['username' => 123]);
    }
    
    /**
     * Test array property merging
     */
    public function testArrayPropertyMerging(): void {
        $obj = new TypedPropertyMapClass();
        $obj->settings = ['theme' => 'dark', 'lang' => 'en'];
        
        $obj->mapPropertiesPublic(['settings' => ['theme' => 'light']]);
        
        // Should merge arrays recursively
        $this->assertEquals(['theme' => 'light', 'lang' => 'en'], $obj->settings);
    }
    
    /**
     * Test array property replacement when target is not array
     */
    public function testArrayPropertyReplacementWhenTargetNotArray(): void {
        $obj = new SimplePropertyMapClass();
        $obj->name = 'Original';
        
        // When target is not array, replace instead of merge
        $obj->mapPropertiesPublic(['name' => 'New']);
        $this->assertEquals('New', $obj->name);
    }
    
    /**
     * Test deeply nested array merging
     */
    public function testDeeplyNestedArrayMerging(): void {
        $obj = new TypedPropertyMapClass();
        $obj->settings = [
            'level1' => [
                'level2' => [
                    'value' => 'original',
                    'other' => 'keep'
                ]
            ]
        ];
        
        $obj->mapPropertiesPublic([
            'settings' => [
                'level1' => [
                    'level2' => [
                        'value' => 'updated'
                    ]
                ]
            ]
        ]);
        
        $this->assertEquals('updated', $obj->settings['level1']['level2']['value']);
        $this->assertEquals('keep', $obj->settings['level1']['level2']['other']);
    }
    
    /**
     * Test object type validation with constraints
     */
    public function testObjectTypeValidationWithConstraints(): void {
        $obj = new ObjectConstrainedPropertyMapClass();
        $mockObj = new \stdClass();
        
        $obj->mapPropertiesPublic(['data' => $mockObj]);
        $this->assertSame($mockObj, $obj->data);
    }
    
    /**
     * Test multiple properties at once
     */
    public function testMultiplePropertiesAtOnce(): void {
        $obj = new SimplePropertyMapClass();
        
        $obj->mapPropertiesPublic([
            'name' => 'John',
            'age' => 30,
            'email' => 'john@example.com'
        ]);
        
        $this->assertEquals('John', $obj->name);
        $this->assertEquals(30, $obj->age);
        $this->assertEquals('john@example.com', $obj->email);
    }
    
    /**
     * Test partial property update
     */
    public function testPartialPropertyUpdate(): void {
        $obj = new SimplePropertyMapClass();
        $obj->name = 'Original';
        $obj->age = 25;
        
        // Update only age
        $obj->mapPropertiesPublic(['age' => 30]);
        
        $this->assertEquals('Original', $obj->name);
        $this->assertEquals(30, $obj->age);
    }
    
    /**
     * Test ignore parameter default behavior
     */
    public function testIgnoreParameterDefaultBehavior(): void {
        $obj = new SimplePropertyMapClass();
        
        // Default is false (don't ignore)
        $this->expectException(\InvalidArgumentException::class);
        $obj->mapPropertiesPublic(['unknown' => 'value']);
    }
    
    /**
     * Test explicit ignore false
     */
    public function testExplicitIgnoreFalse(): void {
        $obj = new SimplePropertyMapClass();
        
        $this->expectException(\InvalidArgumentException::class);
        $obj->mapPropertiesPublic(['unknown' => 'value'], false);
    }
    
    /**
     * Test constraint with integer type
     */
    public function testConstraintWithIntegerType(): void {
        $obj = new ConstrainedPropertyMapClass();
        
        $obj->mapPropertiesPublic(['age' => 25]);
        $this->assertEquals(25, $obj->age);
        
        $this->expectException(\InvalidArgumentException::class);
        $obj->mapPropertiesPublic(['age' => 'twenty']);
    }
    
    /**
     * Test exception message format
     */
    public function testExceptionMessageFormat(): void {
        $obj = new SimplePropertyMapClass();
        $obj->name = 'Original';
        
        try {
            $obj->mapPropertiesPublic(['name' => 123]);
            $this->fail('Should have thrown exception');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('must be of type string', $e->getMessage());
            $this->assertStringContainsString('integer given', $e->getMessage());
        }
    }
    
    /**
     * Test float type validation
     */
    public function testFloatTypeValidation(): void {
        $obj = new TypedPropertyMapClass();
        $obj->price = 9.99;
        
        $obj->mapPropertiesPublic(['price' => 19.99]);
        $this->assertEquals(19.99, $obj->price);
        
        $this->expectException(\InvalidArgumentException::class);
        $obj->mapPropertiesPublic(['price' => 'expensive']);
    }
    
    /**
     * Test array type validation
     */
    public function testArrayTypeValidation(): void {
        $obj = new TypedPropertyMapClass();
        $obj->settings = ['key' => 'value'];
        
        $obj->mapPropertiesPublic(['settings' => ['new' => 'data']]);
        $this->assertIsArray($obj->settings);
        
        $this->expectException(\InvalidArgumentException::class);
        $obj->mapPropertiesPublic(['settings' => 'not an array']);
    }
    
    /**
     * Test property exists check
     */
    public function testPropertyExistsCheck(): void {
        $obj = new SimplePropertyMapClass();
        
        // Existing property should work
        $obj->mapPropertiesPublic(['name' => 'Test']);
        $this->assertEquals('Test', $obj->name);
        
        // Non-existing property should throw
        $this->expectException(\InvalidArgumentException::class);
        $obj->mapPropertiesPublic(['nonexistent' => 'value'], false);
    }
    
    /**
     * Test zero values are properly mapped
     */
    public function testZeroValuesProperlyMapped(): void {
        $obj = new SimplePropertyMapClass();
        
        $obj->mapPropertiesPublic(['age' => 0]);
        $this->assertEquals(0, $obj->age);
    }
    
    /**
     * Test empty string values are properly mapped
     */
    public function testEmptyStringProperlyMapped(): void {
        $obj = new SimplePropertyMapClass();
        
        $obj->mapPropertiesPublic(['name' => '']);
        $this->assertEquals('', $obj->name);
    }
    
    /**
     * Test false values are properly mapped
     */
    public function testFalseValuesProperlyMapped(): void {
        $obj = new TypedPropertyMapClass();
        $obj->enabled = true;
        
        $obj->mapPropertiesPublic(['enabled' => false]);
        $this->assertFalse($obj->enabled);
    }
}

/**
 * Simple class using PropertyMap
 */
class SimplePropertyMapClass {
    use PropertyMap;
    
    public ?string $name = null;
    public ?int $age = null;
    public ?string $email = null;
    
    public function mapPropertiesPublic(array $inputs, ?bool $ignore = null): void {
        $this->mapProperties($inputs, $ignore);
    }
}

/**
 * Class with typed properties
 */
class TypedPropertyMapClass {
    use PropertyMap;
    
    public bool $enabled = false;
    public array $settings = [];
    public float $price = 0.0;
    
    public function mapPropertiesPublic(array $inputs, ?bool $ignore = null): void {
        $this->mapProperties($inputs, $ignore);
    }
}

/**
 * Class with constraints
 */
class ConstrainedPropertyMapClass {
    use PropertyMap;
    
    public mixed $username = null;
    public mixed $age = null;
    
    protected static array $constraints = [
        'username' => ['type' => 'string'],
        'age' => ['type' => 'integer']
    ];
    
    public function mapPropertiesPublic(array $inputs, ?bool $ignore = null): void {
        $this->mapProperties($inputs, $ignore);
    }
}

/**
 * Class with object constraint
 */
class ObjectConstrainedPropertyMapClass {
    use PropertyMap;
    
    public mixed $data = null;
    
    protected static array $constraints = [
        'data' => ['type' => \stdClass::class]
    ];
    
    public function mapPropertiesPublic(array $inputs, ?bool $ignore = null): void {
        $this->mapProperties($inputs, $ignore);
    }
}
