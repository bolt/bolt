<?php

namespace Bolt\Storage\Mapping\Definition;

use Bolt\Storage\Mapping\Definition;

/**
 * Adds specific functionality for the file and filelist definition
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */

class File extends Definition
{

    public $appConfig;

    public function __construct($name, array $parameters, array $config)
    {
        $this->appConfig = $config;
        parent::__construct($name, $parameters);
    }

    public function setup()
    {
        parent::setup();
        $acceptableFileTypes = $this->appConfig['accept_file_types'];
        if (!$this->has('extensions')) {
            $this->set('extensions', $acceptableFileTypes);
        }
        $this->set('extensions', (array) $this->get('extensions'));
    }

    public function getExtensions()
    {
        return $this->get('extensions', []);
    }

    /**
     * File definitions need access to the global config to read allowed file types
     * @param array $config
     */
    public function setAppConfig(array $config)
    {
        $this->appConfig = $config;
    }
}
