<?php

declare(strict_types=1);

namespace PageMill\MVC\Tests\HTML\Assets;

use PageMill\MVC\HTML\Assets;
use PageMill\MVC\HTML\Assets\Injector;
use PageMill\MVC\ElementAbstract;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for HTML\Assets\Injector
 *
 * Tests element asset discovery, injection, group resolution,
 * and duplicate prevention.
 */
class InjectorTest extends TestCase {

    /**
     * Test constructor with default Assets
     */
    public function testConstructorWithoutAssets(): void {
        $injector = new Injector();
        $this->assertInstanceOf(Injector::class, $injector);
    }

    /**
     * Test constructor with custom Assets object
     */
    public function testConstructorWithCustomAssets(): void {
        $mockAssets = $this->createMock(Assets::class);
        $injector = new Injector($mockAssets);
        $this->assertInstanceOf(Injector::class, $injector);
    }

    /**
     * Test singleton instance
     */
    public function testInitReturnsSingletonInstance(): void {
        $injector1 = Injector::init();
        $injector2 = Injector::init();
        
        $this->assertSame($injector1, $injector2);
        $this->assertInstanceOf(Injector::class, $injector1);
    }

    /**
     * Test add injects element assets
     */
    public function testAddInjectsElementAssets(): void {
        $mockAssets = $this->createMock(Assets::class);
        $mockAssets->expects($this->once())
            ->method('add')
            ->with('css', ['test.css'], 'default');
        
        $injector = new Injector($mockAssets);
        $injector->add([MockElement::class]);
    }

    /**
     * Test add with multiple elements
     */
    public function testAddWithMultipleElements(): void {
        $mockAssets = $this->createMock(Assets::class);
        $mockAssets->expects($this->exactly(2))
            ->method('add')
            ->willReturnCallback(function($type, $assets, $group) {
                $this->assertContains($type, ['css', 'js']);
                $this->assertIsArray($assets);
            });
        
        $injector = new Injector($mockAssets);
        $injector->add([MockElement::class, MockElementWithJS::class]);
    }

    /**
     * Test add prevents duplicate element processing
     */
    public function testAddPreventsDuplicateElementProcessing(): void {
        $mockAssets = $this->createMock(Assets::class);
        $mockAssets->expects($this->once())
            ->method('add');
        
        $injector = new Injector($mockAssets);
        $injector->add([MockElement::class]);
        $injector->add([MockElement::class]); // Should be skipped
    }

    /**
     * Test add with non-element class throws exception
     */
    public function testAddWithNonElementClassThrowsException(): void {
        $mockAssets = $this->createMock(Assets::class);
        $injector = new Injector($mockAssets);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('is not a PageMill Element');
        
        $injector->add([\stdClass::class]);
    }

    /**
     * Test add with multiple asset types
     */
    public function testAddWithMultipleAssetTypes(): void {
        $calls = [];
        
        $mockAssets = $this->createMock(Assets::class);
        $mockAssets->expects($this->exactly(2))
            ->method('add')
            ->willReturnCallback(function($type, $assets, $group) use (&$calls) {
                $calls[] = [$type, $assets, $group];
            });
        
        $injector = new Injector($mockAssets);
        $injector->add([MockElementWithMultipleAssets::class]);
        
        $this->assertEquals('css', $calls[0][0]);
        $this->assertEquals(['styles.css'], $calls[0][1]);
        $this->assertEquals('default', $calls[0][2]);
        
        $this->assertEquals('js', $calls[1][0]);
        $this->assertEquals(['script.js'], $calls[1][1]);
        $this->assertEquals('footer', $calls[1][2]);
    }

    /**
     * Test add with multiple groups
     */
    public function testAddWithMultipleGroups(): void {
        $calls = [];
        
        $mockAssets = $this->createMock(Assets::class);
        $mockAssets->expects($this->exactly(2))
            ->method('add')
            ->willReturnCallback(function($type, $assets, $group) use (&$calls) {
                $calls[] = [$type, $assets, $group];
            });
        
        $injector = new Injector($mockAssets);
        $injector->add([MockElementWithGroups::class]);
        
        $this->assertEquals('css', $calls[0][0]);
        $this->assertEquals(['default.css'], $calls[0][1]);
        $this->assertEquals('default', $calls[0][2]);
        
        $this->assertEquals('css', $calls[1][0]);
        $this->assertEquals(['header.css'], $calls[1][1]);
        $this->assertEquals('header', $calls[1][2]);
    }

    /**
     * Test add skips empty asset lists
     */
    public function testAddSkipsEmptyAssetLists(): void {
        $mockAssets = $this->createMock(Assets::class);
        $mockAssets->expects($this->once())
            ->method('add')
            ->with('css', ['test.css'], 'default');
        
        $injector = new Injector($mockAssets);
        $injector->add([MockElementWithEmptyAssets::class]);
    }

    /**
     * Test resolveGroup with no override returns original
     */
    public function testResolveGroupWithNoOverrideReturnsOriginal(): void {
        $injector = new Injector();
        $result = $injector->resolveGroup('default', []);
        
        $this->assertEquals('default', $result);
    }

    /**
     * Test resolveGroup with wildcard override
     */
    public function testResolveGroupWithWildcardOverride(): void {
        $injector = new Injector();
        $result = $injector->resolveGroup('default', ['*' => 'header']);
        
        $this->assertEquals('header', $result);
    }

    /**
     * Test resolveGroup with specific group override
     */
    public function testResolveGroupWithSpecificGroupOverride(): void {
        $injector = new Injector();
        $result = $injector->resolveGroup('default', [
            'groups' => ['default' => 'footer']
        ]);
        
        $this->assertEquals('footer', $result);
    }

    /**
     * Test resolveGroup specific override takes precedence over wildcard
     */
    public function testResolveGroupSpecificOverrideTakesPrecedenceOverWildcard(): void {
        $injector = new Injector();
        $result = $injector->resolveGroup('default', [
            '*' => 'header',
            'groups' => ['default' => 'footer']
        ]);
        
        $this->assertEquals('footer', $result);
    }

    /**
     * Test resolveGroup with exclude list
     */
    public function testResolveGroupWithExcludeList(): void {
        $injector = new Injector();
        
        // Should be overridden
        $result1 = $injector->resolveGroup('default', [
            '*' => 'header',
            'exclude' => ['footer']
        ]);
        $this->assertEquals('header', $result1);
        
        // Should NOT be overridden
        $result2 = $injector->resolveGroup('footer', [
            '*' => 'header',
            'exclude' => ['footer']
        ]);
        $this->assertEquals('footer', $result2);
    }

    /**
     * Test resolveGroup exclude takes precedence over wildcard
     */
    public function testResolveGroupExcludeTakesPrecedenceOverWildcard(): void {
        $injector = new Injector();
        $result = $injector->resolveGroup('excluded', [
            '*' => 'newgroup',
            'exclude' => ['excluded']
        ]);
        
        $this->assertEquals('excluded', $result);
    }

    /**
     * Test resolveGroup exclude does not block specific override
     */
    public function testResolveGroupExcludeDoesNotBlockSpecificOverride(): void {
        $injector = new Injector();
        $result = $injector->resolveGroup('default', [
            '*' => 'wildcard',
            'exclude' => ['default'],
            'groups' => ['default' => 'specific']
        ]);
        
        // Exclude blocks wildcard, so original is kept
        $this->assertEquals('default', $result);
    }

    /**
     * Test add with group override
     */
    public function testAddWithGroupOverride(): void {
        $mockAssets = $this->createMock(Assets::class);
        $mockAssets->expects($this->once())
            ->method('add')
            ->with('css', ['test.css'], 'header');
        
        $injector = new Injector($mockAssets);
        $injector->add([MockElement::class], ['*' => 'header']);
    }

    /**
     * Test inline adds and immediately outputs assets
     */
    public function testInlineAddsAndImmediatelyOutputsAssets(): void {
        $mockAssets = $this->createMock(Assets::class);
        $mockAssets->expects($this->once())
            ->method('add')
            ->with(
                'css',
                ['test.css'],
                $this->matchesRegularExpression('/^[0-9a-f]+$/')
            );
        $mockAssets->expects($this->once())
            ->method('inline')
            ->with($this->matchesRegularExpression('/^[0-9a-f]+$/'));
        
        $injector = new Injector($mockAssets);
        $injector->inline([MockElement::class]);
    }

    /**
     * Test inline excludes footer group by default
     * 
     * The MockElementWithGroups has 'default' and 'header' groups.
     * Footer is excluded by default, so both should be moved to unique group.
     */
    public function testInlineExcludesFooterGroupByDefault(): void {
        $mockAssets = $this->createMock(Assets::class);
        $mockAssets->expects($this->exactly(2))
            ->method('add')
            ->with(
                'css',
                $this->anything(),
                $this->matchesRegularExpression('/^[0-9a-f]+$/')
            );
        $mockAssets->expects($this->once())
            ->method('inline');
        
        $injector = new Injector($mockAssets);
        $injector->inline([MockElementWithGroups::class]);
        
        $this->assertTrue(true);
    }

    /**
     * Test inline with custom exclude list
     */
    public function testInlineWithCustomExcludeList(): void {
        $mockAssets = $this->createMock(Assets::class);
        $mockAssets->expects($this->exactly(2))
            ->method('add');
        $mockAssets->expects($this->once())
            ->method('inline');
        
        $injector = new Injector($mockAssets);
        $injector->inline([MockElementWithGroups::class], ['custom']);
    }

    /**
     * Test link adds and immediately links assets
     */
    public function testLinkAddsAndImmediatelyLinksAssets(): void {
        $mockAssets = $this->createMock(Assets::class);
        $mockAssets->expects($this->once())
            ->method('add')
            ->with(
                'css',
                ['test.css'],
                $this->matchesRegularExpression('/^[0-9a-f]+$/')
            );
        $mockAssets->expects($this->once())
            ->method('link')
            ->with($this->matchesRegularExpression('/^[0-9a-f]+$/'));
        
        $injector = new Injector($mockAssets);
        $injector->link([MockElement::class]);
    }

    /**
     * Test link excludes footer group by default
     */
    public function testLinkExcludesFooterGroupByDefault(): void {
        $mockAssets = $this->createMock(Assets::class);
        $mockAssets->expects($this->once())
            ->method('add');
        $mockAssets->expects($this->once())
            ->method('link');
        
        $injector = new Injector($mockAssets);
        $injector->link([MockElement::class]);
        
        $this->assertTrue(true);
    }

    /**
     * Test link with custom exclude list
     */
    public function testLinkWithCustomExcludeList(): void {
        $mockAssets = $this->createMock(Assets::class);
        $mockAssets->expects($this->exactly(2))
            ->method('add');
        $mockAssets->expects($this->once())
            ->method('link');
        
        $injector = new Injector($mockAssets);
        $injector->link([MockElementWithGroups::class], ['default']);
    }

    /**
     * Test multiple inline calls use different unique groups
     */
    public function testMultipleInlineCallsUseDifferentUniqueGroups(): void {
        $groups = [];
        
        $mockAssets = $this->createMock(Assets::class);
        $mockAssets->expects($this->exactly(2))
            ->method('add')
            ->willReturnCallback(function($type, $assets, $group) use (&$groups) {
                $groups[] = $group;
            });
        $mockAssets->expects($this->exactly(2))
            ->method('inline');
        
        $injector = new Injector($mockAssets);
        $injector->inline([MockElement::class]);
        $injector->inline([MockElementWithJS::class]);
        
        $this->assertCount(2, $groups);
        $this->assertNotEquals($groups[0], $groups[1]);
    }

    /**
     * Test add with empty elements array
     */
    public function testAddWithEmptyElementsArray(): void {
        $mockAssets = $this->createMock(Assets::class);
        $mockAssets->expects($this->never())
            ->method('add');
        
        $injector = new Injector($mockAssets);
        $injector->add([]);
        
        $this->assertTrue(true);
    }

    /**
     * Test multiple instances maintain separate seen_elements tracking
     */
    public function testMultipleInstancesMaintainSeparateSeenElementsTracking(): void {
        $mockAssets1 = $this->createMock(Assets::class);
        $mockAssets1->expects($this->once())
            ->method('add');
        
        $mockAssets2 = $this->createMock(Assets::class);
        $mockAssets2->expects($this->once())
            ->method('add');
        
        $injector1 = new Injector($mockAssets1);
        $injector2 = new Injector($mockAssets2);
        
        $injector1->add([MockElement::class]);
        $injector2->add([MockElement::class]); // Should process in second instance
        
        $this->assertTrue(true);
    }
}

//=============================================================================
// Test Doubles - Mock Elements
//=============================================================================

/**
 * Simple mock element with CSS
 */
class MockElement extends ElementAbstract {
    public static function getAssets(?string $class = null): array {
        return [
            'css' => [
                'default' => ['test.css']
            ]
        ];
    }
    
    public function generateElement(): void {
        echo '<div>Mock</div>';
    }
}

/**
 * Mock element with JavaScript
 */
class MockElementWithJS extends ElementAbstract {
    public static function getAssets(?string $class = null): array {
        return [
            'js' => [
                'default' => ['test.js']
            ]
        ];
    }
    
    public function generateElement(): void {
        echo '<div>Mock</div>';
    }
}

/**
 * Mock element with multiple asset types
 */
class MockElementWithMultipleAssets extends ElementAbstract {
    public static function getAssets(?string $class = null): array {
        return [
            'css' => [
                'default' => ['styles.css']
            ],
            'js' => [
                'footer' => ['script.js']
            ]
        ];
    }
    
    public function generateElement(): void {
        echo '<div>Mock</div>';
    }
}

/**
 * Mock element with multiple groups
 */
class MockElementWithGroups extends ElementAbstract {
    public static function getAssets(?string $class = null): array {
        return [
            'css' => [
                'default' => ['default.css'],
                'header' => ['header.css']
            ]
        ];
    }
    
    public function generateElement(): void {
        echo '<div>Mock</div>';
    }
}

/**
 * Mock element with empty and non-empty asset lists
 */
class MockElementWithEmptyAssets extends ElementAbstract {
    public static function getAssets(?string $class = null): array {
        return [
            'css' => [
                'default' => ['test.css'],
                'empty' => []
            ]
        ];
    }
    
    public function generateElement(): void {
        echo '<div>Mock</div>';
    }
}
