<?php

declare(strict_types=1);

namespace PageMill\MVC\HTML;

use PageMill\MVC\Environment;

/**
 * Asset management system
 *
 * Manages CSS, JavaScript, and other page assets with support for:
 * - Multiple asset types (css, js, custom types)
 * - Asset grouping (default, header, footer, custom)
 * - Multiple output modes (linked, combined, inline, URL-only)
 * - Asset locations (filesystem, CDN, combine servers)
 * - Custom handler callbacks for advanced asset processing
 * - MD5 fingerprinting for cache busting
 *
 * Usage:
 * ```php
 * $assets = Assets::init();
 *
 * // Configure asset location
 * $assets->addLocation('css', [
 *     'directory' => '/path/to/css',
 *     'url' => 'https://cdn.example.com/css'
 * ]);
 *
 * // Add assets to groups
 * $assets->add('css', ['main.css', 'layout.css']); // default group
 * $assets->add('js', ['app.js'], 'footer');        // footer group
 *
 * // Generate output
 * $assets->link('css');           // Linked CSS tags
 * $assets->link('js', 'footer');  // Linked JS tags from footer group
 * $assets->inline('css');         // Inline CSS
 * $assets->combine('css');        // Combined CSS link
 * ```
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present DealNews.com, Inc
 * @package     PageMill\MVC\HTML
 */
class Assets {

    /**
     * Asset tag templates for output generation
     *
     * Defines how assets are formatted for different output modes:
     * - 'url': Just the URL (for custom handling)
     * - 'linked': Full HTML tags for linking assets
     * - 'combined': URLs for combine server
     * - 'inline': Opening/closing tags and comments for inline mode
     *
     * Templates support placeholders:
     * - #md5#: MD5 hash of file content (cache busting)
     * - #mtime#: Modification timestamp
     * - %s: Asset URL/path (sprintf format)
     *
     * @var array<string, array<string, string|array>>
     */
    protected array $asset_tag_templates = [
        'url' => [
            'css' => '%s?#md5#',
            'js'  => '%s?#md5#',
        ],
        'linked' => [
            'css' => '<link href="%s?#md5#" type="text/css" rel="stylesheet">',
            'js'  => '<script crossorigin="use-credentials" type="text/javascript" src="%s?#md5#"></script>',
        ],
        'combined' => [
            'css' => '<link href="%s,#mtime#" type="text/css" rel="stylesheet">',
            'js'  => '<script crossorigin="use-credentials" type="text/javascript" src="%s,#mtime#"></script>',
        ],
        'inline' => [
            'css' => [
                'open'    => '<style>',
                'close'   => '</style>',
                'comment' => '/* %s */',
            ],
            'js' => [
                'open'    => '<script>',
                'close'   => '</script>',
                'comment' => '/* %s */',
            ],
        ],
    ];

    /**
     * Collection of registered assets organized by type and group
     *
     * Structure: [type => [group => [asset_names]]]
     * Example: ['css' => ['default' => ['main.css'], 'footer' => ['late.css']]]
     *
     * CSS and JS are pre-defined because CSS must load before JS to
     * ensure correct box model calculations when JS runs on page load.
     *
     * @var array<string, array<string, array<int, string>>>
     */
    protected array $assets = [
        'css' => [],
        'js'  => [],
    ];

    /**
     * Assets that have already been output
     *
     * Prevents duplicate output of the same asset. Keyed by asset name.
     *
     * @var array<string, bool>
     */
    protected array $loaded_assets = [];

    /**
     * Configured locations for asset loading
     *
     * Maps asset types to location configurations (directory, URL, combine URL, etc.).
     * Multiple locations can be configured per type with fallback behavior.
     *
     * @var array<string, array<int, array<string, mixed>>>
     */
    protected array $asset_locations = [];

    /**
     * Custom handler callbacks for asset processing
     *
     * Allows registering closures to handle asset generation for specific types.
     * Structure: [handler_type => [asset_type => ['callback' => Closure, 'asset_list' => []]]]
     *
     * @var array<string, array<string, array<string, mixed>>>
     */
    protected array $asset_handlers = [];

    /**
     * Whether to throw exceptions for missing assets
     *
     * When true, missing assets throw \PageMill\MVC\HTML\Assets\Exception.
     * When false, missing assets trigger E_USER_WARNING.
     *
     * @var bool
     */
    protected bool $exception_on_missing = false;

    /**
     * Gets the singleton Assets instance
     *
     * Returns the same instance across all calls within a request.
     *
     * @return Assets The singleton instance
     */
    public static function init(): Assets {
        static $inst;
        if (empty($inst)) {
            $inst = new static();
        }

        return $inst;
    }

    /**
     * Configures missing asset error handling
     *
     * Controls whether missing assets throw exceptions or trigger warnings.
     *
     * @param bool $toggle True to throw exceptions, false for warnings
     * @return bool The previous setting
     */
    public function throwExceptionOnMissing(bool $toggle): bool {
        $return                     = $this->exception_on_missing;
        $this->exception_on_missing = (bool)$toggle;

        return $this->exception_on_missing;
    }

    /**
     * Registers an asset location
     *
     * Defines where to find assets of a specific type. Multiple locations can
     * be added per type, creating a fallback chain.
     *
     * Location array must contain one or more of:
     * - `directory`: Filesystem path where assets exist
     * - `url`: Base URL for serving assets
     * - `combine_url`: URL endpoint that combines multiple assets
     *
     * Optional keys:
     * - `combine_root`: Prefix for combine URLs
     * - `inline_replace`: [search => replace] array for inline asset processing
     * - `min_suffix`: Suffix for minified versions (e.g., "min" for file.css.min)
     *
     * Example:
     * ```php
     * $assets->addLocation('css', [
     *     'directory' => '/var/www/css',
     *     'url' => 'https://cdn.example.com/css',
     *     'min_suffix' => 'min'
     * ]);
     * ```
     *
     * @param string $type Asset type (css, js, etc.)
     * @param array<string, mixed> $location Location configuration
     * @return void
     * @throws \InvalidArgumentException If location config is invalid
     */
    public function addLocation(string $type, array $location): void {
        if (empty($this->asset_locations[$type])) {
            $this->asset_locations[$type] = [];
        }

        if (
            empty($location['url']) &&
            empty($location['directory']) &&
            empty($location['combine_url'])
        ) {
            $location = null;
        }

        if (is_null($location)) {
            throw new \InvalidArgumentException('Invalid value for location.');
        }

        if (!empty($location['url'])) {
            $location['url'] = rtrim($location['url'], '/');
        }

        $this->asset_locations[$type][] = $location;
    }

    /**
     * Sets or updates an asset tag template
     *
     * Customizes how assets are formatted for output. Use this to change
     * default templates or add templates for custom asset types.
     *
     * For 'linked' style: Provide a sprintf-compatible string with %s for URL.
     * For 'inline' style: Provide array with 'open', 'close', and 'comment' keys.
     *
     * Template placeholders:
     * - #md5#: Replaced with MD5 hash of file content
     * - #mtime#: Replaced with file modification timestamp
     * - %s: Replaced with asset URL (sprintf)
     *
     * Examples:
     * ```php
     * // Custom linked template
     * $assets->setTagTemplate('linked', 'css', '<link rel="stylesheet" href="%s">');
     *
     * // Custom inline template
     * $assets->setTagTemplate('inline', 'xml', [
     *     'open' => '<!-- XML DATA',
     *     'close' => '-->',
     *     'comment' => '<!-- %s -->'
     * ]);
     * ```
     *
     * @param string $style Template style ('linked', 'inline', 'url', 'combined')
     * @param string $type Asset type (css, js, etc.)
     * @param mixed $tag Template string or array (depends on style)
     * @return void
     * @throws \InvalidArgumentException If template format is invalid
     */
    public function setTagTemplate(string $style, string $type, mixed $tag): void {
        if ($style == 'inline') {
            if (is_array($tag) && isset($tag['open']) && isset($tag['close']) && isset($tag['comment'])) {
                if (isset($tag['open'])) {
                    $this->asset_tag_templates['inline'][$type]['open'] = $tag['open'];
                }
                if (isset($tag['close'])) {
                    $this->asset_tag_templates['inline'][$type]['close'] = $tag['close'];
                }
                if (isset($tag['comment'])) {
                    $this->asset_tag_templates['inline'][$type]['comment'] = $tag['comment'];
                }
            } else {
                throw new \InvalidArgumentException('Asset templates or linked assets should be an array with an open, close and/or comment.');
            }
        } else {
            if (is_string($tag)) {
                $this->asset_tag_templates[$style][$type] = $tag;
            } else {
                throw new \InvalidArgumentException("Asset templates for $style assets should be a string.");
            }
        }
    }

    /**
     * Registers a custom asset handler callback
     *
     * Allows defining custom logic for asset generation using closures.
     * Handlers are invoked during asset output and can implement specialized
     * asset loading logic (e.g., from CDN, with specific attributes, etc.).
     *
     * The callback receives asset names and should output the appropriate HTML.
     * It's bound to the Assets instance, giving access to $this methods.
     *
     * Example:
     * ```php
     * $assets->registerHandler('js', 'linked', function($assets) {
     *     foreach ($assets as $asset) {
     *         echo '<script defer src="' . $asset . '"></script>';
     *     }
     * }, ['jquery.js', 'lodash.js']);
     * ```
     *
     * @param string $asset_type Asset type to handle (css, js, etc.)
     * @param string $handler_type Handler mode ('linked', 'inline', etc.)
     * @param \Closure $callback Handler function(array $assets): void
     * @param array<int, string> $asset_list Optional list of assets this handler manages
     * @return void
     */
    public function registerHandler(
        string $asset_type,
        string $handler_type,
        \Closure $callback,
        array $asset_list = []
    ): void {
        $this->asset_handlers[$handler_type][$asset_type] = [
            'callback'   => \Closure::bind($callback, $this, $this),
            'asset_list' => $asset_list,
        ];
    }

    /**
     * Adds assets to the collection
     *
     * Registers assets for later output. Assets are organized by type and group,
     * allowing fine-grained control over loading order and location.
     *
     * Common groups:
     * - 'default': Standard assets loaded in <head>
     * - 'header': Critical above-the-fold assets
     * - 'footer': Deferred assets loaded at page end
     *
     * Examples:
     * ```php
     * // Add to default group
     * $assets->add('css', ['normalize.css', 'main.css']);
     *
     * // Add to footer group
     * $assets->add('js', ['analytics.js', 'social.js'], 'footer');
     *
     * // Add custom type
     * $assets->add('fonts', ['roboto.woff2']);
     * ```
     *
     * @param string $type Asset type (css, js, or custom)
     * @param array<int, string> $assets List of asset filenames
     * @param string $group Group name for organizational purposes
     * @return void
     * @throws \LogicException If asset type has no templates defined
     */
    public function add(string $type, array $assets, string $group = 'default'): void {
        if (!isset($this->assets[$type])) {
            throw new \LogicException("Unknown type $type. No templates defined.");
        }

        if (empty($this->assets[$type][$group])) {
            $this->assets[$type][$group] = [];
        }

        foreach ($assets as $asset) {
            if (empty($this->assets[$type][$group][$asset])) {
                $this->assets[$type][$group][$asset] = $asset;
            }
        }
    }

    /**
     * Helper function for clarity that wraps the real generate
     *
     * @param  string $group The group of assets to generate
     * @param  string $type  The type of asset to generate
     *
     * @return void
     */
    public function inline(?string $group = null, ?string $type = null) {
        $this->generate('inline', $group, $type);
    }

    /**
     * Helper function for clarity that wraps the real generate
     *
     * @param  string $group The group of assets to generate
     * @param  string $type  The type of asset to generate
     *
     * @return void
     */
    public function link(?string $group = null, ?string $type = null) {
        $this->generate('linked', $group, $type);
    }

    /**
     * Helper function for clarity that wraps the real generate
     *
     * @param  string $group The group of assets to generate
     * @param  string $type  The type of asset to generate
     *
     * @return void
     */
    public function combine(?string $group = null, ?string $type = null) {
        $this->generate('combined', $group, $type);
    }

    /**
     * Generates assets using the handler for type and group
     *
     * If group is not set, all assets will be generated for each group separately.
     * This allows grouping of assets used in different places and have separate
     * cacheable assets for each group. This should improve performance as assets
     * will not have to be loaded on every page if they are grouped well.
     *
     * @param  string $handler The generation handler. Built in values are inline,
     *                         linked, urls, and combined. Other styles can be
     *                         added using register_handler.
     * @param  string $group   The group of assets to generate
     * @param  string $type    The type of asset to generate. By default this is
     *                         js and css. Other types can be added using
     *                         add_template.
     *
     * @return void
     * @throws \LogicException
     */
    public function generate(string $handler, ?string $group = null, ?string $type = null) {
        foreach ($this->assets as $asset_type => $asset_groups) {
            if (is_null($type) || $type == $asset_type) {
                if (empty($this->loaded_assets[$asset_type])) {
                    $this->loaded_assets[$asset_type] = [];
                }

                foreach ($this->assets[$asset_type] as $asset_group => $asset_list) {
                    if (is_null($group) || $group == $asset_group) {
                        if (isset($this->asset_handlers[$handler][$asset_type])) {
                            $callback = $this->asset_handlers[$handler][$asset_type]['callback'];

                            if (empty($callback) || !is_callable($callback)) {
                                throw new \LogicException("Handler for $asset_type/$handler is not a valid callback.");
                            }

                            foreach ($asset_list as $asset) {
                                if (
                                    empty($this->asset_handlers[$handler][$asset_type]['asset_list']) ||
                                    in_array($asset, $this->asset_handlers[$handler][$asset_type]['asset_list'])
                                ) {
                                    $ret = $callback($asset_type, $asset);
                                    if ($ret === true) {
                                        $this->loaded_assets[$asset_type][$asset] = true;
                                    } else {
                                        throw new \LogicException("Unable to load $asset_type asset $asset using handler $handler");
                                    }
                                }
                            }
                        } elseif (method_exists($this, 'generate' . ucfirst($handler))) {
                            $callback = [$this, 'generate' . ucfirst($handler)];
                            $callback($asset_type, $asset_list);
                        } else {
                            throw new \LogicException("No handler for generating $asset_type/$handler");
                        }
                    }
                }
            }
        }
    }

    /**
     * Injects assets directly into the page
     *
     * @param  string   $type        The type of asset to handle
     * @param  array    $asset_list  A list of assets to in inline
     * @return void
     *
     */
    protected function generateInline(string $type, array $asset_list) {
        $open_written = false;

        foreach ($asset_list as $asset) {
            if (isset($this->loaded_assets[$type][$asset])) {
                continue;
            }

            $this->loaded_assets[$type][$asset] = true;

            $asset_location = $this->find($type, $asset);
            if (empty($asset_location['path'])) {
                continue;
            }

            // Write the open tag if it has not yet been written
            // or if we are in debug mode and want to write an open
            // and close tag for every asset.
            if (
                !Environment::debug() &&
                !empty($this->asset_tag_templates['inline'][$type]['open']) &&
                !$open_written
            ) {
                echo $this->asset_tag_templates['inline'][$type]['open'] . "\n";
                $open_written = true;
            }

            if (!empty($this->asset_tag_templates['inline'][$type]['comment'])) {
                printf($this->asset_tag_templates['inline'][$type]['comment'], $asset);
                echo "\n";
            }

            if (!empty($asset_location['inline_replace'])) {
                $contents = file_get_contents($asset_location['path']);
                foreach ($asset_location['inline_replace'] as $replace) {
                    $contents = str_replace($replace[0], $replace[1], $contents);
                }
                echo $contents;
            } else {
                readfile($asset_location['path']);
            }
            echo "\n";

            // If in debug mode, write a close tag and log to the console
            // after every asset.
            if (Environment::debug()) {
                echo $this->asset_tag_templates['inline'][$type]['close'] . "\n";
                echo $this->asset_tag_templates['inline']['js']['open'] . "\n";
                echo "console.log('Asset loaded: $type - $asset');\n";
                echo $this->asset_tag_templates['inline']['js']['close'] . "\n";
            }
        }

        // if we have written an open tag and we are not in debug mode,
        // write the close tag once all assets are loaded
        if ($open_written && !Environment::debug() && !empty($this->asset_tag_templates['inline'][$type]['close'])) {
            echo $this->asset_tag_templates['inline'][$type]['close'] . "\n";
        }
    }

    /**
     * Adds links to assets
     *
     * @param  string   $type        The type of asset to handle
     * @param  array    $asset_list  A list of assets to in inline
     * @return void
     *
     */
    protected function generateLinked(string $type, array $asset_list) {
        $tags = $this->generateUrlTemplate($type, $asset_list, 'linked');
        if (!empty($tags)) {
            echo implode("\n", $tags) . "\n";
        }
    }

    /**
     * Generate a list of URLs for assets
     *
     * @param  string   $type        The type of asset to handle
     * @param  array    $asset_list  A list of assets to in inline
     * @return array
     *
     */
    protected function generateUrls(string $type, array $asset_list): array {
        return $this->generateUrlTemplate($type, $asset_list, 'url');
    }

    /**
     * Generates a list of templates for the assets
     *
     * @param  string   $type        The type of asset to handle
     * @param  array    $asset_list  A list of assets to in inline
     * @param  string   $template    The template set to use
     * @return array
     *
     */
    protected function generateUrlTemplate(string $type, array $asset_list, string $template): array {
        $urls = [];

        foreach ($asset_list as $asset) {
            if (isset($this->loaded_assets[$type][$asset])) {
                continue;
            }

            $this->loaded_assets[$type][$asset] = true;

            $asset_location = $this->find($type, $asset);

            if (empty($asset_location['url'])) {
                continue;
            }

            $tag = $this->asset_tag_templates[$template][$type];

            if (strpos($tag, '#mtime#') !== false) {
                if (!empty($asset_location['path'])) {
                    $mtime = @filemtime($asset_location['path']);
                    if ($mtime) {
                        $tag = str_replace('#mtime#', $mtime, $tag);
                    }
                }
            }

            if (strpos($tag, '#md5#') !== false) {
                if (!empty($asset_location['path'])) {
                    $md5 = @md5_file($asset_location['path']);
                    if ($md5) {
                        $tag = str_replace('#md5#', $md5, $tag);
                    }
                }
            }

            $urls[] = sprintf($tag, $asset_location['url']);
        }

        return $urls;
    }

    /**
     * Adds links to assets
     *
     * @param  string   $type        The type of asset to handle
     * @param  array    $asset_list  A list of assets to in inline
     * @return void
     *
     */
    protected function generateCombined(string $type, array $asset_list) {
        $combine_urls = [];

        $max_mtime = 0;

        foreach ($asset_list as $asset) {
            if (isset($this->loaded_assets[$type][$asset])) {
                continue;
            }

            $this->loaded_assets[$type][$asset] = true;

            $asset_location = $this->find($type, $asset);

            if (empty($asset_location['combine_url'])) {
                continue;
            }

            if (empty($combine_urls[$asset_location['combine_url']])) {
                $combine_urls[$asset_location['combine_url']] = [];
            }

            $combine_urls[$asset_location['combine_url']][$asset] = rawurlencode($asset_location['combine_path']);

            if (strpos($this->asset_tag_templates['combined'][$type], '#mtime#') !== false) {
                if (!empty($asset_location['path'])) {
                    $max_mtime = max($max_mtime, filemtime($asset_location['path']));
                }
            }
        }

        foreach ($combine_urls as $url => $assets) {
            $tag = $this->asset_tag_templates['combined'][$type];

            if ($max_mtime > 0) {
                $tag = str_replace('#mtime#', $max_mtime, $tag);
            }

            $combined_url = $url . '?' . implode(',', $assets);

            printf($tag, $combined_url);

            echo "\n";
        }
    }

    /**
     * Determines if a file is on disk or available only via a URL.
     *
     * @param  string $type  Asset type (css, js, etc.) to find
     * @param  string $asset Asset name to find
     * @return array  An array containing information about how the asset
     *                can be loaded. Possible values include:
     *
     *                path           - The path on disk where the asset can
     *                                 be found
     *                url            - The URL that can be used to linked to
     *                                 the asset
     *                combine_url    - The base URL that can be used to
     *                                 combine this asset with others
     *                combine_path   - The path to add to the combine_url to
     *                                 form a URL to link
     *                inline_replace - Instructions for replacing values in
     *                                 the asset file when inlining it.
     *
     * @throws \PageMill\MVC\HTML\Assets\Exception
     */
    protected function find(string $type, string $asset): array {
        $tried = [];

        $asset_location = [
            'path'           => '',
            'url'            => '',
            'combine_url'    => '',
            'combine_path'   => '',
            'inline_replace' => [],
        ];

        foreach ($this->asset_locations[$type] as $location) {
            if (!empty($location['directory'])) {
                $test_paths = [];

                if (!empty($location['min_suffix'])) {
                    foreach ($location['min_suffix'] as $suffix) {
                        $test_paths["$asset$suffix"] = $location['directory'] . "/$asset$suffix.$type";
                    }
                }

                $test_paths[$asset] = $location['directory'] . "/$asset.$type";

                foreach ($test_paths as $name => $test_path) {
                    if (file_exists($test_path)) {
                        $asset_location['path'] = $test_path;

                        if (!empty($location['url'])) {
                            $asset_location['url'] = $location['url'] . "/$name.$type";
                        }

                        if (!empty($location['combine_url'])) {
                            $asset_location['combine_url'] = $location['combine_url'];
                            if (!empty($location['combine_root'])) {
                                $asset_location['combine_path'] = $location['combine_root'] . "/$name";
                            } else {
                                $asset_location['combine_path'] = $name;
                            }
                        }

                        if (!empty($location['inline_replace'])) {
                            $asset_location['inline_replace'] = $location['inline_replace'];
                        }

                        break 2;
                    }

                    $tried[] = $test_path;
                }
            } elseif (!empty($location['url'])) {
                // fall back to the URL for locations that have no path,
                // but keep trying others in case we find a match on disk
                $asset_location['url'] = $location['url'] . "/$asset.$type";
            } elseif (!empty($location['combine_url'])) {
                // fall back to the URL for locations that have no path,
                // but keep trying others in case we find a match on disk
                $asset_location['combine_url'] = $location['combine_url'];
                if (!empty($location['combine_root'])) {
                    $asset_location['combine_path'] = $location['combine_root'] . "/$asset.$type";
                } else {
                    $asset_location['combine_path'] = "$asset.$type";
                }
            }
        }

        if (empty($asset_location['path']) && empty($asset_location['url']) && empty($asset_location['combine_url'])) {
            $message = "$type asset $asset not found. Tried: " . implode(', ', $tried);
            if ($this->exception_on_missing) {
                throw new Assets\Exception($message);
            } else {
                trigger_error($message, E_USER_WARNING);
            }
        }

        return $asset_location;
    }
}
