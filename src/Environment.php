<?php

declare(strict_types=1);

namespace PageMill\MVC;

/**
 * Environment configuration manager
 *
 * Manages global environment settings for the PageMill MVC framework,
 * such as debug mode and other runtime configurations.
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present DealNews.com, Inc
 * @package     PageMill\MVC
 */
class Environment {

    /**
     * Current debug mode state
     *
     * When true, enables verbose error reporting and debug output.
     *
     * @var bool
     */
    protected static bool $debug = false;

    /**
     * Gets or sets debug mode
     *
     * When called without arguments, returns the current debug state.
     * When called with a boolean argument, sets debug mode and returns
     * the previous state.
     *
     * Example usage:
     * ```php
     * // Get current state
     * $is_debug = Environment::debug();
     *
     * // Enable debug mode
     * $old_state = Environment::debug(true);
     * ```
     *
     * @param bool|null $toggle If provided, sets debug mode to this value
     * @return bool The debug state before any changes were made
     */
    public static function debug(?bool $toggle = null): bool {
        $current_setting = self::$debug;
        if (func_num_args() > 0) {
            self::$debug = (bool)$toggle;
        }

        return $current_setting;
    }
}
