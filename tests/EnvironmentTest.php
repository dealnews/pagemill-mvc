<?php

declare(strict_types=1);

namespace PageMill\MVC\Tests;

use PageMill\MVC\Environment;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Environment
 *
 * Environment is a static configuration manager for the PageMill MVC framework.
 * It manages global settings like debug mode.
 */
class EnvironmentTest extends TestCase {
    
    /**
     * Reset environment state before each test
     */
    protected function setUp(): void {
        // Reset to default state (false)
        Environment::debug(false);
    }
    
    /**
     * Reset environment state after each test
     */
    protected function tearDown(): void {
        // Ensure we leave in a clean state
        Environment::debug(false);
    }
    
    /**
     * Test default debug state is false
     */
    public function testDefaultDebugStateIsFalse(): void {
        $this->assertFalse(Environment::debug());
    }
    
    /**
     * Test debug() without arguments returns current state
     */
    public function testDebugWithoutArgumentsReturnsCurrentState(): void {
        Environment::debug(true);
        $this->assertTrue(Environment::debug());
        
        Environment::debug(false);
        $this->assertFalse(Environment::debug());
    }
    
    /**
     * Test debug() with argument returns previous state
     */
    public function testDebugWithArgumentReturnsPreviousState(): void {
        // Start with false (default)
        $previous = Environment::debug(true);
        $this->assertFalse($previous);
        
        // Now it's true, set to false
        $previous = Environment::debug(false);
        $this->assertTrue($previous);
        
        // Now it's false
        $this->assertFalse(Environment::debug());
    }
    
    /**
     * Test enabling debug mode
     */
    public function testEnableDebugMode(): void {
        $previous = Environment::debug(true);
        
        $this->assertFalse($previous);
        $this->assertTrue(Environment::debug());
    }
    
    /**
     * Test disabling debug mode
     */
    public function testDisableDebugMode(): void {
        Environment::debug(true);
        
        $previous = Environment::debug(false);
        
        $this->assertTrue($previous);
        $this->assertFalse(Environment::debug());
    }
    
    /**
     * Test toggling debug mode multiple times
     */
    public function testToggleDebugMultipleTimes(): void {
        $this->assertFalse(Environment::debug());
        
        Environment::debug(true);
        $this->assertTrue(Environment::debug());
        
        Environment::debug(false);
        $this->assertFalse(Environment::debug());
        
        Environment::debug(true);
        $this->assertTrue(Environment::debug());
        
        Environment::debug(false);
        $this->assertFalse(Environment::debug());
    }
    
    /**
     * Test debug state persists across calls
     */
    public function testDebugStatePersists(): void {
        Environment::debug(true);
        
        // Multiple reads should return same state
        $this->assertTrue(Environment::debug());
        $this->assertTrue(Environment::debug());
        $this->assertTrue(Environment::debug());
    }
    
    /**
     * Test setting debug to same value
     */
    public function testSettingDebugToSameValue(): void {
        Environment::debug(true);
        
        $previous = Environment::debug(true);
        $this->assertTrue($previous);
        $this->assertTrue(Environment::debug());
    }
    
    /**
     * Test debug with null explicitly returns current state
     */
    public function testDebugWithNullReturnsCurrentState(): void {
        Environment::debug(true);
        
        $result = Environment::debug(null);
        
        // Should return current state (before any change)
        $this->assertTrue($result);
        // State should now be false (null casts to false)
        $this->assertFalse(Environment::debug());
    }
    
    /**
     * Test debug state is global/static
     */
    public function testDebugStateIsGlobal(): void {
        Environment::debug(true);
        
        // Changes in one "context" affect others (static property)
        $state1 = Environment::debug();
        $state2 = Environment::debug();
        
        $this->assertEquals($state1, $state2);
        $this->assertTrue($state1);
    }
    
    /**
     * Test return value when enabling from disabled
     */
    public function testReturnValueWhenEnabling(): void {
        Environment::debug(false);
        
        $previous = Environment::debug(true);
        
        $this->assertFalse($previous, 'Should return previous state (false)');
        $this->assertTrue(Environment::debug(), 'New state should be true');
    }
    
    /**
     * Test return value when disabling from enabled
     */
    public function testReturnValueWhenDisabling(): void {
        Environment::debug(true);
        
        $previous = Environment::debug(false);
        
        $this->assertTrue($previous, 'Should return previous state (true)');
        $this->assertFalse(Environment::debug(), 'New state should be false');
    }
    
    /**
     * Test method can be called statically
     */
    public function testMethodCanBeCalledStatically(): void {
        $result = Environment::debug(true);
        
        $this->assertIsBool($result);
        $this->assertTrue(Environment::debug());
    }
    
    /**
     * Test type coercion of boolean argument
     */
    public function testBooleanArgumentTypeCoercion(): void {
        // The method casts to bool, so it handles the bool type correctly
        Environment::debug(true);
        $this->assertTrue(Environment::debug());
        
        Environment::debug(false);
        $this->assertFalse(Environment::debug());
    }
    
    /**
     * Test rapid toggling
     */
    public function testRapidToggling(): void {
        $states = [];
        
        for ($i = 0; $i < 10; $i++) {
            $toggle = ($i % 2 === 0);
            Environment::debug($toggle);
            $states[] = Environment::debug();
        }
        
        // Last state should be true (i=9, 9%2=1, so toggle=false was set, but we read after)
        // Actually, let's just verify the pattern works
        $this->assertFalse(Environment::debug());
    }
    
    /**
     * Test initial state consistency
     */
    public function testInitialStateConsistency(): void {
        // After setUp, should always be false
        $this->assertFalse(Environment::debug());
        $this->assertFalse(Environment::debug());
    }
    
    /**
     * Test reading state multiple times doesn't change it
     */
    public function testReadingStateDoesntChangeIt(): void {
        Environment::debug(true);
        
        $read1 = Environment::debug();
        $read2 = Environment::debug();
        $read3 = Environment::debug();
        
        $this->assertTrue($read1);
        $this->assertTrue($read2);
        $this->assertTrue($read3);
    }
    
    /**
     * Test func_num_args logic
     */
    public function testFuncNumArgsLogic(): void {
        // With argument (func_num_args > 0)
        $prev1 = Environment::debug(true);
        $this->assertTrue(Environment::debug());
        
        // Without argument (func_num_args == 0)
        $current = Environment::debug();
        $this->assertTrue($current);
        $this->assertTrue(Environment::debug());
    }
    
    /**
     * Test sequence of operations
     */
    public function testSequenceOfOperations(): void {
        // Start: false (default)
        $this->assertFalse(Environment::debug());
        
        // Set to true, get false (previous)
        $prev1 = Environment::debug(true);
        $this->assertFalse($prev1);
        
        // Read: true
        $this->assertTrue(Environment::debug());
        
        // Set to true again, get true (previous)
        $prev2 = Environment::debug(true);
        $this->assertTrue($prev2);
        
        // Set to false, get true (previous)
        $prev3 = Environment::debug(false);
        $this->assertTrue($prev3);
        
        // Read: false
        $this->assertFalse(Environment::debug());
    }
    
    /**
     * Test boolean return type
     */
    public function testBooleanReturnType(): void {
        $result1 = Environment::debug(true);
        $result2 = Environment::debug();
        
        $this->assertIsBool($result1);
        $this->assertIsBool($result2);
    }
    
    /**
     * Test debug false explicitly
     */
    public function testDebugFalseExplicitly(): void {
        Environment::debug(false);
        
        $this->assertFalse(Environment::debug());
        $this->assertNotTrue(Environment::debug());
    }
    
    /**
     * Test debug true explicitly
     */
    public function testDebugTrueExplicitly(): void {
        Environment::debug(true);
        
        $this->assertTrue(Environment::debug());
        $this->assertNotFalse(Environment::debug());
    }
}
