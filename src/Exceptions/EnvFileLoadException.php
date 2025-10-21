<?php

declare(strict_types=1);

namespace Rcalicdan\ConfigLoader\Exceptions;

class EnvFileLoadException extends ConfigException
{
    public function __construct(string $message)
    {
        parent::__construct("Failed to load environment file: {$message}");
    }
}
