<?php

declare(strict_types=1);

use Rcalicdan\ConfigLoader\ConfigLoader;

describe('ConfigLoader', function () {

    beforeEach(function () {
        ConfigLoader::reset();

        $configDir = getcwd() . '/config';
        if (is_dir($configDir)) {
            $testFiles = glob($configDir . '/test_*.php');
            foreach ($testFiles as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    });

    afterEach(function () {
        $configDir = getcwd() . '/config';
        if (is_dir($configDir)) {
            $testFiles = glob($configDir . '/test_*.php');
            foreach ($testFiles as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    });

    describe('Singleton Pattern', function () {

        it('returns the same instance', function () {
            $instance1 = ConfigLoader::getInstance();
            $instance2 = ConfigLoader::getInstance();

            expect($instance1)->toBe($instance2);
        });

        it('can be reset', function () {
            $instance1 = ConfigLoader::getInstance();
            ConfigLoader::reset();
            $instance2 = ConfigLoader::getInstance();

            expect($instance1)->not->toBe($instance2);
        });
    });

    describe('Configuration Loading', function () {

        it('loads configuration files from config directory', function () {
            $config = ConfigLoader::getInstance();
            expect($config)->toBeConfigLoader();
        });

        it('returns null for non-existent keys', function () {
            $config = ConfigLoader::getInstance();

            expect($config->get('non_existent_key'))->toBeNull();
        });

        it('returns default value for non-existent keys', function () {
            $config = ConfigLoader::getInstance();

            expect($config->get('non_existent_key', 'default'))->toBe('default');
        });
    });

    describe('Dot Notation', function () {

        it('retrieves nested values using dot notation', function () {
            $configDir = getcwd() . '/config';
            if (! is_dir($configDir)) {
                mkdir($configDir, 0777, true);
            }

            file_put_contents($configDir . '/test_database.php', '<?php return ' . var_export([
                'default' => 'mysql',
                'connections' => [
                    'mysql' => [
                        'host' => 'localhost',
                        'port' => 3306,
                        'database' => 'testdb',
                        'username' => 'root',
                    ],
                    'pgsql' => [
                        'host' => 'postgres',
                        'port' => 5432,
                    ],
                ],
            ], true) . ';');

            file_put_contents($configDir . '/test_app.php', '<?php return ' . var_export([
                'name' => 'Test App',
                'env' => 'testing',
            ], true) . ';');

            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            expect($config->get('test_database'))->toBeArray()
                ->and($config->get('test_app'))->toBeArray()
            ;

            expect($config->get('test_database.default'))->toBe('mysql')
                ->and($config->get('test_app.name'))->toBe('Test App')
                ->and($config->get('test_app.env'))->toBe('testing')
            ;

            expect($config->get('test_database.connections.mysql.host'))->toBe('localhost')
                ->and($config->get('test_database.connections.mysql.port'))->toBe(3306)
                ->and($config->get('test_database.connections.mysql.database'))->toBe('testdb')
                ->and($config->get('test_database.connections.mysql.username'))->toBe('root')
            ;

            expect($config->get('test_database.connections.pgsql.host'))->toBe('postgres')
                ->and($config->get('test_database.connections.pgsql.port'))->toBe(5432)
            ;

            expect($config->get('test_database.connections.sqlite'))->toBeNull()
                ->and($config->get('test_database.connections.mysql.password'))->toBeNull()
                ->and($config->get('nonexistent.key.path'))->toBeNull()
            ;

            expect($config->get('test_database.connections.mysql.charset', 'utf8mb4'))->toBe('utf8mb4')
                ->and($config->get('test_database.timeout', 30))->toBe(30)
            ;
        });

        it('returns default for non-existent nested keys', function () {
            $configDir = getcwd() . '/config';
            if (! is_dir($configDir)) {
                mkdir($configDir, 0777, true);
            }

            file_put_contents($configDir . '/test_sample.php', '<?php return ' . var_export(['name' => 'Test'], true) . ';');

            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            expect($config->get('test_database.non.existent.key', 'default'))->toBe('default')
                ->and($config->get('completely.made.up.path', 'fallback'))->toBe('fallback')
                ->and($config->get('a.b.c.d.e.f', null))->toBeNull()
            ;
        });

        it('handles deeply nested structures', function () {
            $configDir = getcwd() . '/config';
            if (! is_dir($configDir)) {
                mkdir($configDir, 0777, true);
            }

            file_put_contents($configDir . '/test_deeply.php', '<?php return ' . var_export([
                'nested' => [
                    'structure' => [
                        'with' => [
                            'many' => [
                                'levels' => 'deep_value',
                            ],
                        ],
                    ],
                ],
            ], true) . ';');

            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            expect($config->get('test_deeply.nested.structure.with.many.levels'))->toBe('deep_value')
                ->and($config->get('test_deeply.nested.structure.with.many'))->toBeArray()
                ->and($config->get('test_deeply.nested.structure.with.many.nonexistent', 'fallback'))->toBe('fallback')
            ;
        });

        it('handles array values in dot notation', function () {
            $configDir = getcwd() . '/config';
            if (! is_dir($configDir)) {
                mkdir($configDir, 0777, true);
            }

            file_put_contents($configDir . '/test_services.php', '<?php return ' . var_export([
                'mail' => [
                    'providers' => ['smtp', 'sendmail', 'mailgun'],
                    'default' => 'smtp',
                ],
            ], true) . ';');

            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            $providers = $config->get('test_services.mail.providers');

            expect($providers)->toBeArray()
                ->and($providers)->toHaveCount(3)
                ->and($providers)->toContain('smtp', 'sendmail', 'mailgun')
                ->and($config->get('test_services.mail.default'))->toBe('smtp')
            ;
        });

        it('prioritizes exact key matches over dot notation', function () {
            $configDir = getcwd() . '/config';
            if (! is_dir($configDir)) {
                mkdir($configDir, 0777, true);
            }

            file_put_contents($configDir . '/test_database.connection.php', '<?php return "exact_match_value";');
            file_put_contents($configDir . '/test_database.php', '<?php return ' . var_export([
                'connection' => 'nested_value',
            ], true) . ';');

            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            expect($config->get('test_database.connection'))->toBe('exact_match_value');
            expect($config->get('test_database'))->toBeArray()
                ->and($config->get('test_database')['connection'])->toBe('nested_value')
            ;
        });

        it('handles numeric keys in dot notation', function () {
            $configDir = getcwd() . '/config';
            if (! is_dir($configDir)) {
                mkdir($configDir, 0777, true);
            }

            file_put_contents($configDir . '/test_items.php', '<?php return ' . var_export([
                0 => 'first',
                1 => 'second',
                2 => ['nested' => 'value'],
            ], true) . ';');

            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            expect($config->get('test_items.0'))->toBe('first')
                ->and($config->get('test_items.1'))->toBe('second')
                ->and($config->get('test_items.2.nested'))->toBe('value')
            ;
        });

        it('returns null for intermediate non-array values', function () {
            $configDir = getcwd() . '/config';
            if (! is_dir($configDir)) {
                mkdir($configDir, 0777, true);
            }

            file_put_contents($configDir . '/test_config.php', '<?php return ' . var_export([
                'value' => 'string_value',
            ], true) . ';');

            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            expect($config->get('test_config.value.something'))->toBeNull()
                ->and($config->get('test_config.value.something.deep', 'default'))->toBe('default')
            ;
        });
    });

    describe('Helper Methods', function () {

        it('checks if a key exists', function () {
            $configDir = getcwd() . '/config';
            if (! is_dir($configDir)) {
                mkdir($configDir, 0777, true);
            }

            file_put_contents($configDir . '/test_app.php', '<?php return ' . var_export(['name' => 'Test'], true) . ';');

            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            expect($config->has('test_app'))->toBeTrue()
                ->and($config->has('non_existent_key'))->toBeFalse()
            ;
        });

        it('returns all configuration', function () {
            $configDir = getcwd() . '/config';
            if (! is_dir($configDir)) {
                mkdir($configDir, 0777, true);
            }

            file_put_contents($configDir . '/test_app.php', '<?php return ' . var_export(['name' => 'Test'], true) . ';');

            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            $all = $config->all();

            expect($all)->toBeArray()
                ->and($all)->toHaveKey('test_app')
            ;
        });

        it('returns project root path', function () {
            $config = ConfigLoader::getInstance();
            $rootPath = $config->getRootPath();

            expect($rootPath)->toBeString()
                ->and($rootPath)->toContain('ConfigLoader')
            ;
        });
    });

    describe('Environment Variables', function () {

        it('loads environment variables from .env file', function () {
            $config = ConfigLoader::getInstance();

            expect($config)->toBeConfigLoader();
        });
    });

    describe('Error Handling', function () {

        it('handles missing config directory gracefully', function () {
            $config = ConfigLoader::getInstance();

            expect($config->all())->toBeArray();
        });
    });
});
