<?php

declare(strict_types=1);

namespace Rcalicdan\ConfigLoader;

use Dotenv\Dotenv;
use Rcalicdan\ConfigLoader\Exceptions\ConfigException;
use Rcalicdan\ConfigLoader\Exceptions\ConfigKeyNotFoundException;
use Rcalicdan\ConfigLoader\Exceptions\EnvFileLoadException;
use Rcalicdan\ConfigLoader\Exceptions\EnvFileNotFoundException;
use Rcalicdan\ConfigLoader\Exceptions\ProjectRootNotFoundException;

/**
 * A singleton configuration loader that automatically finds the project root.
 *
 * It searches upwards from its directory to locate the project root (identified
 * by a 'vendor' folder), loads the .env and config files from config,
 * and then caches the results to ensure the expensive search happens only once.
 */
final class ConfigLoader
{
    private static ?self $instance = null;
    private ?string $rootPath = null;

    /** @var array<string, mixed> */
    private array $config = [];

    /**
     * The constructor is private to enforce the singleton pattern.
     * It performs the entire one-time loading process.
     */
    private function __construct()
    {
        $this->rootPath = $this->findProjectRoot();

        if ($this->rootPath !== null) {
            $this->loadDotEnv();
            $this->loadConfigFiles();
        }
    }

    /**
     * Gets the singleton instance of the loader.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Resets the singleton instance, primarily for testing.
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * Retrieves a configuration value by its key, supporting dot notation.
     * e.g., get('database') returns the entire database config array
     *       get('database.connections.mysql.host') returns a nested value
     *       get('hello.test') returns config/hello/test.php
     *
     * @param  mixed  $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        if (array_key_exists($key, $this->config)) {
            return $this->config[$key];
        }

        if (str_contains($key, '.')) {
            return $this->getNestedValue($key, $default);
        }

        return $default;
    }

    /**
     * Check if a configuration key exists.
     */
    public function has(string $key): bool
    {
        if (array_key_exists($key, $this->config)) {
            return true;
        }

        if (str_contains($key, '.')) {
            $segments = explode('.', $key);

            for ($i = count($segments); $i > 0; $i--) {
                $fileKey = implode('.', array_slice($segments, 0, $i));

                if (!array_key_exists($fileKey, $this->config)) {
                    continue;
                }

                $remainingSegments = array_slice($segments, $i);

                if ($remainingSegments === []) {
                    return true;
                }

                return $this->arrayKeyExists($this->config[$fileKey], $remainingSegments);
            }

            return false;
        }

        return false;
    }

    /**
     * Set a configuration value at runtime.
     *
     * @param string $key
     * @param mixed $value
     * @return bool True if the key exists and was set successfully, false otherwise
     */
    public function set(string $key, $value): bool
    {
        if (!$this->has($key)) {
            return false;
        }

        if (!str_contains($key, '.')) {
            $this->config[$key] = $value;
            return true;
        }

        $segments = explode('.', $key);

        for ($i = count($segments); $i > 0; $i--) {
            $fileKey = implode('.', array_slice($segments, 0, $i));

            if (!array_key_exists($fileKey, $this->config)) {
                continue;
            }

            $remainingSegments = array_slice($segments, $i);

            // Use strict comparison instead of empty()
            if ($remainingSegments === []) {
                $this->config[$fileKey] = $value;
                return true;
            }

            $this->setNestedValue($fileKey, $remainingSegments, $value);
            return true;
        }

        return false;
    }

    /**
     * Set a configuration value at runtime or fail with an exception.
     *
     * @param string $key
     * @param mixed $value
     * @throws ConfigKeyNotFoundException
     * @return void
     */
    public function setOrFail(string $key, $value): void
    {
        if (!$this->set($key, $value)) {
            throw new ConfigKeyNotFoundException(
                "Configuration key '{$key}' does not exist and cannot be set."
            );
        }
    }

    /**
     * Set a nested value in the configuration array.
     *
     * @param string $fileKey
     * @param array<int, string> $segments
     * @param mixed $value
     * @return void
     */
    private function setNestedValue(string $fileKey, array $segments, $value): void
    {
        $current = &$this->config[$fileKey];

        foreach ($segments as $index => $segment) {
            if (! is_array($current)) {
                throw new ConfigException("Cannot set nested value because a parent key is not an array.");
            }

            if ($index === count($segments) - 1) {
                $current[$segment] = $value;

                return;
            }

            if (! isset($current[$segment]) || ! is_array($current[$segment])) {
                $current[$segment] = [];
            }

            $current = &$current[$segment];
        }
    }

    /**
     * Check if nested keys exist in an array.
     *
     * @param mixed $value
     * @param array<int, string> $segments
     */
    private function arrayKeyExists($value, array $segments): bool
    {
        foreach ($segments as $segment) {
            // Ensure $value is an array before accessing
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return false;
            }

            $value = $value[$segment];
        }

        return true;
    }

    /**
     * Get all configuration values.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * Get the project root path.
     */
    public function getRootPath(): ?string
    {
        return $this->rootPath;
    }

    /**
     * Retrieves a nested configuration value using dot notation.
     *
     * @param  mixed  $default
     * @return mixed
     */
    private function getNestedValue(string $key, $default = null)
    {
        $segments = explode('.', $key);

        for ($i = count($segments); $i > 0; $i--) {
            $fileKey = implode('.', array_slice($segments, 0, $i));

            if (! array_key_exists($fileKey, $this->config)) {
                continue;
            }

            $remainingSegments = array_slice($segments, $i);

            return $this->traverseArray($this->config[$fileKey], $remainingSegments, $default);
        }

        return $default;
    }

    /**
     * Traverse an array using the provided segments.
     *
     * @param  mixed  $value
     * @param  array<int, string>  $segments
     * @param  mixed  $default
     * @return mixed
     */
    private function traverseArray($value, array $segments, $default = null)
    {
        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Searches upwards from the current directory to find the project root.
     * The root is identified by the presence of a `vendor` directory.
     * This operation is memoized (cached) for performance.
     */
    private function findProjectRoot(): ?string
    {
        $dir = __DIR__;
        for ($i = 0; $i < 10; $i++) {
            if (is_dir($dir . '/vendor')) {
                return $dir;
            }

            $parentDir = dirname($dir);
            if ($parentDir === $dir) {
                return null;
            }
            $dir = $parentDir;
        }

        return null;
    }

    private function loadDotEnv(): void
    {
        if ($this->rootPath === null) {
            throw new ProjectRootNotFoundException();
        }

        $envFile = $this->rootPath . '/.env';

        if (! file_exists($envFile)) {
            throw new EnvFileNotFoundException($envFile);
        }

        try {
            $dotenv = Dotenv::createImmutable($this->rootPath);
            $dotenv->load();
        } catch (\Throwable $e) {
            throw new EnvFileLoadException($e->getMessage());
        }
    }

    /**
     * Loads all .php files from the project root's /config directory recursively.
     * Nested directories are converted to dot notation keys.
     *
     * Example structure:
     * - config/database.php -> 'database'
     * - config/hello/test.php -> 'hello.test'
     * - config/services/mail/smtp.php -> 'services.mail.smtp'
     */
    private function loadConfigFiles(): void
    {
        if ($this->rootPath === null) {
            throw new ProjectRootNotFoundException();
        }

        $configDir = $this->rootPath . '/config';

        if (! is_dir($configDir)) {
            return;
        }

        $this->loadConfigFilesRecursively($configDir, $configDir);
    }

    /**
     * Recursively load configuration files from a directory.
     *
     * @param string $directory The directory to scan
     * @param string $baseDir The base config directory for calculating relative paths
     */
    private function loadConfigFilesRecursively(string $directory, string $baseDir): void
    {
        $items = scandir($directory);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;

            if (is_dir($path)) {
                $this->loadConfigFilesRecursively($path, $baseDir);

                continue;
            }

            if (pathinfo($path, PATHINFO_EXTENSION) !== 'php') {
                continue;
            }

            $relativePath = str_replace($baseDir . '/', '', $path);

            $relativePath = substr($relativePath, 0, -4);

            $key = str_replace(['/', '\\'], '.', $relativePath);

            $this->config[$key] = require $path;
        }
    }
}