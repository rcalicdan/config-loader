<?php

declare(strict_types=1);

use Rcalicdan\ConfigLoader\Config;
use Rcalicdan\ConfigLoader\ConfigLoader;
use Rcalicdan\ConfigLoader\Exceptions\ConfigKeyNotFoundException;

describe('ConfigLoader', function () {

    beforeEach(function () {
        ConfigLoader::reset();

        $configDir = getcwd() . '/config';

        if (! is_dir($configDir)) {
            return;
        }

        $testFiles = glob($configDir . '/test_*.php');
        foreach ($testFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    });

    afterEach(function () {
        $configDir = getcwd() . '/config';

        if (! is_dir($configDir)) {
            return;
        }

        $testFiles = glob($configDir . '/test_*.php');
        foreach ($testFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
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
        it('handles nested file structures with dot notation', function () {
            $configDir = getcwd() . '/config';
            if (! is_dir($configDir)) {
                mkdir($configDir, 0777, true);
            }

            $servicesDir = $configDir . '/test_services';
            $mailDir = $servicesDir . '/test_mail';

            if (! is_dir($servicesDir)) {
                mkdir($servicesDir, 0777, true);
            }
            if (! is_dir($mailDir)) {
                mkdir($mailDir, 0777, true);
            }

            file_put_contents($mailDir . '/smtp.php', '<?php return ' . var_export([
                'host' => 'smtp.example.com',
                'port' => 587,
                'encryption' => 'tls',
                'auth' => [
                    'username' => 'user@example.com',
                    'password' => 'secret',
                ],
            ], true) . ';');

            file_put_contents($mailDir . '/mailgun.php', '<?php return ' . var_export([
                'domain' => 'example.com',
                'secret' => 'key-123456',
                'endpoint' => 'api.mailgun.net',
            ], true) . ';');

            file_put_contents($servicesDir . '/cache.php', '<?php return ' . var_export([
                'default' => 'redis',
                'stores' => [
                    'redis' => ['host' => '127.0.0.1'],
                    'memcached' => ['host' => 'localhost'],
                ],
            ], true) . ';');

            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            expect($config->has('test_services.test_mail.smtp'))->toBeTrue()
                ->and($config->has('test_services.test_mail.mailgun'))->toBeTrue()
                ->and($config->has('test_services.cache'))->toBeTrue()
                ->and($config->has('test_services.test_mail.sendgrid'))->toBeFalse()
                ->and($config->has('test_services.test_mail'))->toBeFalse() // directory, not a file
                ->and($config->has('test_services.nonexistent'))->toBeFalse()
            ;

            expect($config->has('test_services.test_mail.smtp.host'))->toBeTrue()
                ->and($config->has('test_services.test_mail.smtp.port'))->toBeTrue()
                ->and($config->has('test_services.test_mail.smtp.auth'))->toBeTrue()
                ->and($config->has('test_services.test_mail.smtp.auth.username'))->toBeTrue()
                ->and($config->has('test_services.test_mail.smtp.auth.password'))->toBeTrue()
                ->and($config->has('test_services.test_mail.smtp.timeout'))->toBeFalse()
                ->and($config->has('test_services.test_mail.smtp.auth.token'))->toBeFalse()
            ;

            expect($config->has('test_services.test_mail.mailgun.domain'))->toBeTrue()
                ->and($config->has('test_services.test_mail.mailgun.secret'))->toBeTrue()
                ->and($config->has('test_services.test_mail.mailgun.endpoint'))->toBeTrue()
                ->and($config->has('test_services.test_mail.mailgun.region'))->toBeFalse()
            ;

            expect($config->has('test_services.cache.default'))->toBeTrue()
                ->and($config->has('test_services.cache.stores'))->toBeTrue()
                ->and($config->has('test_services.cache.stores.redis'))->toBeTrue()
                ->and($config->has('test_services.cache.stores.redis.host'))->toBeTrue()
                ->and($config->has('test_services.cache.stores.memcached.host'))->toBeTrue()
                ->and($config->has('test_services.cache.stores.file'))->toBeFalse()
            ;

            expect($config->get('test_services.test_mail.smtp'))->toBeArray()
                ->and($config->get('test_services.test_mail.mailgun'))->toBeArray()
                ->and($config->get('test_services.cache'))->toBeArray()
            ;

            expect($config->get('test_services.test_mail.smtp.host'))->toBe('smtp.example.com')
                ->and($config->get('test_services.test_mail.smtp.port'))->toBe(587)
                ->and($config->get('test_services.test_mail.smtp.encryption'))->toBe('tls')
                ->and($config->get('test_services.test_mail.smtp.auth.username'))->toBe('user@example.com')
                ->and($config->get('test_services.test_mail.smtp.auth.password'))->toBe('secret')
            ;

            expect($config->get('test_services.test_mail.mailgun.domain'))->toBe('example.com')
                ->and($config->get('test_services.test_mail.mailgun.secret'))->toBe('key-123456')
                ->and($config->get('test_services.test_mail.mailgun.endpoint'))->toBe('api.mailgun.net')
            ;

            expect($config->get('test_services.cache.default'))->toBe('redis')
                ->and($config->get('test_services.cache.stores.redis.host'))->toBe('127.0.0.1')
                ->and($config->get('test_services.cache.stores.memcached.host'))->toBe('localhost')
            ;

            expect($config->get('test_services.test_mail.smtp.timeout', 30))->toBe(30)
                ->and($config->get('test_services.test_mail.sendgrid.api_key', 'default'))->toBe('default')
                ->and($config->get('test_services.cache.stores.file.path', '/tmp'))->toBe('/tmp')
            ;

            expect($config->get('test_services.test_mail.smtp.timeout'))->toBeNull()
                ->and($config->get('test_services.test_mail.sendgrid'))->toBeNull()
                ->and($config->get('test_services.cache.stores.file'))->toBeNull()
            ;

            if (file_exists($mailDir . '/smtp.php')) {
                unlink($mailDir . '/smtp.php');
            }
            if (file_exists($mailDir . '/mailgun.php')) {
                unlink($mailDir . '/mailgun.php');
            }
            if (file_exists($servicesDir . '/cache.php')) {
                unlink($servicesDir . '/cache.php');
            }
            if (is_dir($mailDir)) {
                rmdir($mailDir);
            }
            if (is_dir($servicesDir)) {
                rmdir($servicesDir);
            }
        });

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

    describe('Configuration Mutation', function () {

        beforeEach(function () {
            ConfigLoader::reset();

            $configDir = getcwd() . '/config';

            if (! is_dir($configDir)) {
                return;
            }

            $testFiles = glob($configDir . '/test_*.php');
            foreach ($testFiles as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        });

        afterEach(function () {
            $configDir = getcwd() . '/config';

            if (! is_dir($configDir)) {
                return;
            }

            $testFiles = glob($configDir . '/test_*.php');
            foreach ($testFiles as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        });

        describe('set() method', function () {

            it('sets a simple configuration value', function () {
                $configDir = getcwd() . '/config';
                if (! is_dir($configDir)) {
                    mkdir($configDir, 0777, true);
                }

                file_put_contents($configDir . '/test_app.php', '<?php return ' . var_export([
                    'name' => 'Original Name',
                    'env' => 'testing',
                ], true) . ';');

                ConfigLoader::reset();
                $config = ConfigLoader::getInstance();

                expect($config->get('test_app.name'))->toBe('Original Name');

                $result = $config->set('test_app.name', 'New Name');

                expect($result)->toBeTrue()
                    ->and($config->get('test_app.name'))->toBe('New Name')
                    ->and($config->get('test_app.env'))->toBe('testing')
                ;
            });

            it('sets a nested configuration value using dot notation', function () {
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
                        ],
                    ],
                ], true) . ';');

                ConfigLoader::reset();
                $config = ConfigLoader::getInstance();

                expect($config->get('test_database.connections.mysql.host'))->toBe('localhost');

                $result = $config->set('test_database.connections.mysql.host', '127.0.0.1');

                expect($result)->toBeTrue()
                    ->and($config->get('test_database.connections.mysql.host'))->toBe('127.0.0.1')
                    ->and($config->get('test_database.connections.mysql.port'))->toBe(3306)
                ;
            });

            it('sets deeply nested configuration values', function () {
                $configDir = getcwd() . '/config';
                if (! is_dir($configDir)) {
                    mkdir($configDir, 0777, true);
                }

                file_put_contents($configDir . '/test_services.php', '<?php return ' . var_export([
                    'cache' => [
                        'stores' => [
                            'redis' => [
                                'host' => 'localhost',
                                'port' => 6379,
                            ],
                        ],
                    ],
                ], true) . ';');

                ConfigLoader::reset();
                $config = ConfigLoader::getInstance();

                $result = $config->set('test_services.cache.stores.redis.port', 6380);

                expect($result)->toBeTrue()
                    ->and($config->get('test_services.cache.stores.redis.port'))->toBe(6380)
                    ->and($config->get('test_services.cache.stores.redis.host'))->toBe('localhost')
                ;
            });

            it('sets entire array values', function () {
                $configDir = getcwd() . '/config';
                if (! is_dir($configDir)) {
                    mkdir($configDir, 0777, true);
                }

                file_put_contents($configDir . '/test_mail.php', '<?php return ' . var_export([
                    'driver' => 'smtp',
                    'from' => [
                        'address' => 'test@example.com',
                        'name' => 'Test',
                    ],
                ], true) . ';');

                ConfigLoader::reset();
                $config = ConfigLoader::getInstance();

                $newFrom = [
                    'address' => 'new@example.com',
                    'name' => 'New Sender',
                ];

                $result = $config->set('test_mail.from', $newFrom);

                expect($result)->toBeTrue()
                    ->and($config->get('test_mail.from'))->toBe($newFrom)
                    ->and($config->get('test_mail.from.address'))->toBe('new@example.com')
                    ->and($config->get('test_mail.from.name'))->toBe('New Sender')
                ;
            });

            it('returns false when setting non-existent key', function () {
                $configDir = getcwd() . '/config';
                if (! is_dir($configDir)) {
                    mkdir($configDir, 0777, true);
                }

                file_put_contents($configDir . '/test_app.php', '<?php return ' . var_export([
                    'name' => 'Test',
                ], true) . ';');

                ConfigLoader::reset();
                $config = ConfigLoader::getInstance();

                $result = $config->set('test_app.nonexistent', 'value');

                expect($result)->toBeFalse()
                    ->and($config->get('test_app.nonexistent'))->toBeNull()
                ;
            });

            it('returns false when setting deeply nested non-existent key', function () {
                $configDir = getcwd() . '/config';
                if (! is_dir($configDir)) {
                    mkdir($configDir, 0777, true);
                }

                file_put_contents($configDir . '/test_database.php', '<?php return ' . var_export([
                    'connections' => [
                        'mysql' => [
                            'host' => 'localhost',
                        ],
                    ],
                ], true) . ';');

                ConfigLoader::reset();
                $config = ConfigLoader::getInstance();

                $result = $config->set('test_database.connections.mysql.nonexistent.deep', 'value');

                expect($result)->toBeFalse();
            });

            it('returns false when setting key for non-existent file', function () {
                ConfigLoader::reset();
                $config = ConfigLoader::getInstance();

                $result = $config->set('nonexistent_file.key', 'value');

                expect($result)->toBeFalse();
            });

            it('sets configuration value for nested file structures', function () {
                $configDir = getcwd() . '/config';
                if (! is_dir($configDir)) {
                    mkdir($configDir, 0777, true);
                }

                $servicesDir = $configDir . '/test_services';
                $mailDir = $servicesDir . '/test_mail';

                if (! is_dir($servicesDir)) {
                    mkdir($servicesDir, 0777, true);
                }
                if (! is_dir($mailDir)) {
                    mkdir($mailDir, 0777, true);
                }

                file_put_contents($mailDir . '/smtp.php', '<?php return ' . var_export([
                    'host' => 'smtp.example.com',
                    'port' => 587,
                ], true) . ';');

                ConfigLoader::reset();
                $config = ConfigLoader::getInstance();

                $result = $config->set('test_services.test_mail.smtp.port', 465);

                expect($result)->toBeTrue()
                    ->and($config->get('test_services.test_mail.smtp.port'))->toBe(465)
                    ->and($config->get('test_services.test_mail.smtp.host'))->toBe('smtp.example.com')
                ;

                // Cleanup
                if (file_exists($mailDir . '/smtp.php')) {
                    unlink($mailDir . '/smtp.php');
                }
                if (is_dir($mailDir)) {
                    rmdir($mailDir);
                }
                if (is_dir($servicesDir)) {
                    rmdir($servicesDir);
                }
            });

            it('handles setting values with different types', function () {
                $configDir = getcwd() . '/config';
                if (! is_dir($configDir)) {
                    mkdir($configDir, 0777, true);
                }

                file_put_contents($configDir . '/test_types.php', '<?php return ' . var_export([
                    'string' => 'text',
                    'integer' => 100,
                    'boolean' => true,
                    'array' => ['a', 'b'],
                    'null' => null,
                ], true) . ';');

                ConfigLoader::reset();
                $config = ConfigLoader::getInstance();

                expect($config->set('test_types.string', 'new text'))->toBeTrue()
                    ->and($config->get('test_types.string'))->toBe('new text')
                ;

                expect($config->set('test_types.integer', 200))->toBeTrue()
                    ->and($config->get('test_types.integer'))->toBe(200)
                ;

                expect($config->set('test_types.boolean', false))->toBeTrue()
                    ->and($config->get('test_types.boolean'))->toBeFalse()
                ;

                expect($config->set('test_types.array', ['x', 'y', 'z']))->toBeTrue()
                    ->and($config->get('test_types.array'))->toBe(['x', 'y', 'z'])
                ;

                expect($config->set('test_types.null', 'not null anymore'))->toBeTrue()
                    ->and($config->get('test_types.null'))->toBe('not null anymore')
                ;
            });
        });

        describe('setOrFail() method', function () {

            it('sets configuration value when key exists', function () {
                $configDir = getcwd() . '/config';
                if (! is_dir($configDir)) {
                    mkdir($configDir, 0777, true);
                }

                file_put_contents($configDir . '/test_app.php', '<?php return ' . var_export([
                    'name' => 'Original Name',
                ], true) . ';');

                ConfigLoader::reset();
                $config = ConfigLoader::getInstance();

                $config->setOrFail('test_app.name', 'New Name');

                expect($config->get('test_app.name'))->toBe('New Name');
            });

            it('sets nested configuration value when key exists', function () {
                $configDir = getcwd() . '/config';
                if (! is_dir($configDir)) {
                    mkdir($configDir, 0777, true);
                }

                file_put_contents($configDir . '/test_database.php', '<?php return ' . var_export([
                    'connections' => [
                        'mysql' => [
                            'host' => 'localhost',
                        ],
                    ],
                ], true) . ';');

                ConfigLoader::reset();
                $config = ConfigLoader::getInstance();

                $config->setOrFail('test_database.connections.mysql.host', '127.0.0.1');

                expect($config->get('test_database.connections.mysql.host'))->toBe('127.0.0.1');
            });

            it('throws exception when key does not exist', function () {
                $configDir = getcwd() . '/config';
                if (! is_dir($configDir)) {
                    mkdir($configDir, 0777, true);
                }

                file_put_contents($configDir . '/test_app.php', '<?php return ' . var_export([
                    'name' => 'Test',
                ], true) . ';');

                ConfigLoader::reset();
                $config = ConfigLoader::getInstance();

                expect(fn () => $config->setOrFail('test_app.nonexistent', 'value'))
                    ->toThrow(ConfigKeyNotFoundException::class)
                ;
            });

            it('throws exception with correct message', function () {
                $configDir = getcwd() . '/config';
                if (! is_dir($configDir)) {
                    mkdir($configDir, 0777, true);
                }

                file_put_contents($configDir . '/test_app.php', '<?php return ' . var_export([
                    'name' => 'Test',
                ], true) . ';');

                ConfigLoader::reset();
                $config = ConfigLoader::getInstance();

                try {
                    $config->setOrFail('test_app.missing.key', 'value');
                    expect(true)->toBeFalse();
                } catch (ConfigKeyNotFoundException $e) {
                    expect($e->getMessage())->toContain('test_app.missing.key')
                        ->and($e->getMessage())->toContain('does not exist')
                    ;
                }
            });

            it('throws exception for non-existent file', function () {
                ConfigLoader::reset();
                $config = ConfigLoader::getInstance();

                expect(fn () => $config->setOrFail('nonexistent_file.key', 'value'))
                    ->toThrow(ConfigKeyNotFoundException::class)
                ;
            });

            it('throws exception for deeply nested non-existent key', function () {
                $configDir = getcwd() . '/config';
                if (! is_dir($configDir)) {
                    mkdir($configDir, 0777, true);
                }

                file_put_contents($configDir . '/test_database.php', '<?php return ' . var_export([
                    'connections' => [
                        'mysql' => [
                            'host' => 'localhost',
                        ],
                    ],
                ], true) . ';');

                ConfigLoader::reset();
                $config = ConfigLoader::getInstance();

                expect(fn () => $config->setOrFail('test_database.connections.pgsql.host', 'postgres'))
                    ->toThrow(ConfigKeyNotFoundException::class)
                ;
            });
        });

        describe('Static Config facade', function () {

            it('sets configuration using static method', function () {
                $configDir = getcwd() . '/config';
                if (! is_dir($configDir)) {
                    mkdir($configDir, 0777, true);
                }

                file_put_contents($configDir . '/test_app.php', '<?php return ' . var_export([
                    'name' => 'Original',
                ], true) . ';');

                ConfigLoader::reset();

                $result = Config::set('test_app.name', 'Updated');

                expect($result)->toBeTrue()
                    ->and(Config::get('test_app.name'))->toBe('Updated')
                ;
            });

            it('sets configuration using static setOrFail method', function () {
                $configDir = getcwd() . '/config';
                if (! is_dir($configDir)) {
                    mkdir($configDir, 0777, true);
                }

                file_put_contents($configDir . '/test_app.php', '<?php return ' . var_export([
                    'name' => 'Original',
                ], true) . ';');

                ConfigLoader::reset();

                Config::setOrFail('test_app.name', 'Updated');

                expect(Config::get('test_app.name'))->toBe('Updated');
            });

            it('throws exception using static setOrFail for non-existent key', function () {
                ConfigLoader::reset();

                expect(fn () => Config::setOrFail('nonexistent.key', 'value'))
                    ->toThrow(ConfigKeyNotFoundException::class)
                ;
            });
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

            expect($rootPath)->toBeString();
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

    describe('loadFromRoot() method', function () {

        beforeEach(function () {
            ConfigLoader::reset();

            $rootPath = ConfigLoader::getInstance()->getRootPath();
            $testFiles = glob($rootPath . '/test_*.php');
            foreach ($testFiles as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        });

        afterEach(function () {
            $rootPath = ConfigLoader::getInstance()->getRootPath();
            $testFiles = glob($rootPath . '/test_*.php');
            foreach ($testFiles as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        });

        it('loads config file from project root', function () {
            $rootPath = ConfigLoader::getInstance()->getRootPath();

            file_put_contents($rootPath . '/test_root_config.php', '<?php return ' . var_export([
                'app_name' => 'Test App',
                'version' => '1.0.0',
            ], true) . ';');

            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            $result = $config->loadFromRoot('test_root_config.php');

            expect($result)->toBeArray()
                ->and($result['app_name'])->toBe('Test App')
                ->and($result['version'])->toBe('1.0.0')
                ->and($config->get('test_root_config.app_name'))->toBe('Test App')
            ;
        });

        it('loads config file without .php extension', function () {
            $rootPath = ConfigLoader::getInstance()->getRootPath();

            file_put_contents($rootPath . '/test_simple.php', '<?php return ' . var_export([
                'key' => 'value',
            ], true) . ';');

            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            $result = $config->loadFromRoot('test_simple');

            expect($result)->toBeArray()
                ->and($result['key'])->toBe('value')
            ;
        });

        it('loads and accesses nested values with dot notation', function () {
            $rootPath = ConfigLoader::getInstance()->getRootPath();

            file_put_contents($rootPath . '/test_database.php', '<?php return ' . var_export([
                'database' => [
                    'host' => 'localhost',
                    'port' => 3306,
                    'credentials' => [
                        'username' => 'root',
                        'password' => 'secret',
                    ],
                ],
            ], true) . ';');

            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            $host = $config->loadFromRoot('test_database', 'database.host');

            expect($host)->toBe('localhost');
        });

        it('loads deeply nested values with dot notation', function () {
            $rootPath = ConfigLoader::getInstance()->getRootPath();

            file_put_contents($rootPath . '/test_config.php', '<?php return ' . var_export([
                'connections' => [
                    'mysql' => [
                        'host' => 'localhost',
                        'port' => 3306,
                        'settings' => [
                            'charset' => 'utf8mb4',
                            'collation' => 'utf8mb4_unicode_ci',
                        ],
                    ],
                ],
            ], true) . ';');

            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            $charset = $config->loadFromRoot('test_config', 'connections.mysql.settings.charset');

            expect($charset)->toBe('utf8mb4')
                ->and($config->get('connections.mysql.settings.collation'))->toBe('utf8mb4_unicode_ci')
            ;
        });

        it('loads entire nested array with dot notation', function () {
            $rootPath = ConfigLoader::getInstance()->getRootPath();

            file_put_contents($rootPath . '/test_services.php', '<?php return ' . var_export([
                'services' => [
                    'cache' => [
                        'driver' => 'redis',
                        'host' => '127.0.0.1',
                    ],
                    'queue' => [
                        'driver' => 'sync',
                    ],
                ],
            ], true) . ';');

            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            $cache = $config->loadFromRoot('test_services', 'services.cache');

            expect($cache)->toBeArray()
                ->and($cache['driver'])->toBe('redis')
                ->and($cache['host'])->toBe('127.0.0.1')
            ;
        });

        it('stores config under custom key', function () {
            $rootPath = ConfigLoader::getInstance()->getRootPath();

            file_put_contents($rootPath . '/test_custom.php', '<?php return ' . var_export([
                'setting' => 'value',
            ], true) . ';');

            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            $config->loadFromRoot('test_custom', 'my_custom_key');

            expect($config->get('my_custom_key.setting'))->toBe('value');
        });

        it('returns default value when file does not exist', function () {
            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            $result = $config->loadFromRoot('non_existent_file.php', null, 'default_value');

            expect($result)->toBe('default_value');
        });

        it('returns default value when file is not an array', function () {
            $rootPath = ConfigLoader::getInstance()->getRootPath();

            file_put_contents($rootPath . '/test_string.php', '<?php return "string value";');

            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            $result = $config->loadFromRoot('test_string', null, ['default' => 'array']);

            expect($result)->toBe(['default' => 'array']);
        });

        it('returns default value for non-existent nested key', function () {
            $rootPath = ConfigLoader::getInstance()->getRootPath();

            file_put_contents($rootPath . '/test_partial.php', '<?php return ' . var_export([
                'database' => [
                    'host' => 'localhost',
                ],
            ], true) . ';');

            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            $result = $config->loadFromRoot('test_partial', 'database.port', 3306);

            expect($result)->toBe(3306);
        });

        it('returns empty array as default', function () {
            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            $result = $config->loadFromRoot('missing.php', null, []);

            expect($result)->toBeArray()
                ->and($result)->toBeEmpty()
            ;
        });

        it('loads multiple root config files independently', function () {
            $rootPath = ConfigLoader::getInstance()->getRootPath();

            file_put_contents($rootPath . '/test_app_config.php', '<?php return ' . var_export([
                'name' => 'App1',
            ], true) . ';');

            file_put_contents($rootPath . '/test_db_config.php', '<?php return ' . var_export([
                'name' => 'DB1',
            ], true) . ';');

            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            $config->loadFromRoot('test_app_config', 'app');
            $config->loadFromRoot('test_db_config', 'db');

            expect($config->get('app.name'))->toBe('App1')
                ->and($config->get('db.name'))->toBe('DB1')
            ;
        });

        it('works with Config static facade', function () {
            $rootPath = ConfigLoader::getInstance()->getRootPath();

            file_put_contents($rootPath . '/test_facade.php', '<?php return ' . var_export([
                'setting' => 'facade_value',
            ], true) . ';');

            ConfigLoader::reset();

            $result = Config::loadFromRoot('test_facade');

            expect($result)->toBeArray()
                ->and($result['setting'])->toBe('facade_value')
                ->and(Config::get('test_facade.setting'))->toBe('facade_value')
            ;
        });

        it('works with Config static facade and dot notation', function () {
            $rootPath = ConfigLoader::getInstance()->getRootPath();

            file_put_contents($rootPath . '/test_facade_nested.php', '<?php return ' . var_export([
                'api' => [
                    'key' => 'secret123',
                    'url' => 'https://api.example.com',
                ],
            ], true) . ';');

            ConfigLoader::reset();

            $key = Config::loadFromRoot('test_facade_nested', 'api.key');

            expect($key)->toBe('secret123')
                ->and(Config::get('api.url'))->toBe('https://api.example.com')
            ;
        });

        it('handles numeric array keys', function () {
            $rootPath = ConfigLoader::getInstance()->getRootPath();

            file_put_contents($rootPath . '/test_array.php', '<?php return ' . var_export([
                'items' => ['first', 'second', 'third'],
            ], true) . ';');

            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            $config->loadFromRoot('test_array', 'list');

            expect($config->get('list.items.0'))->toBe('first')
                ->and($config->get('list.items.1'))->toBe('second')
                ->and($config->get('list.items.2'))->toBe('third')
            ;
        });

        it('returns null when traversing non-array value', function () {
            $rootPath = ConfigLoader::getInstance()->getRootPath();

            file_put_contents($rootPath . '/test_scalar.php', '<?php return ' . var_export([
                'value' => 'string',
            ], true) . ';');

            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            $result = $config->loadFromRoot('test_scalar', 'value.nested');

            expect($result)->toBeNull();
        });

        it('can be accessed via get() after loading', function () {
            $rootPath = ConfigLoader::getInstance()->getRootPath();

            file_put_contents($rootPath . '/test_accessible.php', '<?php return ' . var_export([
                'feature' => [
                    'enabled' => true,
                    'options' => ['a', 'b', 'c'],
                ],
            ], true) . ';');

            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            $config->loadFromRoot('test_accessible', 'features');

            expect($config->get('features.feature.enabled'))->toBeTrue()
                ->and($config->get('features.feature.options'))->toBeArray()
                ->and($config->has('features.feature.enabled'))->toBeTrue()
            ;
        });
    });

    describe('setFromRoot() method', function () {

        beforeEach(function () {
            ConfigLoader::reset();

            $rootPath = ConfigLoader::getInstance()->getRootPath();
            $testFiles = glob($rootPath . '/test_*.php');
            foreach ($testFiles as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        });

        afterEach(function () {
            $rootPath = ConfigLoader::getInstance()->getRootPath();
            $testFiles = glob($rootPath . '/test_*.php');
            foreach ($testFiles as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        });

        it('sets a value in a loaded root config file', function () {
            $rootPath = ConfigLoader::getInstance()->getRootPath();
            file_put_contents($rootPath . '/test_settings.php', '<?php return ' . var_export([
                'theme' => 'light',
            ], true) . ';');

            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            $config->loadFromRoot('test_settings');

            $result = $config->setFromRoot('test_settings', 'theme', 'dark');

            expect($result)->toBeTrue()
                ->and($config->get('test_settings.theme'))->toBe('dark');
        });

        it('automatically loads the file if not yet loaded', function () {
            $rootPath = ConfigLoader::getInstance()->getRootPath();
            file_put_contents($rootPath . '/test_auto_load.php', '<?php return ' . var_export([
                'status' => 'pending',
            ], true) . ';');

            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            $result = $config->setFromRoot('test_auto_load', 'status', 'active');

            expect($result)->toBeTrue()
                ->and($config->get('test_auto_load.status'))->toBe('active');
        });

        it('sets a nested value using dot notation', function () {
            $rootPath = ConfigLoader::getInstance()->getRootPath();
            file_put_contents($rootPath . '/test_nested_set.php', '<?php return ' . var_export([
                'app' => [
                    'debug' => false,
                ],
            ], true) . ';');

            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            $result = $config->setFromRoot('test_nested_set', 'app.debug', true);

            expect($result)->toBeTrue()
                ->and($config->get('test_nested_set.app.debug'))->toBeTrue();
        });

        it('creates a new nested path if it does not exist', function () {
            $rootPath = ConfigLoader::getInstance()->getRootPath();
            file_put_contents($rootPath . '/test_create_path.php', '<?php return [];');

            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            $result = $config->setFromRoot('test_create_path', 'services.payment.stripe.key', 'sk_test_123');

            expect($result)->toBeTrue()
                ->and($config->get('test_create_path.services.payment.stripe.key'))->toBe('sk_test_123');
        });

        it('fails if the file does not exist', function () {
            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            $result = $config->setFromRoot('non_existent_file', 'key', 'value');

            expect($result)->toBeFalse();
        });

        it('converts null values to arrays when creating paths', function () {
            $rootPath = ConfigLoader::getInstance()->getRootPath();
            file_put_contents($rootPath . '/test_null_path.php', '<?php return ' . var_export([
                'bootstrap' => null, 
            ], true) . ';');

            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            $result = $config->setFromRoot('test_null_path', 'bootstrap.file', '/path/to/file.php');

            expect($result)->toBeTrue()
                ->and($config->get('test_null_path.bootstrap.file'))->toBe('/path/to/file.php')
                ->and($config->get('test_null_path.bootstrap'))->toBeArray();
        });

        it('does not create path if createPath argument is false', function () {
            $rootPath = ConfigLoader::getInstance()->getRootPath();
            file_put_contents($rootPath . '/test_strict.php', '<?php return [];');

            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            $result = $config->setFromRoot('test_strict', 'new.key', 'value', false);

            expect($result)->toBeFalse()
                ->and($config->has('test_strict.new.key'))->toBeFalse();
        });

        it('overwrites entire arrays', function () {
            $rootPath = ConfigLoader::getInstance()->getRootPath();
            file_put_contents($rootPath . '/test_overwrite.php', '<?php return ' . var_export([
                'items' => ['a', 'b'],
            ], true) . ';');

            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            $newItems = ['x', 'y', 'z'];
            $result = $config->setFromRoot('test_overwrite', 'items', $newItems);

            expect($result)->toBeTrue()
                ->and($config->get('test_overwrite.items'))->toBe($newItems)
                ->and($config->get('test_overwrite.items.0'))->toBe('x');
        });

        it('persists changes when accessed via loadFromRoot afterwards', function () {
            $rootPath = ConfigLoader::getInstance()->getRootPath();
            file_put_contents($rootPath . '/test_persist.php', '<?php return ' . var_export([
                'foo' => 'bar',
            ], true) . ';');

            ConfigLoader::reset();
            $config = ConfigLoader::getInstance();

            $config->setFromRoot('test_persist', 'foo', 'baz');
            $loaded = $config->loadFromRoot('test_persist');

            expect($loaded['foo'])->toBe('baz');
        });
        
        it('works with Config static facade', function () {
            $rootPath = ConfigLoader::getInstance()->getRootPath();
            file_put_contents($rootPath . '/test_facade_set.php', '<?php return ["key" => "old"];');

            ConfigLoader::reset();

            $result = Config::setFromRoot('test_facade_set', 'key', 'new');

            expect($result)->toBeTrue()
                ->and(Config::get('test_facade_set.key'))->toBe('new');
        });
    });
});
