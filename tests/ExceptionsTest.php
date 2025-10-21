<?php

declare(strict_types=1);

use Rcalicdan\ConfigLoader\Exceptions\ConfigException;
use Rcalicdan\ConfigLoader\Exceptions\EnvFileLoadException;
use Rcalicdan\ConfigLoader\Exceptions\EnvFileNotFoundException;
use Rcalicdan\ConfigLoader\Exceptions\ProjectRootNotFoundException;

describe('Exceptions', function () {

    it('ConfigException can be instantiated', function () {
        $exception = new ConfigException('Test message');

        expect($exception)
            ->toBeInstanceOf(ConfigException::class)
            ->and($exception->getMessage())->toBe('Test message')
        ;
    });

    it('ProjectRootNotFoundException has default message', function () {
        $exception = new ProjectRootNotFoundException();

        expect($exception)
            ->toBeInstanceOf(ConfigException::class)
            ->and($exception->getMessage())->toContain('Project root not found')
        ;
    });

    it('EnvFileNotFoundException includes file path in message', function () {
        $path = '/some/path/.env';
        $exception = new EnvFileNotFoundException($path);

        expect($exception)
            ->toBeInstanceOf(ConfigException::class)
            ->and($exception->getMessage())->toContain($path)
        ;
    });

    it('EnvFileLoadException includes error message', function () {
        $message = 'Failed to parse .env file';
        $exception = new EnvFileLoadException($message);

        expect($exception)
            ->toBeInstanceOf(ConfigException::class)
            ->and($exception->getMessage())->toContain($message)
        ;
    });

});
