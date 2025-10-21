# ConfigLoader

A lightweight, zero-configuration PHP library for managing environment variables and configuration files with automatic project root detection.

## Features

**Zero Configuration**: Automatically finds your project root
**Singleton Pattern**: Loads configuration only once per request
**Convention-based**: Works with standard `.env` and `/config` directory structure
**Dot Notation**: Access nested configuration values easily
**Helper Functions**: Simple global functions for quick access
**Performance Optimized**: Caches loaded configuration in memory

## Installation
```bash
composer require rcalicdan/config-loader
```

## Requirements

- PHP 8.2 or higher
- vlucas/phpdotenv package (automatically installed)

## Quick Start

### Basic Usage
```php
use Rcalicdan\ConfigLoader\ConfigLoader;

// Get the singleton instance
$config = ConfigLoader::getInstance();

// Access configuration
$dbHost = $config->get('database.host');
$appName = $config->get('app.name', 'Default App Name');
```

### Using Helper Functions
```php
use function Rcalicdan\ConfigLoader\config;
use function Rcalicdan\ConfigLoader\env;

// Access configuration
$dbHost = config('database.host');
$appName = config('app.name', 'Default App Name');

// Access environment variables
$debug = env('APP_DEBUG', false);
$apiKey = env('API_KEY');
```

## Project Structure

ConfigLoader expects your project to follow this structure:
```
your-project/
├── vendor/              # Composer dependencies (used to detect root)
├── .env                 # Environment variables
├── config/              # Configuration directory
│   ├── app.php
│   ├── database.php
│   └── services.php
└── src/
    └── your-code.php
```

## Configuration Files

Create PHP files in the `/config` directory that return arrays:

### config/app.php
```php
<?php

return [
    'name' => env('APP_NAME', 'My Application'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => 'UTC',
];
```

### config/database.php
```php
<?php

return [
    'default' => env('DB_CONNECTION', 'mysql'),
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', 3306),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
        ],
    ],
];
```

## API Reference

### ConfigLoader Class

#### `getInstance(): self`

Gets the singleton instance of ConfigLoader.
```php
$config = ConfigLoader::getInstance();
```

#### `get(string $key, mixed $default = null): mixed`

Retrieves a configuration value by key. Supports dot notation for nested values.
```php
// Get entire configuration file
$appConfig = $config->get('app');

// Get nested value
$dbHost = $config->get('database.connections.mysql.host');

// With default value
$timeout = $config->get('api.timeout', 30);
```

#### `has(string $key): bool`

Check if a configuration key exists.
```php
if ($config->has('database.connections.mysql')) {
    // MySQL configuration exists
}
```

#### `all(): array`

Get all configuration values.
```php
$allConfig = $config->all();
```

#### `getRootPath(): ?string`

Get the detected project root path.
```php
$rootPath = $config->getRootPath();
```

#### `reset(): void`

Resets the singleton instance (primarily for testing).
```php
ConfigLoader::reset();
```

### Helper Functions

#### `config(?string $key = null, mixed $default = null): mixed|ConfigLoader`

Access configuration values or get the ConfigLoader instance.
```php
// Get configuration value
$value = config('app.name');

// With default
$value = config('app.name', 'Default Name');

// Get ConfigLoader instance
$loader = config();
```

#### `env(string $key, mixed $default = null, bool $convertNumeric = false): mixed`

Get environment variable with automatic type conversion.
```php
// Basic usage
$debug = env('APP_DEBUG', false);

// With numeric conversion
$port = env('DB_PORT', 3306, true); // Returns int
```

**Automatic Type Conversion:**

| .env Value | Converted To |
|------------|--------------|
| `true`, `(true)` | `true` (boolean) |
| `false`, `(false)` | `false` (boolean) |
| `null`, `(null)` | `null` |
| `empty`, `(empty)` | `''` (empty string) |
| Numeric (with `$convertNumeric = true`) | `int` or `float` |

## Environment Variables

Create a `.env` file in your project root:
```env
APP_NAME="My Application"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=myapp
DB_USERNAME=root
DB_PASSWORD=secret
```

## Advanced Usage

### Dot Notation Examples
```php
// Simple key
$appName = config('app.name');

// Deeply nested
$charset = config('database.connections.mysql.charset');

// Array access
$connections = config('database.connections');
$mysqlConfig = $connections['mysql'];
```

### Working with Arrays
```php
// Get entire configuration file as array
$databaseConfig = config('database');

// Check if nested key exists
if (config()->has('database.connections.pgsql')) {
    $pgsqlHost = config('database.connections.pgsql.host');
}

// Get all configuration
$allConfig = config()->all();
```

### Using in Classes
```php
namespace App\Services;

use function Rcalicdan\ConfigLoader\config;

class DatabaseService
{
    private string $host;
    private int $port;
    
    public function __construct()
    {
        $this->host = config('database.connections.mysql.host');
        $this->port = config('database.connections.mysql.port', 3306);
    }
}
```

## Exception Handling

ConfigLoader throws specific exceptions for error cases:
```php
use Rcalicdan\ConfigLoader\Exceptions\ProjectRootNotFoundException;
use Rcalicdan\ConfigLoader\Exceptions\EnvFileNotFoundException;
use Rcalicdan\ConfigLoader\Exceptions\EnvFileLoadException;

try {
    $config = ConfigLoader::getInstance();
} catch (ProjectRootNotFoundException $e) {
    // Project root with vendor directory not found
} catch (EnvFileNotFoundException $e) {
    // .env file not found
} catch (EnvFileLoadException $e) {
    // Error loading .env file
}
```

## Testing

### Resetting the Singleton
```php
use Rcalicdan\ConfigLoader\ConfigLoader;

class MyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ConfigLoader::reset(); // Reset before each test
    }
    
    public function testConfiguration(): void
    {
        $config = ConfigLoader::getInstance();
        $this->assertNotNull($config->getRootPath());
    }
}
```

## Best Practices

1. **Use Helper Functions**: The `config()` and `env()` functions provide cleaner syntax
2. **Provide Defaults**: Always provide sensible defaults for configuration values
3. **Environment-Specific Values**: Store sensitive data in `.env`, structure in config files
4. **Configuration Files**: Keep configuration files simple and return arrays
5. **Don't Mix Concerns**: Use `.env` for environment-specific values, config files for structure

## How It Works

1. **Auto-Detection**: ConfigLoader searches upward from its location to find the `vendor` directory (up to 10 levels)
2. **Load .env**: Uses vlucas/phpdotenv to load environment variables
3. **Load Config Files**: Reads all `.php` files from `/config` directory
4. **Cache**: Results are cached in memory for the request lifetime
5. **Singleton**: Ensures expensive operations happen only once

## Troubleshooting

### "Project root not found"

Ensure your project has a `vendor` directory from Composer. The library searches up to 10 directory levels.

### ".env file not found"

Create a `.env` file in your project root (same level as `vendor` directory).

### Configuration not loading

- Verify config files are in `/config` directory relative to project root
- Ensure config files have `.php` extension
- Check that config files return arrays

### Values not updating

Remember that ConfigLoader uses a singleton pattern. Configuration is loaded once per request. For testing, use `ConfigLoader::reset()`.