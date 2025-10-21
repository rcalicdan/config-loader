<?php

declare(strict_types=1);

namespace Rcalicdan\ConfigLoader;

use Dotenv\Dotenv;
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
            $value = $this->config;

            foreach ($segments as $segment) {
                if (is_array($value) && array_key_exists($segment, $value)) {
                    $value = $value[$segment];
                } else {
                    return false;
                }
            }

            return true;
        }

        return false;
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
        $value = $this->config;

        foreach ($segments as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
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
            if (is_dir($dir.'/vendor')) {
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

        $envFile = $this->rootPath.'/.env';

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
     * Loads all .php files from the project root's /config directory.
     */
    private function loadConfigFiles(): void
    {
        if ($this->rootPath === null) {
            throw new ProjectRootNotFoundException();
        }

        $configDir = $this->rootPath.'/config';
        $files = is_dir($configDir) ? glob($configDir.'/*.php') : false;

        if ($files === false || $files === []) {
            return;
        }

        foreach ($files as $file) {
            $key = basename($file, '.php');
            $this->config[$key] = require $file;
        }
    }
}
