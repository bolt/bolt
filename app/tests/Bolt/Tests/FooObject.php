<?php

namespace Bolt\Tests;

class FooObject {

    private $value;

    public function __construct($value = 'bar'){
        $this->value = $value;
    }
}