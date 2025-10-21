<?php

declare(strict_types=1);

use function Rcalicdan\ConfigLoader\config;

use Rcalicdan\ConfigLoader\ConfigLoader;

use function Rcalicdan\ConfigLoader\env;

describe('Helper Functions', function () {

    describe('config() helper', function () {

        it('returns ConfigLoader instance when called without arguments', function () {
            $result = config();

            expect($result)->toBeInstanceOf(ConfigLoader::class);
        });

        it('retrieves configuration value with key', function () {
            $result = config('non_existent_key', 'default_value');

            expect($result)->toBe('default_value');
        });

        it('supports dot notation', function () {
            $result = config('database.connections.mysql.host', 'localhost');

            expect($result)->toBeString();
        });

        it('returns default when key does not exist', function () {
            $result = config('this.key.does.not.exist', 'my_default');

            expect($result)->toBe('my_default');
        });

    });

    describe('env() helper', function () {

        it('retrieves environment variable from $_ENV', function () {
            $_ENV['TEST_VAR'] = 'test_value';

            $result = env('TEST_VAR');

            expect($result)->toBe('test_value');
        });

        it('retrieves environment variable from $_SERVER', function () {
            $_SERVER['TEST_VAR'] = 'server_value';

            $result = env('TEST_VAR');

            expect($result)->toBe('server_value');
        });

        it('returns default when variable does not exist', function () {
            $result = env('NON_EXISTENT_VAR', 'default');

            expect($result)->toBe('default');
        });

        it('converts string "true" to boolean true', function () {
            $_ENV['BOOL_VAR'] = 'true';

            $result = env('BOOL_VAR');

            expect($result)->toBe(true);
        });

        it('converts string "false" to boolean false', function () {
            $_ENV['BOOL_VAR'] = 'false';

            $result = env('BOOL_VAR');

            expect($result)->toBe(false);
        });

        it('converts string "null" to null', function () {
            $_ENV['NULL_VAR'] = 'null';

            $result = env('NULL_VAR');

            expect($result)->toBeNull();
        });

        it('converts string "empty" to empty string', function () {
            $_ENV['EMPTY_VAR'] = 'empty';

            $result = env('EMPTY_VAR');

            expect($result)->toBe('');
        });

        it('handles boolean strings case-insensitively', function () {
            $_ENV['TRUE_VAR'] = 'TRUE';
            $_ENV['FALSE_VAR'] = 'FALSE';
            $_ENV['NULL_VAR'] = 'NULL';

            expect(env('TRUE_VAR'))->toBe(true);
            expect(env('FALSE_VAR'))->toBe(false);
            expect(env('NULL_VAR'))->toBeNull();
        });

        it('returns original value for non-special strings', function () {
            $_ENV['STRING_VAR'] = 'some_value';

            $result = env('STRING_VAR');

            expect($result)->toBe('some_value');
        });

    });

});
