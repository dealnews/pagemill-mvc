<?php

declare(strict_types=1);

namespace PageMill\MVC\HTML\Assets;

use \PageMill\MVC\HTML\Assets;

/**
 * Element asset injector
 *
 * Automatically discovers and injects assets from Element classes into the
 * Assets manager. Elements can define static getAssets() methods that return
 * their required CSS/JS dependencies, and this class handles loading them.
 *
 * Prevents duplicate asset loading by tracking which elements have been
 * processed. Supports group overrides for controlling asset load order.
 *
 * Usage:
 * ```php
 * $injector = new Injector($assets);
 *
 * // Inject assets from elements
 * $injector->add([
 *     MyElement::class,
 *     AnotherElement::class
 * ]);
 *
 * // Override groups
 * $injector->add([SomeElement::class], [
 *     '*' => 'header',              // All to header
 *     'exclude' => ['footer']       // Except footer
 * ]);
 *
 * // Inline assets from elements immediately
 * $injector->inline([QuickElement::class]);
 * ```
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present DealNews.com, Inc
 * @package     PageMill\MVC\HTML\Assets
 */
class Injector {

    /**
     * Assets manager instance
     *
     * Receives injected assets from elements.
     *
     * @var Assets
     */
    protected Assets $asset_object;

    /**
     * Tracking map of processed elements
     *
     * Keyed by element class name to prevent duplicate asset injection.
     *
     * @var array<string, bool>
     */
    protected array $seen_elements = [];

    /**
     * Creates an Injector instance
     *
     * @param Assets|null $asset_object Optional Assets instance (defaults to singleton)
     */
    public function __construct(?Assets $asset_object = null) {
        if (empty($asset_object)) {
            $this->asset_object = Assets::init();
        } else {
            $this->asset_object = $asset_object;
        }
    }

    /**
     * Injects assets from Element classes
     *
     * Calls each Element's static getAssets() method and adds returned
     * assets to the Assets manager. Elements are tracked to prevent
     * duplicate processing.
     *
     * Elements must:
     * - Extend \PageMill\MVC\ElementAbstract
     * - Implement static getAssets(): array
     *
     * The getAssets() return format:
     * ```php
     * return [
     *     'css' => [
     *         'default' => ['element.css'],
     *         'header' => ['critical.css']
     *     ],
     *     'js' => [
     *         'footer' => ['element.js']
     *     ]
     * ];
     * ```
     *
     * Group override structure:
     * ```php
     * [
     *     '*' => 'new_group',           // Override all groups
     *     'exclude' => ['keep_group'],  // Except these
     *     'groups' => [
     *         'default' => 'header'     // Specific overrides
     *     ]
     * ]
     * ```
     *
     * @param array<int, class-string> $elements Element class names
     * @param array<string, mixed> $group_override Group override configuration
     * @return void
     * @throws \InvalidArgumentException If class is not a PageMill Element
     */
    public function add(array $elements, array $group_override = []): void {
        foreach ($elements as $el) {
            if (isset($this->seen_elements[$el])) {
                continue;
            }

            $this->seen_elements[$el] = true;

            $ref = new \ReflectionClass($el);
            if (!$ref->isSubclassOf("\PageMill\MVC\ElementAbstract")) {
                throw new \InvalidArgumentException("$el is not a PageMill Element");
            }

            $assets = $el::getAssets();

            foreach ($assets as $type => $groups) {
                foreach ($groups as $group => $asset_list) {
                    if (!empty($asset_list)) {
                        $this->asset_object->add(
                            $type,
                            $asset_list,
                            $this->resolveGroup($group, $group_override)
                        );
                    }
                }
            }
        }
    }

    /**
     * Resolves group name with overrides
     *
     * Applies group override rules to determine the final group name.
     * Used internally to process group_override arrays.
     *
     * @param string $group Original group name
     * @param array<string, mixed> $group_override Override configuration
     * @return string Resolved group name
     */
    public function resolveGroup(string $group, array $group_override): string {
        if (!empty($group_override)) {
            if (
                empty($group_override['exclude']) ||
                !in_array($group, $group_override['exclude'])
            ) {
                if (!empty($group_override['groups'][$group])) {
                    $group = $group_override['groups'][$group];
                } elseif (!empty($group_override['*'])) {
                    $group = $group_override['*'];
                }
            }
        }

        return $group;
    }

    /**
     * Injects and immediately inlines element assets
     *
     * Adds element assets to a temporary unique group, then immediately
     * outputs them inline (embedded in HTML). Useful for critical CSS
     * or small JavaScript snippets that should be in the initial payload.
     *
     * @param array<int, class-string> $elements Element class names
     * @param array<int, string> $group_exclude Groups to exclude from inlining
     * @return void
     */
    public function inline(array $elements, array $group_exclude = ['footer']): void {
        $custom_group = uniqid();

        $this->add(
            $elements,
            [
                '*'       => $custom_group,
                'exclude' => $group_exclude,
            ]
        );
        $this->asset_object->inline($custom_group);
    }

    /**
     * Injects and immediately links element assets
     *
     * Adds element assets to a temporary unique group, then immediately
     * outputs them as linked tags (<link> or <script>). Useful for
     * elements that need assets loaded synchronously at their point
     * of insertion in the page.
     *
     * @param array<int, class-string> $elements Element class names
     * @param array<int, string> $group_exclude Groups to exclude from linking
     * @return void
     */
    public function link(array $elements, array $group_exclude = ['footer']): void {
        $custom_group = uniqid();

        $this->add(
            $elements,
            [
                '*'       => $custom_group,
                'exclude' => $group_exclude,
            ]
        );
        $this->asset_object->link($custom_group);
    }

    /**
     * Singleton generator
     * @return Injector
     */
    public static function init(): Injector {
        static $inst;
        if (empty($inst)) {
            $inst = new Injector();
        }

        return $inst;
    }
}
