<?php

declare(strict_types=1);

namespace PageMill\MVC\HTML\Assets;

use PageMill\HTTP\HTTP;
use PageMill\HTTP\Request;
use PageMill\HTTP\Response;
use PageMill\MVC\HTML\Assets;
use PageMill\MVC\Environment;

/**
 * Asset combiner for production environments
 *
 * Handles HTTP requests for combined assets, merging multiple CSS or JS
 * files into a single response. Supports conditional requests via
 * If-Modified-Since headers and modification time checking.
 *
 * Typically used behind a URL like /assets/combine?file1.css,file2.css,mtime
 * where the combine URL is configured in Assets->addLocation().
 *
 * Features:
 * - Combines multiple assets into one HTTP response
 * - Respects If-Modified-Since for 304 responses
 * - Validates asset names to prevent directory traversal
 * - Sends appropriate Content-Type headers
 *
 * Usage:
 * ```php
 * $combiner = new Combine($assets, $request, $response);
 * $combiner->combine('css', 'text/css'); // Combines CSS from query string
 * ```
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present DealNews.com, Inc
 * @package     PageMill\MVC\HTML\Assets
 */
class Combine {
    /**
     * Assets manager instance
     *
     * Used to locate and read asset files.
     *
     * @var Assets
     */
    protected Assets $assets;

    /**
     * HTTP Request instance
     *
     * Provides access to request headers and query parameters.
     *
     * @var Request
     */
    protected Request $request;

    /**
     * HTTP Response instance
     *
     * Used to set response headers and status codes.
     *
     * @var Response
     */
    protected Response $response;

    /**
     * Creates a new Combine instance
     *
     * Automatically enables exception throwing for missing assets to
     * ensure combine requests fail fast on errors.
     *
     * @param Assets $assets Assets manager for file location
     * @param Request $request HTTP request object
     * @param Response $response HTTP response object
     */
    public function __construct(Assets $assets, Request $request, Response $response) {
        $this->assets = $assets;
        $this->assets->throwExceptionOnMissing(true);
        $this->request  = $request;
        $this->response = $response;
    }

    /**
     * Combines and outputs assets from query string
     *
     * Reads the query string (or $asset_string parameter) to determine which
     * assets to combine. Assets are concatenated and returned with appropriate
     * Content-Type and caching headers.
     *
     * Query string format: file1.css,file2.css,1234567890
     * - Comma-separated list of asset names
     * - Optional trailing timestamp for cache validation
     *
     * If timestamp is present and request has If-Modified-Since header,
     * may return 304 Not Modified.
     *
     * @param string $type Asset type (css, js, etc.) for file location
     * @param string $content_type Content-Type header value to send
     * @param string|null $asset_string Optional explicit asset list (uses $_SERVER['QUERY_STRING'] if null)
     * @return void
     */
    public function combine(string $type, string $content_type, ?string $asset_string = null): void {
        $tag = [
            'open'    => '',
            'close'   => '',
            'comment' => '/* %s */',
        ];

        if ($asset_string === null) {
            $asset_string = $_SERVER['QUERY_STRING'];
        }

        // If there is a number on the end of th asset string, assume
        // it is the max modified time of the asset files. If that is older
        // than the If-Modified-Since header, return a Not Modified header.
        if (preg_match("/,(\d+)$/", $asset_string, $match)) {
            $mod_time    = (int)$match[1];
            $if_modified = $this->request->header('If-Modified-Since');
            if (!empty($if_modified)) {
                $iftime = strtotime($if_modified);
                if ($mod_time <= $iftime) {
                    $this->response->status(HTTP::NOT_MODIFIED);
                    echo '';
                    flush();
                    exit();
                }
            }
        } else {

            // Check for bots adding trash to the URL

            $parts = explode(',', $asset_string);

            $last = end($parts);

            if (preg_match("/^\d+&/", $last)) {
                // we have a bot adding trash to the URL
                $this->response->error(404);
            }
        }

        $this->response->contentType($content_type);

        if (!empty($asset_string)) {
            $files = explode(',', $asset_string);

            $asset_list = [];

            foreach ($files as $file) {

                // there is a cache buster timestamp on the end
                if (is_numeric($file)) {
                    continue;
                }

                $asset_list[] = rawurldecode($file);
            }

            $this->assets->add($type, $asset_list);

            ob_start();
            try {
                // inline all the assets and capture the output

                // disable debug output as it breaks CSS
                $old_debug = Environment::debug(false);

                $this->assets->inline();
                $output = ob_get_clean();

                Environment::debug($old_debug);
            } catch (Exception $e) {
                ob_end_clean();
                $output = '';
            }

            // if we have no output, send a 404
            if (strlen($output) === 0) {
                $this->response->error(404);
            }

            // set a 30 day cache header
            $this->response->cache(30 * 86400, !empty($mod_time) ? gmdate('r', $mod_time) : null);

            echo $output;
        } else {

            // if we have no asset string, send a 404
            $this->response->error(404);
        }
    }
}
