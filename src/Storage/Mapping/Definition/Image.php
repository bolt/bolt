<?php

namespace Bolt\Storage\Mapping\Definition;

use Bolt\Storage\Mapping\Definition;

/**
 * Adds specific functionality for the image and imagelist definition
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */

class Image extends Definition
{
    public function setup()
    {
        parent::setup();
        $acceptableFileTypes = $this->config['accept_file_types'];
        if (!$this->has('extensions')) {
            $acceptableFileTypes = array_intersect(
                ['gif', 'jpg', 'jpeg', 'png'],
                $acceptableFileTypes
            );
            $this->set('extensions', $acceptableFileTypes);
        }
        $this->set('extensions', (array) $this->get('extensions'));
    }

    public function getExtensions()
    {
        return $this->get('extensions', []);
    }

}
