<?php

declare(strict_types=1);

namespace PageMill\MVC\Tests;

use PageMill\MVC\ActionAbstract;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for ActionAbstract
 *
 * Tests the base action functionality including construction, property mapping,
 * error handling, and abstract method enforcement.
 */
class ActionAbstractTest extends TestCase {

    /**
     * Tests that constructor properly maps input properties
     */
    public function testConstructorMapsProperties(): void {
        $inputs = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30
        ];

        $action = new ConcreteAction($inputs);

        $this->assertEquals('John Doe', $action->name);
        $this->assertEquals('john@example.com', $action->email);
        $this->assertEquals(30, $action->age);
    }

    /**
     * Tests that constructor ignores unknown properties
     */
    public function testConstructorIgnoresUnknownProperties(): void {
        $inputs = [
            'name' => 'John Doe',
            'unknown_property' => 'should be ignored',
            'another_unknown' => 123
        ];

        $action = new ConcreteAction($inputs);

        $this->assertEquals('John Doe', $action->name);
        $this->assertFalse(property_exists($action, 'unknown_property'));
        $this->assertFalse(property_exists($action, 'another_unknown'));
    }

    /**
     * Tests that errors array is initially empty
     */
    public function testErrorsInitiallyEmpty(): void {
        $action = new ConcreteAction([]);

        $this->assertIsArray($action->errors());
        $this->assertEmpty($action->errors());
    }

    /**
     * Tests that errors can be added and retrieved
     */
    public function testCanAddAndRetrieveErrors(): void {
        $action = new ConcreteAction([]);
        $action->addError('First error');
        $action->addError('Second error');

        $errors = $action->errors();

        $this->assertCount(2, $errors);
        $this->assertEquals('First error', $errors[0]);
        $this->assertEquals('Second error', $errors[1]);
    }

    /**
     * Tests that errors can be structured arrays
     */
    public function testErrorsCanBeStructuredArrays(): void {
        $action = new ConcreteAction([]);
        $action->addError(['field' => 'email', 'message' => 'Invalid email']);
        $action->addError(['field' => 'name', 'message' => 'Name required']);

        $errors = $action->errors();

        $this->assertCount(2, $errors);
        $this->assertIsArray($errors[0]);
        $this->assertEquals('email', $errors[0]['field']);
        $this->assertEquals('Invalid email', $errors[0]['message']);
    }

    /**
     * Tests doAction with empty data array
     */
    public function testDoActionWithEmptyData(): void {
        $action = new ConcreteAction(['name' => 'Test']);
        $result = $action->doAction([]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('processed', $result);
        $this->assertTrue($result['processed']);
    }

    /**
     * Tests doAction with data array
     */
    public function testDoActionWithData(): void {
        $action = new ConcreteAction(['name' => 'Test']);
        $data = ['existing' => 'value', 'count' => 5];
        $result = $action->doAction($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('processed', $result);
        $this->assertArrayHasKey('input_name', $result);
        $this->assertEquals('Test', $result['input_name']);
    }

    /**
     * Tests doAction can return null
     */
    public function testDoActionCanReturnNull(): void {
        $action = new NullReturningAction([]);
        $result = $action->doAction([]);

        $this->assertNull($result);
    }

    /**
     * Tests doAction can return scalar values
     */
    public function testDoActionCanReturnScalar(): void {
        $action = new ScalarReturningAction([]);
        $result = $action->doAction([]);

        $this->assertIsString($result);
        $this->assertEquals('success', $result);
    }

    /**
     * Tests action with validation that adds errors
     */
    public function testActionWithValidationErrors(): void {
        $action = new ValidatingAction(['email' => 'invalid']);
        $action->doAction([]);

        $errors = $action->errors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Invalid email', $errors[0]);
    }

    /**
     * Tests action with successful validation
     */
    public function testActionWithSuccessfulValidation(): void {
        $action = new ValidatingAction(['email' => 'valid@example.com']);
        $action->doAction([]);

        $errors = $action->errors();
        $this->assertEmpty($errors);
    }

    /**
     * Tests that PropertyMap trait is correctly used
     */
    public function testPropertyMapTraitUsage(): void {
        $inputs = [
            'name' => 'John',
            'email' => 'john@example.com'
        ];

        $action = new ConcreteAction($inputs);

        // PropertyMap should have mapped these properties
        $this->assertTrue(property_exists($action, 'name'));
        $this->assertTrue(property_exists($action, 'email'));
    }

    /**
     * Tests constructor with empty inputs array
     */
    public function testConstructorWithEmptyInputs(): void {
        $action = new ConcreteAction([]);

        $this->assertInstanceOf(ActionAbstract::class, $action);
        $this->assertEmpty($action->errors());
    }

    /**
     * Tests multiple action instances maintain separate state
     */
    public function testMultipleInstancesMaintainSeparateState(): void {
        $action1 = new ConcreteAction(['name' => 'Action1']);
        $action2 = new ConcreteAction(['name' => 'Action2']);

        $action1->addError('Error 1');
        $action2->addError('Error 2');

        $this->assertCount(1, $action1->errors());
        $this->assertCount(1, $action2->errors());
        $this->assertEquals('Error 1', $action1->errors()[0]);
        $this->assertEquals('Error 2', $action2->errors()[0]);
    }

    /**
     * Tests that doAction can access instance properties
     */
    public function testDoActionAccessesInstanceProperties(): void {
        $action = new PropertyAccessAction(['multiplier' => 5]);
        $result = $action->doAction(['value' => 10]);

        $this->assertIsArray($result);
        $this->assertEquals(50, $result['result']);
    }

    /**
     * Tests action with complex nested data
     */
    public function testActionWithComplexNestedData(): void {
        $action = new ConcreteAction(['name' => 'Test']);
        $data = [
            'user' => [
                'id' => 1,
                'profile' => ['name' => 'John']
            ],
            'settings' => ['theme' => 'dark']
        ];

        $result = $action->doAction($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('processed', $result);
    }
}

/**
 * Concrete implementation of ActionAbstract for testing
 */
class ConcreteAction extends ActionAbstract {

    public ?string $name = null;
    public ?string $email = null;
    public ?int $age = null;

    public function doAction(array $data = []): mixed {
        $result = ['processed' => true];
        if (!empty($this->name)) {
            $result['input_name'] = $this->name;
        }
        return $result;
    }

    public function addError(string|array $error): void {
        $this->errors[] = $error;
    }
}

/**
 * Action that returns null
 */
class NullReturningAction extends ActionAbstract {

    public function doAction(array $data = []): mixed {
        return null;
    }
}

/**
 * Action that returns scalar value
 */
class ScalarReturningAction extends ActionAbstract {

    public function doAction(array $data = []): mixed {
        return 'success';
    }
}

/**
 * Action with validation logic
 */
class ValidatingAction extends ActionAbstract {

    public ?string $email = null;

    public function doAction(array $data = []): mixed {
        if (!empty($this->email) && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = 'Invalid email format';
            return null;
        }
        return ['valid' => true];
    }
}

/**
 * Action that uses properties in calculations
 */
class PropertyAccessAction extends ActionAbstract {

    public ?int $multiplier = null;

    public function doAction(array $data = []): mixed {
        if (isset($data['value']) && !is_null($this->multiplier)) {
            return ['result' => $data['value'] * $this->multiplier];
        }
        return null;
    }
}
