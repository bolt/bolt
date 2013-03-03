<?php

namespace Bolt;

use Silex\Application as BaseApplication;

class Application extends BaseApplication
{
    public function __construct(array $values = array())
    {
        $values['bolt_version'] = '1.0.2';
        $values['bolt_name'] = 'prerelease';

        parent::__construct($values);
    }
}
