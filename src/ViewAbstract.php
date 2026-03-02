<?php

declare(strict_types=1);

namespace PageMill\MVC;

use PageMill\HTTP\Response;
use PageMill\MVC\Traits\PropertyMap;

/**
 * Base view class
 *
 * Views are responsible for generating output (HTML, JSON, XML, etc.).
 * They receive data from models/actions and inputs from the request,
 * then produce the final response body.
 *
 * Views are instantiated by responders and should focus purely on
 * presentation logic without performing business logic or data retrieval.
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present DealNews.com, Inc
 * @package     PageMill\MVC
 */
abstract class ViewAbstract {
    use PropertyMap;

    /**
     * HTTP Response object
     *
     * Provides access to response methods for setting headers, status codes,
     * and managing output.
     *
     * @var \PageMill\HTTP\Response
     */
    protected \PageMill\HTTP\Response $http_response;

    /**
     * Generates the view output
     *
     * Child classes must implement this method to produce the response body.
     * This method should echo or print the final output that will be sent
     * to the client.
     *
     * @return void
     */
    abstract public function generate(): void;

    /**
     * Creates a new view instance
     *
     * Data and inputs are automatically mapped to class properties using
     * the PropertyMap trait, making all data accessible as $this->property_name.
     *
     * @param array<string, mixed> $data Data from models and actions
     * @param array<string, mixed> $inputs Filtered request inputs
     * @param Response $http_response HTTP response object for header manipulation
     */
    public function __construct(array $data, array $inputs, Response $http_response) {
        $this->mapProperties($data, true);
        $this->mapProperties($inputs, true);
        $this->http_response = $http_response;
    }
}
