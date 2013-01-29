<?php

namespace Bolt;

use Silex\Application as BaseApplication;

class Application extends BaseApplication
{
    public function __construct(array $values = array())
    {
        $values['bolt_version'] = '0.9.8';
        $values['bolt_name'] = 'Almost RC';

        parent::__construct($values);
    }
}
