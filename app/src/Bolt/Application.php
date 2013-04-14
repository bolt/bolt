<?php

namespace Bolt;

use Silex\Application as BaseApplication;

class Application extends BaseApplication
{
    public function __construct(array $values = array())
    {
        $values['bolt_version'] = '1.0.5';
        $values['bolt_name'] = '';

        parent::__construct($values);
    }

    public function getVersion($long = true) {

        if ($long) {
            return $this['bolt_version'] . " " . $this['bolt_name'];
        } else {
            return $this['bolt_version'];
        }

    }

}
