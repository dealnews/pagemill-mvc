<?php

declare(strict_types=1);

namespace PageMill\MVC\Tests\HTML;

use PageMill\MVC\HTML\Document;
use PageMill\HTTP\Response\Headers;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for HTML\Document
 *
 * Tests document metadata management, property access, header generation,
 * and HTML head section output.
 */
class DocumentTest extends TestCase {

    /**
     * Test singleton instance
     */
    public function testInitReturnsSingletonInstance(): void {
        $doc1 = Document::init();
        $doc2 = Document::init();
        
        $this->assertSame($doc1, $doc2);
        $this->assertInstanceOf(Document::class, $doc1);
    }

    /**
     * Test constructor with default Headers
     */
    public function testConstructorWithoutHeaders(): void {
        $doc = new Document();
        $this->assertInstanceOf(Document::class, $doc);
    }

    /**
     * Test constructor with custom Headers object
     */
    public function testConstructorWithCustomHeaders(): void {
        $mockHeaders = $this->createMock(Headers::class);
        $doc = new Document($mockHeaders);
        $this->assertInstanceOf(Document::class, $doc);
    }

    /**
     * Test setting and getting title property
     */
    public function testSetAndGetTitle(): void {
        $doc = new Document();
        $doc->title = 'Test Page Title';
        
        $this->assertEquals('Test Page Title', $doc->title);
    }

    /**
     * Test setting title with invalid type throws exception
     */
    public function testSetTitleWithInvalidTypeThrowsException(): void {
        $doc = new Document();
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value for title');
        
        $doc->title = 123;
    }

    /**
     * Test setting and getting canonical URL
     */
    public function testSetAndGetCanonical(): void {
        $doc = new Document();
        $doc->canonical = 'https://example.com/page';
        
        $this->assertEquals('https://example.com/page', $doc->canonical);
    }

    /**
     * Test setting canonical with invalid URL throws exception
     */
    public function testSetCanonicalWithInvalidURLThrowsException(): void {
        $doc = new Document();
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value for canonical');
        
        $doc->canonical = 'not a valid url';
    }

    /**
     * Test index property defaults to true
     */
    public function testIndexDefaultsToTrue(): void {
        $doc = new Document();
        $this->assertTrue($doc->index);
    }

    /**
     * Test setting and getting index property
     */
    public function testSetAndGetIndex(): void {
        $doc = new Document();
        $doc->index = false;
        
        $this->assertFalse($doc->index);
    }

    /**
     * Test setting index with invalid type throws exception
     */
    public function testSetIndexWithInvalidTypeThrowsException(): void {
        $doc = new Document();
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value for index');
        
        $doc->index = 'yes';
    }

    /**
     * Test follow property defaults to true
     */
    public function testFollowDefaultsToTrue(): void {
        $doc = new Document();
        $this->assertTrue($doc->follow);
    }

    /**
     * Test setting and getting follow property
     */
    public function testSetAndGetFollow(): void {
        $doc = new Document();
        $doc->follow = false;
        
        $this->assertFalse($doc->follow);
    }

    /**
     * Test setting follow with invalid type throws exception
     */
    public function testSetFollowWithInvalidTypeThrowsException(): void {
        $doc = new Document();
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value for follow');
        
        $doc->follow = 1;
    }

    /**
     * Test archive property defaults to true
     */
    public function testArchiveDefaultsToTrue(): void {
        $doc = new Document();
        $this->assertTrue($doc->archive);
    }

    /**
     * Test setting and getting archive property
     */
    public function testSetAndGetArchive(): void {
        $doc = new Document();
        $doc->archive = false;
        
        $this->assertFalse($doc->archive);
    }

    /**
     * Test setting archive with invalid type throws exception
     */
    public function testSetArchiveWithInvalidTypeThrowsException(): void {
        $doc = new Document();
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value for archive');
        
        $doc->archive = [];
    }

    /**
     * Test isset for standard properties
     */
    public function testIssetForStandardProperties(): void {
        $doc = new Document();
        $doc->title = 'Test';
        $doc->canonical = 'https://example.com';
        
        $this->assertTrue(isset($doc->title));
        $this->assertTrue(isset($doc->canonical));
        $this->assertTrue(isset($doc->index)); // default true
    }

    /**
     * Test isset for null canonical returns false
     */
    public function testIssetForNullCanonicalReturnsFalse(): void {
        $doc = new Document();
        $this->assertFalse(isset($doc->canonical));
    }

    /**
     * Test addVariable stores custom variable
     */
    public function testAddVariableStoresCustomVariable(): void {
        $doc = new Document();
        $doc->addVariable('page_class', 'homepage');
        
        $this->assertEquals('homepage', $doc->page_class);
    }

    /**
     * Test setting unknown property via magic setter stores as variable
     */
    public function testSettingUnknownPropertyStoresAsVariable(): void {
        $doc = new Document();
        $doc->custom_value = 'test';
        
        $this->assertEquals('test', $doc->custom_value);
    }

    /**
     * Test isset for custom variables
     */
    public function testIssetForCustomVariables(): void {
        $doc = new Document();
        $doc->addVariable('test_var', 'value');
        
        $this->assertTrue(isset($doc->test_var));
        $this->assertFalse(isset($doc->nonexistent));
    }

    /**
     * Test getting nonexistent variable returns null
     */
    public function testGettingNonexistentVariableReturnsNull(): void {
        $doc = new Document();
        $this->assertNull($doc->nonexistent);
    }

    /**
     * Test addMeta stores meta tag data
     */
    public function testAddMetaStoresMetaTagData(): void {
        $doc = new Document();
        $doc->addMeta(['name' => 'description', 'content' => 'Test description']);
        
        // We can't directly access $meta, but we can test via generateHead()
        ob_start();
        $doc->generateHead();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('name="description"', $output);
        $this->assertStringContainsString('content="Test description"', $output);
    }

    /**
     * Test addMeta with multiple tags
     */
    public function testAddMetaWithMultipleTags(): void {
        $doc = new Document();
        $doc->addMeta(['name' => 'description', 'content' => 'Description']);
        $doc->addMeta(['name' => 'keywords', 'content' => 'test,keywords']);
        $doc->addMeta(['property' => 'og:title', 'content' => 'OG Title']);
        
        ob_start();
        $doc->generateHead();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('name="description"', $output);
        $this->assertStringContainsString('name="keywords"', $output);
        $this->assertStringContainsString('property="og:title"', $output);
    }

    /**
     * Test generateHead outputs title tag
     */
    public function testGenerateHeadOutputsTitleTag(): void {
        $doc = new Document();
        $doc->title = 'Test Page';
        
        ob_start();
        $doc->generateHead();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('<title>Test Page</title>', $output);
    }

    /**
     * Test generateHead escapes title content
     */
    public function testGenerateHeadEscapesTitleContent(): void {
        $doc = new Document();
        $doc->title = 'Test <script>alert("XSS")</script>';
        
        ob_start();
        $doc->generateHead();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('&lt;script&gt;', $output);
        $this->assertStringNotContainsString('<script>', $output);
    }

    /**
     * Test generateHead outputs canonical link
     */
    public function testGenerateHeadOutputsCanonicalLink(): void {
        $doc = new Document();
        $doc->canonical = 'https://example.com/page';
        
        ob_start();
        $doc->generateHead();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('<link rel="canonical" href="https://example.com/page" />', $output);
    }

    /**
     * Test generateHead escapes canonical URL
     */
    public function testGenerateHeadEscapesCanonicalURL(): void {
        $doc = new Document();
        $doc->canonical = 'https://example.com/page?foo=bar&baz=qux';
        
        ob_start();
        $doc->generateHead();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('&amp;', $output);
    }

    /**
     * Test generateHead outputs robots meta when noindex
     */
    public function testGenerateHeadOutputsRobotsMetaWhenNoindex(): void {
        $doc = new Document();
        $doc->index = false;
        
        ob_start();
        $doc->generateHead();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('<meta name="robots" content="noindex">', $output);
    }

    /**
     * Test generateHead outputs robots meta when nofollow
     */
    public function testGenerateHeadOutputsRobotsMetaWhenNofollow(): void {
        $doc = new Document();
        $doc->follow = false;
        
        ob_start();
        $doc->generateHead();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('<meta name="robots" content="nofollow">', $output);
    }

    /**
     * Test generateHead outputs robots meta when noarchive
     */
    public function testGenerateHeadOutputsRobotsMetaWhenNoarchive(): void {
        $doc = new Document();
        $doc->archive = false;
        
        ob_start();
        $doc->generateHead();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('<meta name="robots" content="noarchive">', $output);
    }

    /**
     * Test generateHead outputs combined robots directives
     */
    public function testGenerateHeadOutputsCombinedRobotsDirectives(): void {
        $doc = new Document();
        $doc->index = false;
        $doc->follow = false;
        $doc->archive = false;
        
        ob_start();
        $doc->generateHead();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('noindex,nofollow,noarchive', $output);
    }

    /**
     * Test generateHead does not output robots meta when all true
     */
    public function testGenerateHeadDoesNotOutputRobotsMetaWhenAllTrue(): void {
        $doc = new Document();
        // All defaults are true
        
        ob_start();
        $doc->generateHead();
        $output = ob_get_clean();
        
        $this->assertStringNotContainsString('<meta name="robots"', $output);
    }

    /**
     * Test generateHead with invalid meta data throws exception
     */
    public function testGenerateHeadWithInvalidMetaDataThrowsException(): void {
        $doc = new Document();
        $doc->addMeta(['name' => 'test', 'content' => ['invalid', 'array']]);
        
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Invalid meta data provided');
        
        ob_start();
        try {
            $doc->generateHead();
        } finally {
            ob_end_clean();
        }
    }

    /**
     * Test generateHead escapes meta tag attributes
     */
    public function testGenerateHeadEscapesMetaTagAttributes(): void {
        $doc = new Document();
        $doc->addMeta([
            'name' => 'description',
            'content' => 'Test "quoted" & <special> chars'
        ]);
        
        ob_start();
        $doc->generateHead();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('&quot;', $output);
        $this->assertStringContainsString('&amp;', $output);
        $this->assertStringContainsString('&lt;', $output);
    }

    /**
     * Test generateHeaders sets canonical Link header
     */
    public function testGenerateHeadersSetsCanonicalLinkHeader(): void {
        $mockHeaders = $this->createMock(Headers::class);
        $mockHeaders->expects($this->once())
            ->method('set')
            ->with('Link', '<https://example.com/page>; rel="canonical"');
        
        $doc = new Document($mockHeaders);
        $doc->canonical = 'https://example.com/page';
        $doc->generateHeaders();
    }

    /**
     * Test generateHeaders does not set Link header without canonical
     */
    public function testGenerateHeadersDoesNotSetLinkHeaderWithoutCanonical(): void {
        $mockHeaders = $this->createMock(Headers::class);
        $mockHeaders->expects($this->never())
            ->method('set')
            ->with('Link', $this->anything());
        
        $doc = new Document($mockHeaders);
        $doc->generateHeaders();
    }

    /**
     * Test generateHeaders sets X-Robots-Tag when noindex
     */
    public function testGenerateHeadersSetsXRobotsTagWhenNoindex(): void {
        $mockHeaders = $this->createMock(Headers::class);
        $mockHeaders->expects($this->once())
            ->method('set')
            ->with('X-Robots-Tag', 'noindex');
        
        $doc = new Document($mockHeaders);
        $doc->index = false;
        $doc->generateHeaders();
    }

    /**
     * Test generateHeaders sets combined X-Robots-Tag directives
     */
    public function testGenerateHeadersSetsCombinedXRobotsTagDirectives(): void {
        $mockHeaders = $this->createMock(Headers::class);
        $mockHeaders->expects($this->once())
            ->method('set')
            ->with('X-Robots-Tag', 'noindex,nofollow,noarchive');
        
        $doc = new Document($mockHeaders);
        $doc->index = false;
        $doc->follow = false;
        $doc->archive = false;
        $doc->generateHeaders();
    }

    /**
     * Test generateHeaders does not set X-Robots-Tag when all true
     */
    public function testGenerateHeadersDoesNotSetXRobotsTagWhenAllTrue(): void {
        $mockHeaders = $this->createMock(Headers::class);
        $mockHeaders->expects($this->never())
            ->method('set')
            ->with('X-Robots-Tag', $this->anything());
        
        $doc = new Document($mockHeaders);
        $doc->generateHeaders();
    }

    /**
     * Test generateHeaders sets both Link and X-Robots-Tag
     */
    public function testGenerateHeadersSetsBothLinkAndXRobotsTag(): void {
        $mockHeaders = $this->createMock(Headers::class);
        $mockHeaders->expects($this->exactly(2))
            ->method('set')
            ->willReturnCallback(function($header, $value) {
                $this->assertTrue(
                    ($header === 'Link' && str_contains($value, 'canonical')) ||
                    ($header === 'X-Robots-Tag' && $value === 'noindex')
                );
            });
        
        $doc = new Document($mockHeaders);
        $doc->canonical = 'https://example.com';
        $doc->index = false;
        $doc->generateHeaders();
    }

    /**
     * Test complete document workflow
     */
    public function testCompleteDocumentWorkflow(): void {
        $doc = new Document();
        
        // Set properties
        $doc->title = 'Complete Test Page';
        $doc->canonical = 'https://example.com/test';
        $doc->index = false;
        $doc->addMeta(['name' => 'description', 'content' => 'Test description']);
        $doc->addVariable('page_class', 'test-page');
        
        // Verify properties
        $this->assertEquals('Complete Test Page', $doc->title);
        $this->assertEquals('https://example.com/test', $doc->canonical);
        $this->assertFalse($doc->index);
        $this->assertEquals('test-page', $doc->page_class);
        
        // Generate head
        ob_start();
        $doc->generateHead();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Complete Test Page', $output);
        $this->assertStringContainsString('https://example.com/test', $output);
        $this->assertStringContainsString('noindex', $output);
        $this->assertStringContainsString('description', $output);
    }

    /**
     * Test multiple instances maintain separate state
     */
    public function testMultipleInstancesMaintainSeparateState(): void {
        $doc1 = new Document();
        $doc2 = new Document();
        
        $doc1->title = 'Document 1';
        $doc2->title = 'Document 2';
        
        $this->assertEquals('Document 1', $doc1->title);
        $this->assertEquals('Document 2', $doc2->title);
    }

    /**
     * Test empty title is not output
     */
    public function testEmptyTitleIsNotOutput(): void {
        $doc = new Document();
        // title defaults to empty string
        
        ob_start();
        $doc->generateHead();
        $output = ob_get_clean();
        
        $this->assertStringNotContainsString('<title>', $output);
    }
}
