# ConfigLoader

A lightweight, zero-configuration PHP library for managing environment variables and configuration files with automatic project root detection.

## Features

*   **Zero Configuration**: Automatically finds your project root without any setup.
*   **Singleton Pattern**: Loads configuration only once per request, ensuring high performance.
*   **Convention-based**: Works out-of-the-box with a standard `.env` file and `/config` directory structure.
*   **Dot Notation**: Access nested configuration values easily (e.g., `database.connections.mysql.host`).
*   **Runtime Configuration**: Modify configuration values on the fly for testing or dynamic adjustments.
*   **Static Facade**: A convenient static wrapper (`Config::class`) for easy access anywhere in your code.
*   **Helper Functions**: Simple global `config()` and `env()` functions for quick access.
*   **Performance Optimized**: All loaded configuration is cached in memory for the duration of the request.

## Installation

```bash
composer require rcalicdan/config-loader
```

## Requirements

*   PHP 8.2 or higher

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
use function Rcalicdan\ConfigLoader\env;

// Access a configuration value
$dbHost = config('database.connections.mysql.host');

// Provide a default if the key doesn't exist
$appName = config('app.name', 'Default App Name');

// Access an environment variable directly
$debugMode = env('APP_DEBUG', false);
$apiKey = env('API_KEY');
```

## Project Structure

ConfigLoader expects your project to follow this standard structure:

```
your-project/
├── vendor/              # Composer dependencies (used to detect the project root)
├── .env                 # Your environment variables
├── config/              # Configuration directory
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

#### `config/database.php`

```php
<?php

return [
    'default' => env('DB_CONNECTION', 'mysql'),
    'connections' => [
        'mysql' => [
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', 3306),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
        ],
    ],
];
```

## Environment Variables (.env)

Create a `.env` file in your project root. This file should **not** be committed to version control.

```env
APP_NAME="My Awesome App"
APP_ENV=local
APP_DEBUG=true

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=my_app
DB_USERNAME=root
```

## API Reference

### `ConfigLoader` Class

The core singleton class that handles all loading and caching logic.

#### `getInstance(): self`

Gets the singleton instance of `ConfigLoader`.
```php
$config = ConfigLoader::getInstance();
```

#### `get(string $key, mixed $default = null): mixed`

Retrieves a configuration value by key. Supports dot notation for nested values.
#### `get(string $key): mixed`

Gets a configuration value. Returns the value if the key exists, `null` otherwise.
```php
$dbHost = $config->get('database.connections.mysql.host');
```

#### `set(string $key, mixed $value): bool`

Sets a configuration value at runtime. The key must already exist. Returns `true` on success, `false` on failure.
```php
// Overrides a value for the current request
$config->set('app.env', 'testing');
```

#### `setOrFail(string $key, mixed $value): void`

Sets a configuration value at runtime. Throws `ConfigKeyNotFoundException` if the key does not exist.
```php
$config->setOrFail('app.name', 'New App Name');
```

#### `has(string $key): bool`

Checks if a configuration key exists using dot notation.
```php
if ($config->has('database.connections.mysql')) {
    // Logic here
}
```

#### `all(): array`

Gets all loaded configuration values as a single array.
```php
$allConfig = $config->all();
```

#### `getRootPath(): ?string`

Gets the auto-detected project root path.
```php
$rootPath = $config->getRootPath();
```

#### `reset(): void`

Resets the singleton instance. This is primarily useful for testing purposes.
```php
ConfigLoader::reset();
```

### `Config` Class (Static Facade)

A static helper class that provides convenient access to the `ConfigLoader` instance. All methods on this class are static and correspond directly to the instance methods on `ConfigLoader`.

```php
use Rcalicdan\ConfigLoader\Config;

// Examples:
Config::get('app.name');
Config::set('app.debug', true);
Config::has('database.default');
Config::all();
Config::getRootPath();
Config::reset();
```

### Helper Functions

#### `config(?string $key = null, mixed $default = null): mixed|ConfigLoader`

The most common way to access configuration.
```php
// Get a configuration value
$value = config('app.name');

// Get a value with a default
$value = config('app.timezone', 'UTC');

// Get the entire ConfigLoader instance
$loader = config();
$loader->set('app.name', 'New Name');
```

#### `env(string $key, mixed $default = null, bool $convertNumeric = false): mixed`

Gets an environment variable with automatic type conversion for booleans, null, and empty strings.

```php
// Basic usage
$debug = env('APP_DEBUG', false); // Returns a boolean

// With numeric conversion
$port = env('DB_PORT', 3306, true); // Returns an integer or float
```

**`env()` Automatic Type Conversion:**

| .env Value        | Converted To           |
| ----------------- | ---------------------- |
| `true`, `(true)`  | `true` (boolean)       |
| `false`, `(false)`| `false` (boolean)      |
| `null`, `(null)`  | `null`                 |
| `empty`, `(empty)`| `''` (empty string)    |
| Numeric string (with `$convertNumeric = true`) | `int` or `float` |

## Advanced Usage

### Nested Configuration Directories

The library automatically loads files from subdirectories within `/config` and prefixes them with the directory name.

For a file located at `config/services/mail.php`:
```php
// config/services/mail.php
<?php
return ['host' => env('MAIL_HOST', 'smtp.mailtrap.io')];
```

You can access its values using dot notation:
```php
$mailHost = config('services.mail.host');
```

### Modifying Configuration at Runtime

You can override configuration values for a single request. This is especially useful in testing environments or for dynamically adjusting behavior.

```php
// Temporarily switch to the sqlite database for a test
config()->set('database.default', 'sqlite');

// Or use the static facade
Config::set('database.connections.mysql.host', '127.0.0.1');

// This is useful in a testing bootstrap file:
if (env('APP_ENV') === 'testing') {
    Config::set('app.debug', true);
    Config::set('database.default', 'testing_db');
}
```

## Exception Handling

The library throws specific, typed exceptions to make error handling clear.

```php
use Rcalicdan\ConfigLoader\ConfigLoader;
use Rcalicdan\ConfigLoader\Exceptions\ProjectRootNotFoundException;
use Rcalicdan\ConfigLoader\Exceptions\EnvFileNotFoundException;
use Rcalicdan\ConfigLoader\Exceptions\EnvFileLoadException;
use Rcalicdan\ConfigLoader\Exceptions\ConfigKeyNotFoundException;
use Rcalicdan\ConfigLoader\Exceptions\ConfigException;

try {
    $config = ConfigLoader::getInstance();
    $config->setOrFail('non.existent.key', 'value');
} catch (ProjectRootNotFoundException $e) {
    // Project root with a 'vendor' directory was not found.
} catch (EnvFileNotFoundException $e) {
    // The .env file does not exist in the project root.
} catch (EnvFileLoadException $e) {
    // There was an error parsing the .env file.
} catch (ConfigKeyNotFoundException $e) {
    // Thrown by setOrFail() when the key does not exist.
} catch (ConfigException $e) {
    // A general configuration error occurred, e.g., trying to set a nested
    // value on a key that holds a string instead of an array.
}
```

## Testing

When writing unit or integration tests, it's crucial to reset the `ConfigLoader`'s state between tests to ensure they are isolated. Use the `reset()` method in your test suite's `setUp()` or `tearDown()` method.

```php
use PHPUnit\Framework\TestCase;
use Rcalicdan\ConfigLoader\ConfigLoader;
use Rcalicdan\ConfigLoader\Config;

class MyTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        ConfigLoader::reset(); // Or Config::reset();
    }

    public function test_config_can_be_mocked_for_testing(): void
    {
        // First, ensure the instance is loaded
        $config = Config::get('app.name');

        // Now, modify it for this specific test
        Config::set('app.env', 'testing');
        $this->assertSame('testing', Config::get('app.env'));
    }
}
```

## How It Works

1.  **Project Root Detection**: On first instantiation, `ConfigLoader` searches upwards from its own file location to find a directory containing a `vendor` folder. It caches this path.
2.  **Load .env File**: It uses `vlucas/phpdotenv` to parse the `.env` file from the project root and load the variables into the environment.
3.  **Load Config Files**: It recursively scans the `/config` directory, loading every `.php` file. The file's path and name are converted into a configuration key (e.g., `services/mail.php` becomes `services.mail`).
4.  **Cache Results**: The final merged configuration array is stored in a private property of the singleton instance, ensuring all file I/O operations happen only once per request.