<?php

declare(strict_types=1);

namespace Rcalicdan\ConfigLoader;

/**
 * Get a configuration value using dot notation.
 *
 * @param  string|null  $key
 * @param  mixed  $default
 * @return mixed|ConfigLoader
 */
function config(?string $key = null, $default = null)
{
    $configLoader = ConfigLoader::getInstance();

    if ($key === null) {
        return $configLoader;
    }

    return $configLoader->get($key, $default);
}

/**
 * Get an environment variable value.
 * Automatically initializes ConfigLoader to load .env file.
 *
 * @param  string  $key
 * @param  mixed  $default
 * @param  bool  $convertNumeric  Whether to convert numeric strings to int/float
 * @return mixed
 */
function env(string $key, $default = null, bool $convertNumeric = false)
{
    static $initialized = false;
    if (! $initialized) {
        ConfigLoader::getInstance();
        $initialized = true;
    }

    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false) {
        return $default;
    }

    if (is_string($value)) {
        $converted = match (strtolower($value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => null,
        };

        if ($converted !== null || in_array(strtolower($value), ['null', '(null)', 'empty', '(empty)'], true)) {
            return $converted;
        }

        if ($convertNumeric && is_numeric($value)) {
            if (ctype_digit($value) || (str_starts_with($value, '-') && ctype_digit(substr($value, 1)))) {
                return (int) $value;
            }

            return (float) $value;
        }
    }

    return $value;
}
