<?php

namespace Bolt;

use Silex\Application as BaseApplication;

class Application extends BaseApplication
{
    public function __construct(array $values = array())
    {
        $values['bolt_version'] = '1.2.1';
        $values['bolt_name'] = '';

        parent::__construct($values);
    }

    /**
     * @param string $name
     * @return mixed
     * @throws \InvalidArgumentException
     * @deprecated
     */
    public function __get($name)
    {
        trigger_error('$app->'.$name.' is deprecated, use $app[\''.$name.'\'] instead',E_USER_DEPRECATED);
        if (isset($name)) {
            return $this[$name];
        }

        if (strcasecmp('dbPrefix', $name) == 0) {
            return $this->config['general']['database']['prefix'];
        }

        throw new \InvalidArgumentException;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @deprecated
     */
    public function __set($name, $value)
    {
        trigger_error('$app->'.$name.' is deprecated, use $app[\''.$name.'\'] instead',E_USER_DEPRECATED);
        $this[$name] = $value;
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return (array_key_exists($name, $this));
    }

    public function getVersion($long = true) {

        if ($long) {
            return $this['bolt_version'] . " " . $this['bolt_name'];
        } else {
            return $this['bolt_version'];
        }

    }

}
