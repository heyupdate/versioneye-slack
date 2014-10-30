<?php

namespace HeyUpdate\VersionEyeSlack;

use HeyUpdate\VersionEyeSlack\Command as Commands;
use Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication
{
    public function __construct($name, $version)
    {
        parent::__construct($name, $version);

        $this->add(new Commands\CheckCommand());
    }
}
