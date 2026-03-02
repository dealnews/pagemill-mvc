<?php

declare(strict_types=1);

namespace PageMill\MVC;

use PageMill\MVC\Traits\PropertyMap;

/**
 * Base class for PageMill elements
 *
 * Provides the foundation for creating reusable, self-contained UI components
 * with automatic asset management, dependency resolution, and lifecycle hooks.
 * Extends HTMLElement to inherit all HTML5 global attribute support.
 *
 * Core Features:
 * - Automatic asset loading and dependency management
 * - Component composition via generator callbacks
 * - HTML5 attribute support via HTMLElement parent
 * - Automatic ID generation for accessibility
 * - Debug mode support for development
 * - Property mapping from configuration arrays
 *
 * Element Lifecycle:
 * 1. __construct() - Initialize element from config array
 * 2. prepareData() - Validate/transform data before render (optional override)
 * 3. generate() - Check assets and call generateElement()
 * 4. generateElement() - Render HTML output (must override)
 *
 * Asset Management:
 * - Define $assets array with CSS/JS files needed by this element
 * - Define $deps array with element classes this element depends on
 * - Assets are loaded once per element class per request
 * - Parent class assets are loaded first, then dependencies, then element assets
 * - Assets must be loaded via getAssets() before render (framework handles this)
 *
 * Usage Example:
 * ```php
 * class MyButton extends ElementAbstract {
 *     public static $assets = [
 *         'css' => ['assets/button.css'],
 *         'js' => ['assets/button.js']
 *     ];
 *
 *     protected ?string $label = null;
 *
 *     protected function generateElement(): void {
 *         $this->open('button', ['label']);
 *         echo htmlspecialchars($this->label);
 *         $this->close('button');
 *     }
 * }
 *
 * // In your template or view
 * $assets = MyButton::getAssets();
 * Assets::init()->add($assets);
 *
 * // Render the button
 * MyButton::render(['id' => 'submit-btn', 'label' => 'Submit']);
 * ```
 *
 * Component Composition:
 * Elements can contain other elements via the $generator property or by
 * using generateComponent() to handle varied input types (elements, callbacks,
 * scalars).
 *
 * @see HTMLElement For inherited HTML5 attribute support
 * @see PropertyMap For automatic property mapping from arrays
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present dealnews.com, Inc.
 * @package     PageMill
 */
abstract class ElementAbstract extends HTMLElement {
    use PropertyMap;

    /**
     * Content generator callback or element
     *
     * A callback or element instance that generates the interior content of
     * this element. Useful for container elements that wrap other components.
     *
     * Can be:
     * - Callable: Invoked to generate content
     * - ElementAbstract: prepareData() and generate() called
     * - Scalar: Echoed directly
     *
     * @var callable|ElementAbstract|string|null
     */
    protected mixed $generator = null;

    /**
     * Asset files required by this element
     *
     * Array of CSS and JavaScript files needed for this element to function.
     * Framework loads these assets before rendering via Assets class.
     *
     * Format: ['css' => ['file1.css', 'file2.css'], 'js' => ['file1.js']]
     *
     * @var array<string, array<int, string>>
     */
    public static array $assets = [];

    /**
     * Element dependencies
     *
     * Array of element class names this element depends on. Their assets
     * will be loaded automatically before this element's assets.
     *
     * Example: [OtherElement::class, ThirdElement::class]
     *
     * @var array<int, class-string<ElementAbstract>>
     */
    public static array $deps = [];

    /**
     * Asset load tracking registry
     *
     * Tracks which element classes have had their assets loaded to avoid
     * duplicate loading. Keys are safe class names, values are true.
     *
     * @var array<string, bool>
     */
    private static array $assets_loaded = [];

    /**
     * Property validation constraints
     *
     * Array of validation rules for properties set via constructor config.
     * Follows the Constraints Pattern for data validation.
     *
     * @see https://redmine.dealnews.com/projects/bi/wiki/Constraints_Pattern
     * @var array<string, mixed>
     */
    protected static array $constraints = [];

    /**
     * Creates an element instance
     *
     * Maps configuration array to element properties and generates an automatic
     * ID if one is not provided. Configuration keys must match property names.
     *
     * @param array<string, mixed> $config Element configuration options
     */
    public function __construct(array $config = []) {
        $this->mapProperties($config);

        if (empty($this->id)) {
            /**
             * If we don't have an id for this element,
             * generate a semi-random ticket.
             */
            $this->id = $this->generateId();
        }
    }

    /**
     * Magic getter for element properties
     *
     * Provides read access to protected/private properties. This is particularly
     * useful for container elements that need to access properties of their
     * child elements (e.g., form labels reading the ID from input fields).
     *
     * Example:
     * ```php
     * $element = new MyElement(['id' => 'btn-1']);
     * echo $element->id; // Returns 'btn-1'
     * ```
     *
     * @param string $var Property name
     * @return mixed Property value
     * @throws \LogicException If property doesn't exist or is not accessible
     */
    public function __get(string $var): mixed {
        if (property_exists($this, $var)) {
            $value = $this->$var;
        } else {
            throw new \LogicException("Property `$var` does not exist or is not public");
        }

        return $value;
    }

    /**
     * Magic isset check for element properties
     *
     * Enables isset() and empty() calls on element properties. Returns true
     * only if the property exists and has a non-null value.
     *
     * Example:
     * ```php
     * if (isset($element->label)) {
     *     echo $element->label;
     * }
     * ```
     *
     * @param string $var Property name
     * @return bool True if property exists and is not null
     */
    public function __isset(string $var): bool {
        return property_exists($this, $var) && !is_null($this->$var);
    }

    /**
     * Data preparation hook
     *
     * Override this method in child classes to validate input, set defaults,
     * transform data, or perform other setup before rendering. Called
     * automatically before generateElement().
     *
     * Example:
     * ```php
     * public function prepareData(): void {
     *     if (empty($this->label)) {
     *         throw new \InvalidArgumentException('Label is required');
     *     }
     *     $this->label = trim($this->label);
     * }
     * ```
     *
     * @return void
     */
    public function prepareData(): void {
        // noop
    }

    /**
     * Generates the element output
     *
     * Final method that performs asset loading validation, optional debug
     * output, and calls the child class's generateElement() method. This
     * method is final to ensure consistent element lifecycle behavior.
     *
     * Asset loading validation is skipped in CLI mode (for testing).
     *
     * @return void
     */
    final public function generate(): void {
        // We don't need to check this if we're running
        // in the CLI. Tests run in the CLI.
        if (php_sapi_name() != 'cli') {
            $class      = get_called_class();
            $safe_class = self::safeClass($class);
            if (empty(self::$assets_loaded[$safe_class]) && (!empty($class::$assets) || !empty($class::$deps))) {
                trigger_error('Assets for ' . $class . ' not loaded. Perhaps a call to load the assets in the view was missed?', E_USER_NOTICE);
            }
        }

        if (Environment::debug()) {
            $this->debug();
        }

        $this->generateElement();
    }

    /**
     * Static helper to render an element
     *
     * Convenience method that creates an element instance, prepares data,
     * and generates output in one call. Useful for simple one-off rendering.
     *
     * Example:
     * ```php
     * MyButton::render(['label' => 'Click me', 'class' => 'primary']);
     * ```
     *
     * @param array<string, mixed> $config Element configuration options
     * @return void
     */
    public static function render(array $config = []): void {
        $class = get_called_class();

        /** @var ElementAbstract $inst */
        $inst = new $class($config);
        $inst->prepareData();
        $inst->generate();
    }

    /**
     * Static helper to capture rendered output
     *
     * Convenience method that renders an element and returns the output as a
     * string instead of echoing it. Uses output buffering internally.
     *
     * Example:
     * ```php
     * $html = MyButton::get(['label' => 'Click me']);
     * $email_body = MyEmailTemplate::get(['user' => $user]);
     * ```
     *
     * @param array<string, mixed> $config Element configuration options
     * @return string Rendered HTML output
     */
    public static function get(array $config = []): string {
        ob_start();
        self::render($config);

        return ob_get_clean();
    }

    /**
     * Collects all assets needed by this element
     *
     * Recursively loads assets from parent classes, dependencies, and the
     * element itself. Returns a merged array of all CSS and JavaScript files
     * needed. Tracks loaded elements to avoid duplicate processing.
     *
     * Asset Loading Order:
     * 1. Parent class assets (closest to furthest ancestor)
     * 2. Dependency assets (in order listed in $deps)
     * 3. This element's assets
     *
     * This ensures proper load order - dependencies are loaded before
     * dependents, base classes before derived classes.
     *
     * Example:
     * ```php
     * $assets = MyButton::getAssets();
     * // Returns: [
     * //   'css' => ['base.css', 'button.css'],
     * //   'js' => ['base.js', 'button.js']
     * // ]
     * ```
     *
     * @param class-string<ElementAbstract>|null $class Element class name (uses called class if null)
     * @return array<string, array<int, string>> Merged array of CSS and JS assets
     */
    public static function getAssets(?string $class = null): array {
        if (is_null($class)) {
            $class = get_called_class();
        }

        $assets = [];

        $safe_class = self::safeClass($class);

        if (empty(self::$assets_loaded[$safe_class]) || PHP_SAPI == 'cli') {
            self::$assets_loaded[$safe_class] = true;

            /**
             * First we load parent class assets, putting each parent
             * on the front of the asset list
             */
            $parent = get_parent_class($class);
            while ($parent != __CLASS__ && !empty($parent)) {
                $parent_assets = self::getAssets($parent);
                if (!empty($parent_assets)) {
                    $assets = array_merge_recursive($parent_assets, $assets);
                }
                $parent = get_parent_class($parent);
            }

            /**
             * Next we load dependencies
             */
            if (isset($class::$deps)) {
                foreach ($class::$deps as $dep) {
                    $dep_assets = $dep::getAssets();
                    if (!empty($dep_assets)) {
                        $assets = array_merge_recursive($assets, $dep_assets);
                    }
                }
            }

            /**
             * Lastly, we load the elements assets, putting them last
             */
            if (isset($class::$assets)) {
                $assets = array_merge_recursive($assets, $class::$assets);
            }
        }

        return $assets;
    }

    /**
     * Renders the element HTML
     *
     * Child classes must implement this method to generate their HTML output.
     * Called by generate() after asset validation and debug output.
     *
     * Use $this->open() and $this->close() (from HTMLElement) to generate
     * tags with attributes. Use echo or print for content output.
     *
     * Example:
     * ```php
     * protected function generateElement(): void {
     *     $this->open('button', ['disabled', 'data-action']);
     *     echo htmlspecialchars($this->label);
     *     $this->close('button');
     * }
     * ```
     *
     * @return void
     */
    abstract protected function generateElement(): void;

    /**
     * Generates component content flexibly
     *
     * Handles multiple input types for generating component content within
     * container elements. Supports elements, callbacks, scalars, and custom
     * generator functions. Enables flexible component composition.
     *
     * Supported component types (tried in order):
     * 1. ElementAbstract instance: Calls prepareData() and generate()
     * 2. PageMill v1 Element: Calls prepare_data() and generate() (legacy)
     * 3. Scalar (string/number): Echoes directly
     * 4. Callable: Invokes the callable
     * 5. $this->generator: Uses element's generator callback with component as arg
     * 6. $this->generator() method: Calls if method exists
     * 7. $default_callback: Uses provided fallback callback
     * 8. None matched: Triggers warning
     *
     * Example:
     * ```php
     * // In a container element's generateElement()
     * foreach ($this->items as $item) {
     *     $this->generateComponent($item, function($data) {
     *         echo '<li>' . htmlspecialchars($data) . '</li>';
     *     });
     * }
     * ```
     *
     * @param mixed $component Content to generate (element, callback, scalar, etc.)
     * @param callable|null $default_callback Fallback callback if component type not recognized
     * @return void
     * @suppress PhanUndeclaredMethod
     */
    protected function generateComponent(mixed $component, ?callable $default_callback = null): void {

        // is the generator an element?
        if ($component instanceof self) {
            $component->prepareData();
            $component->generate();

        // is this a PageMill v1 Element?
        } elseif ($component instanceof \PageMill_Element) {
            $component->prepare_data();
            $component->generate();

        // is it a scalar?
        } elseif (is_scalar($component)) {
            echo $component;

        // is it callable?
        } elseif (is_callable($component)) {
            $component();

        // does this element have a generator defined?
        } elseif (!empty($this->generator) && is_callable($this->generator)) {
            $callback = $this->generator;
            $callback($component);

        // does the object have a generator method?
        } elseif (method_exists($this, 'generator')) {
            $this->generator($component);

        // try the default passed in generator
        } elseif (is_callable($default_callback)) {
            $default_callback($component);

        // trigger a warning
        } else {
            trigger_error('Failed to generate component for ' . get_called_class(), E_USER_WARNING);
        }
    }

    /**
     * Normalizes class name for safe use as array key
     *
     * Converts class names to a safe format for use as array keys by
     * removing leading backslashes and replacing backslashes with underscores.
     * Handles both namespaced and non-namespaced class names.
     *
     * Example:
     * - `PageMill\MVC\MyElement` becomes `PageMill_MVC_MyElement`
     * - `\Vendor\Package\Class` becomes `Vendor_Package_Class`
     *
     * @param string $class Fully qualified class name
     * @return string Safe class name for array keys
     */
    protected static function safeClass(string $class): string {
        return preg_replace('!\\+!', '_', ltrim($class, '\\'));
    }

    /**
     * Generates automatic element ID
     *
     * Creates a unique ID for elements that don't have one specified.
     * Uses an incrementing counter to ensure uniqueness within the request.
     * IDs follow the pattern: auto-id-1, auto-id-2, etc.
     *
     * Used automatically by __construct() when no ID is provided.
     *
     * @return string Generated ID
     */
    private function generateId(): string {
        static $id_counter = 0;
        $id_counter++;

        return 'auto-id-' . $id_counter;
    }

    /**
     * Debug hook for development
     *
     * Called during generate() when Environment::debug() returns true.
     * Override in child classes to output debug information, log data,
     * or perform development-time checks.
     *
     * Example:
     * ```php
     * public function debug(): void {
     *     error_log('Rendering MyElement with config: ' . json_encode([
     *         'id' => $this->id,
     *         'class' => $this->class
     *     ]));
     * }
     * ```
     *
     * @return void
     */
    public function debug(): void {
        // no-op
    }
}
