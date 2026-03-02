<?php

declare(strict_types=1);

namespace PageMill\MVC;

use PageMill\MVC\Traits\PropertyMap;

/**
 * Base model class
 *
 * Models are responsible for retrieving and preparing data for views.
 * They typically interact with databases, APIs, or other data sources
 * and return structured data arrays that will be merged into the
 * controller's data array.
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present DealNews.com, Inc
 * @package     PageMill\MVC
 */
abstract class ModelAbstract {
    use PropertyMap;

    /**
     * Retrieves and returns model data
     *
     * This method should fetch data from the appropriate source (database,
     * cache, API, etc.) and return it as an associative array. The returned
     * data will be merged into the controller's data array.
     *
     * @return array<string, mixed> Data array to be merged into controller data
     */
    abstract public function getData(): array;

    /**
     * Creates a new Model instance
     *
     * Input data from the controller is automatically mapped to class
     * properties using the PropertyMap trait. This allows models to
     * access request parameters as class properties.
     *
     * @param array<string, mixed> $inputs Inputs from the controller
     */
    public function __construct(array $inputs) {
        $this->mapProperties($inputs, true);
    }
}
