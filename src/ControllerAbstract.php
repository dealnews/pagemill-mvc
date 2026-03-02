<?php

declare(strict_types=1);

namespace PageMill\MVC;

use DealNews\Filter\Filter;

/**
 * Base controller class
 *
 * Controllers orchestrate the request/response cycle by coordinating actions,
 * models, and views. They filter input, execute actions, build model data,
 * and delegate response generation to responders.
 *
 * Most methods in this class can be extended. See individual method documentation
 * for extension guidance.
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present DealNews.com, Inc
 * @package     PageMill\MVC
 */
abstract class ControllerAbstract {

    /**
     * Data from models to be sent to views
     *
     * This array is populated by models and actions, then passed to
     * the view for rendering.
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * The request URI path being handled
     *
     * @var string
     */
    protected string $request_path;

    /**
     * Filtered request inputs
     *
     * Contains GET, POST, COOKIE, and SERVER variables after filtering,
     * plus any additional inputs added by the controller.
     *
     * @var array<string, mixed>
     */
    protected array $inputs = [];

    /**
     * Creates a new Controller instance
     *
     * @param string $request_path The REQUEST_URI being served
     * @param array<string, mixed> $inputs Array of input values (optional pre-filtered data)
     */
    public function __construct(string $request_path, array $inputs = []) {
        $this->request_path = $request_path;
        $this->inputs       = $inputs;
    }

    /**
     * Default request handler
     *
     * Orchestrates the full request/response cycle:
     * 1. Filters input via getFilters()
     * 2. Gets responder and checks content type acceptability
     * 3. Executes request actions (pre-model)
     * 4. Builds models to populate data
     * 5. Executes data actions (post-model)
     * 6. Delegates to responder for response generation
     *
     * Child classes can override this to customize the flow, but should
     * maintain the general order of operations for predictability.
     *
     * @return void
     */
    public function handleRequest(): void {
        $this->filterInput($this->getFilters());

        $responder = $this->getResponder();

        if ($responder->acceptable()) {
            $this->doActions($this->getRequestActions(), true);

            $this->buildModels($this->getModels());

            $this->doActions($this->getDataActions());

            $responder->respond($this->data, $this->inputs);
        }
    }

    /**
     * Returns input filters for the request
     *
     * Override this method to define which GET, POST, COOKIE, and SERVER
     * variables should be filtered and how. Return an array where keys are
     * filter types (INPUT_GET, INPUT_POST, etc.) and values are filter
     * definitions compatible with filter_input_array().
     *
     * Example:
     * ```php
     * return [
     *     INPUT_GET => [
     *         'page' => FILTER_VALIDATE_INT,
     *         'sort' => ['filter' => FILTER_SANITIZE_STRING, 'flags' => FILTER_FLAG_STRIP_HIGH]
     *     ],
     *     INPUT_POST => [
     *         'email' => FILTER_VALIDATE_EMAIL
     *     ]
     * ];
     * ```
     *
     * @return array<int, array<string, mixed>> Filter configuration array
     */
    protected function getFilters(): array {
        return [];
    }

    /**
     * Filters input variables based on provided filters
     *
     * Processes GET, POST, COOKIE, and SERVER data according to the filters
     * returned by getFilters(). Filtered values are stored in $this->inputs.
     *
     * Child classes can extend this to add custom input processing, such as
     * parsing IDs from the request path. When extending, call parent::filterInput()
     * first to perform standard filtering, then add custom logic.
     *
     * @param array<int, array<string, mixed>> $filters Filter definitions from getFilters()
     * @return void
     */
    protected function filterInput(array $filters): void {
        if (!empty($filters)) {
            $inputs = [];

            foreach ($filters as $type => $filter) {
                $type_inputs = Filter::init()->inputArray($type, $filter);

                if (empty($type_inputs)) {
                    $type_inputs = [];
                }

                foreach ($filter as $field=>$f) {
                    if (array_key_exists($field, $type_inputs) && (!array_key_exists($field, $inputs) || isset($type_inputs[$field]))) {
                        $inputs[$field] = $type_inputs[$field];
                    } elseif ((!isset($inputs[$field]) || $inputs[$field] === false) &&
                       is_array($f) &&
                       isset($f['default'])) {
                        $inputs[$field] = $f['default'];
                    }
                }
            }

            $this->inputs = array_merge($inputs, $this->inputs);
        }
    }

    /**
     * Returns the responder for this request
     *
     * Child classes must implement this method to return an appropriate
     * responder instance that will handle response generation and content
     * negotiation for the request.
     *
     * Example:
     * ```php
     * protected function getResponder(): ResponderAbstract {
     *     return new \MyApp\Responders\HTMLResponder();
     * }
     * ```
     *
     * @return ResponderAbstract Responder instance
     */
    abstract protected function getResponder(): ResponderAbstract;

    /**
     * Returns request actions to execute before models
     *
     * Request actions are executed before models are built, typically to
     * handle form submissions, data mutations, or other operations that
     * should complete before data is retrieved. Return an array of fully
     * qualified action class names.
     *
     * Example:
     * ```php
     * protected function getRequestActions(): array {
     *     return [
     *         \MyApp\Actions\SaveUserAction::class,
     *         \MyApp\Actions\UploadFileAction::class,
     *     ];
     * }
     * ```
     *
     * @return array<int, class-string> Array of action class names
     */
    protected function getRequestActions(): array {
        return [];
    }

    /**
     * Returns models to build for this request
     *
     * Child classes must implement this method to return an array of model
     * class names. Models are instantiated in order and their getData()
     * results are merged into $this->data.
     *
     * Return an empty array if no models are needed or if custom model
     * building is implemented in an overridden buildModels() method.
     *
     * Example:
     * ```php
     * protected function getModels(): array {
     *     return [
     *         \MyApp\Models\UserModel::class,
     *         \MyApp\Models\ProductModel::class,
     *     ];
     * }
     * ```
     *
     * @return array<int, class-string> Array of model class names
     */
    abstract protected function getModels(): array;

    /**
     * Builds models and populates data array
     *
     * Instantiates each model with $this->inputs, calls getData(), and
     * merges results into $this->data using array_merge_recursive.
     *
     * Child classes can override this for custom model building logic,
     * but in most cases using getModels() is sufficient.
     *
     * @param array<int, class-string> $models Array of model class names
     * @return void
     */
    protected function buildModels(array $models = []): void {
        if (!empty($models)) {

            // ensure there are no duplicates
            $models = array_unique($models);

            foreach ($models as $model) {
                $m    = new $model($this->inputs);
                $data = $m->getData();

                if ($data) {
                    $this->data = array_merge_recursive($this->data, $data);
                }
            }
        }
    }

    /**
     * Returns data actions to execute after models
     *
     * Data actions are executed after models are built, typically to perform
     * operations based on the retrieved data (e.g., sending emails, logging,
     * analytics). Return an array of fully qualified action class names.
     *
     * Example:
     * ```php
     * protected function getDataActions(): array {
     *     return [
     *         \MyApp\Actions\SendEmailAction::class,
     *         \MyApp\Actions\LogActivityAction::class,
     *     ];
     * }
     * ```
     *
     * @return array<int, class-string> Array of action class names
     */
    protected function getDataActions(): array {
        return [];
    }

    /**
     * Executes actions and updates inputs or data
     *
     * Instantiates and executes each action, collecting any errors and
     * merging results. When $alter_inputs is true, action results are
     * merged into $this->inputs (for request actions). Otherwise, results
     * are merged into $this->data (for data actions).
     *
     * Any errors from actions are collected in $this->data['errors'].
     *
     * @param array<int, class-string> $actions Array of action class names
     * @param bool $alter_inputs When true, results go to inputs; otherwise to data
     * @return void
     */
    protected function doActions(array $actions, bool $alter_inputs = false): void {
        if (!isset($this->data['errors'])) {
            $this->data['errors'] = [];
        }

        foreach ($actions as $action) {
            $a = new $action($this->inputs);
            if ($alter_inputs) {
                $response = $a->doAction();
            } else {
                $response = $a->doAction($this->data);
            }
            $errors = $a->errors();

            if (!empty($errors)) {
                $this->inputs['error'] = true;
                $this->data['errors']  = array_merge($this->data['errors'], $errors);
            }
            if (!empty($response)) {
                if ($alter_inputs) {
                    $this->inputs = $response + $this->inputs;
                } else {
                    $this->data = array_merge_recursive($this->data, $response);
                }
            }
        }
    }
}
