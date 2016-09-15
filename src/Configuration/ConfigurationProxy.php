<?php
/**
 * Class ConfigurationProxy a simple wrapper that allows passing a pointer to the eventual
 * compiled and validated configuration.
 * @package Bolt\Configuration
 * @author Ross Riley <riley.ross@gmail.com>
 */

namespace Bolt\Configuration;


use Bolt\Config;

class ConfigurationProxy
{

    protected $path;
    protected $default;

    public function __construct($path, $default = null)
    {
        $this->path = $path;
        $this->default = $default;
    }

    public function get()
    {
        return parent::get($this->path, $this->default);
    }
}