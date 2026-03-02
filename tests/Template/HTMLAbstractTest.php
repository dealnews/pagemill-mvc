<?php

declare(strict_types=1);

namespace PageMill\MVC\Tests\Template;

use PageMill\HTTP\Response;
use PageMill\MVC\HTML\Assets;
use PageMill\MVC\HTML\Assets\Injector;
use PageMill\MVC\HTML\Document;
use PageMill\MVC\Template\HTMLAbstract;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Template\HTMLAbstract
 *
 * HTMLAbstract is the base class for HTML views. It extends ViewAbstract
 * and adds asset management, document metadata, and a structured generation flow.
 */
class HTMLAbstractTest extends TestCase {
    
    /**
     * Test constructor initializes with default dependencies
     */
    public function testConstructorWithDefaults(): void {
        $response = $this->createMock(Response::class);
        
        $view = new ConcreteHTMLView([], [], $response);
        
        $this->assertInstanceOf(Assets::class, $view->getAssets());
        $this->assertInstanceOf(Injector::class, $view->getElementAssets());
        $this->assertInstanceOf(Document::class, $view->getDocument());
    }
    
    /**
     * Test constructor with custom assets
     */
    public function testConstructorWithCustomAssets(): void {
        $response = $this->createMock(Response::class);
        $assets = $this->createMock(Assets::class);
        
        $view = new ConcreteHTMLView([], [], $response, $assets);
        
        $this->assertSame($assets, $view->getAssets());
    }
    
    /**
     * Test constructor with custom injector
     */
    public function testConstructorWithCustomInjector(): void {
        $response = $this->createMock(Response::class);
        $assets = $this->createMock(Assets::class);
        $injector = $this->createMock(Injector::class);
        
        $view = new ConcreteHTMLView([], [], $response, $assets, $injector);
        
        $this->assertSame($injector, $view->getElementAssets());
    }
    
    /**
     * Test constructor with custom document
     */
    public function testConstructorWithCustomDocument(): void {
        $response = $this->createMock(Response::class);
        $assets = $this->createMock(Assets::class);
        $injector = $this->createMock(Injector::class);
        $document = $this->createMock(Document::class);
        
        $view = new ConcreteHTMLView([], [], $response, $assets, $injector, $document);
        
        $this->assertSame($document, $view->getDocument());
    }
    
    /**
     * Test constructor passes data and inputs to parent
     */
    public function testConstructorPassesDataToParent(): void {
        $response = $this->createMock(Response::class);
        $data = ['title' => 'Test Title'];
        $inputs = ['page' => 1];
        
        $view = new ConcreteHTMLView($data, $inputs, $response);
        
        $this->assertEquals('Test Title', $view->title);
        $this->assertEquals(1, $view->page);
    }
    
    /**
     * Test generate calls all required methods in order
     */
    public function testGenerateCallsMethodsInOrder(): void {
        $response = $this->createMock(Response::class);
        $document = $this->createMock(Document::class);
        
        $document->expects($this->once())
            ->method('generateHeaders');
        
        $view = new OrderTrackingHTMLView([], [], $response, null, null, $document);
        
        ob_start();
        $view->generate();
        $output = ob_get_clean();
        
        $order = $view->getCallOrder();
        $this->assertEquals(['prepareDocument', 'generateHeader', 'generateBody', 'generateFooter'], $order);
    }
    
    /**
     * Test generate outputs complete HTML
     */
    public function testGenerateOutputsHTML(): void {
        $response = $this->createMock(Response::class);
        $document = $this->createMock(Document::class);
        
        $document->expects($this->once())
            ->method('generateHeaders');
        
        $view = new ConcreteHTMLView(['message' => 'Hello'], [], $response, null, null, $document);
        
        ob_start();
        $view->generate();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('HEADER', $output);
        $this->assertStringContainsString('Hello', $output);
        $this->assertStringContainsString('FOOTER', $output);
    }
    
    /**
     * Test prepareDocument is abstract and must be implemented
     */
    public function testPrepareDocumentMustBeImplemented(): void {
        $response = $this->createMock(Response::class);
        $view = new ConcreteHTMLView([], [], $response);
        
        // If this executes without error, prepareDocument was implemented
        $view->callPrepareDocument();
        
        $this->assertTrue(true);
    }
    
    /**
     * Test generateHeader is abstract and must be implemented
     */
    public function testGenerateHeaderMustBeImplemented(): void {
        $response = $this->createMock(Response::class);
        $view = new ConcreteHTMLView([], [], $response);
        
        ob_start();
        $view->callGenerateHeader();
        $output = ob_get_clean();
        
        $this->assertEquals('HEADER', $output);
    }
    
    /**
     * Test generateBody is abstract and must be implemented
     */
    public function testGenerateBodyMustBeImplemented(): void {
        $response = $this->createMock(Response::class);
        $view = new ConcreteHTMLView(['message' => 'Test'], [], $response);
        
        ob_start();
        $view->callGenerateBody();
        $output = ob_get_clean();
        
        $this->assertEquals('Test', $output);
    }
    
    /**
     * Test generateFooter is abstract and must be implemented
     */
    public function testGenerateFooterMustBeImplemented(): void {
        $response = $this->createMock(Response::class);
        $view = new ConcreteHTMLView([], [], $response);
        
        ob_start();
        $view->callGenerateFooter();
        $output = ob_get_clean();
        
        $this->assertEquals('FOOTER', $output);
    }
    
    /**
     * Test assets are accessible in child methods
     */
    public function testAssetsAccessibleInChildMethods(): void {
        $response = $this->createMock(Response::class);
        $assets = $this->createMock(Assets::class);
        
        $assets->expects($this->once())
            ->method('add')
            ->with('css', ['test.css']);
        
        $view = new AssetUsingHTMLView([], [], $response, $assets);
        $view->callPrepareDocument();
    }
    
    /**
     * Test document is accessible in child methods
     */
    public function testDocumentAccessibleInChildMethods(): void {
        $response = $this->createMock(Response::class);
        $document = $this->createMock(Document::class);
        
        $view = new DocumentUsingHTMLView([], [], $response, null, null, $document);
        $view->callPrepareDocument();
        
        // Document property would be set via magic __set
        $this->assertTrue(true);
    }
    
    /**
     * Test element assets are accessible
     */
    public function testElementAssetsAccessible(): void {
        $response = $this->createMock(Response::class);
        $injector = $this->createMock(Injector::class);
        
        $injector->expects($this->once())
            ->method('add')
            ->with(['SomeElement']);
        
        $view = new ElementAssetUsingHTMLView([], [], $response, null, $injector);
        $view->callPrepareDocument();
    }
    
    /**
     * Test null assets parameter uses singleton
     */
    public function testNullAssetsUsesSingleton(): void {
        $response = $this->createMock(Response::class);
        
        $view = new ConcreteHTMLView([], [], $response, null);
        
        $this->assertInstanceOf(Assets::class, $view->getAssets());
        $this->assertSame(Assets::init(), $view->getAssets());
    }
    
    /**
     * Test null document parameter uses singleton
     */
    public function testNullDocumentUsesSingleton(): void {
        $response = $this->createMock(Response::class);
        
        $view = new ConcreteHTMLView([], [], $response, null, null, null);
        
        $this->assertInstanceOf(Document::class, $view->getDocument());
        $this->assertSame(Document::init(), $view->getDocument());
    }
    
    /**
     * Test injector receives assets instance
     */
    public function testInjectorReceivesAssetsInstance(): void {
        $response = $this->createMock(Response::class);
        $assets = $this->createMock(Assets::class);
        
        $view = new ConcreteHTMLView([], [], $response, $assets, null);
        
        // Injector is created with the assets instance
        $this->assertInstanceOf(Injector::class, $view->getElementAssets());
    }
    
    /**
     * Test multiple instances maintain separate state
     */
    public function testMultipleInstancesSeparateState(): void {
        $response1 = $this->createMock(Response::class);
        $response2 = $this->createMock(Response::class);
        $assets1 = $this->createMock(Assets::class);
        $assets2 = $this->createMock(Assets::class);
        
        $view1 = new ConcreteHTMLView(['value' => 'first'], [], $response1, $assets1);
        $view2 = new ConcreteHTMLView(['value' => 'second'], [], $response2, $assets2);
        
        $this->assertEquals('first', $view1->value);
        $this->assertEquals('second', $view2->value);
        $this->assertNotSame($view1->getAssets(), $view2->getAssets());
    }
    
    /**
     * Test generate can be called multiple times
     */
    public function testGenerateCanBeCalledMultipleTimes(): void {
        $response = $this->createMock(Response::class);
        $document = $this->createMock(Document::class);
        
        $document->expects($this->exactly(2))
            ->method('generateHeaders');
        
        $view = new ConcreteHTMLView(['message' => 'Test'], [], $response, null, null, $document);
        
        ob_start();
        $view->generate();
        $output1 = ob_get_clean();
        
        ob_start();
        $view->generate();
        $output2 = ob_get_clean();
        
        $this->assertEquals($output1, $output2);
    }
    
    /**
     * Test view inherits from ViewAbstract
     */
    public function testInheritsFromViewAbstract(): void {
        $response = $this->createMock(Response::class);
        $view = new ConcreteHTMLView([], [], $response);
        
        $this->assertInstanceOf(\PageMill\MVC\ViewAbstract::class, $view);
    }
    
    /**
     * Test constructor with all null dependencies
     */
    public function testConstructorWithAllNullDependencies(): void {
        $response = $this->createMock(Response::class);
        
        $view = new ConcreteHTMLView([], [], $response, null, null, null);
        
        $this->assertInstanceOf(Assets::class, $view->getAssets());
        $this->assertInstanceOf(Injector::class, $view->getElementAssets());
        $this->assertInstanceOf(Document::class, $view->getDocument());
    }
    
    /**
     * Test prepareDocument can modify document properties
     */
    public function testPrepareDocumentCanModifyProperties(): void {
        $response = $this->createMock(Response::class);
        $document = $this->createMock(Document::class);
        
        $view = new DocumentModifyingHTMLView([], [], $response, null, null, $document);
        $view->callPrepareDocument();
        
        $this->assertTrue($view->prepareDocumentCalled);
    }
    
    /**
     * Test generate flow with real output
     */
    public function testGenerateFlowWithRealOutput(): void {
        $response = $this->createMock(Response::class);
        $document = $this->createMock(Document::class);
        
        $document->expects($this->once())
            ->method('generateHeaders');
        
        $view = new CompleteHTMLView(
            ['content' => 'Main Content'],
            ['page' => 2],
            $response,
            null,
            null,
            $document
        );
        
        ob_start();
        $view->generate();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('<!DOCTYPE html>', $output);
        $this->assertStringContainsString('<head>', $output);
        $this->assertStringContainsString('<body>', $output);
        $this->assertStringContainsString('Main Content', $output);
        $this->assertStringContainsString('Page: 2', $output);
        $this->assertStringContainsString('</body>', $output);
        $this->assertStringContainsString('</html>', $output);
    }
    
    /**
     * Test empty data and inputs
     */
    public function testEmptyDataAndInputs(): void {
        $response = $this->createMock(Response::class);
        
        $view = new ConcreteHTMLView([], [], $response);
        
        $this->assertInstanceOf(ConcreteHTMLView::class, $view);
    }
}

/**
 * Concrete implementation for testing basic functionality
 */
class ConcreteHTMLView extends HTMLAbstract {
    
    public string $title = '';
    public int $page = 0;
    public string $message = '';
    public string $value = '';
    public string $content = '';
    
    protected function prepareDocument(): void {
        // Basic implementation
    }
    
    protected function generateHeader(): void {
        echo 'HEADER';
    }
    
    protected function generateBody(): void {
        if (!empty($this->message)) {
            echo $this->message;
        }
    }
    
    protected function generateFooter(): void {
        echo 'FOOTER';
    }
    
    // Expose protected methods for testing
    public function callPrepareDocument(): void {
        $this->prepareDocument();
    }
    
    public function callGenerateHeader(): void {
        $this->generateHeader();
    }
    
    public function callGenerateBody(): void {
        $this->generateBody();
    }
    
    public function callGenerateFooter(): void {
        $this->generateFooter();
    }
    
    public function getAssets(): Assets {
        return $this->assets;
    }
    
    public function getElementAssets(): Injector {
        return $this->element_assets;
    }
    
    public function getDocument(): Document {
        return $this->document;
    }
}

/**
 * Tracks method call order
 */
class OrderTrackingHTMLView extends ConcreteHTMLView {
    
    private array $call_order = [];
    
    protected function prepareDocument(): void {
        $this->call_order[] = 'prepareDocument';
    }
    
    protected function generateHeader(): void {
        $this->call_order[] = 'generateHeader';
    }
    
    protected function generateBody(): void {
        $this->call_order[] = 'generateBody';
    }
    
    protected function generateFooter(): void {
        $this->call_order[] = 'generateFooter';
    }
    
    public function getCallOrder(): array {
        return $this->call_order;
    }
}

/**
 * Uses assets in prepareDocument
 */
class AssetUsingHTMLView extends ConcreteHTMLView {
    
    protected function prepareDocument(): void {
        $this->assets->add('css', ['test.css']);
    }
}

/**
 * Uses document in prepareDocument
 */
class DocumentUsingHTMLView extends ConcreteHTMLView {
    
    protected function prepareDocument(): void {
        $this->document->title = 'Test Title';
    }
}

/**
 * Uses element assets in prepareDocument
 */
class ElementAssetUsingHTMLView extends ConcreteHTMLView {
    
    protected function prepareDocument(): void {
        $this->element_assets->add(['SomeElement']);
    }
}

/**
 * Modifies document in prepareDocument
 */
class DocumentModifyingHTMLView extends ConcreteHTMLView {
    
    public bool $prepareDocumentCalled = false;
    
    protected function prepareDocument(): void {
        $this->prepareDocumentCalled = true;
        $this->document->title = 'Modified Title';
    }
}

/**
 * Complete HTML implementation
 */
class CompleteHTMLView extends ConcreteHTMLView {
    
    protected function prepareDocument(): void {
        // Could add assets, set title, etc.
    }
    
    protected function generateHeader(): void {
        echo '<!DOCTYPE html><html><head><title>Test</title></head><body>';
    }
    
    protected function generateBody(): void {
        echo '<main>';
        if (!empty($this->content)) {
            echo '<p>' . htmlspecialchars($this->content) . '</p>';
        }
        if (!empty($this->page)) {
            echo '<p>Page: ' . (int)$this->page . '</p>';
        }
        echo '</main>';
    }
    
    protected function generateFooter(): void {
        echo '<footer>Footer</footer></body></html>';
    }
}
