<?php

namespace Bolt;

use Silex\Application as BaseApplication;

class Application extends BaseApplication
{
    public function __construct(array $values = array())
    {
        $values['bolt_version'] = '1.3';
        $values['bolt_name'] = 'beta 2';

        parent::__construct($values);
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return (array_key_exists($name, $this));
    }

    public function getVersion($long = true)
    {
        if ($long) {
            return $this['bolt_version'] . " " . $this['bolt_name'];
        } else {
            return $this['bolt_version'];
        }

    }

}
