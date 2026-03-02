<?php

declare(strict_types=1);

namespace PageMill\MVC\HTML;

/**
 * HTML document metadata manager
 *
 * Manages document-level metadata including page title, canonical URL,
 * meta tags, and robots directives (index, follow, archive). Generates
 * both HTTP headers and HTML <head> content for metadata.
 *
 * Usage:
 * ```php
 * $doc = Document::init();
 * $doc->title = 'Page Title';
 * $doc->canonical = 'https://example.com/page';
 * $doc->index = false; // noindex
 * $doc->addMeta(['name' => 'description', 'content' => 'Page description']);
 * $doc->generateHeaders(); // Sends HTTP headers
 * $doc->generateHead();    // Outputs HTML <head> tags
 * ```
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present DealNews.com, Inc
 * @package     PageMill\MVC\HTML
 */
class Document {

    /**
     * Page title
     *
     * Used in <title> tag and may be referenced in templates.
     *
     * @var string
     */
    protected string $title = '';

    /**
     * Canonical URL for this page
     *
     * When set, generates both a Link HTTP header and <link rel="canonical"> tag.
     * Must be a valid URL.
     *
     * @var string|null
     */
    protected ?string $canonical = null;

    /**
     * Whether search engines should index this page
     *
     * When false, adds "noindex" to robots meta tag and X-Robots-Tag header.
     *
     * @var bool
     */
    protected bool $index = true;

    /**
     * Whether search engines should follow links on this page
     *
     * When false, adds "nofollow" to robots meta tag and X-Robots-Tag header.
     *
     * @var bool
     */
    protected bool $follow = true;

    /**
     * Whether search engines should cache/archive this page
     *
     * When false, adds "noarchive" to robots meta tag and X-Robots-Tag header.
     *
     * @var bool
     */
    protected bool $archive = true;

    /**
     * Collection of meta tag data
     *
     * Each element is an associative array of attribute name/value pairs.
     * Example: ['name' => 'description', 'content' => 'Page description']
     *
     * @var array<int, array<string, scalar>>
     */
    protected array $meta = [];

    /**
     * Arbitrary document variables
     *
     * Storage for custom page data accessible to components (header, footer, etc.).
     *
     * @var array<string, mixed>
     */
    protected array $variables = [];

    /**
     * Gets a singleton instance of Document
     *
     * Returns the same instance across all calls within a request.
     *
     * @return Document The singleton instance
     */
    public static function init(): Document {
        static $inst;
        if (empty($inst)) {
            $inst = new static();
        }

        return $inst;
    }

    /**
     * HTTP Response Headers manager
     *
     * @var \PageMill\HTTP\Response\Headers
     */
    private \PageMill\HTTP\Response\Headers $headers;

    /**
     * Creates a new Document instance
     *
     * @param \PageMill\HTTP\Response\Headers|null $header_obj Optional headers object (for testing)
     */
    public function __construct(?\PageMill\HTTP\Response\Headers $header_obj = null) {
        if (empty($header_obj)) {
            $this->headers = \PageMill\HTTP\Response\Headers::init();
        } else {
            $this->headers = $header_obj;
        }
    }

    /**
     * Magic setter for document properties
     *
     * Allows setting document properties via $doc->property = value syntax.
     * Performs validation on standard properties (title, canonical, index,
     * follow, archive). Unknown properties are stored in $variables.
     *
     * @param string $var Property name
     * @param mixed $value Property value
     * @return void
     * @throws \InvalidArgumentException If value is invalid for the property type
     */
    public function __set(string $var, mixed $value): void {
        switch ($var) {
            case 'title':
                if (!is_string($value)) {
                    throw new \InvalidArgumentException('Invalid value for title in ' . __CLASS__);
                }
                $this->title = $value;
                break;
            case 'canonical':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    throw new \InvalidArgumentException('Invalid value for canonical in ' . __CLASS__);
                }
                $this->canonical = $value;
                break;
            case 'index':
            case 'follow':
            case 'archive':
                if (!is_bool($value)) {
                    throw new \InvalidArgumentException("Invalid value for $var in " . __CLASS__);
                }
                $this->$var = $value;
                break;
            default:
                $this->addVariable($var, $value);
        }
    }

    /**
     * Magic getter for document properties
     *
     * Retrieves document properties via $doc->property syntax.
     * Returns standard property values or custom variables.
     *
     * @param string $var Property name
     * @return mixed Property value, or null if not set
     */
    public function __get(string $var): mixed {
        $value = null;

        switch ($var) {
            case 'title':
            case 'canonical':
            case 'index':
            case 'follow':
            case 'archive':
                $value = $this->$var;
                break;
            default:
                if (isset($this->variables[$var])) {
                    $value = $this->variables[$var];
                }
        }

        return $value;
    }

    /**
     * Magic isset check for document properties
     *
     * Allows isset($doc->property) checks for both standard properties
     * and custom variables.
     *
     * @param string $var Property name to check
     * @return bool True if property exists and is not null
     */
    public function __isset(string $var): bool {
        switch ($var) {
            case 'title':
            case 'canonical':
            case 'index':
            case 'follow':
            case 'archive':
                $return = !is_null($this->$var);
                break;
            default:
                $return = isset($this->variables[$var]);
        }

        return $return;
    }

    /**
     * Adds a meta tag to be generated in <head>
     *
     * Stores meta tag attributes for later generation. Each meta tag is
     * defined by an associative array of attribute name/value pairs.
     *
     * Examples:
     * ```php
     * // Standard meta tag
     * $doc->addMeta([
     *     'name' => 'description',
     *     'content' => 'Page description'
     * ]);
     *
     * // Viewport meta tag
     * $doc->addMeta([
     *     'name' => 'viewport',
     *     'content' => 'width=device-width, initial-scale=1'
     * ]);
     *
     * // Open Graph tag
     * $doc->addMeta([
     *     'property' => 'og:title',
     *     'content' => 'Page Title'
     * ]);
     * ```
     *
     * Validation occurs during generateHead().
     *
     * @param array<string, scalar> $values Associative array of meta tag attributes
     * @return void
     */
    public function addMeta(array $values): void {
        // We validate the data when we generate the tag
        $this->meta[] = $values;
    }

    /**
     * Stores a custom variable for document
     *
     * Allows storing arbitrary data about the document that can be accessed
     * by components like header/footer elements. Useful for page-specific
     * configuration that needs to be shared across components.
     *
     * Examples:
     * ```php
     * $doc->addVariable('page_class', 'homepage');
     * $doc->addVariable('show_sidebar', true);
     * $doc->addVariable('breadcrumbs', ['Home', 'Products']);
     * ```
     *
     * @param string $var Variable name
     * @param mixed $value Variable value
     * @return void
     */
    public function addVariable(string $var, mixed $value): void {
        $this->variables[$var] = $value;
    }

    /**
     * Generates metadata-related HTTP headers
     *
     * Sends HTTP headers for:
     * - Canonical URL (Link header)
     * - Robots directives (X-Robots-Tag header)
     *
     * Should be called before any output is sent to the browser.
     *
     * @return void
     */
    public function generateHeaders(): void {
        if (!empty($this->canonical)) {
            $this->headers->set('Link', "<{$this->canonical}>; rel=\"canonical\"");
        }

        if (!$this->index || !$this->follow || !$this->archive) {
            $this->headers->set('X-Robots-Tag', $this->generateRobotsValues());
        }
    }

    /**
     * Generates HTML tags for <head> section
     *
     * Outputs:
     * - <title> tag
     * - <link rel="canonical"> tag
     * - <meta name="robots"> tag (if needed)
     * - All custom meta tags added via addMeta()
     *
     * Should be called within the <head> section of your HTML.
     *
     * @return void
     * @throws \LogicException If meta tag data is not scalar values
     */
    public function generateHead(): void {
        if (!empty($this->title)) {
            echo '<title>' . htmlspecialchars($this->title, ENT_COMPAT) . "</title>\n";
        }

        if (!empty($this->canonical)) {
            echo '<link rel="canonical" href="' . htmlspecialchars($this->canonical, ENT_COMPAT) . "\" />\n";
        }

        $robots_value = $this->generateRobotsValues();
        if (!empty($robots_value)) {
            echo '<meta name="robots" content="' . htmlspecialchars($robots_value, ENT_COMPAT) . "\">\n";
        }

        foreach ($this->meta as $tag) {
            $attr = [];
            foreach ($tag as $name=>$value) {
                if (!is_scalar($value)) {
                    throw new \LogicException('Invalid meta data provided. Meta data must be a single level array.');
                }
                $attr[] = htmlspecialchars((string)$name, ENT_COMPAT) . '="' . htmlspecialchars((string)$value, ENT_COMPAT) . '"';
            }
            echo '<meta ' . implode(' ', $attr) . ">\n";
        }
    }

    /**
     * Generates robots directive value string
     *
     * Builds a comma-separated string of robots directives based on
     * the $index, $follow, and $archive properties.
     *
     * Returns empty string if all are true (allow all).
     * Returns combinations like "noindex,nofollow" as needed.
     *
     * @return string Robots directive string
     */
    protected function generateRobotsValues(): string {
        $robots = [];
        if (!$this->index) {
            $robots[] = 'noindex';
        }
        if (!$this->follow) {
            $robots[] = 'nofollow';
        }
        if (!$this->archive) {
            $robots[] = 'noarchive';
        }
        $robots_value = implode(',', $robots);

        return $robots_value;
    }
}
