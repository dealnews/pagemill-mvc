<?php

declare(strict_types=1);

namespace PageMill\MVC\Template;

use PageMill\MVC\HTML\Assets;
use PageMill\MVC\HTML\Assets\Injector;
use PageMill\MVC\HTML\Document;
use PageMill\HTTP\Response;
use PageMill\MVC\ViewAbstract;

/**
 * HTML template base class
 *
 * Provides structure and utilities for building HTML views. Extends ViewAbstract
 * with asset management (CSS/JS), document metadata (title, meta tags), and
 * a structured generation flow (header, body, footer).
 *
 * HTML templates have access to:
 * - Asset manager ($this->assets) for CSS/JS
 * - Element asset injector ($this->element_assets) for component assets
 * - Document manager ($this->document) for title, meta tags, canonical URL
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present DealNews.com, Inc
 * @package     PageMill\MVC\Template
 */
abstract class HTMLAbstract extends ViewAbstract {

    /**
     * Asset manager for CSS and JavaScript
     *
     * Add assets directly: $this->assets->add('css', ['styles.css']);
     *
     * @var Assets
     */
    protected Assets $assets;

    /**
     * Element asset injector
     *
     * Automatically loads assets from Element classes:
     * $this->element_assets->add([MyElement::class]);
     *
     * @var Injector
     */
    protected Injector $element_assets;

    /**
     * Document metadata manager
     *
     * Manages page title, canonical URL, meta tags, and robots directives.
     *
     * @var Document
     */
    protected Document $document;

    /**
     * Creates a new HTML template instance
     *
     * Initializes asset management and document metadata systems. Accepts
     * optional instances for testing/customization.
     *
     * @param array<string, mixed> $data Data from models and actions
     * @param array<string, mixed> $inputs Filtered request inputs
     * @param Response $http_response HTTP response object
     * @param Assets|null $asset_object Optional Assets instance (defaults to singleton)
     * @param Injector|null $injector Optional Injector instance
     * @param Document|null $document Optional Document instance (defaults to singleton)
     */
    public function __construct(
        array $data,
        array $inputs,
        Response $http_response,
        ?Assets $asset_object = null,
        ?Injector $injector     = null,
        ?Document $document     = null
    ) {
        parent::__construct($data, $inputs, $http_response);

        if (empty($asset_object)) {
            $this->assets = Assets::init();
        } else {
            $this->assets = $asset_object;
        }

        if (empty($injector)) {
            $this->element_assets = new Injector($this->assets);
        } else {
            $this->element_assets = $injector;
        }

        if (empty($document)) {
            $this->document = Document::init();
        } else {
            $this->document = $document;
        }
    }

    /**
     * Generates the complete HTML output
     *
     * Orchestrates the full page generation flow:
     * 1. prepareDocument() - Set up assets, metadata
     * 2. document->generateHeaders() - Send HTTP headers
     * 3. generateHeader() - Output HTML <head> and page header
     * 4. generateBody() - Output main content
     * 5. generateFooter() - Output page footer and closing tags
     *
     * @return void
     */
    public function generate(): void {
        $this->prepareDocument();
        $this->document->generateHeaders();
        $this->generateHeader();
        $this->generateBody();
        $this->generateFooter();
    }

    /**
     * Prepares document metadata and assets
     *
     * Override this method to configure the page before output begins.
     * Common tasks include setting the page title, adding meta tags,
     * registering CSS/JS assets, and configuring document properties.
     *
     * Example:
     * ```php
     * protected function prepareDocument(): void {
     *     $this->document->title = 'My Page Title';
     *     $this->document->canonical = 'https://example.com/page';
     *     $this->document->addMeta([
     *         'name' => 'description',
     *         'content' => 'Page description'
     *     ]);
     *     $this->assets->add('css', ['main.css']);
     *     $this->assets->add('js', ['app.js'], 'footer');
     *     $this->element_assets->add([SomeElement::class]);
     * }
     * ```
     *
     * @return void
     */
    abstract protected function prepareDocument(): void;

    /**
     * Generates the HTML header section
     *
     * Override this method to output the <html>, <head>, and opening <body>
     * tags, along with any header navigation, logo, or top-of-page content.
     *
     * Typically includes:
     * - DOCTYPE and <html> tag
     * - <head> with document metadata ($this->document->generateHead())
     * - CSS assets ($this->assets->link('css'))
     * - Opening <body> tag
     * - Site header/navigation
     *
     * @return void
     */
    abstract protected function generateHeader(): void;

    /**
     * Generates the main page content
     *
     * Override this method to output the primary content of the page.
     * This is where you'll render the page-specific HTML using data
     * from $this (populated via PropertyMap).
     *
     * @return void
     */
    abstract protected function generateBody(): void;

    /**
     * Generates the footer and closes the document
     *
     * Override this method to output footer content and close HTML tags.
     *
     * Typically includes:
     * - Site footer content
     * - Footer JS assets ($this->assets->link('js', 'footer'))
     * - Closing </body> and </html> tags
     *
     * @return void
     */
    abstract protected function generateFooter(): void;
}
