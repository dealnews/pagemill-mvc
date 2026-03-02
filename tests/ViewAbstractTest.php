<?php

declare(strict_types=1);

namespace PageMill\MVC\Tests;

use PageMill\HTTP\Response;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ViewAbstract
 *
 * ViewAbstract is the base class for all views in PageMill MVC.
 * It handles property mapping from data and inputs, and requires
 * child classes to implement the generate() method.
 */
class ViewAbstractTest extends TestCase {
    
    /**
     * Test constructor maps data and inputs to properties
     */
    public function testConstructorMapsProperties(): void {
        $data = ['title' => 'Test Title', 'content' => 'Test Content'];
        $inputs = ['page' => 1, 'limit' => 10];
        $response = $this->createMock(Response::class);
        
        $view = new ConcreteView($data, $inputs, $response);
        
        $this->assertEquals('Test Title', $view->title);
        $this->assertEquals('Test Content', $view->content);
        $this->assertEquals(1, $view->page);
        $this->assertEquals(10, $view->limit);
    }
    
    /**
     * Test constructor with empty data and inputs
     */
    public function testConstructorWithEmptyArrays(): void {
        $response = $this->createMock(Response::class);
        
        $view = new ConcreteView([], [], $response);
        
        $this->assertInstanceOf(ConcreteView::class, $view);
    }
    
    /**
     * Test constructor stores response object
     */
    public function testConstructorStoresResponse(): void {
        $response = $this->createMock(Response::class);
        
        $view = new ConcreteView([], [], $response);
        
        $this->assertSame($response, $view->getHttpResponse());
    }
    
    /**
     * Test properties from data take precedence
     */
    public function testDataAndInputsMerge(): void {
        $data = ['value' => 'from_data'];
        $inputs = ['value' => 'from_inputs', 'other' => 'input_value'];
        $response = $this->createMock(Response::class);
        
        // Inputs are mapped second, so they override data
        $view = new ConcreteView($data, $inputs, $response);
        
        $this->assertEquals('from_inputs', $view->value);
        $this->assertEquals('input_value', $view->other);
    }
    
    /**
     * Test view can access response for header manipulation
     */
    public function testViewCanAccessResponse(): void {
        $response = $this->createMock(Response::class);
        
        $view = new ConcreteView([], [], $response);
        
        // Just verify we can access the response object
        $this->assertSame($response, $view->getHttpResponse());
        $this->assertInstanceOf(Response::class, $view->getHttpResponse());
    }
    
    /**
     * Test generate must be implemented by child class
     */
    public function testGenerateMustBeImplemented(): void {
        $response = $this->createMock(Response::class);
        $view = new ConcreteView(['message' => 'Hello'], [], $response);
        
        ob_start();
        $view->generate();
        $output = ob_get_clean();
        
        $this->assertEquals('Hello', $output);
    }
    
    /**
     * Test view with complex data structures
     */
    public function testComplexDataStructures(): void {
        $data = [
            'user' => ['id' => 1, 'name' => 'John'],
            'items' => ['apple', 'banana', 'cherry'],
            'settings' => ['theme' => 'dark', 'notifications' => true]
        ];
        $response = $this->createMock(Response::class);
        
        $view = new ConcreteView($data, [], $response);
        
        $this->assertEquals(['id' => 1, 'name' => 'John'], $view->user);
        $this->assertEquals(['apple', 'banana', 'cherry'], $view->items);
        $this->assertEquals(['theme' => 'dark', 'notifications' => true], $view->settings);
    }
    
    /**
     * Test view with various data types
     */
    public function testVariousDataTypes(): void {
        $data = [
            'string' => 'text',
            'int' => 42,
            'float' => 3.14,
            'bool' => true,
            'null' => null,
            'array' => [1, 2, 3]
        ];
        $response = $this->createMock(Response::class);
        
        $view = new ConcreteView($data, [], $response);
        
        $this->assertIsString($view->string);
        $this->assertIsInt($view->int);
        $this->assertIsFloat($view->float);
        $this->assertIsBool($view->bool);
        $this->assertNull($view->null);
        $this->assertIsArray($view->array);
    }
    
    /**
     * Test property map trait ignore mode
     */
    public function testPropertyMapIgnoreMode(): void {
        $data = ['valid_property' => 'value', 'unknown_property' => 'should_be_ignored'];
        $response = $this->createMock(Response::class);
        
        // Constructor uses ignore=true, so unknown properties don't throw exception
        $view = new ConcreteView($data, [], $response);
        
        $this->assertEquals('value', $view->valid_property);
    }
    
    /**
     * Test inputs override data properties
     */
    public function testInputsOverrideData(): void {
        $data = ['id' => 1, 'name' => 'Original'];
        $inputs = ['name' => 'Updated'];
        $response = $this->createMock(Response::class);
        
        $view = new ConcreteView($data, $inputs, $response);
        
        $this->assertEquals(1, $view->id);
        $this->assertEquals('Updated', $view->name);
    }
    
    /**
     * Test view with only data
     */
    public function testViewWithOnlyData(): void {
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        $response = $this->createMock(Response::class);
        
        $view = new ConcreteView($data, [], $response);
        
        $this->assertEquals('value1', $view->key1);
        $this->assertEquals('value2', $view->key2);
    }
    
    /**
     * Test view with only inputs
     */
    public function testViewWithOnlyInputs(): void {
        $inputs = ['search' => 'test', 'page' => 5];
        $response = $this->createMock(Response::class);
        
        $view = new ConcreteView([], $inputs, $response);
        
        $this->assertEquals('test', $view->search);
        $this->assertEquals(5, $view->page);
    }
    
    /**
     * Test multiple instances maintain separate state
     */
    public function testMultipleInstancesSeparateState(): void {
        $response1 = $this->createMock(Response::class);
        $response2 = $this->createMock(Response::class);
        
        $view1 = new ConcreteView(['value' => 'first'], [], $response1);
        $view2 = new ConcreteView(['value' => 'second'], [], $response2);
        
        $this->assertEquals('first', $view1->value);
        $this->assertEquals('second', $view2->value);
        $this->assertNotSame($view1->getHttpResponse(), $view2->getHttpResponse());
    }
    
    /**
     * Test view with magic methods from PropertyMap
     */
    public function testPropertyMapMagicMethods(): void {
        $data = ['name' => 'Test'];
        $response = $this->createMock(Response::class);
        
        $view = new ConcreteView($data, [], $response);
        
        // __isset should work
        $this->assertTrue(isset($view->name));
        $this->assertFalse(isset($view->nonexistent));
        
        // __get should work
        $this->assertEquals('Test', $view->name);
    }
    
    /**
     * Test view can generate output using data
     */
    public function testGenerateUsesData(): void {
        $data = ['message' => 'Hello World'];
        $response = $this->createMock(Response::class);
        
        $view = new ConcreteView($data, [], $response);
        
        ob_start();
        $view->generate();
        $output = ob_get_clean();
        
        $this->assertEquals('Hello World', $output);
    }
    
    /**
     * Test view with numeric array keys
     */
    public function testNumericArrayKeys(): void {
        $data = ['items' => [0 => 'first', 1 => 'second', 2 => 'third']];
        $response = $this->createMock(Response::class);
        
        $view = new ConcreteView($data, [], $response);
        
        $this->assertEquals([0 => 'first', 1 => 'second', 2 => 'third'], $view->items);
    }
    
    /**
     * Test view with deeply nested data
     */
    public function testDeeplyNestedData(): void {
        $data = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'value' => 'deep'
                    ]
                ]
            ]
        ];
        $response = $this->createMock(Response::class);
        
        $view = new ConcreteView($data, [], $response);
        
        $this->assertEquals('deep', $view->level1['level2']['level3']['value']);
    }
    
    /**
     * Test generate can be called multiple times
     */
    public function testGenerateCanBeCalledMultipleTimes(): void {
        $data = ['message' => 'Test'];
        $response = $this->createMock(Response::class);
        
        $view = new ConcreteView($data, [], $response);
        
        ob_start();
        $view->generate();
        $output1 = ob_get_clean();
        
        ob_start();
        $view->generate();
        $output2 = ob_get_clean();
        
        $this->assertEquals($output1, $output2);
    }
    
    /**
     * Test view with boolean values
     */
    public function testBooleanValues(): void {
        $data = ['enabled' => true, 'disabled' => false];
        $response = $this->createMock(Response::class);
        
        $view = new ConcreteView($data, [], $response);
        
        $this->assertTrue($view->enabled);
        $this->assertFalse($view->disabled);
    }
    
    /**
     * Test view with null values
     */
    public function testNullValues(): void {
        $data = ['nullable_value' => null];
        $response = $this->createMock(Response::class);
        
        $view = new ConcreteView($data, [], $response);
        
        $this->assertNull($view->nullable_value);
    }
    
    /**
     * Test view with empty strings
     */
    public function testEmptyStrings(): void {
        $data = ['empty' => ''];
        $response = $this->createMock(Response::class);
        
        $view = new ConcreteView($data, [], $response);
        
        $this->assertEquals('', $view->empty);
        $this->assertIsString($view->empty);
    }
    
    /**
     * Test view with zero values
     */
    public function testZeroValues(): void {
        $data = ['zero_int' => 0, 'zero_float' => 0.0, 'zero_string' => '0'];
        $response = $this->createMock(Response::class);
        
        $view = new ConcreteView($data, [], $response);
        
        $this->assertEquals(0, $view->zero_int);
        $this->assertEquals(0.0, $view->zero_float);
        $this->assertEquals('0', $view->zero_string);
    }
}

/**
 * Concrete implementation of ViewAbstract for testing
 */
class ConcreteView extends \PageMill\MVC\ViewAbstract {
    
    public string $title = '';
    public string $content = '';
    public int $page = 0;
    public int $limit = 0;
    public string $value = '';
    public string $other = '';
    public string $message = '';
    public string $valid_property = '';
    public int $id = 0;
    public string $name = '';
    public string $key1 = '';
    public string $key2 = '';
    public string $search = '';
    public array $user = [];
    public array $items = [];
    public array $settings = [];
    public string $string = '';
    public int $int = 0;
    public float $float = 0.0;
    public bool $bool = false;
    public mixed $null = null;
    public array $array = [];
    public array $level1 = [];
    public bool $enabled = false;
    public bool $disabled = false;
    public ?string $nullable_value = null;
    public string $empty = '';
    public int $zero_int = 0;
    public float $zero_float = 0.0;
    public string $zero_string = '';
    
    public function generate(): void {
        if (!empty($this->message)) {
            echo $this->message;
        }
    }
    
    /**
     * Expose protected response for testing
     */
    public function getHttpResponse(): \PageMill\HTTP\Response {
        return $this->http_response;
    }
}
