<?php

declare(strict_types=1);

namespace PageMill\MVC\Traits;

/**
 * Property mapping trait
 *
 * Provides functionality to automatically map array keys to object properties
 * with optional type validation. Supports static constraints for enforcing
 * property types beyond native type declarations.
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present DealNews.com, Inc
 * @package     PageMill\MVC\Traits
 */
trait PropertyMap {
    /**
     * Maps array keys to object properties
     *
     * Takes an associative array and maps its keys to class properties.
     * Performs type validation based on existing property values or
     * static $constraints array if defined.
     *
     * Type validation checks:
     * 1. If static::$constraints[$var]['type'] exists, uses that type
     * 2. Otherwise, if property has a non-null value, uses gettype()
     * 3. For objects, uses instanceof check
     *
     * Example $constraints:
     * ```php
     * protected static array $constraints = [
     *     'email' => ['type' => 'string'],
     *     'age' => ['type' => 'integer'],
     * ];
     * ```
     *
     * @param array<string, mixed> $inputs Array of values to map to properties
     * @param bool|null $ignore When true, unknown properties are ignored.
     *                          When false, throws exception for unknown properties.
     *                          Default is false.
     * @return void
     * @throws \InvalidArgumentException If property doesn't exist (when $ignore is false)
     *                                   or if type validation fails
     * @suppress PhanUndeclaredStaticProperty
     */
    protected function mapProperties(array $inputs, ?bool $ignore = null): void {
        if (!empty($inputs)) {
            foreach ($inputs as $var => $value) {
                if (property_exists($this, $var)) {
                    $has_constraint = isset(static::$constraints) &&
                                      isset(static::$constraints[$var]['type']);

                    $type = null;

                    if ($has_constraint) {
                        $type = static::$constraints[$var]['type'];
                    } elseif (!is_null($this->$var)) {
                        $type = gettype($this->$var);
                    }

                    $valid = !isset($type) ||
                             gettype($value) == $type ||
                             $value instanceof $type;

                    if ($valid) {
                        if (is_array($value) && is_array($this->$var)) {
                            $this->$var = array_replace_recursive(
                                $this->$var,
                                $value
                            );
                        } else {
                            $this->$var = $value;
                        }
                    } else {
                        throw new \InvalidArgumentException(get_class($this) . "::$var must be of type " . $type . '; ' . gettype($value) . ' given.');
                    }
                } elseif (!$ignore) {
                    throw new \InvalidArgumentException("Unknown configuration input $var");
                }
            }
        }
    }
}
