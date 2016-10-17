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

    public function setup()
    {
        parent::setup();
        $acceptableFileTypes = $this->config['accept_file_types'];
        if (!$this->has('extensions')) {
            $this->set('extensions', $acceptableFileTypes);
        }
        $this->set('extensions', (array) $this->get('extensions'));
    }

    public function getExtensions()
    {
        return $this->get('extensions', []);
    }

}
