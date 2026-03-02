<?php

declare(strict_types=1);

namespace PageMill\MVC;

use PageMill\MVC\Traits\PropertyMap;

/**
 * HTML Element base class
 *
 * Provides a foundation for creating HTML elements with proper attribute handling
 * and generation. Defines all HTML global attributes as properties that can be
 * used on any HTML element.
 *
 * This class implements the complete set of HTML5 global attributes based on
 * the WHATWG HTML specification and MDN documentation. It also provides utilities
 * for generating HTML opening/closing tags and attribute strings.
 *
 * Features:
 * - All HTML5 global attributes (accesskey, class, id, data-*, etc.)
 * - Microdata support (itemscope, itemprop, itemtype, etc.)
 * - Automatic attribute escaping for XSS protection
 * - data-* attribute handling via $data array
 * - Custom attribute support via $meta_attributes
 * - Flexible attribute generation with boolean and value types
 *
 * Usage:
 * ```php
 * $element = new SomeElement([
 *     'id' => 'my-element',
 *     'class' => 'primary active',
 *     'data' => ['user-id' => 123, 'action' => 'click'],
 *     'tabindex' => 0,
 *     'hidden' => false
 * ]);
 * ```
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Global_attributes
 * @see https://html.spec.whatwg.org/multipage/dom.html#global-attributes
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present DealNews.com, Inc
 * @package     PageMill
 */
class HTMLElement {
    use PropertyMap;

    /**
     * Keyboard shortcut hint
     *
     * Space-separated list of characters for keyboard shortcuts. Browser uses
     * the first character that exists on the current keyboard layout.
     *
     * @var string|null
     */
    protected ?string $accesskey = null;

    /**
     * CSS class names
     *
     * Space-separated list of CSS classes for styling and JavaScript selection.
     *
     * @var string|null
     */
    protected ?string $class = null;

    /**
     * Content editability flag
     *
     * Controls whether element content can be edited by the user.
     * Values: true (editable), false (not editable), or null (inherit).
     *
     * @var bool|null
     */
    protected ?bool $contenteditable = null;

    /**
     * Custom data attributes
     *
     * Key-value pairs for data-* attributes accessible via HTMLElement.dataset.
     * Keys become data-{key} attributes in HTML output.
     *
     * Example: ['user-id' => 123] becomes data-user-id="123"
     *
     * @var array<string, scalar>
     */
    protected array $data = [];

    /**
     * Text directionality
     *
     * Controls text direction for internationalization.
     * Values: 'ltr' (left-to-right), 'rtl' (right-to-left), 'auto' (detect).
     *
     * @var string|null
     */
    protected ?string $dir = null;

    /**
     * Drag and drop enabled flag
     *
     * Controls whether element can be dragged using the Drag and Drop API.
     *
     * @var bool|null
     */
    protected ?bool $draggable = null;

    /**
     * Drop behavior specification
     *
     * Defines what happens when content is dropped on the element.
     * Values: 'copy', 'move', 'link'.
     *
     * @var string|null
     */
    protected ?string $dropzone = null;

    /**
     * Hidden state flag
     *
     * When true, element is not yet or no longer relevant and won't be rendered.
     * Use for content that will be shown after some condition (e.g., login).
     *
     * @var bool|null
     */
    protected ?bool $hidden = null;

    /**
     * Unique element identifier
     *
     * Must be unique within the entire document. Used for linking (fragment
     * identifiers), scripting, and styling (CSS selectors).
     *
     * @var string|null
     */
    protected ?string $id = null;

    /**
     * Custom element type override
     *
     * Specifies that a standard HTML element should behave like a registered
     * custom built-in element from the Web Components API.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/Web_Components/Using_custom_elements
     * @var string|null
     */
    protected ?string $is = null;

    /**
     * Microdata item ID
     *
     * Unique global identifier for a microdata item.
     *
     * @see https://html.spec.whatwg.org/multipage/microdata.html#microdata
     * @var string|null
     */
    protected ?string $itemid = null;

    /**
     * Microdata property name
     *
     * Adds a property to a microdata item. Consists of name-value pairs.
     *
     * @see https://html.spec.whatwg.org/multipage/microdata.html#microdata
     * @var string|null
     */
    protected ?string $itemprop = null;

    /**
     * Microdata property references
     *
     * Space-separated list of element IDs containing additional properties
     * for the item, located elsewhere in the document.
     *
     * @see https://html.spec.whatwg.org/multipage/microdata.html#microdata
     * @var string|null
     */
    protected ?string $itemref = null;

    /**
     * Microdata scope marker
     *
     * Creates a new item and defines the scope for associated itemtype.
     * Works with itemtype to structure microdata.
     *
     * @see https://html.spec.whatwg.org/multipage/microdata.html#microdata
     * @var string|null
     */
    protected ?string $itemscope = null;

    /**
     * Microdata vocabulary URL
     *
     * URL of the vocabulary (like schema.org) used to define item properties.
     * Defines the context for itemprop values within the itemscope.
     *
     * @see https://html.spec.whatwg.org/multipage/microdata.html#microdata
     * @var string|null
     */
    protected ?string $itemtype = null;

    /**
     * Content language
     *
     * Defines the language of element content using BCP47 language tags.
     * Takes priority over xml:lang attribute.
     *
     * @see https://tools.ietf.org/html/bcp47
     * @var string|null
     */
    protected ?string $lang = null;

    /**
     * Shadow DOM slot name
     *
     * Assigns element to a named slot in a shadow DOM tree created by
     * a <slot> element with matching name.
     *
     * @var string|null
     */
    protected ?string $slot = null;

    /**
     * Spellcheck preference
     *
     * Controls whether element content should be checked for spelling errors.
     * Values: true (check), false (don't check), null (inherit).
     *
     * @var bool|null
     */
    protected ?bool $spellcheck = null;

    /**
     * Tab navigation index
     *
     * Controls focus order in sequential keyboard navigation:
     * - Negative: focusable but not reachable via Tab key
     * - 0: focusable and Tab-reachable in document order
     * - Positive: focusable and Tab-reachable in tabindex order
     *
     * @var int|null
     */
    protected ?int $tabindex = null;

    /**
     * Advisory title/tooltip text
     *
     * Advisory information typically displayed as a tooltip on hover.
     * Should complement, not duplicate, main content.
     *
     * @var string|null
     */
    protected ?string $title = null;

    /**
     * Translation eligibility
     *
     * Specifies whether element content should be translated when localizing.
     * Values: true/"yes" (translate), "no" (don't translate), null (inherit).
     *
     * @var bool|string|null
     */
    protected bool|string|null $translate = null;

    /**
     * Non-standard custom attributes
     *
     * Allows setting arbitrary attributes not part of HTML specification.
     * Useful for JavaScript framework attributes (e.g., Vue's v-*, Angular's ng-*).
     *
     * Example: ['v-if' => 'isVisible', 'ng-model' => 'userName']
     *
     * @var array<string, scalar>
     */
    protected array $meta_attributes = [];

    /**
     * Creates a new HTML Element instance
     *
     * Accepts an associative array of attributes that map to class properties.
     * Unknown properties are ignored by default via PropertyMap trait.
     *
     * @param array<string, mixed> $attributes Attribute name-value pairs
     */
    public function __construct(array $attributes = []) {
        $this->mapProperties($attributes);
    }

    /**
     * Generates and outputs an HTML opening tag
     *
     * Creates an opening tag with all non-null attributes. Boolean attributes
     * are rendered as name-only (e.g., `hidden`), while valued attributes are
     * properly escaped and rendered as name="value".
     *
     * Example:
     * ```php
     * $element->open('div', ['role', 'aria-label']);
     * // Output: <div id="my-id" class="active" role="button" aria-label="Close">
     * ```
     *
     * @param string $tag_name HTML tag name (e.g., 'div', 'span', 'button')
     * @param array<int, string> $attribute_names Additional child class attributes to include
     * @return void
     */
    public function open(string $tag_name, array $attribute_names = []): void {
        $attributes = $this->generateAttributes($attribute_names);
        echo "<$tag_name";
        if (!empty($attributes)) {
            echo ' ' . $attributes;
        }
        echo '>';
    }

    /**
     * Generates and outputs an HTML closing tag
     *
     * @param string $tag_name HTML tag name (e.g., 'div', 'span', 'button')
     * @return void
     */
    public function close(string $tag_name): void {
        echo "</$tag_name>";
    }

    /**
     * Generates attribute string for HTML tag
     *
     * Builds a space-separated string of HTML attributes from element properties.
     * Handles three types of attributes:
     * 1. Standard properties from this class and child classes
     * 2. data-* attributes from $data array
     * 3. Custom attributes from $meta_attributes array
     *
     * Attribute rendering rules:
     * - null values are omitted entirely
     * - Boolean true renders as name-only attribute (e.g., `hidden`)
     * - Boolean false omits the attribute
     * - Scalar values are escaped and rendered as name="value"
     * - Array values are JSON-encoded and escaped
     *
     * Example output: `id="btn-1" class="primary" data-user-id="123" hidden`
     *
     * @param array<int, string> $extra_attribute_names Additional attribute names from child classes
     * @return string Space-separated attribute string (may be empty)
     */
    protected function generateAttributes(array $extra_attribute_names = []): string {
        $attribute_names = $this->getAttributeProperties(__CLASS__);

        $attribute_names = array_unique(array_merge(
            $attribute_names,
            $extra_attribute_names
        ));

        $attributes = [];
        foreach ($attribute_names as $attr) {
            if ($attr != 'data' && $attr != 'meta_attributes' && property_exists($this, $attr)) {
                if (is_string($this->$attr)) {
                    $attr_value = trim($this->$attr);
                } else {
                    $attr_value = $this->$attr;
                }
                $value = $this->generateAttribute($attr, $attr_value);
                if ($value !== '') {
                    $attributes[] = $value;
                }
            }
        }

        if (!empty($this->data)) {
            foreach ($this->data as $attr=>$value) {
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                }
                $value = $this->generateAttribute('data-' . $attr, $value);
                if ($value !== '') {
                    $attributes[] = $value;
                }
            }
        }

        if (!empty($this->meta_attributes)) {
            foreach ($this->meta_attributes as $attr=>$value) {
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                }
                $value = $this->generateAttribute($attr, $value);
                if ($value !== '') {
                    $attributes[] = $value;
                }
            }
        }

        return implode(' ', $attributes);
    }

    /**
     * Extracts protected property names from a class
     *
     * Uses reflection to find all non-static protected properties of the given
     * class. These property names correspond to HTML attributes that can be
     * generated for elements.
     *
     * @param string|object $class Class name or instance
     * @return array<int, string> Array of property names
     */
    protected function getAttributeProperties(string|object $class): array {
        $attribute_names = [];
        $reflect         = new \ReflectionClass($class);
        $props           = $reflect->getProperties(\ReflectionProperty::IS_PROTECTED);
        foreach ($props as $prop) {
            if (!$prop->isStatic()) {
                $attribute_names[] = $prop->name;
            }
        }

        return $attribute_names;
    }

    /**
     * Generates a single attribute string
     *
     * Converts an attribute name-value pair into a properly formatted and
     * escaped HTML attribute string.
     *
     * Rendering logic:
     * - Boolean true: Returns name only (e.g., `hidden`)
     * - Boolean false: Returns empty string (attribute omitted)
     * - Array: JSON-encodes and escapes (e.g., `data-config="{"x":1}"`)
     * - Scalar: Escapes and quotes (e.g., `id="my-id"`)
     * - Non-scalar: Returns empty string (attribute omitted)
     *
     * All string values are escaped with htmlspecialchars(ENT_COMPAT) to
     * prevent XSS attacks.
     *
     * @param string $name Attribute name
     * @param mixed $value Attribute value (bool, scalar, array, or other)
     * @return string Formatted attribute string or empty string
     */
    private function generateAttribute(string $name, mixed $value): string {
        $attribute = '';
        if (is_bool($value)) {
            if ($value === true) {
                $attribute = $name;
            }
        } elseif (is_array($value)) {
            $attribute = "$name=\"" . htmlspecialchars(json_encode($value), ENT_COMPAT) . '"';
        } elseif (is_scalar($value)) {
            $attribute = "$name=\"" . htmlspecialchars((string)$value, ENT_COMPAT) . '"';
        }

        return $attribute;
    }
}
