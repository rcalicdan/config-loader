<?php

declare(strict_types=1);

use Rcalicdan\ConfigLoader\ConfigLoader;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

uses()->beforeEach(function () {
    ConfigLoader::reset();

    $_ENV = [];
    $_SERVER = [];
})->in(__DIR__);

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeConfigLoader', function () {
    return $this->toBeInstanceOf(ConfigLoader::class);
});
