<?php

namespace Bolt;

use Silex\Application as BaseApplication;

class Application extends BaseApplication
{
    public function __construct(array $values = array())
    {
        $values['bolt_version'] = '0.9.4';
        $values['bolt_name'] = 'Third beta';

        parent::__construct($values);
    }
}
