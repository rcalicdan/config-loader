# ConfigLoader

A lightweight, zero-configuration PHP library for managing environment variables and configuration files with automatic project root detection.

## Features

*   **Zero Configuration**: Automatically finds your project root without any setup.
*   **Singleton Pattern**: Loads configuration only once per request, ensuring high performance.
*   **Convention-based**: Works out-of-the-box with a standard `.env` file and `/config` directory structure.
*   **Dot Notation**: Access nested configuration values easily (e.g., `database.connections.mysql.host`).
*   **Runtime Configuration**: Modify configuration values on the fly for testing or dynamic adjustments.
*   **Manual File Loading**: Load specific configuration files from your project root on demand.
*   **Static Facade**: A convenient static wrapper (`Config::class`) for easy access anywhere in your code.
*   **Helper Functions**: Simple global `config()`, `configRoot()`, and `env()` functions for quick access.
*   **Performance Optimized**: All loaded configuration is cached in memory for the duration of the request.

## Installation

```bash
composer require rcalicdan/config-loader
```

## Requirements

*   PHP 8.3 or higher

## Quick Start

### Basic Usage

```php
use Rcalicdan\ConfigLoader\ConfigLoader;
use Rcalicdan\ConfigLoader\Config;

// Option 1: Get the singleton instance
$config = ConfigLoader::getInstance();
$dbHost = $config->get('database.connections.mysql.host');

// Option 2: Use the static facade for cleaner access
$appName = Config::get('app.name', 'Default App Name');
```

### Using Helper Functions

The library provides global helper functions for the most common use cases. This is the recommended way to interact with the library in most applications.

```php
use function Rcalicdan\ConfigLoader\config;
use function Rcalicdan\ConfigLoader\configRoot;
use function Rcalicdan\ConfigLoader\env;

// Access a configuration value
$dbHost = config('database.connections.mysql.host');

// Provide a default if the key doesn't exist
$appName = config('app.name', 'Default App Name');

// Manually load a file from the project root
$customSettings = configRoot('custom_settings.php');

// Access an environment variable directly
$debugMode = env('APP_DEBUG', false);
```

## Project Structure

ConfigLoader expects your project to follow this standard structure:

```
your-project/
├── vendor/              # Composer dependencies (used to detect the project root)
├── .env                 # Your environment variables
├── custom.php           # Custom config file in root (optional)
├── config/              # Standard Configuration directory
│   ├── app.php
│   ├── database.php
│   └── services/        # Nested directories are supported
│       └── mail.php
└── src/
    └── your-code.php
```

## Configuration Files

Create PHP files in the `/config` directory that return an array. You can use the `env()` helper to load values from your `.env` file.

#### `config/app.php`

```php
<?php

return [
    'name' => env('APP_NAME', 'My Application'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
];
```

## Environment Variables (.env)

Create a `.env` file in your project root. This file should **not** be committed to version control.

```env
APP_NAME="My Awesome App"
APP_ENV=local
APP_DEBUG=true
```

## API Reference

### `ConfigLoader` Class

The core singleton class that handles all loading and caching logic.

#### `getInstance(): self`

Gets the singleton instance of `ConfigLoader`.

#### `get(string $key, mixed $default = null): mixed`

Retrieves a configuration value by key. Supports dot notation for nested values.

#### `set(string $key, mixed $value): bool`

Sets a configuration value at runtime. The key must already exist. Returns `true` on success.

#### `setOrFail(string $key, mixed $value): void`

Sets a configuration value at runtime. Throws `ConfigKeyNotFoundException` if the key does not exist.

#### `loadFromRoot(string $filename, ?string $key = null, mixed $default = null): mixed`

Loads a specific configuration file from the project root directory.
*   **$filename**: The name of the file (e.g., `'my_settings'` or `'my_settings.php'`).
*   **$key**: Optional. The dot-notation key to store the data under. If `null`, the filename is used as the key.
*   **$default**: Value to return if the file is not found or is not an array.

#### `has(string $key): bool`

Checks if a configuration key exists using dot notation.

#### `all(): array`

Gets all loaded configuration values as a single array.

#### `getRootPath(): ?string`

Gets the auto-detected project root path.

#### `reset(): void`

Resets the singleton instance.

### `Config` Class (Static Facade)

A static helper class that provides convenient access to the `ConfigLoader` instance.

```php
use Rcalicdan\ConfigLoader\Config;

// Examples:
Config::get('app.name');
Config::loadFromRoot('extra_config'); // Loads extra_config.php from root
Config::set('app.debug', true);
Config::all();
```

### Helper Functions

#### `config(?string $key = null, mixed $default = null): mixed|ConfigLoader`

The most common way to access standard configuration.
```php
// Get a value
$value = config('app.name');

// Get the instance
$loader = config();
```

#### `configRoot(string $filename, ?string $key = null, mixed $default = null): mixed`

A helper function to load a configuration file from the project root (mirroring `loadFromRoot`).

```php
// Loads 'settings.php' from project root into the 'settings' key
$settings = configRoot('settings'); 

// Loads 'legacy.php' from root but stores it under the 'app.legacy' key
configRoot('legacy', 'app.legacy');
```

#### `env(string $key, mixed $default = null, bool $convertNumeric = false): mixed`

Gets an environment variable with automatic type conversion.

## Advanced Usage

### Nested Configuration Directories

The library automatically loads files from subdirectories within `/config` and prefixes them with the directory name.
For `config/services/mail.php`, access values via: `config('services.mail.host')`.

### Loading Files Manually (`loadFromRoot`)

While the library automatically scans the `config/` directory, you might want to load a file located in your project root or load a file strictly on demand.

**Example: Loading a `payment.php` file from the project root**

```php
// Project Root contains: payment.php returning ['stripe' => ['key' => '123']]

// 1. Using the Facade
Config::loadFromRoot('payment'); 
// Access it:
$key = Config::get('payment.stripe.key');

// 2. Using the Helper with a custom key
configRoot('payment', 'gateways');
// Access it:
$key = config('gateways.stripe.key');
```

**Note on Dot Notation in `loadFromRoot`:**
If you load a file using a dot-notation key (e.g., `configRoot('file', 'my.nested.key')`), the library is smart enough to handle array wrapping. If your file returns `['my' => ['nested' => ['key' => ...]]]` and you load it into `'my.nested.key'`, the library will "unwrap" the redundant nesting so you don't end up with `my.nested.key.my.nested.key`.

### Modifying Configuration at Runtime

You can override configuration values for a single request.

```php
// Temporarily switch to the sqlite database for a test
Config::set('database.default', 'sqlite');
```

## Exception Handling

The library throws specific exceptions for error handling:
*   `ProjectRootNotFoundException`
*   `ConfigKeyNotFoundException`
*   `ConfigException`

## Testing

Use `ConfigLoader::reset()` or `Config::reset()` in your test `tearDown()` to ensure test isolation.

```php
protected function tearDown(): void
{
    Config::reset();
}
```