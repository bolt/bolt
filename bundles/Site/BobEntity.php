<?php

namespace Bundle\Site;

use Bolt\Storage\Entity\Content;

class BobEntity extends Content
{
    public function __construct() {
        echo "joe";
    }
}