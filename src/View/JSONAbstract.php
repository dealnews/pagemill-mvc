<?php

declare(strict_types=1);

namespace PageMill\MVC\View;

use PageMill\MVC\ViewAbstract;

/**
 * JSON view base class
 *
 * Abstract view for generating JSON responses. Automatically encodes
 * the array returned by getData() as JSON and outputs it.
 *
 * Usage:
 * ```php
 * class MyAPIView extends JSONAbstract {
 *     protected function getData(): array {
 *         return [
 *             'status' => 'success',
 *             'data' => $this->items, // From model data
 *         ];
 *     }
 * }
 * ```
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present DealNews.com, Inc
 * @package     PageMill\MVC\View
 */
abstract class JSONAbstract extends ViewAbstract {
    /**
     * Generates JSON output
     *
     * Encodes the data from getData() as JSON and outputs it.
     *
     * @return void
     */
    public function generate(): void {
        echo json_encode($this->getData());
    }

    /**
     * Returns data to be encoded as JSON
     *
     * Child classes must implement this to return the data structure
     * that should be JSON encoded. This is typically an associative array
     * representing the API response.
     *
     * @return array<string, mixed> Data to encode as JSON
     */
    abstract protected function getData(): array;
}
