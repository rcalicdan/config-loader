<?php

declare(strict_types=1);

namespace Rcalicdan\ConfigLoader;

use Rcalicdan\ConfigLoader\Exceptions\ConfigKeyNotFoundException;

/**
 * Static Api for ConfigLoader providing convenient static access to configuration.
 */
final class Config
{
    /**
     * Prevent instantiation of this static class.
     */
    private function __construct()
    {
    }

    /**
     * Retrieves a configuration value by its key, supporting dot notation.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return ConfigLoader::getInstance()->get($key, $default);
    }

    /**
     * Load a configuration file from the project root directory.
     *
     * @param string $filename
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public static function loadFromRoot(string $filename, ?string $key = null, $default = null)
    {
        return ConfigLoader::getInstance()->loadFromRoot($filename, $key, $default);
    }

    /**
     * Set a configuration value at runtime.
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public static function set(string $key, $value): bool
    {
        return ConfigLoader::getInstance()->set($key, $value);
    }

    /**
     * Set a configuration value at runtime or fail with an exception.
     *
     * @param string $key
     * @param mixed $value
     * @throws ConfigKeyNotFoundException
     * @return void
     */
    public static function setOrFail(string $key, $value): void
    {
        ConfigLoader::getInstance()->setOrFail($key, $value);
    }

    /**
     * Set a configuration value from a root config file at runtime.
     *
     * @param string $filename The name of the config file (with or without .php extension)
     * @param string $key The key to set (supports dot notation)
     * @param mixed $value The value to set
     * @param bool $createPath Whether to create the path if it doesn't exist (default: true)
     * @return bool True if successfully set, false otherwise
     */
    public static function setFromRoot(string $filename, string $key, $value, bool $createPath = true): bool
    {
        return ConfigLoader::getInstance()->setFromRoot($filename, $key, $value, $createPath);
    }

    /**
     * Check if a configuration key exists.
     *
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return ConfigLoader::getInstance()->has($key);
    }

    /**
     * Get all configuration values.
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return ConfigLoader::getInstance()->all();
    }

    /**
     * Get the project root path.
     *
     * @return string|null
     */
    public static function getRootPath(): ?string
    {
        return ConfigLoader::getInstance()->getRootPath();
    }

    /**
     * Reset the ConfigLoader instance, primarily for testing.
     *
     * @return void
     */
    public static function reset(): void
    {
        ConfigLoader::reset();
    }
}
