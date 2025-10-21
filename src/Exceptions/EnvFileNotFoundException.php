<?php

declare(strict_types=1);

namespace Rcalicdan\ConfigLoader\Exceptions;

class EnvFileNotFoundException extends ConfigException
{
    public function __construct(string $path)
    {
        parent::__construct("Environment file not found at: {$path}");
    }
}
