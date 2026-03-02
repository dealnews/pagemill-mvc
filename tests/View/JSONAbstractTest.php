<?php

declare(strict_types=1);

namespace PageMill\MVC\Tests\View;

use PageMill\HTTP\Response;
use PageMill\MVC\View\JSONAbstract;
use PHPUnit\Framework\TestCase;

/**
 * Tests for View\JSONAbstract
 *
 * JSONAbstract is a base class for JSON views that automatically
 * encodes data as JSON and outputs it.
 */
class JSONAbstractTest extends TestCase {
    
    /**
     * Test generate outputs JSON
     */
    public function testGenerateOutputsJSON(): void {
        $response = $this->createMock(Response::class);
        $data = ['status' => 'success', 'value' => 42];
        
        $view = new ConcreteJSONView($data, [], $response);
        
        ob_start();
        $view->generate();
        $output = ob_get_clean();
        
        $this->assertJson($output);
        $decoded = json_decode($output, true);
        $this->assertEquals(['status' => 'success', 'value' => 42], $decoded);
    }
    
    /**
     * Test generate with empty data
     */
    public function testGenerateWithEmptyData(): void {
        $response = $this->createMock(Response::class);
        
        $view = new EmptyJSONView([], [], $response);
        
        ob_start();
        $view->generate();
        $output = ob_get_clean();
        
        $this->assertJson($output);
        $this->assertEquals('[]', $output);
    }
    
    /**
     * Test generate with complex data structures
     */
    public function testGenerateWithComplexData(): void {
        $response = $this->createMock(Response::class);
        $data = [
            'user' => ['id' => 1, 'name' => 'John'],
            'items' => ['apple', 'banana', 'cherry'],
            'meta' => ['count' => 3, 'page' => 1]
        ];
        
        $view = new ConcreteJSONView($data, [], $response);
        
        ob_start();
        $view->generate();
        $output = ob_get_clean();
        
        $this->assertJson($output);
        $decoded = json_decode($output, true);
        $this->assertEquals($data, $decoded);
    }
    
    /**
     * Test getData must be implemented
     */
    public function testGetDataMustBeImplemented(): void {
        $response = $this->createMock(Response::class);
        $view = new ConcreteJSONView(['test' => 'value'], [], $response);
        
        $data = $view->callGetData();
        
        $this->assertIsArray($data);
        $this->assertEquals(['test' => 'value'], $data);
    }
    
    /**
     * Test view inherits from ViewAbstract
     */
    public function testInheritsFromViewAbstract(): void {
        $response = $this->createMock(Response::class);
        $view = new ConcreteJSONView([], [], $response);
        
        $this->assertInstanceOf(\PageMill\MVC\ViewAbstract::class, $view);
    }
    
    /**
     * Test JSON with nested arrays
     */
    public function testJSONWithNestedArrays(): void {
        $response = $this->createMock(Response::class);
        $data = [
            'level1' => [
                'level2' => [
                    'level3' => ['value' => 'deep']
                ]
            ]
        ];
        
        $view = new ConcreteJSONView($data, [], $response);
        
        ob_start();
        $view->generate();
        $output = ob_get_clean();
        
        $decoded = json_decode($output, true);
        $this->assertEquals('deep', $decoded['level1']['level2']['level3']['value']);
    }
    
    /**
     * Test JSON with numeric arrays
     */
    public function testJSONWithNumericArrays(): void {
        $response = $this->createMock(Response::class);
        $data = ['items' => [0 => 'first', 1 => 'second', 2 => 'third']];
        
        $view = new ConcreteJSONView($data, [], $response);
        
        ob_start();
        $view->generate();
        $output = ob_get_clean();
        
        $decoded = json_decode($output, true);
        $this->assertEquals(['first', 'second', 'third'], $decoded['items']);
    }
    
    /**
     * Test JSON with various types
     */
    public function testJSONWithVariousTypes(): void {
        $response = $this->createMock(Response::class);
        $data = [
            'string' => 'text',
            'int' => 42,
            'float' => 3.14,
            'bool' => true,
            'null' => null,
            'array' => [1, 2, 3]
        ];
        
        $view = new ConcreteJSONView($data, [], $response);
        
        ob_start();
        $view->generate();
        $output = ob_get_clean();
        
        $decoded = json_decode($output, true);
        $this->assertEquals('text', $decoded['string']);
        $this->assertEquals(42, $decoded['int']);
        $this->assertEquals(3.14, $decoded['float']);
        $this->assertTrue($decoded['bool']);
        $this->assertNull($decoded['null']);
        $this->assertEquals([1, 2, 3], $decoded['array']);
    }
    
    /**
     * Test view can access properties from data
     */
    public function testViewAccessesDataProperties(): void {
        $response = $this->createMock(Response::class);
        $data = ['message' => 'Hello', 'count' => 5];
        
        $view = new PropertyUsingJSONView($data, [], $response);
        
        ob_start();
        $view->generate();
        $output = ob_get_clean();
        
        $decoded = json_decode($output, true);
        $this->assertEquals('Hello', $decoded['message']);
        $this->assertEquals(5, $decoded['count']);
    }
    
    /**
     * Test view can access properties from inputs
     */
    public function testViewAccessesInputProperties(): void {
        $response = $this->createMock(Response::class);
        $inputs = ['page' => 2, 'limit' => 10];
        
        $view = new PropertyUsingJSONView([], $inputs, $response);
        
        ob_start();
        $view->generate();
        $output = ob_get_clean();
        
        $decoded = json_decode($output, true);
        $this->assertEquals(2, $decoded['page']);
        $this->assertEquals(10, $decoded['limit']);
    }
    
    /**
     * Test API response format
     */
    public function testAPIResponseFormat(): void {
        $response = $this->createMock(Response::class);
        $data = ['items' => ['apple', 'banana']];
        
        $view = new APIJSONView($data, [], $response);
        
        ob_start();
        $view->generate();
        $output = ob_get_clean();
        
        $decoded = json_decode($output, true);
        $this->assertEquals('success', $decoded['status']);
        $this->assertEquals(['apple', 'banana'], $decoded['data']['items']);
    }
    
    /**
     * Test error response format
     */
    public function testErrorResponseFormat(): void {
        $response = $this->createMock(Response::class);
        $data = ['error' => 'Not found'];
        
        $view = new ErrorJSONView($data, [], $response);
        
        ob_start();
        $view->generate();
        $output = ob_get_clean();
        
        $decoded = json_decode($output, true);
        $this->assertEquals('error', $decoded['status']);
        $this->assertEquals('Not found', $decoded['message']);
    }
    
    /**
     * Test generate can be called multiple times
     */
    public function testGenerateCanBeCalledMultipleTimes(): void {
        $response = $this->createMock(Response::class);
        $data = ['value' => 'test'];
        
        $view = new ConcreteJSONView($data, [], $response);
        
        ob_start();
        $view->generate();
        $output1 = ob_get_clean();
        
        ob_start();
        $view->generate();
        $output2 = ob_get_clean();
        
        $this->assertEquals($output1, $output2);
    }
    
    /**
     * Test JSON with empty string values
     */
    public function testJSONWithEmptyStrings(): void {
        $response = $this->createMock(Response::class);
        $data = ['empty' => ''];
        
        $view = new ConcreteJSONView($data, [], $response);
        
        ob_start();
        $view->generate();
        $output = ob_get_clean();
        
        $decoded = json_decode($output, true);
        $this->assertEquals('', $decoded['empty']);
    }
    
    /**
     * Test JSON with boolean values
     */
    public function testJSONWithBooleans(): void {
        $response = $this->createMock(Response::class);
        $data = ['enabled' => true, 'disabled' => false];
        
        $view = new ConcreteJSONView($data, [], $response);
        
        ob_start();
        $view->generate();
        $output = ob_get_clean();
        
        $decoded = json_decode($output, true);
        $this->assertTrue($decoded['enabled']);
        $this->assertFalse($decoded['disabled']);
    }
    
    /**
     * Test multiple instances maintain separate state
     */
    public function testMultipleInstancesSeparateState(): void {
        $response1 = $this->createMock(Response::class);
        $response2 = $this->createMock(Response::class);
        
        $view1 = new ConcreteJSONView(['value' => 'first'], [], $response1);
        $view2 = new ConcreteJSONView(['value' => 'second'], [], $response2);
        
        ob_start();
        $view1->generate();
        $output1 = ob_get_clean();
        
        ob_start();
        $view2->generate();
        $output2 = ob_get_clean();
        
        $this->assertNotEquals($output1, $output2);
        $this->assertStringContainsString('first', $output1);
        $this->assertStringContainsString('second', $output2);
    }
}

/**
 * Concrete implementation for basic testing
 */
class ConcreteJSONView extends JSONAbstract {
    
    public string $message = '';
    public int $count = 0;
    public int $page = 0;
    public int $limit = 0;
    public array $items = [];
    private array $rawData = [];
    
    public function __construct($data, $inputs, $response) {
        parent::__construct($data, $inputs, $response);
        // Store the raw data that was passed in
        $this->rawData = array_merge($data, $inputs);
    }
    
    protected function getData(): array {
        // Return the raw data that was passed in constructor
        return $this->rawData;
    }
    
    // Expose for testing
    public function callGetData(): array {
        return $this->getData();
    }
}

/**
 * Empty data implementation
 */
class EmptyJSONView extends JSONAbstract {
    
    protected function getData(): array {
        return [];
    }
}

/**
 * Property-using implementation
 */
class PropertyUsingJSONView extends ConcreteJSONView {
    
    protected function getData(): array {
        return [
            'message' => $this->message,
            'count' => $this->count,
            'page' => $this->page,
            'limit' => $this->limit
        ];
    }
}

/**
 * API response format
 */
class APIJSONView extends ConcreteJSONView {
    
    protected function getData(): array {
        return [
            'status' => 'success',
            'data' => parent::getData()
        ];
    }
}

/**
 * Error response format
 */
class ErrorJSONView extends ConcreteJSONView {
    
    public string $error = '';
    
    protected function getData(): array {
        return [
            'status' => 'error',
            'message' => $this->error
        ];
    }
}
