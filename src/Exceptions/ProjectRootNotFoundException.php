<?php

declare(strict_types=1);

namespace Rcalicdan\ConfigLoader\Exceptions;

class ProjectRootNotFoundException extends ConfigException
{
    public function __construct()
    {
        parent::__construct('Project root not found. Make sure a vendor directory exists.');
    }
}
