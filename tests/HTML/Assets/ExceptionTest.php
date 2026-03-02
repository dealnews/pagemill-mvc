<?php

declare(strict_types=1);

namespace PageMill\MVC\Tests\HTML\Assets;

use PageMill\MVC\HTML\Assets\Exception;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for HTML\Assets\Exception
 *
 * Tests the custom exception class for asset-related errors.
 */
class ExceptionTest extends TestCase {

    /**
     * Test that Exception extends base Exception class
     */
    public function testExceptionExtendsBaseException(): void {
        $exception = new Exception();
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    /**
     * Test exception with custom message
     */
    public function testExceptionWithMessage(): void {
        $message = 'Asset file not found: /path/to/missing.css';
        $exception = new Exception($message);
        
        $this->assertEquals($message, $exception->getMessage());
    }

    /**
     * Test exception with message and code
     */
    public function testExceptionWithMessageAndCode(): void {
        $message = 'Asset file not found';
        $code = 404;
        $exception = new Exception($message, $code);
        
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }

    /**
     * Test exception with previous exception
     */
    public function testExceptionWithPrevious(): void {
        $previous = new \RuntimeException('File system error');
        $exception = new Exception('Asset error', 0, $previous);
        
        $this->assertSame($previous, $exception->getPrevious());
    }

    /**
     * Test exception can be thrown and caught
     */
    public function testExceptionCanBeThrownAndCaught(): void {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test exception');
        
        throw new Exception('Test exception');
    }

    /**
     * Test exception can be caught as base Exception
     */
    public function testExceptionCanBeCaughtAsBaseException(): void {
        try {
            throw new Exception('Asset error');
        } catch (\Exception $e) {
            $this->assertInstanceOf(Exception::class, $e);
            $this->assertEquals('Asset error', $e->getMessage());
        }
    }

    /**
     * Test exception with empty message
     */
    public function testExceptionWithEmptyMessage(): void {
        $exception = new Exception('');
        $this->assertEquals('', $exception->getMessage());
    }

    /**
     * Test exception default values
     */
    public function testExceptionDefaultValues(): void {
        $exception = new Exception();
        
        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    /**
     * Test exception file and line information
     */
    public function testExceptionFileAndLineInfo(): void {
        $exception = new Exception('Test');
        
        $this->assertIsString($exception->getFile());
        $this->assertIsInt($exception->getLine());
        $this->assertGreaterThan(0, $exception->getLine());
    }

    /**
     * Test exception trace information
     */
    public function testExceptionTraceInfo(): void {
        $exception = new Exception('Test');
        
        $trace = $exception->getTrace();
        $this->assertIsArray($trace);
        
        $traceString = $exception->getTraceAsString();
        $this->assertIsString($traceString);
    }

    /**
     * Test exception string representation
     */
    public function testExceptionToString(): void {
        $exception = new Exception('Asset error', 123);
        $string = (string) $exception;
        
        $this->assertStringContainsString('Asset error', $string);
        $this->assertStringContainsString('Exception', $string);
        // Note: Exception code doesn't appear in __toString() output by default
        $this->assertEquals(123, $exception->getCode());
    }
}
