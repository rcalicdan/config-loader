<?php

declare(strict_types=1);

use function Rcalicdan\ConfigLoader\env;

describe('env() helper', function () {

    beforeEach(function () {
        $_ENV = [];
        $_SERVER = [];
    });

    it('retrieves environment variable from $_ENV', function () {
        $_ENV['TEST_VAR'] = 'test_value';

        expect(env('TEST_VAR'))->toBe('test_value');
    });

    it('retrieves environment variable from $_SERVER', function () {
        $_SERVER['TEST_VAR'] = 'test_value';

        expect(env('TEST_VAR'))->toBe('test_value');
    });

    it('returns default when variable does not exist', function () {
        expect(env('NON_EXISTENT', 'default'))->toBe('default');
    });

    it('converts string "true" to boolean true', function () {
        $_ENV['BOOL_VAR'] = 'true';

        expect(env('BOOL_VAR'))->toBeTrue();
    });

    it('converts string "false" to boolean false', function () {
        $_ENV['BOOL_VAR'] = 'false';

        expect(env('BOOL_VAR'))->toBeFalse();
    });

    it('converts string "null" to null', function () {
        $_ENV['NULL_VAR'] = 'null';

        expect(env('NULL_VAR'))->toBeNull();
    });

    it('converts string "empty" to empty string', function () {
        $_ENV['EMPTY_VAR'] = 'empty';

        expect(env('EMPTY_VAR'))->toBe('');
    });

    it('handles boolean strings case-insensitively', function () {
        $_ENV['TRUE_VAR'] = 'TRUE';
        $_ENV['FALSE_VAR'] = 'FALSE';
        $_ENV['NULL_VAR'] = 'NULL';

        expect(env('TRUE_VAR'))->toBeTrue()
            ->and(env('FALSE_VAR'))->toBeFalse()
            ->and(env('NULL_VAR'))->toBeNull()
        ;
    });

    it('returns original value for non-special strings', function () {
        $_ENV['STRING_VAR'] = 'some_value';

        expect(env('STRING_VAR'))->toBe('some_value');
    });

    it('keeps numeric strings as strings by default', function () {
        $_ENV['INT_VAR'] = '123';
        $_ENV['FLOAT_VAR'] = '3.14';
        $_ENV['NEGATIVE_INT'] = '-456';

        expect(env('INT_VAR'))->toBe('123')
            ->and(env('INT_VAR'))->toBeString()
            ->and(env('FLOAT_VAR'))->toBe('3.14')
            ->and(env('FLOAT_VAR'))->toBeString()
            ->and(env('NEGATIVE_INT'))->toBe('-456')
            ->and(env('NEGATIVE_INT'))->toBeString()
        ;
    });

    it('converts numeric strings to integers when convertNumeric is true', function () {
        $_ENV['INT_VAR'] = '123';
        $_ENV['NEGATIVE_INT'] = '-456';
        $_ENV['ZERO_VAR'] = '0';

        expect(env('INT_VAR', null, true))->toBe(123)
            ->and(env('INT_VAR', null, true))->toBeInt()
            ->and(env('NEGATIVE_INT', null, true))->toBe(-456)
            ->and(env('NEGATIVE_INT', null, true))->toBeInt()
            ->and(env('ZERO_VAR', null, true))->toBe(0)
            ->and(env('ZERO_VAR', null, true))->toBeInt()
        ;
    });

    it('converts numeric strings to floats when convertNumeric is true', function () {
        $_ENV['FLOAT_VAR'] = '3.14';
        $_ENV['NEGATIVE_FLOAT'] = '-2.5';

        expect(env('FLOAT_VAR', null, true))->toBe(3.14)
            ->and(env('FLOAT_VAR', null, true))->toBeFloat()
            ->and(env('NEGATIVE_FLOAT', null, true))->toBe(-2.5)
            ->and(env('NEGATIVE_FLOAT', null, true))->toBeFloat()
        ;
    });

    it('handles scientific notation when convertNumeric is true', function () {
        $_ENV['SCIENTIFIC'] = '1.5e3';

        expect(env('SCIENTIFIC', null, true))->toBe(1500.0)
            ->and(env('SCIENTIFIC', null, true))->toBeFloat()
        ;
    });
});
