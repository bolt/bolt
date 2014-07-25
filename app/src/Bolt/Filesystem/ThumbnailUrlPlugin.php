<?php

namespace Bolt\Filesystem;

use Bolt\TwigExtension;


class ThumnailUrlPlugin extends AdapterPlugin
{
    

    public function getMethod()
    {
        return 'thumb';
    }

    
    public function getLocalThumb($path, $width, $height, $type)
    {
        $twigHelper = new TwigExtension($this->app);
        return $twigHelper->thumbnail($path, $width, $height, $type);
    }
    
    

    
    
}