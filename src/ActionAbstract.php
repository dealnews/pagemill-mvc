<?php

declare(strict_types=1);

namespace PageMill\MVC;

use PageMill\MVC\Traits\PropertyMap;

/**
 * Base Action class
 *
 * Actions perform operations that may modify data or state, such as form
 * submissions, data updates, or API calls. They are invoked by controllers
 * either before models are built (request actions) or after (data actions).
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present DealNews.com, Inc
 * @package     PageMill\MVC
 */
abstract class ActionAbstract {
    use PropertyMap;

    /**
     * Array of errors encountered during action execution
     *
     * Each error should be a descriptive string or structured array.
     *
     * @var array<int, string|array>
     */
    protected array $errors = [];

    /**
     * Performs the action logic
     *
     * This method contains the primary logic of the action. It should return
     * data to be merged into either $inputs (for request actions) or $data
     * (for data actions), or null if no merge is needed.
     *
     * @param array<string, mixed> $data Data array passed to Data Actions
     * @return mixed Data to merge, or null
     */
    abstract public function doAction(array $data = []): mixed;

    /**
     * Creates a new Action instance
     *
     * Input data is automatically mapped to class properties using the
     * PropertyMap trait.
     *
     * @param array<string, mixed> $inputs Input data to act upon
     */
    public function __construct(array $inputs) {
        $this->mapProperties($inputs, true);
    }

    /**
     * Returns errors encountered during action execution
     *
     * @return array<int, string|array> Array of error messages or structures
     */
    public function errors(): array {
        return $this->errors;
    }
}
