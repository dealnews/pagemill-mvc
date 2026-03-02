<?php

declare(strict_types=1);

namespace PageMill\MVC;

use PageMill\Accept\Accept;
use PageMill\HTTP\HTTP;

/**
 * Base responder class
 *
 * Responders handle response generation and content negotiation. They are
 * invoked by controllers after data is prepared, and are responsible for
 * setting HTTP headers and delegating content generation to views.
 *
 * Responders support content negotiation via Accept headers and can handle
 * multiple content types (HTML, JSON, XML, etc.).
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present DealNews.com, Inc
 * @package     PageMill\MVC
 */
abstract class ResponderAbstract {

    /**
     * The content type for the response
     *
     * Defaults to HTML. Modified by acceptable() based on Accept header
     * negotiation against getAcceptedContentTypes().
     *
     * @var string
     */
    protected string $content_type = HTTP::CONTENT_TYPE_HTML;

    /**
     * HTTP Response object
     *
     * Handles HTTP status codes, headers, and output buffering.
     *
     * @var \PageMill\HTTP\Response
     */
    protected \PageMill\HTTP\Response $http_response;

    /**
     * Creates a responder instance
     *
     * @param \PageMill\HTTP\Response|null $http_response Optional response object (for testing)
     */
    public function __construct(?\PageMill\HTTP\Response $http_response = null) {
        if (empty($http_response)) {
            $this->http_response = \PageMill\HTTP\Response::init();
        } else {
            $this->http_response = $http_response;
        }
    }

    /**
     * Returns accepted content types for this responder
     *
     * Override this method in child classes to support multiple content types.
     * The default implementation only supports HTML. If your responder can
     * generate JSON, XML, or other formats, return an array of supported
     * content type constants.
     *
     * Example:
     * ```php
     * protected function getAcceptedContentTypes(): array {
     *     return [
     *         HTTP::CONTENT_TYPE_HTML,
     *         HTTP::CONTENT_TYPE_JSON,
     *     ];
     * }
     * ```
     *
     * @return array<int, string> Array of supported content type strings
     */
    protected function getAcceptedContentTypes(): array {
        return [
            $this->content_type,
        ];
    }

    /**
     * Performs content negotiation via Accept header
     *
     * Checks if the client's Accept header matches any content types this
     * responder can generate. If no acceptable type is found, sends a
     * 406 Not Acceptable response and returns false.
     *
     * The matched content type is stored in $this->content_type for use
     * in generateHeaders() and view selection.
     *
     * @return bool True if content negotiation succeeded
     */
    public function acceptable(): bool {
        $valid_content_types = $this->getAcceptedContentTypes();

        $accept             = new Accept();
        $this->content_type = $accept->determine($valid_content_types);

        if ($this->content_type === false) {
            $this->http_response->error(406); // Not Acceptable
        }

        return true;
    }

    /**
     * Generates the response
     *
     * Coordinates the full response generation:
     * 1. Generates HTTP headers via generateHeaders()
     * 2. Gets view class name via getView()
     * 3. Instantiates and executes view via generateView()
     *
     * @param array<string, mixed> $data Data from models and actions
     * @param array<string, mixed> $inputs Filtered request inputs
     * @return void
     */
    public function respond(array $data, array $inputs): void {
        $this->generateHeaders($inputs);
        $this->generateView($this->getView($data, $inputs), $data, $inputs);
    }

    /**
     * Generates HTTP headers for the response
     *
     * Sets the Content-Type header based on content negotiation. Child classes
     * should extend this to set additional headers like cookies, cache control,
     * security headers, etc.
     *
     * When extending, call parent::generateHeaders() first to ensure the
     * content type is set properly.
     *
     * Example:
     * ```php
     * protected function generateHeaders(array $inputs): void {
     *     parent::generateHeaders($inputs);
     *     $this->http_response->header('X-Frame-Options', 'DENY');
     *     // Set cookies, etc.
     * }
     * ```
     *
     * @param array<string, mixed> $inputs Request inputs (for conditional header logic)
     * @return void
     */
    protected function generateHeaders(array $inputs): void {
        $this->http_response->contentType($this->content_type);
    }

    /**
     * Returns the view class name for this request
     *
     * Child classes must implement this to return a fully qualified view
     * class name based on the data and inputs. The view class will be
     * instantiated and its generate() method called.
     *
     * Example:
     * ```php
     * protected function getView(array $data, array $inputs): string {
     *     if ($this->content_type === HTTP::CONTENT_TYPE_JSON) {
     *         return \MyApp\Views\APIView::class;
     *     }
     *     return \MyApp\Views\DefaultView::class;
     * }
     * ```
     *
     * @param array<string, mixed> $data Data from models and actions
     * @param array<string, mixed> $inputs Filtered request inputs
     * @return string Fully qualified view class name
     */
    abstract protected function getView(array $data, array $inputs): string;

    /**
     * Instantiates and executes the view
     *
     * Creates a view instance with the provided data and inputs, then calls
     * its generate() method to produce output. Sends a 400 Bad Request if
     * no view class is provided.
     *
     * In most cases, this default implementation is sufficient. Override only
     * if you need custom view instantiation or error handling.
     *
     * @param string $view Fully qualified view class name
     * @param array<string, mixed> $data Data for the view
     * @param array<string, mixed> $inputs Request inputs for the view
     * @return void
     */
    protected function generateView(string $view, array $data, array $inputs): void {
        if (empty($view)) {
            $this->http_response->error(400);
        }

        $v = new $view($data, $inputs, $this->http_response);
        $v->generate();
    }
}
